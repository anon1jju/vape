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

$current = auth_user();
if ($current && (string)($current['username'] ?? '') === (string)($users[$index]['username'] ?? '')) {
  header('Location: ../admin_users.php?err=' . urlencode('Tidak bisa menghapus akun yang sedang login.'));
  exit;
}

// safety: jangan hapus admin terakhir
$adminCount = 0;
foreach ($users as $u) {
  if (is_array($u) && (string)($u['role'] ?? '') === 'admin' && (bool)($u['aktif'] ?? true) === true) $adminCount++;
}
if ((string)($users[$index]['role'] ?? '') === 'admin' && $adminCount <= 1) {
  header('Location: ../admin_users.php?err=' . urlencode('Tidak boleh menghapus admin aktif terakhir.'));
  exit;
}

array_splice($users, $index, 1);

$auth['users'] = $users;
auth_save($auth);

header('Location: ../admin_users.php?msg=' . urlencode('User berhasil dihapus.'));
exit;