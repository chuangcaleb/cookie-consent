<?php
require_once __DIR__ . '/../Config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../utils.php';

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