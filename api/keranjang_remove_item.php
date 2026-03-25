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

if ($trxIndex < 0 || $trxIndex >= count($data['items'])) {
  header('Location: ../keranjang.php?msg=' . urlencode('Index transaksi tidak valid.'));
  exit;
}

$items = $data['items'][$trxIndex]['items'] ?? [];
if (!is_array($items) || $itemIndex < 0 || $itemIndex >= count($items)) {
  header('Location: ../keranjang.php?msg=' . urlencode('Index item tidak valid.'));
  exit;
}

$it = $data['items'][$trxIndex]['items'][$itemIndex];
$pid = (string)($it['produk_id'] ?? '');
$qty = to_int($it['jumlah'] ?? 0);

// rollback stok sebanyak qty yang dihapus
if ($qty > 0) adjust_stock($produk, $pid, +$qty);

// hapus item
array_splice($data['items'][$trxIndex]['items'], $itemIndex, 1);

// recalc totals
$data['items'][$trxIndex] = recalc_trx($data['items'][$trxIndex]);

file_put_contents($keranjangFile, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
file_put_contents($produkFile, json_encode($produk, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

header('Location: ../keranjang.php?msg=' . urlencode('Item dihapus, stok & total otomatis disesuaikan.'));
exit;