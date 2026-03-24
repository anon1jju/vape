<?php
require_once __DIR__ . '/../lib/auth.php';
require_admin();
require_once __DIR__ . '/../lib/json.php';

$file = __DIR__ . '/../data/kategori.json';
$data = read_json_file($file, ['items'=>[]]);
if (!isset($data['items']) || !is_array($data['items'])) $data['items'] = [];

$new = trim((string)($_POST['nama'] ?? ''));
if ($new === '') {
  header('Location: ../admin_products.php?err=' . urlencode('Kategori baru kosong.'));
  exit;
}

$newLower = mb_strtolower($new);
foreach ($data['items'] as $c) {
  if (mb_strtolower((string)$c) === $newLower) {
    header('Location: ../admin_products.php?err=' . urlencode('Kategori sudah ada.'));
    exit;
  }
}

$data['items'][] = $new;
sort($data['items']);

file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);
header('Location: ../admin_products.php?msg=' . urlencode('Kategori ditambahkan.'));
exit;