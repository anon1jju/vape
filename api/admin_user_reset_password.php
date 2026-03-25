<?php
require_once __DIR__ . '/../lib/auth.php';
require_admin();

$index = to_int($_POST['index'] ?? -1);
$newPw = (string)($_POST['new_password'] ?? '');

if ($newPw === '') {
  header('Location: ../admin_users.php?err=' . urlencode('Password baru wajib diisi.'));
  exit;
}

$auth = auth_load();
$users = $auth['users'] ?? [];
if (!is_array($users)) $users = [];

if ($index < 0 || $index >= count($users) || !is_array($users[$index])) {
  header('Location: ../admin_users.php?err=' . urlencode('Index user tidak valid.'));
  exit;
}

$users[$index]['password_hash'] = password_hash($newPw, PASSWORD_DEFAULT);

$auth['users'] = $users;
auth_save($auth);

header('Location: ../admin_users.php?msg=' . urlencode('Password berhasil direset.'));
exit;