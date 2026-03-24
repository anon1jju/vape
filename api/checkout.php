<?php
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../lib/json.php';

$cartFile     = __DIR__ . '/../data/cart.json';
$checkoutFile = __DIR__ . '/../data/checkout.json';
$produkFile   = __DIR__ . '/../data/produk.json';

$cart = read_json_file($cartFile, []);
if (!is_array($cart) || count($cart) === 0) {
  header('Location: ../kasir.php?msg=' . urlencode('Cart kosong.'));
  exit;
}

$produk = read_json_file($produkFile, []);
$checkout = read_json_file($checkoutFile, ['items'=>[]]);
if (!isset($checkout['items']) || !is_array($checkout['items'])) $checkout['items'] = [];

$kasir = trim((string)($_POST['kasir'] ?? ''));
if ($kasir === '') $kasir = 'unknown';

$payCash = to_int($_POST['pay_cash'] ?? 0);
$payQris = to_int($_POST['pay_qris'] ?? 0);
$payTransfer = to_int($_POST['pay_transfer'] ?? 0);

$diskon = to_int($_POST['diskon'] ?? 0);
if ($diskon < 0) $diskon = 0;

$waktu = date('H:i:s');
$tanggal = (string)($cart[0]['tanggal'] ?? date('d-m-Y'));

$totalPemasukan = 0;
$totalLaba = 0;

// tentukan method_bayar item: mixed / cash / qris / transfer
$methodsUsed = 0;
if ($payCash > 0) $methodsUsed++;
if ($payQris > 0) $methodsUsed++;
if ($payTransfer > 0) $methodsUsed++;

$methodItem = 'cash';
if ($methodsUsed >= 2) $methodItem = 'mixed';
else if ($payQris > 0) $methodItem = 'qris';
else if ($payTransfer > 0) $methodItem = 'transfer';
else if ($payCash > 0) $methodItem = 'cash';
// kalau semuanya 0, tetap allow tapi anggap cash (atau bisa ditolak)
if ($payCash === 0 && $payQris === 0 && $payTransfer === 0) $methodItem = 'cash';

foreach ($cart as $i => $it) {
  if (!is_array($it)) continue;

  $pid = (string)($it['produk_id'] ?? '');
  $harga = to_int($it['harga_jual'] ?? 0);
  $qty = to_int($it['jumlah'] ?? 0);
  $lpp = to_int($it['laba_per_produk'] ?? 0);

  $totalPemasukan += $harga * $qty;
  $totalLaba += $lpp * $qty;

  // field item sesuai format kamu
  if (!isset($cart[$i]['kategori']) || $cart[$i]['kategori'] === '' || $cart[$i]['kategori'] === null) {
    $cart[$i]['kategori'] = (string)($produk[$pid]['kategori'] ?? 'umum');
    if ($cart[$i]['kategori'] === '') $cart[$i]['kategori'] = 'umum';
  }
  $cart[$i]['waktu'] = $waktu;
  $cart[$i]['kasir'] = $kasir;
  $cart[$i]['method_bayar'] = $methodItem;
}

// apply diskon
if ($diskon > $totalPemasukan) $diskon = $totalPemasukan;
$grandTotal = $totalPemasukan - $diskon;

// validasi pembayaran (boleh tidak pas?)
// rekomendasi: harus sama persis dengan grandTotal, supaya rapi.
$paid = $payCash + $payQris + $payTransfer;
if ($paid !== 0 && $paid !== $grandTotal) {
  header('Location: ../kasir.php?msg=' . urlencode("Pembayaran ($paid) harus sama dengan grand total ($grandTotal)."));
  exit;
}

// append transaksi
$checkout['items'][] = [
  'items' => $cart,
  'total_laba' => $totalLaba,
  'total_pemasukan' => $totalPemasukan,
  'diskon' => $diskon,
  'grand_total' => $grandTotal,
  'pembayaran' => [
    'cash' => $payCash,
    'qris' => $payQris,
    'transfer' => $payTransfer
  ]
];

// simpan checkout + kosongkan cart
file_put_contents($checkoutFile, json_encode($checkout, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
file_put_contents($cartFile, json_encode([], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

header('Location: ../kasir.php?msg=' . urlencode('Checkout sukses.'));
exit;