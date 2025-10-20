-- 002_remove_expires_at
-- Remove expires_at column

USE cookie_consent;

-- Only drop if column exists
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'cookie_consent'
    AND TABLE_NAME = 'cookie_consents'
    AND COLUMN_NAME = 'expires_at'
);

SET @query := IF(@col_exists > 0,
  'ALTER TABLE cookie_consents DROP COLUMN expires_at;',
  'SELECT "Column expires_at already removed";'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;