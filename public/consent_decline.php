<?php
require_once __DIR__ . '/../includes/cookie/consent_actions.php';

// Early reject, if not `POST` method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

try {
  decline_consent();
  header('Content-Type: application/json');
  echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
