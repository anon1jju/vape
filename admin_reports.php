<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/lib/auth.php';
require_admin();
require_once __DIR__ . '/lib/json.php';

$active = 'reports';

$checkoutFile = __DIR__ . '/data/checkout.json';
$raw = read_json_file($checkoutFile, ['items'=>[]]);

// Struktur kamu: { items: [ { items: [ ...rows... ] }, ... ] }
$rows = [];
if (is_array($raw) && isset($raw['items']) && is_array($raw['items'])) {
  foreach ($raw['items'] as $block) {
    if (is_array($block) && isset($block['items']) && is_array($block['items'])) {
      $blockDiskon = to_int($block['diskon'] ?? 0);
      $blockTotal = to_int($block['total_pemasukan'] ?? 0);

      // Hitung total_pemasukan dari items jika belum ada di block
      if ($blockTotal <= 0) {
        foreach ($block['items'] as $r) {
          if (!is_array($r)) continue;
          $blockTotal += to_int($r['harga_jual'] ?? 0) * to_int($r['jumlah'] ?? 0);
        }
      }

      $sisaDiskon = $blockDiskon;
      $itemCount = count($block['items']);
      $idx = 0;

      foreach ($block['items'] as $r) {
        if (!is_array($r)) continue;
        $idx++;

        // Jika item sudah punya diskon_item (data baru), pakai itu
        if (isset($r['diskon_item'])) {
          $r['_diskon_item'] = to_int($r['diskon_item']);
        } elseif ($blockDiskon > 0 && $blockTotal > 0) {
          // Distribusi proporsional untuk data lama
          $qty = to_int($r['jumlah'] ?? 0);
          if ($qty < 1) $qty = 1;
          $harga = to_int($r['harga_jual'] ?? 0);
          $subtotal = $harga * $qty;
          if ($idx === $itemCount) {
            $r['_diskon_item'] = $sisaDiskon;
          } else {
            $di = (int)round($blockDiskon * $subtotal / $blockTotal);
            $r['_diskon_item'] = $di;
            $sisaDiskon -= $di;
          }
        } else {
          $r['_diskon_item'] = 0;
        }

        $rows[] = $r;
      }
    }
  }
}

function rupiah($n) { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }

// helper date convert
function ymd_to_dmy(string $ymd): string {
  // 2026-03-20 -> 20-03-2026
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) return '';
  [$y,$m,$d] = explode('-', $ymd);
  return $d.'-'.$m.'-'.$y;
}
function dmy_to_ymd(string $dmy): string {
  // 20-03-2026 -> 2026-03-20
  if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $dmy)) return '';
  [$d,$m,$y] = explode('-', $dmy);
  return $y.'-'.$m.'-'.$d;
}

// --- filter input (UI pakai yyyy-mm-dd) ---
$todayYmd = date('Y-m-d');
$fromYmd = $_GET['from'] ?? $todayYmd;
$toYmd   = $_GET['to'] ?? $todayYmd;
$kasirFilter = trim((string)($_GET['kasir'] ?? ''));
$qFilter = trim((string)($_GET['q'] ?? ''));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$fromYmd)) $fromYmd = $todayYmd;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$toYmd)) $toYmd = $todayYmd;
if ($fromYmd > $toYmd) { $tmp=$fromYmd; $fromYmd=$toYmd; $toYmd=$tmp; }

$fromDmy = ymd_to_dmy($fromYmd);
$toDmy   = ymd_to_dmy($toYmd);

// kasir options
$kasirSet = [];
foreach ($rows as $r) {
  $k = (string)($r['kasir'] ?? '');
  if ($k !== '') $kasirSet[$k] = true;
}
$kasirOptions = array_keys($kasirSet);
sort($kasirOptions);

$needle = $qFilter !== '' ? mb_strtolower($qFilter) : '';

// filter rows by date + kasir + query
$filtered = [];
foreach ($rows as $r) {
  $tglDmy = (string)($r['tanggal'] ?? '');
  $tglYmd = dmy_to_ymd($tglDmy);
  if ($tglYmd === '') continue;

  if ($tglYmd < $fromYmd || $tglYmd > $toYmd) continue;

  if ($kasirFilter !== '' && (string)($r['kasir'] ?? '') !== $kasirFilter) continue;

  if ($needle !== '') {
    $hay = mb_strtolower(
      (string)($r['nama'] ?? '') . ' ' .
      (string)($r['produk_id'] ?? '') . ' ' .
      (string)($r['kategori'] ?? '') . ' ' .
      (string)($r['kasir'] ?? '') . ' ' .
      (string)($r['method_bayar'] ?? '')
    );
    if (strpos($hay, $needle) === false) continue;
  }

  $filtered[] = $r;
}

