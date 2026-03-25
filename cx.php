<?php
// Jalankan: php migrate_checkout_v2.php
date_default_timezone_set('Asia/Jakarta');

$checkoutFile = __DIR__ . '/data/checkout.json';
$produkFile   = __DIR__ . '/data/produk.json';

if (!file_exists($checkoutFile)) { fwrite(STDERR, "Missing: $checkoutFile\n"); exit(1); }
if (!file_exists($produkFile))   { fwrite(STDERR, "Missing: $produkFile\n"); exit(1); }

$checkoutRaw = file_get_contents($checkoutFile);
$checkout = json_decode($checkoutRaw, true);
if (!is_array($checkout) || !isset($checkout['items']) || !is_array($checkout['items'])) {
  fwrite(STDERR, "checkout.json format harus {\"items\": [...]}.\n"); exit(1);
}

$produk = json_decode(file_get_contents($produkFile), true);
if (!is_array($produk)) $produk = [];

$ts = date('Ymd-His');
$backup = __DIR__ . "/data/checkout.backup-$ts.json";
file_put_contents($backup, $checkoutRaw);

function yyyymmddFromDdMmYyyy($ddmmyyyy) {
  $parts = explode('-', (string)$ddmmyyyy);
  if (count($parts) !== 3) return '19700101';
  [$dd,$mm,$yyyy] = $parts;
  $dd = str_pad(preg_replace('/\D/','',$dd),2,'0',STR_PAD_LEFT);
  $mm = str_pad(preg_replace('/\D/','',$mm),2,'0',STR_PAD_LEFT);
  $yyyy = preg_replace('/\D/','',$yyyy);
  if (strlen($yyyy) !== 4) return '19700101';
  return $yyyy.$mm.$dd;
}

function getKategoriProduk($produkMap, $produkId) {
  $pid = (string)$produkId;
  if ($pid !== '' && isset($produkMap[$pid]) && is_array($produkMap[$pid])) {
    $kat = $produkMap[$pid]['kategori'] ?? 'umum';
    return ($kat === '' || $kat === null) ? 'umum' : $kat;
  }
  return 'umum';
}

$counterPerTanggal = [];

foreach ($checkout['items'] as $idx => $trx) {
  if (!is_array($trx)) continue;

  // tanggal ambil dari items[0].tanggal
  $tanggal = $trx['tanggal'] ?? null;
  if ((!is_string($tanggal) || trim($tanggal)==='') && isset($trx['items'][0]['tanggal'])) {
    $tanggal = $trx['items'][0]['tanggal'];
  }
  if (!is_string($tanggal) || trim($tanggal)==='') $tanggal = '01-01-1970';
  $trx['tanggal'] = $tanggal;

  // trx_id
  if (!isset($trx['trx_id']) || trim((string)$trx['trx_id'])==='') {
    $yyyymmdd = yyyymmddFromDdMmYyyy($tanggal);
    $counterPerTanggal[$yyyymmdd] = ($counterPerTanggal[$yyyymmdd] ?? 0) + 1;
    $seq = str_pad((string)$counterPerTanggal[$yyyymmdd], 4, '0', STR_PAD_LEFT);
    $trx['trx_id'] = "TRX-$yyyymmdd-$seq";
  }

  // default jam/method/kasir untuk data lama
  if (!isset($trx['jam']) || trim((string)$trx['jam'])==='') $trx['jam'] = '00:00:00';
  if (!isset($trx['method_bayar']) || trim((string)$trx['method_bayar'])==='') $trx['method_bayar'] = 'cash';
  if (!isset($trx['kasir']) || !is_array($trx['kasir'])) $trx['kasir'] = ['id'=>'U0000','username'=>'unknown'];

  // kategori per item
  if (isset($trx['items']) && is_array($trx['items'])) {
    foreach ($trx['items'] as $i => $item) {
      if (!is_array($item)) continue;
      if (!isset($item['kategori']) || $item['kategori']==='' || $item['kategori']===null) {
        $trx['items'][$i]['kategori'] = getKategoriProduk($produk, $item['produk_id'] ?? '');
      }
    }
  }

  $checkout['items'][$idx] = $trx;
}

file_put_contents($checkoutFile, json_encode($checkout, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "OK checkout.json migrated. Backup: $backup\n";