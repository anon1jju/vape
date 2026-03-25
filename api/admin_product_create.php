<?php
require_once __DIR__ . '/../lib/auth.php';
require_admin();

require_once __DIR__ . '/../lib/json.php';

$produkFile = __DIR__ . '/../data/produk.json';
$produk = read_json_file($produkFile, []);
if (!is_array($produk)) $produk = [];

$nama = trim((string)($_POST['nama'] ?? ''));
$kategori = trim((string)($_POST['kategori'] ?? 'umum'));
$hargaModal = to_int($_POST['harga_modal'] ?? 0);
$hargaJual = to_int($_POST['harga_jual'] ?? 0);
$stok = to_int($_POST['stok'] ?? 0);

if ($nama === '') {
  header('Location: ../admin_products.php?err=' . urlencode('Nama produk wajib diisi.'));
  exit;
}
if ($kategori === '') $kategori = 'umum';
if ($hargaModal < 0) $hargaModal = 0;
if ($hargaJual < 0) $hargaJual = 0;
if ($stok < 0) $stok = 0;

$pid = 'P-' . date('YmdHis') . '-' . rand(100,999);

$produk[$pid] = [
  'nama' => $nama,
  'kategori' => $kategori,
  'harga_modal' => $hargaModal,
  'harga_jual' => $hargaJual,
  'stok' => $stok
];

file_put_contents($produkFile, json_encode($produk, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);
header('Location: ../admin_products.php?msg=' . urlencode('Produk ditambahkan: ' . $pid));
exit;