// sort newest first (by tanggal, waktu)
usort($filtered, function($a, $b){
  $ta = dmy_to_ymd((string)($a['tanggal'] ?? ''));
  $tb = dmy_to_ymd((string)($b['tanggal'] ?? ''));
  if ($ta !== $tb) return $tb <=> $ta;
  $wa = (string)($a['waktu'] ?? '');
  $wb = (string)($b['waktu'] ?? '');
  return $wb <=> $wa;
});

// totals
$totalLines = count($filtered);
$totalQty = 0;
$sumOmzet = 0;
$sumLaba = 0;

$methodCash = 0; $methodQris = 0; $methodPartial = 0; $methodOther = 0;

foreach ($filtered as $r) {
  $qty = to_int($r['jumlah'] ?? 0);
  $harga = to_int($r['harga_jual'] ?? 0);
  $labaPer = to_int($r['laba_per_produk'] ?? 0);

  $totalQty += $qty;
  $sumOmzet += ($harga * $qty);

  // asumsi laba_per_produk = per pcs (lebih aman untuk qty>1)
  $diskonItem = to_int($r['_diskon_item'] ?? ($r['diskon_item'] ?? 0));
  $labaLine = ($labaPer * max(1, $qty)) - $diskonItem;
  if ($labaLine < 0) $labaLine = 0;
  $sumLaba += $labaLine;

  $m = strtolower((string)($r['method_bayar'] ?? ''));
  if ($m === 'cash') $methodCash++;
  elseif ($m === 'qris') $methodQris++;
  elseif ($m === 'partial' || $m === 'mixed') $methodPartial++;
  else $methodOther++;
}

// grouping top products (optional quick view)
$top = [];
foreach ($filtered as $r) {
  $name = (string)($r['nama'] ?? '');
  $qty = to_int($r['jumlah'] ?? 0);
  $harga = to_int($r['harga_jual'] ?? 0);
  if ($name === '') continue;
  if (!isset($top[$name])) $top[$name] = ['qty'=>0,'omzet'=>0];
  $top[$name]['qty'] += $qty;
  $top[$name]['omzet'] += $harga * $qty;
}
uasort($top, fn($a,$b) => ($b['qty'] <=> $a['qty']));
$top5 = array_slice($top, 0, 5, true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />
<link as="style" href="https://fonts.googleapis.com/css2?display=swap&amp;family=Inter%3Awght%40400%3B500%3B700%3B900&amp;family=Noto+Sans%3Awght%40400%3B500%3B700%3B900" onload="this.rel='stylesheet'" rel="stylesheet" />
  <title>Laporan</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <style type="text/tailwindcss">
  :root {
    --primary-color: #0d7ff2;
    --secondary-color: #f0f8ff;
    --background-color: #f7fafc;
    --text-primary: #1a202c;
    --text-secondary: #4a5568;
    --accent-color: #e2e8f0;
  }
  body {
    font-family: 'Inter', 'Noto Sans', sans-serif;
  }
  /* For smooth sidebar transition */
  #sidebar {
    transition: transform 0.3s ease-in-out;
  }
