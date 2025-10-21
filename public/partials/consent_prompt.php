<div id="consent-overlay" tabindex="-1">
  <div id="consent-box" class="flow" role="dialog" aria-modal="true" aria-labelledby="consent-title">
    <h4 id="consent-title">🍪 We use cookies</h4>
    <p>Cookies are necessary for this website to function properly, for performance measurement, and to provide you
      with the best experience.</p>
    <p>
      By continuing to access or use this site, you acknowledge and consent to our use of cookies in accordance with
      our <a href="/terms.php">Terms & Conditions</a> and <a href="privacy.php">Privacy Statement</a>.</p>
    <button id="acceptBtn">Accept</button>
    <button id="declineBtn">Decline</button>
  </div>
</div>

<script>
  document.getElementById('acceptBtn').addEventListener('click', function () {
    fetch('/consent_accept.php', { method: 'POST' })
      .then(r => r.json())
      .then(json => {
        if (json.status === 'ok') {
          document.getElementById('consent-overlay').remove();
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
          document.getElementById('consent-overlay').remove();
        } else {
          alert('Something went wrong');
        }
      })
      .catch(err => { alert('Network error'); console.error(err); });
  });
</script>