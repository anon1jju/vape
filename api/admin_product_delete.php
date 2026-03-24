<?php
require_once __DIR__ . '/../lib/auth.php';
require_admin();

require_once __DIR__ . '/../lib/json.php';

$produkFile = __DIR__ . '/../data/produk.json';
$cartFile = __DIR__ . '/../data/cart.json';
$keranjangFile = __DIR__ . '/../data/keranjang.json';

$pid = (string)($_POST['produk_id'] ?? '');
if ($pid === '') {
  header('Location: ../admin_products.php?err=' . urlencode('produk_id kosong.'));
  exit;
}

$produk = read_json_file($produkFile, []);
if (!is_array($produk) || !isset($produk[$pid])) {
  header('Location: ../admin_products.php?err=' . urlencode('Produk tidak ditemukan.'));
  exit;
}

// cek dipakai di cart
$cart = read_json_file($cartFile, []);
if (is_array($cart)) {
  foreach ($cart as $it) {
    if (is_array($it) && (string)($it['produk_id'] ?? '') === $pid) {
      header('Location: ../admin_products.php?err=' . urlencode('Tidak bisa hapus: produk masih ada di cart aktif.'));
      exit;
    }
  }
}

// cek dipakai di keranjang transaksi
$keranjang = read_json_file($keranjangFile, ['items'=>[]]);
$list = is_array($keranjang['items'] ?? null) ? $keranjang['items'] : [];
foreach ($list as $trx) {
  $items = is_array($trx['items'] ?? null) ? $trx['items'] : [];
  foreach ($items as $it) {
    if (is_array($it) && (string)($it['produk_id'] ?? '') === $pid) {
      header('Location: ../admin_products.php?err=' . urlencode('Tidak bisa hapus: produk masih ada di keranjang transaksi.'));
      exit;
    }
  }
}

unset($produk[$pid]);
file_put_contents($produkFile, json_encode($produk, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);

header('Location: ../admin_products.php?msg=' . urlencode('Produk dihapus: ' . $pid));
exit;