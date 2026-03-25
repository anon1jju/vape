<?php
require_once __DIR__ . '/../lib/auth.php';
require_admin();

$username = trim((string)($_POST['username'] ?? ''));
$role = (string)($_POST['role'] ?? 'kasir');
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
  header('Location: ../admin_users.php?err=' . urlencode('Username dan password wajib diisi.'));
  exit;
}
if (!in_array($role, ['admin','kasir'], true)) $role = 'kasir';

$auth = auth_load();
$users = $auth['users'] ?? [];
if (!is_array($users)) $users = [];

// unique username
foreach ($users as $u) {
  if (is_array($u) && (string)($u['username'] ?? '') === $username) {
    header('Location: ../admin_users.php?err=' . urlencode('Username sudah dipakai.'));
    exit;
  }
}

$users[] = [
  'username' => $username,
  'password_hash' => password_hash($password, PASSWORD_DEFAULT),
  'role' => $role,
  'aktif' => true
];

$auth['users'] = $users;
auth_save($auth);

header('Location: ../admin_users.php?msg=' . urlencode('User berhasil dibuat.'));
exit;