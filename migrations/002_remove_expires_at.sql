-- 002_remove_expires_at
-- Remove expires_at column

USE cookie_consent;

ALTER TABLE cookie_consents
  DROP COLUMN expires_at;
