<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';

use App\Config;

/**
 * Set a cookie to mark declined consent.
 */
function decline_consent(): void
{
  $declinedAt = new DateTimeImmutable('now');
  setcookie(
    Config::DECLINE_COOKIE_NAME,
    json_encode(['declined_at' => $declinedAt->format(DateTime::ATOM)]),
    [
      'expires' => $declinedAt->modify('+1 day')->getTimestamp(),
      'path' => '/',
      'secure' => false,
      'httponly' => true,
      'samesite' => 'Lax'
    ]
  );
}

/**
 * Check if expiry cookie exists and is valid.
 *
 * @param string $cookie_name Name of the cookie to check.
 * @param string $initial_datetime_key Key in the cookie's JSON data that stores the initial timestamp.
 * @return array|bool Returns cookie data if valid, or false if not present or expired.
 */
function parse_expiry_cookie(string $cookie_name, string $initial_datetime_key, string $expiry_duration): array|bool
{
  // if missing cookie, return false
  if (!isset($_COOKIE[$cookie_name])) {
    return false;
  }

  $data = json_decode($_COOKIE[$cookie_name], true);

  // if malformed (or missing property), return false
  if (!is_array($data) || empty($data[$initial_datetime_key])) {
    clear_cookie($cookie_name);
    return false;
  }

  // # check expiry
  // ## if malformed (invalid json value), return false
  try {
    $initial_datetime = new DateTimeImmutable($data[$initial_datetime_key]);
  } catch (Exception $e) {
    clear_cookie($cookie_name);

    return false;
  }
  // ## if cookie expired, return false
  $expiry_datetime = $initial_datetime->add(new DateInterval($expiry_duration));
  if (new DateTimeImmutable('now') > $expiry_datetime) {

    clear_cookie($cookie_name);
    return false;
  }
  // else valid declined cookie, return true
  return $data;
}

/**
 * Create a consent cookie and insert record to DB.
 *
 * @param PDO $pdo
 * @param int $version
 * @return array Assoc with guid, accepted_at, version
 */
function accept_consent(PDO $pdo, int $version = Config::CONSENT_COOKIE_VERSION): array
{
  $guid = generate_guid_v4();
  $acceptedAt = new DateTimeImmutable('now');
  $expiresAt = $acceptedAt->add(new DateInterval(Config::CONSENT_COOKIE_EXPIRE_INTERVAL));

  // TODO: Set cookie (path=/ so it's site-wide). For local dev, secure=false.
  // TODO: samesite too
  // For production use secure=true and proper domain.
  $cookieOptions = [
    'expires' => $expiresAt->getTimestamp(),
    'path' => '/',
    'domain' => '', // leave blank for current host
    'secure' => isset($_SERVER['HTTPS']), // set true for HTTPS in prod
    'httponly' => true,
    'samesite' => 'Lax' // or 'Strict'/'None' depending on needs
  ];

  setcookie(Config::CONSENT_COOKIE_NAME, json_encode([
    'guid' => $guid,
    'accepted_at' => $acceptedAt->format(DateTime::ATOM),
    'version' => $version
  ]), $cookieOptions);

  // Insert into DB using prepared statement
  $sql = "INSERT INTO cookie_consents (guid, accepted_at, version)
            VALUES (:guid, :accepted_at, :version)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':guid' => $guid,
    ':accepted_at' => $acceptedAt->format('Y-m-d H:i:s'),
    ':version' => $version,
  ]);

  return [
    'guid' => $guid,
    'accepted_at' => $acceptedAt->format(DateTime::ATOM),
    'version' => $version
  ];
}


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
