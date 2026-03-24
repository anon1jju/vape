<?php
require_once __DIR__ . '/../lib/json.php';

$keranjangFile = __DIR__ . '/../data/keranjang.json';
$checkoutFile  = __DIR__ . '/../data/checkout.json';

$keranjang = read_json_file($keranjangFile, ['items'=>[]]);
$list = is_array($keranjang['items'] ?? null) ? $keranjang['items'] : [];
if (count($list) === 0) {
  header('Location: ../keranjang.php?msg=' . urlencode('Keranjang kosong. Tidak ada yang ditutup.'));
  exit;
}

$checkout = read_json_file($checkoutFile, ['items'=>[]]);
if (!isset($checkout['items']) || !is_array($checkout['items'])) $checkout['items'] = [];

// append semua transaksi
foreach ($list as $trx) {
  $checkout['items'][] = $trx;
}

// simpan checkout, kosongkan keranjang
file_put_contents($checkoutFile, json_encode($checkout, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
file_put_contents($keranjangFile, json_encode(['items'=>[]], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

header('Location: ../keranjang.php?msg=' . urlencode('Tutup hari sukses. Semua transaksi dipindahkan ke checkout.json.'));
exit;