<?php
// Initialize global settings
require_once __DIR__ . '/../../includes/config.php';
use App\Config;
Config::init();

require_once __DIR__ . '/../../includes/consent_helper.php';
$is_resolved_consent = verify_is_resolved_consent($pdo);

$currentPath = strtolower(trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$excluded = ['terms.php', 'privacy.php'];
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
<script>
  const overlay = document.getElementById('consent-overlay');
  overlay.hidden = false;
  document.body.classList.add('modal-open');
</script>


<body>
  <header>
    <h1>C is for Cookie</h1>
    <nav>
      <a href="/index.php">Home</a>
      <a href="/about.php">About</a>
    </nav>
  </header>
  <main class="flow">