</style>
</head>
<body class="bg-[var(--background-color)]">
<div class="relative min-h-screen lg:flex">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div id="overlay" class="fixed inset-0 bg-black/60 z-30 hidden lg:hidden"></div>

  <main class="flex-1 p-4 sm:p-8">
    <div class="flex items-center justify-between gap-3 mb-6">
      <div class="flex items-center gap-4">
        <button id="menu-toggle" class="p-2 rounded-md text-[var(--text-secondary)] hover:bg-[var(--secondary-color)] lg:hidden">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-[var(--text-primary)]">Laporan Penjualan</h1>
          <p class="text-sm text-[var(--text-secondary)]">Format data: baris per produk terjual (dd-mm-yyyy)</p>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-xl border border-[var(--accent-color)] p-4">
      <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <div>
          <label class="block text-xs text-[var(--text-secondary)]">Dari</label>
          <input type="date" name="from" value="<?= htmlspecialchars($fromYmd) ?>" class="mt-1 w-full rounded-lg border border-[var(--accent-color)] p-2 text-sm">
        </div>
        <div>
          <label class="block text-xs text-[var(--text-secondary)]">Sampai</label>
          <input type="date" name="to" value="<?= htmlspecialchars($toYmd) ?>" class="mt-1 w-full rounded-lg border border-[var(--accent-color)] p-2 text-sm">
        </div>
        <div>
          <label class="block text-xs text-[var(--text-secondary)]">Kasir</label>
          <select name="kasir" class="mt-1 w-full rounded-lg border border-[var(--accent-color)] p-2 text-sm">
            <option value="">Semua</option>
            <?php foreach ($kasirOptions as $k): ?>
              <option value="<?= htmlspecialchars($k) ?>" <?= $kasirFilter===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs text-[var(--text-secondary)]">Cari (nama/kategori/kasir/metode)</label>
          <input name="q" value="<?= htmlspecialchars($qFilter) ?>" class="mt-1 w-full rounded-lg border border-[var(--accent-color)] p-2 text-sm" placeholder="contoh: oxva / liquid / cash ...">
        </div>

        <div class="md:col-span-5 flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
          <div class="text-xs text-[var(--text-secondary)]">
            Menampilkan <b><?= (int)$totalLines ?></b> baris penjualan •
            Total qty <b><?= (int)$totalQty ?></b> •
            Range <?= htmlspecialchars($fromDmy) ?> s/d <?= htmlspecialchars($toDmy) ?>
          </div>
          <div class="flex gap-2">
            <a class="rounded-lg border border-[var(--accent-color)] bg-white px-3 py-2 text-sm hover:bg-[var(--secondary-color)]"
               href="admin_reports.php?from=<?= htmlspecialchars($todayYmd) ?>&to=<?= htmlspecialchars($todayYmd) ?>">
              Hari ini
            </a>
            <button class="rounded-lg bg-[var(--primary-color)] text-white px-4 py-2 text-sm font-semibold hover:opacity-95">Terapkan</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Summary -->
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl border border-[var(--accent-color)] p-4">
        <div class="text-xs text-[var(--text-secondary)]">Baris penjualan</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= (int)$totalLines ?></div>
      </div>

      <div class="bg-white rounded-xl border border-[var(--accent-color)] p-4">
        <div class="text-xs text-[var(--text-secondary)]">Total qty</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= (int)$totalQty ?></div>
      </div>

      <div class="bg-white rounded-xl border border-[var(--accent-color)] p-4">
        <div class="text-xs text-[var(--text-secondary)]">Omzet</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= rupiah($sumOmzet) ?></div>
      </div>

      <div class="bg-white rounded-xl border border-[var(--accent-color)] p-4">
        <div class="text-xs text-[var(--text-secondary)]">Laba (estimasi)</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= rupiah($sumLaba) ?></div>
        <div class="text-[11px] text-[var(--text-secondary)] mt-2">Asumsi: laba_per_produk = per pcs</div>
      </div>
    </div>

    <!-- Method + top products -->
    <div class="mt-4 grid grid-cols-1 xl:grid-cols-2 gap-4">
      <div class="bg-white rounded-xl border border-[var(--accent-color)] p-4">
        <div class="font-semibold text-[var(--text-primary)]">Jumlah baris per metode bayar</div>
        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div class="rounded-lg border border-[var(--accent-color)] p-3">
            <div class="text-xs text-[var(--text-secondary)]">Cash</div>
            <div class="text-lg font-bold"><?= (int)$methodCash ?></div>
          </div>
          <div class="rounded-lg border border-[var(--accent-color)] p-3">
            <div class="text-xs text-[var(--text-secondary)]">QRIS</div>
            <div class="text-lg font-bold"><?= (int)$methodQris ?></div>
          </div>
          <div class="rounded-lg border border-[var(--accent-color)] p-3">
            <div class="text-xs text-[var(--text-secondary)]">Partial</div>
            <div class="text-lg font-bold"><?= (int)$methodPartial ?></div>
          </div>
          <div class="rounded-lg border border-[var(--accent-color)] p-3">
            <div class="text-xs text-[var(--text-secondary)]">Other</div>
            <div class="text-lg font-bold"><?= (int)$methodOther ?></div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-[var(--accent-color)] p-4">
        <div class="font-semibold text-[var(--text-primary)]">Top 5 produk (qty)</div>
        <div class="mt-3 space-y-2">
          <?php if (count($top5) === 0): ?>
            <div class="text-sm text-[var(--text-secondary)]">Belum ada data.</div>
          <?php else: ?>
            <?php foreach ($top5 as $name => $v): ?>
              <div class="flex items-center justify-between rounded-lg border border-[var(--accent-color)] p-3">
                <div class="min-w-0">
                  <div class="font-semibold truncate"><?= htmlspecialchars($name) ?></div>
                  <div class="text-xs text-[var(--text-secondary)]">Omzet: <?= rupiah($v['omzet']) ?></div>
                </div>
                <div class="text-right">
                  <div class="text-xs text-[var(--text-secondary)]">Qty</div>
                  <div class="text-lg font-bold"><?= (int)$v['qty'] ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="mt-6 bg-white rounded-xl border border-[var(--accent-color)] overflow-hidden">
      <div class="p-4 border-b border-[var(--accent-color)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div class="font-semibold text-[var(--text-primary)]">Detail Penjualan (per produk)</div>
        <div class="text-xs text-[var(--text-secondary)]">Search realtime di tabel (client-side)</div>
      </div>

      <div class="p-4 border-b border-[var(--accent-color)]">
        <input id="tableSearch" class="w-full rounded-lg border border-[var(--accent-color)] p-2 text-sm"
               placeholder="Cari di tabel: nama / kategori / kasir / metode / tanggal ...">
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-[var(--secondary-color)] text-[var(--text-secondary)]">
            <tr>
              <th class="text-left p-3">Tanggal</th>
              <th class="text-left p-3">Produk</th>
              <th class="text-left p-3">Kategori</th>
              <th class="text-left p-3">Kasir</th>
              <th class="text-left p-3">Metode</th>
              <th class="text-right p-3">Harga</th>
              <th class="text-right p-3">Qty</th>
              <th class="text-right p-3">Subtotal</th>
              <th class="text-right p-3">Laba</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filtered as $r): ?>
              <?php
                $tgl = (string)($r['tanggal'] ?? '');
                $nama = (string)($r['nama'] ?? '');
                $kat = (string)($r['kategori'] ?? '');
                $kasir = (string)($r['kasir'] ?? '');
                $met = (string)($r['method_bayar'] ?? '');
                $harga = to_int($r['harga_jual'] ?? 0);
                $qty = to_int($r['jumlah'] ?? 0);
                $sub = $harga * $qty;
                $labaPer = to_int($r['laba_per_produk'] ?? 0);
                $diskonItem = to_int($r['_diskon_item'] ?? ($r['diskon_item'] ?? 0));
                $laba = ($labaPer * max(1, $qty)) - $diskonItem;
                if ($laba < 0) $laba = 0;

                $key = mb_strtolower("$tgl $nama $kat $kasir $met");
              ?>
              <tr class="border-t border-[var(--accent-color)] row" data-key="<?= htmlspecialchars($key) ?>">
                <td class="p-3 whitespace-nowrap"><?= htmlspecialchars($tgl) ?></td>
                <td class="p-3 font-semibold"><?= htmlspecialchars($nama) ?></td>
                <td class="p-3"><?= htmlspecialchars($kat) ?></td>
                <td class="p-3"><?= htmlspecialchars($kasir) ?></td>
                <td class="p-3"><?= htmlspecialchars($met) ?></td>
                <td class="p-3 text-right"><?= rupiah($harga) ?></td>
                <td class="p-3 text-right font-semibold"><?= (int)$qty ?></td>
                <td class="p-3 text-right font-semibold"><?= rupiah($sub) ?></td>
                <td class="p-3 text-right"><?= rupiah($laba) ?></td>
              </tr>
            <?php endforeach; ?>

            <?php if (count($filtered) === 0): ?>
              <tr>
                <td class="p-4 text-sm text-[var(--text-secondary)]" colspan="9">Tidak ada data untuk filter ini.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div id="noTableResult" class="hidden p-4 text-sm text-[var(--text-secondary)]">
        Tidak ada baris yang cocok di tabel.
      </div>
    </div>

  </main>
</div>

<?php include __DIR__ . '/partials/sidebar_js.php'; ?>

<script>
(() => {
  const box = document.getElementById('tableSearch');
  const rows = Array.from(document.querySelectorAll('tr.row'));
  const noRes = document.getElementById('noTableResult');

  function filter(){
    const q = (box.value || '').trim().toLowerCase();
    let shown = 0;
    for (const r of rows) {
      const key = r.getAttribute('data-key') || '';
      const ok = q === '' || key.includes(q);
      r.style.display = ok ? '' : 'none';
      if (ok) shown++;
    }
    noRes.classList.toggle('hidden', shown !== 0 || rows.length === 0);
  }

  box?.addEventListener('input', filter);
  filter();
})();
</script>
</body>
</html>