<?php
require_once __DIR__ . '/../lib/json.php';

$keranjangFile = __DIR__ . '/../data/keranjang.json';
$keranjang = read_json_file($keranjangFile, ['items' => []]);
if (!isset($keranjang['items']) || !is_array($keranjang['items'])) $keranjang['items'] = [];

$trxIndex = to_int($_POST['trx_index'] ?? -1);
$newTanggal = trim((string)($_POST['tanggal'] ?? ''));

if ($trxIndex < 0 || !isset($keranjang['items'][$trxIndex])) {
  header('Location: ../keranjang.php?msg=' . urlencode('Transaksi tidak ditemukan.'));
  exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newTanggal)) {
  header('Location: ../keranjang.php?msg=' . urlencode('Format tanggal tidak valid.'));
  exit;
}

[$y, $m, $d] = explode('-', $newTanggal);
if (!checkdate((int)$m, (int)$d, (int)$y)) {
  header('Location: ../keranjang.php?msg=' . urlencode('Tanggal tidak valid.'));
  exit;
}

$tanggalDmy = $d . '-' . $m . '-' . $y;

$keranjang['items'][$trxIndex]['tanggal'] = $tanggalDmy;

if (isset($keranjang['items'][$trxIndex]['items']) && is_array($keranjang['items'][$trxIndex]['items'])) {
  foreach ($keranjang['items'][$trxIndex]['items'] as $i => $it) {
    if (!is_array($it)) continue;
    $keranjang['items'][$trxIndex]['items'][$i]['tanggal'] = $tanggalDmy;
  }
}

$ok = file_put_contents($keranjangFile, json_encode($keranjang, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
if ($ok === false) {
  header('Location: ../keranjang.php?msg=' . urlencode('Gagal menyimpan perubahan tanggal.'));
  exit;
}

header('Location: ../keranjang.php?msg=' . urlencode("Tanggal transaksi berhasil diubah ke $tanggalDmy."));
exit;