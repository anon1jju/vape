<?php
require_once __DIR__ . '/../lib/json.php';

$keranjangFile = __DIR__ . '/../data/keranjang.json';
$keranjang = read_json_file($keranjangFile, ['items' => []]);
if (!isset($keranjang['items']) || !is_array($keranjang['items'])) $keranjang['items'] = [];

$newTanggal = trim((string)($_POST['tanggal'] ?? ''));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newTanggal)) {
  header('Location: ../keranjang.php?msg=' . urlencode('Format tanggal tidak valid.'));
  exit;
}

[$y, $m, $d] = explode('-', $newTanggal);
if (!checkdate((int)$m, (int)$d, (int)$y)) {
  header('Location: ../keranjang.php?msg=' . urlencode('Tanggal tidak valid.'));
  exit;
}

if (count($keranjang['items']) === 0) {
  header('Location: ../keranjang.php?msg=' . urlencode('Keranjang kosong.'));
  exit;
}

$tanggalDmy = $d . '-' . $m . '-' . $y;

foreach ($keranjang['items'] as $tIdx => $trx) {
  if (!is_array($trx)) continue;
  $keranjang['items'][$tIdx]['tanggal'] = $tanggalDmy;

  if (isset($trx['items']) && is_array($trx['items'])) {
    foreach ($trx['items'] as $iIdx => $it) {
      if (!is_array($it)) continue;
      $keranjang['items'][$tIdx]['items'][$iIdx]['tanggal'] = $tanggalDmy;
    }
  }
}

$ok = file_put_contents($keranjangFile, json_encode($keranjang, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
if ($ok === false) {
  header('Location: ../keranjang.php?msg=' . urlencode('Gagal menyimpan perubahan tanggal.'));
  exit;
}

header('Location: ../keranjang.php?msg=' . urlencode("Tanggal semua transaksi berhasil diubah ke $tanggalDmy."));
exit;