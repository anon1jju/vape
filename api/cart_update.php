<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../lib/json.php';

$produkFile = __DIR__ . '/../data/produk.json';
$cartFile   = __DIR__ . '/../data/cart.json';

$produk = read_json_file($produkFile, []);
$cart   = read_json_file($cartFile, []);
if (!is_array($cart)) $cart = [];

$index  = to_int($_POST['index'] ?? -1);
$jumlah = to_int($_POST['jumlah'] ?? 1);
if ($jumlah < 1) $jumlah = 1;

if ($index < 0 || $index >= count($cart) || !is_array($cart[$index])) {
  header('Location: ../kasir.php?msg=' . urlencode('Index cart tidak valid.'));
  exit;
}

$produkId = (string)($cart[$index]['produk_id'] ?? '');
if ($produkId === '' || !isset($produk[$produkId]) || !is_array($produk[$produkId])) {
  header('Location: ../kasir.php?msg=' . urlencode('Produk item cart tidak ditemukan.'));
  exit;
}

$nama = (string)($produk[$produkId]['nama'] ?? $produkId);
$stokNow = to_int($produk[$produkId]['stok'] ?? 0);

// Validasi: qty di cart tidak boleh melebihi stok (karena stok dipotong saat "Selesaikan Transaksi")
if ($jumlah > $stokNow) {
  header('Location: ../kasir.php?msg=' . urlencode("Stok tidak cukup untuk '$nama'. Stok: $stokNow, qty diminta: $jumlah"));
  exit;
}

$cart[$index]['jumlah'] = $jumlah;

file_put_contents($cartFile, json_encode($cart, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
header('Location: ../kasir.php?msg=' . urlencode('Qty cart diupdate.'));
exit;