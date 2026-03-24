<?php
require_once __DIR__ . '/../lib/auth.php';
require_admin();

require_once __DIR__ . '/../lib/json.php';

$produkFile = __DIR__ . '/../data/produk.json';
$produk = read_json_file($produkFile, []);
if (!is_array($produk)) $produk = [];

$pid = (string)($_POST['produk_id'] ?? '');
$delta = to_int($_POST['delta'] ?? 0);

if ($pid === '' || !isset($produk[$pid]) || !is_array($produk[$pid])) {
  header('Location: ../admin_products.php?err=' . urlencode('Produk tidak ditemukan.'));
  exit;
}

$stok = to_int($produk[$pid]['stok'] ?? 0);
$stokNew = $stok + $delta;
if ($stokNew < 0) $stokNew = 0;

$produk[$pid]['stok'] = $stokNew;

file_put_contents($produkFile, json_encode($produk, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);
header('Location: ../admin_products.php?msg=' . urlencode("Stok diupdate: $pid ($stok → $stokNew)"));
exit;