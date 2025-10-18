<?php
require_once __DIR__ . '/../includes/consent_helper.php';

// Early reject, if not `POST` method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

// TODO: Optionally verify CSRF/token if you have one?
try {
  $result = accept_consent($pdo, CONSENT_COOKIE_VERSION);
  header('Content-Type: application/json');
  echo json_encode(['status' => 'ok', 'data' => $result]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
