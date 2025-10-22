<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/parse_expiry_cookie.php';

use App\Config;

/**
 * Verify the consent cookie, on client and DB.
 *
 * @param PDO $pdo
 * @return bool
 */
function verify_consent(PDO $pdo): bool
{
  $cookie = parse_expiry_cookie(Config::CONSENT_COOKIE_NAME, 'accepted_at', Config::CONSENT_COOKIE_EXPIRE_INTERVAL);

  // if invalid cookie, or missing extra keys, then clear it and return false
  if (!$cookie || empty($cookie['guid']) || empty($cookie['version'])) {
    clear_cookie(Config::CONSENT_COOKIE_NAME);
    return false;
  }

  // Lookup in DB
  $stmt = $pdo->prepare("SELECT * FROM cookie_consents WHERE guid = :guid LIMIT 1");
  $stmt->execute([':guid' => $cookie['guid']]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  // if db has no record, then invalidate local cookie
  if (!$row) {
    clear_cookie(Config::CONSENT_COOKIE_NAME);
    return false; // no record
  }

  // check db expiry (trust server over client)
  $server_accepted_at = new DateTimeImmutable($row['accepted_at']);
  $server_expired_at = $server_accepted_at->add(new DateInterval(Config::CONSENT_COOKIE_EXPIRE_INTERVAL));
  if (new DateTimeImmutable('now') > $server_expired_at) {
    clear_cookie(Config::CONSENT_COOKIE_NAME);
    return false; // expired
  }

  // check version match
  if ((int) $cookie['version'] !== (int) $row['version']) {
    clear_cookie(Config::CONSENT_COOKIE_NAME);
    return false; // outdated version
  }

  return true; // consent is valid
}

/**
 * Get consent state, on both client-side (for both decline cookie and consent cookie), and verified on DB-side.
 *
 * @param PDO $pdo
 * @return bool False if invalid or missing
 */
function verify_is_resolved_consent(PDO $pdo): bool
{
  // check for both declined cookie & consented cookie
  $has_cookie_declined = parse_expiry_cookie(Config::DECLINE_COOKIE_NAME, 'declined_at', Config::DECLINE_COOKIE_EXPIRE_INTERVAL);
  // for consented, does extra step to check on db
  $has_cookie_consented = verify_consent($pdo);

  // edge case: if both cookies somehow exist, assume declined
  if ($has_cookie_declined && $has_cookie_consented) {
    // remove consent cookie, keep decline cookie
    clear_cookie(Config::CONSENT_COOKIE_NAME);
    return true;
  }

  // else: if either cookie exists and is valid, then don't show prompt
  return $has_cookie_declined || $has_cookie_consented;
}
