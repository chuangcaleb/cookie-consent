<?php
namespace App;

/**
 * Central application configuration constants.
 *
 * This class holds immutable configuration values (DB, cookies, time, etc.)
 * following modern PHP best practices.
 */
final class Config
{
  // ─── Database ────────────────────────────────────────────────
  public const DB_HOST = '127.0.0.1';
  public const DB_NAME = 'cookie_consent';
  public const DB_USER = 'root';
  public const DB_PASS = 'FTXNNE^t&VFHf!6B2HCybmp*YtMoYyR';

  // ─── Consent Cookies ─────────────────────────────────────────
  public const CONSENT_COOKIE_NAME = 'consent_cookie_accepted';
  public const CONSENT_COOKIE_VERSION = 1;

  public const CONSENT_COOKIE_EXPIRE_YEARS = 1;
  public const CONSENT_COOKIE_EXPIRE_INTERVAL = 'P' . self::CONSENT_COOKIE_EXPIRE_YEARS . 'Y';

  public const DECLINE_COOKIE_NAME = 'consent_cookie_declined';
  public const DECLINE_COOKIE_EXPIRE_DAYS = 1;
  public const DECLINE_COOKIE_EXPIRE_INTERVAL = 'P' . self::DECLINE_COOKIE_EXPIRE_DAYS . 'D';

  // Retention for old records (DB cleanup etc.)
  public const CONSENT_COOKIE_RETENTION_DAYS = 90;

  // ─── Timezone ────────────────────────────────────────────────
  public const TIMEZONE = 'Asia/Kuala_Lumpur';

  /**
   * Initialize global runtime settings (timezone, etc.).
   * Call this early in bootstrap.
   */
  public static function init(): void
  {
    date_default_timezone_set(self::TIMEZONE);
  }
}
