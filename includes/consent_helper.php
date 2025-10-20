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

function parse_decline_consent_cookie(): bool
{
  // if missing cookie
  if (!isset($_COOKIE[CONSENT_COOKIE_DECLINE_NAME]))
    return false;

  $data = json_decode($_COOKIE[CONSENT_COOKIE_DECLINE_NAME], true);

  // if malformed (missing property)
  if (!$data || empty($data['declined_at'])) {
    clear_cookie(CONSENT_COOKIE_DECLINE_NAME);
    return false;
  }
  // parse declined_at and check expiry
  try {
    $declined_at = new DateTimeImmutable($data['declined_at']);
  } catch (Exception $e) {
    // if malformed (invalid declined_at)
    clear_cookie(CONSENT_COOKIE_DECLINE_NAME);
    return false;
  }
  $expires = $declined_at->add(new DateInterval('P' . CONSENT_COOKIE_DECLINE_EXPIRE_DAYS . 'D'));
  // if cookie expired
  if (new DateTimeImmutable('now') > $expires) {
    clear_cookie(CONSENT_COOKIE_DECLINE_NAME);
    return false;
  }
  // else, return true
  return true; // valid declined cookie
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

/**
 * Check if consent cookie exists and is valid.
 * Returns false if not present or expired.
 */
function parse_consent_cookie(): array|false
{
  // if no cookie, then early exit
  if (!isset($_COOKIE[CONSENT_COOKIE_NAME])) {
    return false;
  }

  $data = json_decode($_COOKIE[CONSENT_COOKIE_NAME], true);

  // if invalid cookie structure, then early exit
  if (!is_array($data) || empty($data['guid']) || empty($data['accepted_at'])) {
    return false;
  }

  // parse accepted_at and check expiry
  try {
    $accepted_at = new DateTimeImmutable($data['accepted_at']);
  } catch (Exception $e) {
    return false;
  }
  $expires = $accepted_at->add(new DateInterval('P' . CONSENT_COOKIE_EXPIRE_YEARS . 'Y'));
  if (new DateTimeImmutable('now') > $expires)
    return false; // cookie expired

  // else, return parsed cookie
  return $data;
}

function verify_consent(PDO $pdo): bool
{
  $cookie = parse_consent_cookie();

  if (!$cookie) {
    clear_cookie(CONSENT_COOKIE_NAME);
    return false; // missing cookie
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
function verify_prompt_consent(PDO $pdo): bool
{
  // first, check for declined cookie
  $is_cookie_declined = parse_decline_consent_cookie();
  $is_cookie_accepted = verify_consent($pdo);

  // edge case: if both cookies somehow exist, assume declined
  if ($is_cookie_declined && $is_cookie_accepted) {
    clear_cookie(CONSENT_COOKIE_NAME);
  }

  // else: if either cookie exists and is valid, then don't show prompt
  return !($is_cookie_declined || $is_cookie_accepted);
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
