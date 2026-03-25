<?php
require_once __DIR__ . '/../lib/json.php';
require_once __DIR__ . '/../lib/keranjang_helpers.php';

$keranjangFile = __DIR__ . '/../data/keranjang.json';
$produkFile    = __DIR__ . '/../data/produk.json';

$data = read_json_file($keranjangFile, ['items'=>[]]);
if (!isset($data['items']) || !is_array($data['items'])) $data['items'] = [];

$produk = read_json_file($produkFile, []);

$trxIndex = to_int($_POST['trx_index'] ?? -1);
if ($trxIndex < 0 || $trxIndex >= count($data['items'])) {
  header('Location: ../keranjang.php?msg=' . urlencode('Index transaksi tidak valid.'));
  exit;
}

// rollback stok untuk semua item transaksi ini
$trx = $data['items'][$trxIndex];
rollback_stock_for_trx($produk, $trx);

// hapus transaksi
array_splice($data['items'], $trxIndex, 1);

file_put_contents($keranjangFile, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
file_put_contents($produkFile, json_encode($produk, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

header('Location: ../keranjang.php?msg=' . urlencode('Transaksi dihapus, stok otomatis dikembalikan.'));
exit;