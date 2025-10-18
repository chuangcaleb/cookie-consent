<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Generate a GUID/UUID v4 string (36 chars)
 * Uses random_bytes for cryptographic randomness and formats as UUID v4
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

/**
 * Create a consent cookie and insert record to DB.
 *
 * @param PDO $pdo
 * @param int $version
 * @return array assoc with guid, accepted_at, expires_at
 */
function accept_consent(PDO $pdo, int $version = CONSENT_COOKIE_VERSION): array
{
  $guid = generate_guid_v4();
  $acceptedAt = new DateTimeImmutable('now');
  $expiresAt = $acceptedAt->add(new DateInterval('P' . CONSENT_COOKIE_EXPIRE_YEARS . 'Y'));

  // TODO: Set cookie (path=/ so it's site-wide). For local dev, secure=false.
  // For production use secure=true and proper domain.
  $cookieOptions = [
    'expires' => $expiresAt->getTimestamp(),
    'path' => '/',
    'domain' => '',            // leave blank for current host
    'secure' => false,         // set true for HTTPS in prod
    'httponly' => true,
    'samesite' => 'Lax'        // or 'Strict'/'None' depending on needs
  ];

  setcookie(CONSENT_COOKIE_NAME, json_encode([
    'guid' => $guid,
    'accepted_at' => $acceptedAt->format(DateTime::ATOM),
    'version' => $version
  ]), $cookieOptions);

  // Insert into DB using prepared statement
  $sql = "INSERT INTO cookie_consents (guid, accepted_at, version, cookie_expires_at)
            VALUES (:guid, :accepted_at, :version, :cookie_expires_at)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':guid' => $guid,
    ':accepted_at' => $acceptedAt->format('Y-m-d H:i:s'),
    ':version' => $version,
    ':cookie_expires_at' => $expiresAt->format('Y-m-d H:i:s')
  ]);

  return [
    'guid' => $guid,
    'accepted_at' => $acceptedAt->format(DateTime::ATOM),
    'cookie_expires_at' => $expiresAt->format(DateTime::ATOM),
    'version' => $version
  ];
}

/**
 * Check if consent cookie exists and is valid.
 * Returns false if not present or expired.
 */
function get_consent_from_cookie()
{
  // if no cookie, then early exit
  if (!isset($_COOKIE[CONSENT_COOKIE_NAME])) {
    return false;
  }

  $raw = $_COOKIE[CONSENT_COOKIE_NAME];
  $data = json_decode($raw, true);

  // if invalid cookie structure, then early exit
  if (!is_array($data) || empty($data['guid']) || empty($data['accepted_at'])) {
    // TODO: do I need to clear the cookie entry?
    return false;
  }
  // Parse accepted_at and check expiry
  try {
    $accepted = new DateTimeImmutable($data['accepted_at']);
  } catch (Exception $e) {
    return false;
  }

  $expires = $accepted->add(new DateInterval('P' . CONSENT_COOKIE_EXPIRE_YEARS . 'Y'));

  if (new DateTimeImmutable('now') > $expires) {
    return false; // cookie expired
  }
  return $data;
}

/**
 * Optionally: check DB for existence of GUID (returns DB row or false)
 */
function find_consent_by_guid(PDO $pdo, string $guid)
{
  $sql = "SELECT * FROM cookie_consents WHERE guid = :guid LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':guid' => $guid]);
  return $stmt->fetch() ?: false;
}
