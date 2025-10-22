<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/Config.php';

use App\Config;

// How long to retain expired consents after expiry (e.g. 90 days)

$sql = "DELETE FROM cookie_consents
        WHERE expires_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
$stmt = $pdo->prepare($sql);
$stmt->execute([':days' => Config::CONSENT_COOKIE_RETENTION_DAYS]);

echo "Deleted {$stmt->rowCount()} expired consent record(s).\n";
