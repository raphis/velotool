-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).
-- stock_note was a free-text note that never updated automatically when
-- stock_qty changed, so it went stale and confused more than it helped.

ALTER TABLE parts_catalog DROP COLUMN stock_note;
