<?php

/**
 * Manually load environment variables from specified local file.
 * @param string $file File path to env file.
 */
function load_env(string $file): void
{
  if (!file_exists($file))
    return;
  foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#'))
      continue;
    [$key, $value] = explode('=', $line, 2);
    $_ENV[$key] = $value;
    putenv("$key=$value");
  }
}

/**
 * Generate a cryptographically secure GUID/UUID v4 string (36 chars)
 */
function generate_guid_v4(): string
{
  $data = random_bytes(16);
  // set version to 0100
  $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
  // set bits 6-7 to 10
  $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Clear the client-side consent cookie
 * @param string Cookie name.
 */
function clear_cookie(string $name): void
{
  setcookie(
    $name,
    '',
    [
      'expires' => time() - 3600,
      'path' => '/',
      'domain' => '',
      'secure' => isset($_SERVER['HTTPS']),
      'httponly' => true,
      'samesite' => 'Lax',
    ]
  );
  unset($_COOKIE[$name]);
}
