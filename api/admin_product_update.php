<?php
require_once __DIR__ . '/../lib/auth.php';
require_admin();
require_once __DIR__ . '/../lib/json.php';

$produkFile = __DIR__ . '/../data/produk.json';
$produk = read_json_file($produkFile, []);
if (!is_array($produk)) $produk = [];

$pid = (string)($_POST['produk_id'] ?? '');
if ($pid === '' || !isset($produk[$pid]) || !is_array($produk[$pid])) {
  header('Location: ../admin_products.php?err=' . urlencode('Produk tidak ditemukan.'));
  exit;
}

$nama = trim((string)($_POST['nama'] ?? ''));
$kategori = trim((string)($_POST['kategori'] ?? 'umum'));
$hargaModal = to_int($_POST['harga_modal'] ?? 0);
$hargaJual  = to_int($_POST['harga_jual'] ?? 0);
$stok       = to_int($_POST['stok'] ?? 0);

if ($nama === '') {
  header('Location: ../admin_products.php?err=' . urlencode('Nama produk tidak boleh kosong.'));
  exit;
}
if ($kategori === '') $kategori = 'umum';
if ($hargaModal < 0) $hargaModal = 0;
if ($hargaJual < 0) $hargaJual = 0;
if ($stok < 0) $stok = 0;

$produk[$pid]['nama'] = $nama;
$produk[$pid]['kategori'] = $kategori;
$produk[$pid]['harga_modal'] = $hargaModal;
$produk[$pid]['harga_jual'] = $hargaJual;
$produk[$pid]['stok'] = $stok;

file_put_contents($produkFile, json_encode($produk, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);
header('Location: ../admin_products.php?msg=' . urlencode('Produk diupdate: ' . $pid));
exit;