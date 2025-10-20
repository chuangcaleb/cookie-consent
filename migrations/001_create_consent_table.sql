-- 001_create_consent_table.sql
-- Creates a table to store cookie consent records

CREATE DATABASE IF NOT EXISTS cookie_consent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cookie_consent;

CREATE TABLE IF NOT EXISTS cookie_consents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  guid CHAR(36) NOT NULL,
  accepted_at DATETIME NOT NULL,
  version TINYINT UNSIGNED NOT NULL,
  cookie_expires_at DATETIME NOT NULL,
  UNIQUE KEY ux_guid_accepted (guid)
);
