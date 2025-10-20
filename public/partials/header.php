<?php
// Initialize global settings
require_once __DIR__ . '/../../includes/config.php';
use App\Config;
Config::init();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= $pageTitle ?? 'My PHP Site' ?></title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>
  <header>
    <h1>My PHP Site</h1>
    <nav>
      <a href="/index.php">Home</a> |
      <a href="/about.php">About</a>
    </nav>
  </header>
  <main>

    <?php
    require_once __DIR__ . '/../../includes/consent_helper.php';
    $is_resolved_consent = verify_is_resolved_consent($pdo);
    ?>
    <?php if ($is_resolved_consent === false): ?>
      <div id="consent-box"
        style="position: fixed; bottom: 10px; left: 10px; right: 10px; background: #fff; border: 1px solid #ddd; padding: 16px;">
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