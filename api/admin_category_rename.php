<?php
require_once __DIR__ . '/../lib/auth.php';
require_admin();
require_once __DIR__ . '/../lib/json.php';

$catFile = __DIR__ . '/../data/kategori.json';
$prodFile = __DIR__ . '/../data/produk.json';

$from = trim((string)($_POST['from'] ?? ''));
$to   = trim((string)($_POST['to'] ?? ''));

if ($from === '' || $to === '') {
  header('Location: ../admin_products.php?err=' . urlencode('Nama kategori lama/baru wajib diisi.'));
  exit;
}

$cat = read_json_file($catFile, ['items'=>[]]);
if (!isset($cat['items']) || !is_array($cat['items'])) $cat['items'] = [];

$fromLower = mb_strtolower($from);
$toLower = mb_strtolower($to);

$found = false;
foreach ($cat['items'] as $i => $c) {
  if (mb_strtolower((string)$c) === $fromLower) {
    $cat['items'][$i] = $to;
    $found = true;
    break;
  }
}
if (!$found) {
  header('Location: ../admin_products.php?err=' . urlencode('Kategori lama tidak ditemukan.'));
  exit;
}

// cegah duplikat setelah rename
$seen = [];
$unique = [];
foreach ($cat['items'] as $c) {
  $k = mb_strtolower((string)$c);
  if (isset($seen[$k])) continue;
  $seen[$k] = true;
  $unique[] = (string)$c;
}
$cat['items'] = $unique;
sort($cat['items']);

file_put_contents($catFile, json_encode($cat, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);

// update produk.json: kategori lama -> baru
$prod = read_json_file($prodFile, []);
if (!is_array($prod)) $prod = [];
foreach ($prod as $pid => $p) {
  if (!is_array($p)) continue;
  $k = (string)($p['kategori'] ?? 'umum');
  if (mb_strtolower($k) === $fromLower) {
    $prod[$pid]['kategori'] = $to;
  }
}
file_put_contents($prodFile, json_encode($prod, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);

header('Location: ../admin_products.php?msg=' . urlencode('Kategori berhasil diupdate & produk ikut disesuaikan.'));
exit;