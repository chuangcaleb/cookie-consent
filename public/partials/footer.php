</main>
<footer>
  <span>
    <a href="/terms.php">Terms & Conditions</a> ⋅ <a href="privacy.php">Privacy Statement</a> ⋅
  </span>
  &copy; <?= date('Y') ?> My PHP Site</p>
</footer>
<?php
require_once __DIR__ . '/../../includes/consent_helper.php';
$is_resolved_consent = verify_is_resolved_consent($pdo);

$currentPath = strtolower(trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$excluded = ['terms.php', 'privacy.php'];

if ($is_resolved_consent === false && !in_array($currentPath, $excluded, true)) {
  include __DIR__ . '/consent_prompt.php';
}
?>
</body>

</html>