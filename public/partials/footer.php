</main>
<footer>
  <span>
    <a href="/terms.php">Terms & Conditions</a> ⋅ <a href="privacy.php">Privacy Statement</a> ⋅
  </span>
  &copy; <?= date('Y') ?> My PHP Site</p>
</footer>
<?php if ($is_resolved_consent === false && !in_array($currentPath, $excluded, true)): ?>
  <?php include 'partials/consent_prompt.php'; ?>
<?php endif; ?>
</body>

</html>