# Velotool

Wartungs- und Ausstattungs-Tracker für die Familienvelos. Reines PHP + MySQL,
kein Composer/Framework nötig — läuft auf normalem Hostpoint-Webhosting per FTP.

## Stack
- PHP 8.x, PDO/MySQL
- Login via Google OIDC (Authorization Code Flow, manuelle RS256-Verifikation,
  keine externen Libraries)
- Reines CSS, keine JS-Frameworks

## Einmaliges Setup

### 1. Datenbank
In phpMyAdmin (oder via `mysql`-CLI) auf der Hostpoint-DB `raphi_velo` ausführen:
```
sql/schema.sql
sql/seed_radon.sql   -- optional: Beispiel-Velo (Radon Relate 8.0 Lady 625)
```

### 2. Google Cloud Console
Unter **APIs & Services → Credentials** bei der bestehenden OAuth-Client-ID
folgende **Authorized redirect URI** eintragen:

```
https://velo.thoma.cx/callback.php
```

Als **Authorized JavaScript origin** (falls gefordert):
```
https://velo.thoma.cx
```

### 3. Konfiguration
`config/config.php` ist bereits mit DB- und OIDC-Zugangsdaten befüllt
(git-ignored, wird nicht committed). **Vor dem ersten Login unbedingt
`allowed_emails` ausfüllen** — sonst wird jeder Login abgelehnt:

```php
'allowed_emails' => [
    'deine.adresse@gmail.com',
    'familienmitglied@gmail.com',
],
```

### 4. Deploy (FTP)
FTP-User `velo@thoma.cx` lädt direkt ins Rootverzeichnis von `velo.thoma.cx`.
Kompletten Projektinhalt hochladen (inkl. `.htaccess`-Dateien — die schützen
`config/`, `src/` und `sql/` vor direktem Web-Zugriff; nicht ausblenden).

`config/config.php` liegt lokal nicht im Git-Repo — beim Deploy separat
mit hochladen bzw. direkt auf dem Server anlegen (Vorlage: `config/config.example.php`).

## Struktur
```
index.php            Dashboard: Velo-Liste
bike.php             Velo-Detail: Komponenten, Wartungshistorie, Ersatzteile
bike_edit.php         Velo anlegen/bearbeiten
component_edit.php    Komponente anlegen/bearbeiten
maintenance_edit.php  Wartungseintrag anlegen/bearbeiten
part_edit.php         Ersatzteil-Bedarf anlegen/bearbeiten
parts.php             Offene Ersatzteile über alle Velos
login.php / callback.php / logout.php   Google-OIDC-Login
src/                  PHP-Klassen (Database, Auth, GoogleOidc) + Views
config/               DB- & OIDC-Konfiguration (nicht im Web erreichbar)
sql/                  Schema + Seed-Daten (nicht im Web erreichbar)
```

## Sicherheit
- `config/`, `src/`, `sql/` sind per `.htaccess` (`Require all denied`)
  vor direktem HTTP-Zugriff gesperrt.
- Login ist auf eine E-Mail-Whitelist (`allowed_emails`) beschränkt.
- Alle Formulare sind mit CSRF-Tokens abgesichert.
- Erzwungenes HTTPS via `.htaccess`-Redirect.
