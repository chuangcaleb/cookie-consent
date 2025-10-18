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
    $consent = get_consent_from_cookie();
    ?>
    <?php if ($consent === false): ?>
      <div id="consent-box"
        style="position: fixed; bottom: 10px; left: 10px; right: 10px; background: #fff; border: 1px solid #ddd; padding: 16px;">
        <p>We use cookies. By clicking accept you consent to our cookie policy.</p>
        <button id="acceptBtn">Accept</button>
      </div>

      <script>
        document.getElementById('acceptBtn').addEventListener('click', function () {
          fetch('/consent_accept.php', { method: 'POST' })
            .then(r => r.json())
            .then(json => {
              if (json.status === 'ok') {
                document.getElementById('consent-box').remove();
                console.log('Consent accepted', json.data);
              } else {
                alert('Something went wrong');
              }
            })
            .catch(err => { alert('Network error'); console.error(err); });
        });
      </script>
    <?php else: ?>
      <p>Consent already given. GUID: <?= htmlspecialchars($consent['guid']) ?></p>
    <?php endif; ?>