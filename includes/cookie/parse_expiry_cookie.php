<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

/**
 * Check if expiry cookie exists and is valid.
 *
 * @param string $cookie_name Name of the cookie to check.
 * @param string $initial_datetime_key Key in the cookie's JSON data that stores the initial timestamp.
 * @return array|bool Returns cookie data if valid, or false if not present or expired.
 */
function parse_expiry_cookie(string $cookie_name, string $initial_datetime_key, string $expiry_duration): array|bool
{
  // if missing cookie, return false
  if (!isset($_COOKIE[$cookie_name])) {
    return false;
  }

  $data = json_decode($_COOKIE[$cookie_name], true);

  // if malformed (or missing property), return false
  if (!is_array($data) || empty($data[$initial_datetime_key])) {
    clear_cookie($cookie_name);
    return false;
  }

  // # check expiry
  // ## if malformed (invalid json value), return false
  try {
    $initial_datetime = new DateTimeImmutable($data[$initial_datetime_key]);
  } catch (Exception $e) {
    clear_cookie($cookie_name);

    return false;
  }
  // ## if cookie expired, return false
  $expiry_datetime = $initial_datetime->add(new DateInterval($expiry_duration));
  if (new DateTimeImmutable('now') > $expiry_datetime) {

    clear_cookie($cookie_name);
    return false;
  }
  // else valid declined cookie, return true
  return $data;
}
