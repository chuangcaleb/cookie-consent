<?php
require_once __DIR__ . '/config.php';
use App\Config;

try {
  $dsn = "mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME . ";charset=utf8mb4";
  $pdo = new PDO($dsn, Config::DB_USER, Config::DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  // In dev show error; in prod log and show friendly message.
  die("Database connection failed: " . $e->getMessage());
}
?>