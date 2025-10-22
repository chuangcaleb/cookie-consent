<?php
require_once __DIR__ . '/Config.php';
use App\Config;
$db = Config::db();

try {
  $dsn = "mysql:host=" . $db['host'] . ";dbname=" . $db['name'] . ";charset=utf8mb4";
  $pdo = new PDO($dsn, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  // In dev show error; in prod log and show friendly message.
  die("Database connection failed: " . $e->getMessage());
}
?>