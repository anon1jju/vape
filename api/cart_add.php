<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../lib/json.php';

$produkFile = __DIR__ . '/../data/produk.json';
$cartFile   = __DIR__ . '/../data/cart.json';

$produk = read_json_file($produkFile, []);
$cart   = read_json_file($cartFile, []);
if (!is_array($cart)) $cart = [];

$produkId = (string)($_POST['produk_id'] ?? '');
$jumlah = to_int($_POST['jumlah'] ?? 1);
if ($jumlah < 1) $jumlah = 1;

if ($produkId === '' || !isset($produk[$produkId]) || !is_array($produk[$produkId])) {
  header('Location: ../kasir.php?msg=' . urlencode('Produk tidak ditemukan.'));
  exit;
}

$p = $produk[$produkId];

$nama = (string)($p['nama'] ?? '');
$hargaJual = (string)($p['harga_jual'] ?? '0');
$hargaModal = to_int($p['harga_modal'] ?? 0);
$hargaJualInt = to_int($p['harga_jual'] ?? 0);
$labaPerProduk = max(0, $hargaJualInt - $hargaModal);

$stokNow = to_int($p['stok'] ?? 0);
$today = date('d-m-Y');

// cari qty existing di cart
$existingQty = 0;
$existingIndex = -1;
foreach ($cart as $i => $it) {
  if (!is_array($it)) continue;
  if ((string)($it['produk_id'] ?? '') === $produkId) {
    $existingQty = to_int($it['jumlah'] ?? 0);
    $existingIndex = $i;
    break;
  }
}

$targetQty = $existingQty + $jumlah;
if ($targetQty > $stokNow) {
  header('Location: ../kasir.php?msg=' . urlencode("Stok tidak cukup untuk '$nama'. Stok: $stokNow, cart: $existingQty, tambah: $jumlah"));
  exit;
}

if ($existingIndex >= 0) {
  $cart[$existingIndex]['jumlah'] = $targetQty;
} else {
  $cart[] = [
    'tanggal' => $today,
    'nama' => $nama,
    'harga_jual' => $hargaJual,
    'jumlah' => $jumlah,
    'laba_per_produk' => $labaPerProduk,
    'produk_id' => $produkId
  ];
}

file_put_contents($cartFile, json_encode($cart, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
header('Location: ../kasir.php');
exit;