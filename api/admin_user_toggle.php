<?php
require_once __DIR__ . '/../lib/auth.php';
require_admin();

$index = to_int($_POST['index'] ?? -1);

$auth = auth_load();
$users = $auth['users'] ?? [];
if (!is_array($users)) $users = [];

if ($index < 0 || $index >= count($users) || !is_array($users[$index])) {
  header('Location: ../admin_users.php?err=' . urlencode('Index user tidak valid.'));
  exit;
}

// jangan nonaktifkan diri sendiri (opsional safety)
$current = auth_user();
if ($current && (string)($current['username'] ?? '') === (string)($users[$index]['username'] ?? '')) {
  header('Location: ../admin_users.php?err=' . urlencode('Tidak bisa menonaktifkan akun yang sedang login.'));
  exit;
}

$aktif = (bool)($users[$index]['aktif'] ?? true);
$users[$index]['aktif'] = !$aktif;

$auth['users'] = $users;
auth_save($auth);

header('Location: ../admin_users.php?msg=' . urlencode('Status user diubah.'));
exit;