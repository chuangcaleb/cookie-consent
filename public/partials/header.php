<?php
// Initialize global settings
require_once __DIR__ . '/../../includes/config.php';
use App\Config;
Config::init();

require_once __DIR__ . '/../../includes/consent_helper.php';
$is_resolved_consent = verify_is_resolved_consent($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= $pageTitle ?? 'C is for Cookie' ?></title>
  <link rel="stylesheet" href="/assets/index.css">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon/favicon-16x16.png">
  <link rel="shortcut icon" href='/assets/favicon/favicon.ico' />
  <link rel="manifest" href="/assets/site.webmanifest">
</head>

<body>
  <header>
    <h1>C is for Cookie</h1>
    <nav>
      <a href="/index.php">Home</a>
      <a href="/about.php">About</a>
    </nav>
  </header>
  <main class="flow">
    <?php if ($is_resolved_consent === false): ?>
      <div id="consent-box" class="flow">
        <p>Cookies are necessary for this website to function properly, for performance measurement, and to provide you
          with the best experience.</p>
        <p>
          By continuing to access or use this site, you acknowledge and consent to our use of cookies in accordance with
          our <a href="/terms.php">Terms & Conditions</a> and <a href="privacy-policy.php">Privacy Statement</a>.</p>
        <button id="acceptBtn">Accept</button>
        <button id="declineBtn">Decline</button>
      </div>

      <script>
        document.getElementById('acceptBtn').addEventListener('click', function () {
          fetch('/consent_accept.php', { method: 'POST' })
            .then(r => r.json())
            .then(json => {
              if (json.status === 'ok') {
                document.getElementById('consent-box').remove();
              } else {
                alert('Something went wrong');
              }
            })
            .catch(err => { alert('Network error'); console.error(err); });
        });
        document.getElementById('declineBtn').addEventListener('click', function () {
          fetch('/consent_decline.php', { method: 'POST' })
            .then(r => r.json())
            .then(json => {
              if (json.status === 'ok') {
                document.getElementById('consent-box').remove();
              } else {
                alert('Something went wrong');
              }
            })
            .catch(err => { alert('Network error'); console.error(err); });
        });
      </script>
    <?php endif; ?>