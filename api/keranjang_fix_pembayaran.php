<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../lib/auth.php';
require_login(); // admin/kasir boleh, karena ini hanya membetulkan pembayaran transaksi
require_once __DIR__ . '/../lib/json.php';

$keranjangFile = __DIR__ . '/../data/keranjang.json';

$trxIndex = to_int($_POST['trx_index'] ?? -1);

$keranjang = read_json_file($keranjangFile, ['items'=>[]]);
$items = is_array($keranjang['items'] ?? null) ? $keranjang['items'] : [];

if ($trxIndex < 0 || $trxIndex >= count($items) || !is_array($items[$trxIndex])) {
  header('Location: ../keranjang.php?msg=' . urlencode('Index transaksi tidak valid.'));
  exit;
}

$trx = $items[$trxIndex];
$grand = to_int($trx['grand_total'] ?? ($trx['total_pemasukan'] ?? 0));

// set pembayaran: cash full
$items[$trxIndex]['pembayaran'] = [
  'cash' => $grand,
  'qris' => 0,
  'transfer' => 0
];
$items[$trxIndex]['method_bayar'] = 'cash';

$keranjang['items'] = $items;

file_put_contents($keranjangFile, json_encode($keranjang, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);

header('Location: ../keranjang.php?msg=' . urlencode('Pembayaran di-auto-fix: cash = grand total.'));
exit;