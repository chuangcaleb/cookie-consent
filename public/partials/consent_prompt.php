<div id="consent-overlay" tabindex="-1">
  <div id="consent-box" class="flow" role="dialog" aria-modal="true" aria-labelledby="consent-title">
    <h4 id="consent-title">🍪 We use cookies</h4>
    <p>Cookies are necessary for this website to function properly, for performance measurement, and to provide you
      with the best experience.</p>
    <p>
      By continuing to access or use this site, you acknowledge and consent to our use of cookies in accordance with
      our <a href="/terms.php">Terms & Conditions</a> and <a href="privacy.php">Privacy Statement</a>.</p>
    <button data-action="accept">Accept</button>
    <button data-action="decline">Decline</button>
  </div>
</div>


<script>
  (() => {
    const overlay = document.getElementById('consent-overlay');
    if (!overlay) return;

    const endpoints = {
      accept: '/consent_accept.php',
      decline: '/consent_decline.php'
    };

    overlay.addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-action]');
      if (!btn) return;

      const action = btn.dataset.action;
      const endpoint = endpoints[action];
      if (!endpoint) return;

      try {
        const res = await fetch(endpoint, { method: 'POST' });
        const json = await res.json();

        if (json?.status === 'ok') {
          overlay.remove();
        } else {
          console.warn(`Failed to ${action} cookies:`, json);
          alert('Something went wrong. Please try again.');
        }
      } catch (err) {
        console.error('Network error:', err);
        alert('Network error. Please try again.');
      }
    });
  })();
</script>