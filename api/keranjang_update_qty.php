<?php
require_once __DIR__ . '/../lib/json.php';
require_once __DIR__ . '/../lib/keranjang_helpers.php';

$keranjangFile = __DIR__ . '/../data/keranjang.json';
$produkFile    = __DIR__ . '/../data/produk.json';

$data = read_json_file($keranjangFile, ['items'=>[]]);
if (!isset($data['items']) || !is_array($data['items'])) $data['items'] = [];

$produk = read_json_file($produkFile, []);

$trxIndex  = to_int($_POST['trx_index'] ?? -1);
$itemIndex = to_int($_POST['item_index'] ?? -1);
$newQty    = to_int($_POST['jumlah'] ?? 1);
if ($newQty < 1) $newQty = 1;

if ($trxIndex < 0 || $trxIndex >= count($data['items'])) {
  header('Location: ../keranjang.php?msg=' . urlencode('Index transaksi tidak valid.'));
  exit;
}

$trx = $data['items'][$trxIndex];
$items = $trx['items'] ?? [];
if (!is_array($items) || $itemIndex < 0 || $itemIndex >= count($items)) {
  header('Location: ../keranjang.php?msg=' . urlencode('Index item tidak valid.'));
  exit;
}

$oldQty = to_int($data['items'][$trxIndex]['items'][$itemIndex]['jumlah'] ?? 0);
$pid = (string)($data['items'][$trxIndex]['items'][$itemIndex]['produk_id'] ?? '');

if ($oldQty < 0) $oldQty = 0;

// delta untuk stok:
// old=2 new=1 => deltaQty = +1 (stok naik)
// old=1 new=3 => deltaQty = -2 (stok turun)
$deltaQty = $oldQty - $newQty;

if ($deltaQty < 0) {
  // butuh stok tambahan
  $need = abs($deltaQty);
  $stokNow = isset($produk[$pid]) ? to_int($produk[$pid]['stok'] ?? 0) : 0;
  if ($stokNow < $need) {
    header('Location: ../keranjang.php?msg=' . urlencode("Stok tidak cukup untuk tambah qty. Stok sekarang: $stokNow, butuh: $need"));
    exit;
  }
}

// update qty item
$data['items'][$trxIndex]['items'][$itemIndex]['jumlah'] = $newQty;

// adjust stok produk
adjust_stock($produk, $pid, $deltaQty);

// recalc totals transaksi
$data['items'][$trxIndex] = recalc_trx($data['items'][$trxIndex]);

file_put_contents($keranjangFile, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
file_put_contents($produkFile, json_encode($produk, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

header('Location: ../keranjang.php?msg=' . urlencode('Qty diupdate, stok & total otomatis disesuaikan.'));
exit;