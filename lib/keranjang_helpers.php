<?php
require_once __DIR__ . '/json.php';

/**
 * Hitung ulang total transaksi berdasarkan items.
 * Diskon dipertahankan (grand_total = total_pemasukan - diskon).
 */
function recalc_trx(array $trx): array {
  $items = $trx['items'] ?? [];
  if (!is_array($items)) $items = [];

  $totalPemasukan = 0;
  $totalLaba = 0;

  foreach ($items as $it) {
    if (!is_array($it)) continue;
    $harga = to_int($it['harga_jual'] ?? 0);
    $qty = to_int($it['jumlah'] ?? 0);
    $lpp = to_int($it['laba_per_produk'] ?? 0);

    $totalPemasukan += $harga * $qty;
    $totalLaba += $lpp * $qty;
  }

  $diskon = to_int($trx['diskon'] ?? 0);
  if ($diskon < 0) $diskon = 0;
  if ($diskon > $totalPemasukan) $diskon = $totalPemasukan;

  $trx['total_pemasukan'] = $totalPemasukan;
  $trx['total_laba'] = $totalLaba;
  $trx['diskon'] = $diskon;
  $trx['grand_total'] = $totalPemasukan - $diskon;

  // validasi pembayaran jika ada
  if (isset($trx['pembayaran']) && is_array($trx['pembayaran'])) {
    $payCash = to_int($trx['pembayaran']['cash'] ?? 0);
    $payQris = to_int($trx['pembayaran']['qris'] ?? 0);
    $payTf   = to_int($trx['pembayaran']['transfer'] ?? 0);
    $paid = $payCash + $payQris + $payTf;

    // kalau payment diisi dan tidak cocok, kita biarkan (jangan auto ubah),
    // tapi bisa kamu cek manual di UI.
    $trx['paid_total'] = $paid;
  }

  return $trx;
}

/**
 * Apply perubahan stok untuk 1 produk_id.
 * $deltaQty:
 *   +1 artinya stok bertambah (rollback, karena item dihapus/dikurangi)
 *   -1 artinya stok berkurang (karena qty ditambah)
 */
function adjust_stock(array &$produkMap, string $produkId, int $deltaQty): void {
  if ($produkId === '') return;
  if (!isset($produkMap[$produkId]) || !is_array($produkMap[$produkId])) return;

  $stokNow = to_int($produkMap[$produkId]['stok'] ?? 0);
  $stokNew = $stokNow + $deltaQty;
  if ($stokNew < 0) $stokNew = 0; // safety

  // simpan dalam tipe int biar rapi
  $produkMap[$produkId]['stok'] = $stokNew;
}

/**
 * Rollback stok untuk seluruh items transaksi:
 * jika transaksi dihapus, stok harus kembali naik sebanyak qty item.
 */
function rollback_stock_for_trx(array &$produkMap, array $trx): void {
  $items = $trx['items'] ?? [];
  if (!is_array($items)) return;

  foreach ($items as $it) {
    if (!is_array($it)) continue;
    $pid = (string)($it['produk_id'] ?? '');
    $qty = to_int($it['jumlah'] ?? 0);
    if ($qty > 0) adjust_stock($produkMap, $pid, +$qty);
  }
}