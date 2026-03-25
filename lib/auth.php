<?php
require_once __DIR__ . '/json.php';

function auth_start(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

function auth_load(): array {
  $file = __DIR__ . '/../data/auth.json';
  $data = read_json_file($file, ['users' => []]);
  if (!isset($data['users']) || !is_array($data['users'])) $data['users'] = [];
  return $data;
}

function auth_user(): ?array {
  auth_start();
  return $_SESSION['user'] ?? null;
}

function require_login(): void {
  if (!auth_user()) {
    header('Location: login.php');
    exit;
  }
}

function require_role(string $role): void {
  $u = auth_user();
  if (!$u || ($u['role'] ?? '') !== $role) {
    http_response_code(403);
    echo "Forbidden";
    exit;
  }
}

function auth_login(string $username, string $password): bool {
  auth_start();
  $auth = auth_load();
  foreach ($auth['users'] as $u) {
    if (!is_array($u)) continue;
    if (($u['aktif'] ?? true) === false) continue;

    if ((string)($u['username'] ?? '') !== $username) continue;

    $hash = (string)($u['password_hash'] ?? '');
    if ($hash !== '' && password_verify($password, $hash)) {
      $_SESSION['user'] = [
        'username' => (string)$u['username'],
        'role' => (string)($u['role'] ?? 'kasir')
      ];
      return true;
    }
    return false;
  }
  return false;
}

function auth_logout(): void {
  auth_start();
  $_SESSION = [];
  session_destroy();
}

function require_admin(): void {
  require_login();
  require_role('admin');
}

// simpan auth.json dengan aman
function auth_save(array $data): void {
  $file = __DIR__ . '/../data/auth.json';
  file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}