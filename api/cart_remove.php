<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../lib/json.php';

$cartFile = __DIR__ . '/../data/cart.json';
$cart = read_json_file($cartFile, []);
if (!is_array($cart)) $cart = [];

$index = to_int($_POST['index'] ?? -1);
if ($index < 0 || $index >= count($cart)) {
  header('Location: ../kasir.php?msg=' . urlencode('Index cart tidak valid.'));
  exit;
}

// Hapus item dari cart aktif (TIDAK ubah stok)
array_splice($cart, $index, 1);

file_put_contents($cartFile, json_encode($cart, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
header('Location: ../kasir.php?msg=' . urlencode('Item dihapus dari cart.'));
exit;