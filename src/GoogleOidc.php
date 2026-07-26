<?php

/**
 * Minimal Google OIDC client (authorization code flow) with manual
 * RS256 ID-token verification. No Composer dependencies, so it works
 * on plain FTP-only shared hosting.
 */
final class GoogleOidc
{
    private const AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const JWKS_URI = 'https://www.googleapis.com/oauth2/v3/certs';
    private const VALID_ISSUERS = ['https://accounts.google.com', 'accounts.google.com'];

    private array $cfg;

    public function __construct(array $oidcConfig)
    {
        $this->cfg = $oidcConfig;
    }

    public function buildAuthUrl(string $state, string $nonce): string
    {
        $params = [
            'client_id'     => $this->cfg['client_id'],
            'redirect_uri'  => $this->cfg['redirect_uri'],
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'nonce'         => $nonce,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ];
        return self::AUTH_ENDPOINT . '?' . http_build_query($params);
    }

    /**
     * Exchanges an authorization code for tokens and returns the verified
     * ID-token claims (sub, email, name, picture, ...).
     *
     * @throws RuntimeException on any failure (network, invalid signature, etc.)
     */
    public function handleCallback(string $code, string $expectedNonce): array
    {
        $tokenResponse = $this->httpPostForm(self::TOKEN_ENDPOINT, [
            'code'          => $code,
            'client_id'     => $this->cfg['client_id'],
            'client_secret' => $this->cfg['client_secret'],
            'redirect_uri'  => $this->cfg['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ]);

        $data = json_decode($tokenResponse, true);
        if (!is_array($data) || empty($data['id_token'])) {
            throw new RuntimeException('Google token endpoint returned no id_token.');
        }

        return $this->verifyIdToken($data['id_token'], $expectedNonce);
    }

    private function verifyIdToken(string $idToken, string $expectedNonce): array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new RuntimeException('Malformed ID token.');
        }
        [$headerB64, $payloadB64, $sigB64] = $parts;

        $header = json_decode(self::base64UrlDecode($headerB64), true);
        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        $signature = self::base64UrlDecode($sigB64);

        if (!is_array($header) || !is_array($payload) || ($header['alg'] ?? '') !== 'RS256') {
            throw new RuntimeException('Unsupported or malformed ID token header.');
        }

        $pem = $this->publicKeyPemForKid($header['kid'] ?? '');
        $pubKey = openssl_pkey_get_public($pem);
        if ($pubKey === false) {
            throw new RuntimeException('Could not load Google public key.');
        }

        $signingInput = $headerB64 . '.' . $payloadB64;
        if (openssl_verify($signingInput, $signature, $pubKey, OPENSSL_ALGO_SHA256) !== 1) {
            throw new RuntimeException('ID token signature verification failed.');
        }

        if (!in_array($payload['iss'] ?? '', self::VALID_ISSUERS, true)) {
            throw new RuntimeException('Unexpected token issuer.');
        }
        if (($payload['aud'] ?? '') !== $this->cfg['client_id']) {
            throw new RuntimeException('Token audience mismatch.');
        }
        if (($payload['exp'] ?? 0) < time()) {
            throw new RuntimeException('ID token expired.');
        }
        if (!hash_equals($expectedNonce, $payload['nonce'] ?? '')) {
            throw new RuntimeException('Nonce mismatch (possible replay attack).');
        }

        return $payload;
    }

    private function publicKeyPemForKid(string $kid): string
    {
        if ($kid === '') {
            throw new RuntimeException('ID token header missing kid.');
        }
        $jwks = json_decode($this->httpGet(self::JWKS_URI), true);
        foreach ($jwks['keys'] ?? [] as $key) {
            if (($key['kid'] ?? '') === $kid && ($key['kty'] ?? '') === 'RSA') {
                return self::rsaJwkToPem($key['n'], $key['e']);
            }
        }
        throw new RuntimeException('No matching Google signing key found for kid.');
    }

    private static function rsaJwkToPem(string $n, string $e): string
    {
        $modulus = self::base64UrlDecode($n);
        $exponent = self::base64UrlDecode($e);

        $rsaOid = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        $seq = self::asn1Sequence(self::asn1Integer($modulus) . self::asn1Integer($exponent));
        $bitString = self::asn1BitString($seq);
        $spki = self::asn1Sequence($rsaOid . $bitString);

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function asn1Length(int $len): string
    {
        if ($len <= 0x7f) {
            return chr($len);
        }
        $tmp = ltrim(pack('N', $len), "\x00");
        return chr(0x80 | strlen($tmp)) . $tmp;
    }

    private static function asn1Integer(string $bin): string
    {
        if ($bin === '' || ord($bin[0]) > 0x7f) {
            $bin = "\x00" . $bin;
        }
        return "\x02" . self::asn1Length(strlen($bin)) . $bin;
    }

    private static function asn1Sequence(string $bin): string
    {
        return "\x30" . self::asn1Length(strlen($bin)) . $bin;
    }

    private static function asn1BitString(string $bin): string
    {
        $bin = "\x00" . $bin;
        return "\x03" . self::asn1Length(strlen($bin)) . $bin;
    }

    private static function base64UrlDecode(string $data): string
    {
        $padded = strtr($data, '-_', '+/');
        $padLen = 4 - (strlen($padded) % 4);
        if ($padLen < 4) {
            $padded .= str_repeat('=', $padLen);
        }
        return base64_decode($padded);
    }

    private function httpGet(string $url): string
    {
        return $this->httpRequest($url, 'GET', null);
    }

    private function httpPostForm(string $url, array $fields): string
    {
        return $this->httpRequest($url, 'POST', http_build_query($fields), [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
    }

    private function httpRequest(string $url, string $method, ?string $body, array $headers = []): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 15,
            ]);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            $response = curl_exec($ch);
            if ($response === false) {
                $err = curl_error($ch);
                curl_close($ch);
                throw new RuntimeException("HTTP request to Google failed: {$err}");
            }
            curl_close($ch);
            return $response;
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $body ?? '',
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new RuntimeException('HTTP request to Google failed (file_get_contents).');
        }
        return $response;
    }
}
