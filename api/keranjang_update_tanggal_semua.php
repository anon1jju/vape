<?php
require_once __DIR__ . '/../lib/json.php';

$keranjangFile = __DIR__ . '/../data/keranjang.json';
$keranjang = read_json_file($keranjangFile, ['items' => []]);
if (!isset($keranjang['items']) || !is_array($keranjang['items'])) $keranjang['items'] = [];

$newTanggal = trim((string)($_POST['tanggal'] ?? ''));

// Validasi format tanggal (YYYY-MM-DD dari input type=date)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newTanggal)) {
  header('Location: ../keranjang.php?msg=' . urlencode('Format tanggal tidak valid.'));
  exit;
}

// Validasi tanggal benar-benar valid (misal bukan 2024-02-30)
[$yv, $mv, $dv] = explode('-', $newTanggal);
if (!checkdate((int)$mv, (int)$dv, (int)$yv)) {
  header('Location: ../keranjang.php?msg=' . urlencode('Tanggal tidak valid.'));
  exit;
}

if (count($keranjang['items']) === 0) {
  header('Location: ../keranjang.php?msg=' . urlencode('Keranjang kosong.'));
  exit;
}

// Convert YYYY-MM-DD → dd-mm-YYYY (format yang dipakai di seluruh sistem)
[$y, $m, $d] = explode('-', $newTanggal);
$tanggalDmy = $d . '-' . $m . '-' . $y;

// Update tanggal di SEMUA transaksi
foreach ($keranjang['items'] as $tIdx => $trx) {
  if (!is_array($trx)) continue;

  // Update tanggal di level transaksi
  $keranjang['items'][$tIdx]['tanggal'] = $tanggalDmy;

  // Update juga tanggal di setiap item dalam transaksi (supaya konsisten di checkout.json & dashboard)
  if (isset($trx['items']) && is_array($trx['items'])) {
    foreach ($trx['items'] as $iIdx => $it) {
      if (!is_array($it)) continue;
      $keranjang['items'][$tIdx]['items'][$iIdx]['tanggal'] = $tanggalDmy;
    }
  }
}

$result = file_put_contents($keranjangFile, json_encode($keranjang, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

if ($result === false) {
  header('Location: ../keranjang.php?msg=' . urlencode('Gagal menyimpan perubahan. Silakan coba lagi.'));
  exit;
}

header('Location: ../keranjang.php?msg=' . urlencode("Tanggal semua transaksi berhasil diubah ke $tanggalDmy."));
exit;
