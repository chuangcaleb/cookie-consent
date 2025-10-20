<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Generate a cryptographically secure GUID/UUID v4 string (36 chars)
 */
function generate_guid_v4(): string
{
  $data = random_bytes(16);
  // set version to 0100
  $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
  // set bits 6-7 to 10
  $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function decline_consent(): void
{
  $declinedAt = new DateTimeImmutable('now');
  setcookie(
    CONSENT_COOKIE_DECLINE_NAME,
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
 * Returns false if not present or expired.
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
  $expiry_datetime = $initial_datetime->add(new DateInterval('P' . $expiry_duration));
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
 * @return array assoc with guid, accepted_at, version
 */
function accept_consent(PDO $pdo, int $version = CONSENT_COOKIE_VERSION): array
{
  $guid = generate_guid_v4();
  $acceptedAt = new DateTimeImmutable('now');
  $expiresAt = $acceptedAt->add(new DateInterval('P' . CONSENT_COOKIE_EXPIRE_YEARS . 'Y'));

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

  setcookie(CONSENT_COOKIE_NAME, json_encode([
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


function verify_consent(PDO $pdo): bool
{
  $cookie = parse_expiry_cookie(CONSENT_COOKIE_NAME, 'accepted_at', CONSENT_COOKIE_EXPIRE_YEARS . 'Y');

  // if invalid cookie, or missing extra keys, then clear it and return false
  if (!$cookie || empty($cookie['guid']) || empty($cookie['version'])) {
    clear_cookie(CONSENT_COOKIE_NAME);
    return false;
  }

  // Lookup in DB
  $stmt = $pdo->prepare("SELECT * FROM cookie_consents WHERE guid = :guid LIMIT 1");
  $stmt->execute([':guid' => $cookie['guid']]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  // if db has no record, then invalidate local cookie
  if (!$row) {
    clear_cookie(CONSENT_COOKIE_NAME);
    return false; // no record
  }

  // check db expiry (trust server over client)
  if (new DateTimeImmutable('now') > new DateTimeImmutable($row['expires_at'])) {
    clear_cookie(CONSENT_COOKIE_NAME);
    return false; // expired
  }

  // check version match
  if ((int) $cookie['version'] !== (int) $row['version']) {
    clear_cookie(CONSENT_COOKIE_NAME);
    return false; // outdated version
  }

  return true; // consent is valid
}

/**
 * Verify consent both client + DB side.
 * Returns boolean, false if invalid or missing.
 */
function verify_is_resolved_consent(PDO $pdo): bool
{
  // first, check for declined cookie
  $is_cookie_declined = parse_expiry_cookie(CONSENT_COOKIE_DECLINE_NAME, 'declined_at', CONSENT_COOKIE_DECLINE_EXPIRE_DAYS . 'D');
  $is_cookie_accepted = verify_consent($pdo);

  // edge case: if both cookies somehow exist, assume declined
  if ($is_cookie_declined && $is_cookie_accepted) {
    clear_cookie(CONSENT_COOKIE_NAME);
  }

  // else: if either cookie exists and is valid, then don't show prompt
  return $is_cookie_declined || $is_cookie_accepted;
}

/**
 * Clear the client-side consent cookie
 */
function clear_cookie(string $name): void
{
  setcookie(
    $name,
    '',
    [
      'expires' => time() - 3600,
      'path' => '/',
      'domain' => '',
      'secure' => isset($_SERVER['HTTPS']),
      'httponly' => true,
      'samesite' => 'Lax',
    ]
  );
  unset($_COOKIE[$name]);
}
