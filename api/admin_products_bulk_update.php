<?php
require_once __DIR__ . '/../lib/auth.php';
require_admin();
require_once __DIR__ . '/../lib/json.php';

$produkFile = __DIR__ . '/../data/produk.json';
$produk = read_json_file($produkFile, []);
if (!is_array($produk)) $produk = [];

// arrays dari form
$namaArr = $_POST['nama'] ?? [];
$katArr  = $_POST['kategori'] ?? [];
$modalArr= $_POST['harga_modal'] ?? [];
$jualArr = $_POST['harga_jual'] ?? [];
$stokArr = $_POST['stok'] ?? [];

if (!is_array($namaArr) || !is_array($katArr) || !is_array($modalArr) || !is_array($jualArr) || !is_array($stokArr)) {
  header('Location: ../admin_products.php?err=' . urlencode('Format data tidak valid.'));
  exit;
}

$updated = 0;
$skipped = 0;

foreach ($produk as $pid => $p) {
  if (!is_array($p)) continue;

  // hanya update produk yang ada inputnya
  if (!array_key_exists($pid, $namaArr)) { $skipped++; continue; }

  $nama = trim((string)($namaArr[$pid] ?? ''));
  $kat  = trim((string)($katArr[$pid] ?? 'umum'));
  $modal= to_int($modalArr[$pid] ?? 0);
  $jual = to_int($jualArr[$pid] ?? 0);
  $stok = to_int($stokArr[$pid] ?? 0);

  if ($nama === '') { $skipped++; continue; }
  if ($kat === '') $kat = 'umum';

  if ($modal < 0) $modal = 0;
  if ($jual < 0) $jual = 0;
  if ($stok < 0) $stok = 0;

  $produk[$pid]['nama'] = $nama;
  $produk[$pid]['kategori'] = $kat;
  $produk[$pid]['harga_modal'] = $modal;
  $produk[$pid]['harga_jual'] = $jual;
  $produk[$pid]['stok'] = $stok;

  $updated++;
}

file_put_contents($produkFile, json_encode($produk, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);

header('Location: ../admin_products.php?msg=' . urlencode("Bulk save sukses. Updated: $updated, skipped: $skipped"));
exit;