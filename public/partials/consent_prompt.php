<?php
require_once __DIR__ . '/../../includes/consent_helper.php';
$is_resolved_consent = verify_is_resolved_consent($pdo);
?>
<?php if ($is_resolved_consent === false): ?>
  <div id="consent-box" class="flow">
    <p>Cookies are necessary for this website to function properly, for performance measurement, and to provide you
      with the best experience.</p>
    <p>
      By continuing to access or use this site, you acknowledge and consent to our use of cookies in accordance with
      our <a href="/terms.php">Terms & Conditions</a> and <a href="privacy.php">Privacy Statement</a>.</p>
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