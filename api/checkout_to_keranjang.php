<?php
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../lib/json.php';

$cartFile      = __DIR__ . '/../data/cart.json';          // cart aktif (array item)
$keranjangFile = __DIR__ . '/../data/keranjang.json';     // kumpulan transaksi (object items[])
$produkFile    = __DIR__ . '/../data/produk.json';

$cart = read_json_file($cartFile, []);
if (!is_array($cart) || count($cart) === 0) {
  header('Location: ../kasir.php?msg=' . urlencode('Cart kosong.'));
  exit;
}

$produk = read_json_file($produkFile, []);
$keranjang = read_json_file($keranjangFile, ['items' => []]);
if (!isset($keranjang['items']) || !is_array($keranjang['items'])) $keranjang['items'] = [];

$kasir = trim((string)($_POST['kasir'] ?? ''));
if ($kasir === '') $kasir = 'unknown';

$payCash = to_int($_POST['pay_cash'] ?? 0);
$payQris = to_int($_POST['pay_qris'] ?? 0);
$payTransfer = to_int($_POST['pay_transfer'] ?? 0);

$diskon = to_int($_POST['diskon'] ?? 0);
if ($diskon < 0) $diskon = 0;

$waktu = date('H:i:s');
$tanggal = (string)($cart[0]['tanggal'] ?? date('d-m-Y'));

$methodsUsed = 0;
if ($payCash > 0) $methodsUsed++;
if ($payQris > 0) $methodsUsed++;
if ($payTransfer > 0) $methodsUsed++;
$methodItem = 'cash';
if ($methodsUsed >= 2) $methodItem = 'mixed';
else if ($payQris > 0) $methodItem = 'qris';
else if ($payTransfer > 0) $methodItem = 'transfer';
else if ($payCash > 0) $methodItem = 'cash';

// --------------------
// 1) VALIDASI STOK DULU (supaya tidak ada yang sudah terpotong sebagian)
// --------------------
foreach ($cart as $it) {
  if (!is_array($it)) continue;
  $pid = (string)($it['produk_id'] ?? '');
  $qty = to_int($it['jumlah'] ?? 0);
  if ($qty < 1) $qty = 1;

  if ($pid === '' || !isset($produk[$pid]) || !is_array($produk[$pid])) {
    header('Location: ../kasir.php?msg=' . urlencode('Produk_id tidak valid: ' . $pid));
    exit;
  }

  $stokNow = to_int($produk[$pid]['stok'] ?? 0);
  if ($stokNow < $qty) {
    $nama = (string)($produk[$pid]['nama'] ?? $pid);
    header('Location: ../kasir.php?msg=' . urlencode("Stok tidak cukup untuk '$nama'. Stok: $stokNow, butuh: $qty"));
    exit;
  }
}

// --------------------
// 2) HITUNG TOTAL + LENGKAPI ITEM + POTONG STOK
// --------------------
$totalPemasukan = 0;
$totalLaba = 0;

foreach ($cart as $i => $it) {
  if (!is_array($it)) continue;

  $pid = (string)($it['produk_id'] ?? '');
  $qty = to_int($it['jumlah'] ?? 0);
  if ($qty < 1) $qty = 1;

  // potong stok (karena transaksi diselesaikan)
  $stokNow = to_int($produk[$pid]['stok'] ?? 0);
  $produk[$pid]['stok'] = max(0, $stokNow - $qty);

  $harga = to_int($it['harga_jual'] ?? 0);
  $lpp = to_int($it['laba_per_produk'] ?? 0);

  $totalPemasukan += $harga * $qty;
  $totalLaba += $lpp * $qty;

  $kategori = (string)($it['kategori'] ?? '');
  if ($kategori === '') {
    $kategori = (string)($produk[$pid]['kategori'] ?? 'umum');
    if ($kategori === '') $kategori = 'umum';
  }

  // item jadi mirip checkout.json
  $cart[$i]['jumlah'] = $qty;
  $cart[$i]['kategori'] = $kategori;
  $cart[$i]['waktu'] = $waktu;
  $cart[$i]['kasir'] = $kasir;
  $cart[$i]['method_bayar'] = $methodItem;
}

// apply diskon
if ($diskon > $totalPemasukan) $diskon = $totalPemasukan;
$grandTotal = $totalPemasukan - $diskon;

// kurangi laba dengan diskon
$totalLaba = $totalLaba - $diskon;
if ($totalLaba < 0) $totalLaba = 0;

// distribusikan diskon proporsional ke setiap item
if ($diskon > 0 && $totalPemasukan > 0) {
  $sisaDiskon = $diskon;
  $lastIdx = -1;
  foreach ($cart as $i => $it) {
    if (!is_array($it)) continue;
    $lastIdx = $i;
  }
  foreach ($cart as $i => $it) {
    if (!is_array($it)) continue;
    $qtyItem = to_int($it['jumlah'] ?? 0);
    if ($qtyItem < 1) $qtyItem = 1;
    $hargaItem = to_int($it['harga_jual'] ?? 0);
    $subtotalItem = $hargaItem * $qtyItem;

    if ($i === $lastIdx) {
      $diskonItem = $sisaDiskon;
    } else {
      $diskonItem = (int)round($diskon * $subtotalItem / $totalPemasukan);
      $sisaDiskon -= $diskonItem;
    }

    $cart[$i]['diskon_item'] = $diskonItem;
    $cart[$i]['subtotal_setelah_diskon'] = $subtotalItem - $diskonItem;
  }
} else {
  foreach ($cart as $i => $it) {
    if (!is_array($it)) continue;
    $qtyItem = to_int($it['jumlah'] ?? 0);
    if ($qtyItem < 1) $qtyItem = 1;
    $hargaItem = to_int($it['harga_jual'] ?? 0);
    $cart[$i]['diskon_item'] = 0;
    $cart[$i]['subtotal_setelah_diskon'] = $hargaItem * $qtyItem;
  }
}

// validasi pembayaran (kalau diisi harus sama)
$paid = $payCash + $payQris + $payTransfer;
if ($paid !== 0 && $paid !== $grandTotal) {
  header('Location: ../kasir.php?msg=' . urlencode("Pembayaran ($paid) harus sama dengan grand total ($grandTotal)."));
  exit;
}

// --------------------
// 3) SIMPAN KE KERANJANG (ANTRIAN) + KOSONGKAN CART AKTIF
// --------------------
$trxId = 'TRX-' . date('Ymd-His') . '-' . rand(100,999);

$keranjang['items'][] = [
  'trx_id' => $trxId,
  'tanggal' => $tanggal,
  'waktu' => $waktu,
  'kasir' => $kasir,
  'method_bayar' => $methodItem,
  'items' => $cart,
  'total_pemasukan' => $totalPemasukan,
  'total_laba' => $totalLaba,
  'diskon' => $diskon,
  'grand_total' => $grandTotal,
  'pembayaran' => [
    'cash' => $payCash,
    'qris' => $payQris,
    'transfer' => $payTransfer
  ]
];

file_put_contents($keranjangFile, json_encode($keranjang, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
file_put_contents($produkFile, json_encode($produk, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
file_put_contents($cartFile, json_encode([], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

header('Location: ../kasir.php?msg=' . urlencode('Transaksi disimpan ke keranjang. Stok otomatis berkurang.'));
exit;