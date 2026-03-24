<?php
require_once __DIR__ . '/lib/auth.php';
require_login();
require_role('admin');
date_default_timezone_set('Asia/Jakarta');

$active = 'dashboard';

require_once __DIR__ . '/lib/json.php';

$produk = read_json_file(__DIR__ . '/data/produk.json', []);
$checkout = read_json_file(__DIR__ . '/data/checkout.json', ['items'=>[]]);
$trxList = is_array($checkout['items'] ?? null) ? $checkout['items'] : [];

function rupiah($n) { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }

// helpers date convert
function ddmmyyyy_to_ymd(string $dmy): string {
  if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $dmy)) return '';
  [$d,$m,$y] = explode('-', $dmy);
  return $y.'-'.$m.'-'.$d;
}
function ymd_to_ddmmyyyy(string $ymd): string {
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) return '';
  [$y,$m,$d] = explode('-', $ymd);
  return $d.'-'.$m.'-'.$y;
}

// ===== Range filter (YYYY-MM-DD) =====
$todayYmd = date('Y-m-d');
$fromYmd = $_GET['from'] ?? $todayYmd;
$toYmd   = $_GET['to'] ?? $todayYmd;

if (!is_string($fromYmd) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromYmd)) $fromYmd = $todayYmd;
if (!is_string($toYmd)   || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toYmd))   $toYmd = $todayYmd;
if ($fromYmd > $toYmd) { $tmp=$fromYmd; $fromYmd=$toYmd; $toYmd=$tmp; }

$fromDmy = ymd_to_ddmmyyyy($fromYmd);
$toDmy   = ymd_to_ddmmyyyy($toYmd);

$stokBatas = 2;

// ===== Flatten checkout ke 1 list rows penjualan =====
$salesRows = [];
foreach ($trxList as $node) {
  if (!is_array($node)) continue;

  // format item-based: { items: [ row,row,... ] }
  if (isset($node['items']) && is_array($node['items']) && isset($node['items'][0]) && is_array($node['items'][0]) && isset($node['items'][0]['produk_id'])) {
    $nodeDiskon = to_int($node['diskon'] ?? 0);
    $nodeTotal = to_int($node['total_pemasukan'] ?? 0);
    if ($nodeTotal <= 0) {
      foreach ($node['items'] as $r) {
        if (!is_array($r)) continue;
        $nodeTotal += to_int($r['harga_jual'] ?? 0) * to_int($r['jumlah'] ?? 0);
      }
    }
    $sisaDiskon = $nodeDiskon;
    $itemCount = count($node['items']);
    $idx = 0;
    foreach ($node['items'] as $r) {
      if (!is_array($r)) continue;
      $idx++;
      if (isset($r['diskon_item'])) {
        $r['_diskon_item'] = to_int($r['diskon_item']);
      } elseif ($nodeDiskon > 0 && $nodeTotal > 0) {
        $qty = to_int($r['jumlah'] ?? 0);
        if ($qty < 1) $qty = 1;
        $harga = to_int($r['harga_jual'] ?? 0);
        $subtotal = $harga * $qty;
        if ($idx === $itemCount) {
          $r['_diskon_item'] = $sisaDiskon;
        } else {
          $di = (int)round($nodeDiskon * $subtotal / $nodeTotal);
          $r['_diskon_item'] = $di;
          $sisaDiskon -= $di;
        }
      } else {
        $r['_diskon_item'] = 0;
      }
      $salesRows[] = $r;
    }
    continue;
  }

  // format transaksi: {tanggal, items:[...]}
  if (isset($node['items']) && is_array($node['items'])) {
    $trxTanggal = (string)($node['tanggal'] ?? ($node['items'][0]['tanggal'] ?? ''));
    $nodeDiskon = to_int($node['diskon'] ?? 0);
    $nodeTotal = to_int($node['total_pemasukan'] ?? 0);
    if ($nodeTotal <= 0) {
      foreach ($node['items'] as $it) {
        if (!is_array($it)) continue;
        $nodeTotal += to_int($it['harga_jual'] ?? 0) * to_int($it['jumlah'] ?? 0);
      }
    }
    $sisaDiskon = $nodeDiskon;
    $itemCount = count($node['items']);
    $idx = 0;
    foreach ($node['items'] as $it) {
      if (!is_array($it)) continue;
      $idx++;
      $it['tanggal'] = (string)($it['tanggal'] ?? $trxTanggal);
      $it['waktu'] = (string)($it['waktu'] ?? ($node['jam'] ?? '00:00:00'));
      if (!isset($it['kasir'])) $it['kasir'] = $node['kasir'] ?? 'unknown';
      if (!isset($it['method_bayar'])) $it['method_bayar'] = $node['method_bayar'] ?? 'cash';
      if (isset($it['diskon_item'])) {
        $it['_diskon_item'] = to_int($it['diskon_item']);
      } elseif ($nodeDiskon > 0 && $nodeTotal > 0) {
        $qty = to_int($it['jumlah'] ?? 0);
        if ($qty < 1) $qty = 1;
        $harga = to_int($it['harga_jual'] ?? 0);
        $subtotal = $harga * $qty;
        if ($idx === $itemCount) {
          $it['_diskon_item'] = $sisaDiskon;
        } else {
          $di = (int)round($nodeDiskon * $subtotal / $nodeTotal);
          $it['_diskon_item'] = $di;
          $sisaDiskon -= $di;
        }
      } else {
        $it['_diskon_item'] = 0;
      }
      $salesRows[] = $it;
    }
  }
}

// ===== Filter by date range =====
$filteredRows = [];
foreach ($salesRows as $r) {
  if (!is_array($r)) continue;
  $dmy = (string)($r['tanggal'] ?? '');
  $ymd = ddmmyyyy_to_ymd($dmy);
  if ($ymd === '') continue;

  if ($ymd < $fromYmd || $ymd > $toYmd) continue;
  $filteredRows[] = $r;
}

// ===== Ringkasan range =====
$omzet = 0;
$laba = 0;
$jumlahTrx = 0;     // estimasi
$itemTerjual = 0;

$totalHargaBeli = 0;
$totalHargaJual = 0;
$profit = 0;

$produkTerjual = []; // pid => ['nama'=>, 'qty'=>]
$trxKeys = [];       // estimasi trx grouping

foreach ($filteredRows as $it) {
  $pid = (string)($it['produk_id'] ?? '');
  $qty = to_int($it['jumlah'] ?? 0);
  $hargaJual = to_int($it['harga_jual'] ?? 0);
  $lpp = to_int($it['laba_per_produk'] ?? 0);

  $itemTerjual += $qty;

  $diskonItem = to_int($it['_diskon_item'] ?? ($it['diskon_item'] ?? 0));
  $lineJual = ($hargaJual * $qty) - $diskonItem;
  $totalHargaJual += $lineJual;

  $hargaModal = 0;
  if ($pid !== '' && isset($produk[$pid]) && is_array($produk[$pid])) {
    $hargaModal = to_int($produk[$pid]['harga_modal'] ?? 0);
  }
  $lineBeli = $hargaModal * $qty;
  $totalHargaBeli += $lineBeli;

  // laba: pakai laba_per_produk jika ada, else jual-modal, dikurangi diskon
  if ($lpp > 0) $laba += ($lpp * max(1, $qty)) - $diskonItem;
  else $laba += (($hargaJual - $hargaModal) * $qty) - $diskonItem;

  // top produk
  if ($pid !== '') {
    if (!isset($produkTerjual[$pid])) {
      $produkTerjual[$pid] = [
        'nama' => (string)($it['nama'] ?? ($produk[$pid]['nama'] ?? 'Unknown')),
        'qty' => 0
      ];
    }
    $produkTerjual[$pid]['qty'] += $qty;
  }

  // estimasi transaksi
  $tgl = (string)($it['tanggal'] ?? '');
  $waktu = (string)($it['waktu'] ?? '00:00:00');
  $kasir = (string)($it['kasir'] ?? 'unknown');
  $method = (string)($it['method_bayar'] ?? 'cash');
  $trxKeys[$tgl.'|'.$waktu.'|'.$kasir.'|'.$method] = true;
}

$jumlahTrx = count($trxKeys);

$omzet = $totalHargaJual;
$profit = $totalHargaJual - $totalHargaBeli;

// top 5
$topProduk = array_values($produkTerjual);
usort($topProduk, fn($x,$y) => ($y['qty'] ?? 0) <=> ($x['qty'] ?? 0));
$topProduk = array_slice($topProduk, 0, 5);

// stok menipis
$stokMenipis = [];
foreach ($produk as $pid => $p) {
  if (!is_array($p)) continue;
  $stok = to_int($p['stok'] ?? 0);
  if ($stok <= $stokBatas) {
    $stokMenipis[] = [
      'produk_id' => (string)$pid,
      'nama' => (string)($p['nama'] ?? ''),
      'kategori' => (string)($p['kategori'] ?? 'umum'),
      'stok' => $stok
    ];
  }
}
usort($stokMenipis, fn($a,$b) => ($a['stok'] ?? 0) <=> ($b['stok'] ?? 0));

// “Transaksi terakhir” (estimasi) di range: group & ambil 20 terbaru berdasarkan tanggal+jam
$groups = [];
foreach ($filteredRows as $it) {
  $dmy = (string)($it['tanggal'] ?? '');
  $ymd = ddmmyyyy_to_ymd($dmy);
  if ($ymd === '') continue;

  $waktu = (string)($it['waktu'] ?? '00:00:00');
  $kasir = (string)($it['kasir'] ?? 'unknown');
  $method = (string)($it['method_bayar'] ?? 'cash');
  $gkey = $ymd.'|'.$waktu.'|'.$kasir.'|'.$method;

  $qty = to_int($it['jumlah'] ?? 0);
  $hargaJual = to_int($it['harga_jual'] ?? 0);
  $pid = (string)($it['produk_id'] ?? '');
  $hargaModal = ($pid !== '' && isset($produk[$pid]) && is_array($produk[$pid])) ? to_int($produk[$pid]['harga_modal'] ?? 0) : 0;
  $lpp = to_int($it['laba_per_produk'] ?? 0);
  $diskonItem = to_int($it['_diskon_item'] ?? ($it['diskon_item'] ?? 0));

  if (!isset($groups[$gkey])) {
    $groups[$gkey] = [
      'ymd' => $ymd,
      'dmy' => $dmy,
      'waktu' => $waktu,
      'kasir' => $kasir,
      'method' => $method,
      'omzet' => 0,
      'modal' => 0,
      'profit' => 0,
      'qty' => 0
    ];
  }

  $groups[$gkey]['qty'] += $qty;
  $groups[$gkey]['omzet'] += ($hargaJual * $qty) - $diskonItem;
  $groups[$gkey]['modal'] += ($hargaModal * $qty);

  $lineProfit = ($lpp > 0) ? ($lpp * max(1,$qty)) - $diskonItem : (($hargaJual - $hargaModal) * $qty) - $diskonItem;
  $groups[$gkey]['profit'] += $lineProfit;
}

$trxTerakhir = array_values($groups);
usort($trxTerakhir, function($a, $b){
  $ka = $a['ymd'].' '.$a['waktu'];
  $kb = $b['ymd'].' '.$b['waktu'];
  return strcmp($kb, $ka); // desc
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />
<link as="style" href="https://fonts.googleapis.com/css2?display=swap&amp;family=Inter%3Awght%40400%3B500%3B700%3B900&amp;family=Noto+Sans%3Awght%40400%3B500%3B700%3B900" onload="this.rel='stylesheet'" rel="stylesheet" />
<title>Dashboard</title>
<link href="data:image/x-icon;base64," rel="icon" type="image/x-icon" />
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
  body { font-family: 'Inter', 'Noto Sans', sans-serif; }
  #sidebar { transition: transform 0.3s ease-in-out; }
</style>
</head>
<body class="bg-[var(--background-color)]">
<div class="relative min-h-screen lg:flex">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div id="overlay" class="fixed inset-0 bg-black/60 z-30 hidden lg:hidden"></div>

  <main class="flex-1 p-6 sm:p-8">
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center gap-4">
        <button id="menu-toggle" class="p-2 rounded-md text-[var(--text-secondary)] hover:bg-[var(--secondary-color)] lg:hidden">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
        <div>
          <h1 class="text-3xl font-bold text-[var(--text-primary)]">Dashboard</h1>
          <p class="text-[var(--text-secondary)]">Ringkasan penjualan</p>
        </div>
      </div>
    </div>

    <!-- Filter tanggal range -->
    <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
      <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
          <label class="block text-sm text-[var(--text-secondary)]">Dari</label>
          <input type="date" name="from" value="<?= htmlspecialchars($fromYmd) ?>"
                 class="mt-1 w-48 rounded border border-[var(--accent-color)] p-2" />
        </div>
        <div>
          <label class="block text-sm text-[var(--text-secondary)]">Sampai</label>
          <input type="date" name="to" value="<?= htmlspecialchars($toYmd) ?>"
                 class="mt-1 w-48 rounded border border-[var(--accent-color)] p-2" />
        </div>
        <button class="rounded bg-[var(--primary-color)] text-white px-4 py-2">Filter</button>
        <a href="dashboard.php" class="rounded border border-[var(--accent-color)] px-4 py-2 text-[var(--text-secondary)]">Reset</a>
        <div class="text-xs text-[var(--text-secondary)] w-full sm:w-auto sm:ml-auto">
          Diproses: <?= htmlspecialchars($fromDmy) ?> s/d <?= htmlspecialchars($toDmy) ?>
        </div>
      </form>
    </div>

    <!-- Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
      <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
        <div class="text-sm text-[var(--text-secondary)]">Omzet (Total Jual)</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= rupiah($omzet) ?></div>
        <div class="text-xs text-[var(--text-secondary)] mt-2">Range: <?= htmlspecialchars($fromDmy) ?> → <?= htmlspecialchars($toDmy) ?></div>
      </div>

      <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
        <div class="text-sm text-[var(--text-secondary)]">Total Harga Beli (Modal)</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= rupiah($totalHargaBeli) ?></div>
        <div class="text-xs text-[var(--text-secondary)] mt-2">Sum(harga_modal × qty)</div>
      </div>

      <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
        <div class="text-sm text-[var(--text-secondary)]">Profit</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= rupiah($profit) ?></div>
        <div class="text-xs text-[var(--text-secondary)] mt-2">Jual - Modal</div>
      </div>

      <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
        <div class="text-sm text-[var(--text-secondary)]">Item Terjual</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= (int)$itemTerjual ?></div>
        <div class="text-xs text-[var(--text-secondary)] mt-2">Qty total</div>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-4">
      <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
        <div class="text-sm text-[var(--text-secondary)]">Transaksi (estimasi)</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= (int)$jumlahTrx ?></div>
        <div class="text-xs text-[var(--text-secondary)] mt-2">Grouping tgl+waktu+kasir+metode</div>
      </div>

      <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
        <div class="text-sm text-[var(--text-secondary)]">Laba (estimasi)</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= rupiah($laba) ?></div>
        <div class="text-xs text-[var(--text-secondary)] mt-2">Dari laba_per_produk atau (jual-modal)</div>
      </div>

      <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
        <div class="text-sm text-[var(--text-secondary)]">Total Barang (qty)</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= (int)$itemTerjual ?></div>
        <div class="text-xs text-[var(--text-secondary)] mt-2">Jumlah barang terjual</div>
      </div>

      <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
        <div class="text-sm text-[var(--text-secondary)]">Total Modal (nilai)</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= rupiah($totalHargaBeli) ?></div>
        <div class="text-xs text-[var(--text-secondary)] mt-2">Sama dengan total harga beli</div>
      </div>
    </div>

    <!-- Top produk + Stok menipis -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-6">
      <div class="bg-white rounded-lg border border-[var(--accent-color)]">
        <div class="p-4 border-b border-[var(--accent-color)]">
          <h2 class="font-semibold text-[var(--text-primary)]">Top 5 Produk Terlaris (Range)</h2>
        </div>
        <div class="p-4">
          <?php if (count($topProduk) === 0): ?>
            <div class="text-sm text-[var(--text-secondary)]">Belum ada penjualan pada range ini.</div>
          <?php else: ?>
            <ul class="space-y-2">
              <?php foreach ($topProduk as $tp): ?>
                <li class="flex items-center justify-between border border-[var(--accent-color)] rounded p-3">
                  <div class="text-[var(--text-primary)] font-medium"><?= htmlspecialchars((string)($tp['nama'] ?? '')) ?></div>
                  <div class="text-[var(--text-secondary)] text-sm">Qty: <?= (int)($tp['qty'] ?? 0) ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-[var(--accent-color)]">
        <div class="p-4 border-b border-[var(--accent-color)] flex items-center justify-between">
          <h2 class="font-semibold text-[var(--text-primary)]">Stok Menipis (≤ <?= (int)$stokBatas ?>)</h2>
          <div class="text-sm text-[var(--text-secondary)]">Total: <?= count($stokMenipis) ?></div>
        </div>
        <div class="p-4">
          <?php if (count($stokMenipis) === 0): ?>
            <div class="text-sm text-[var(--text-secondary)]">Tidak ada stok menipis.</div>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-[var(--secondary-color)] text-[var(--text-secondary)]">
                  <tr>
                    <th class="text-left p-2">Produk</th>
                    <th class="text-left p-2">Kategori</th>
                    <th class="text-right p-2">Stok</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach (array_slice($stokMenipis, 0, 20) as $sm): ?>
                    <tr class="border-t border-[var(--accent-color)]">
                      <td class="p-2"><?= htmlspecialchars($sm['nama']) ?></td>
                      <td class="p-2"><?= htmlspecialchars($sm['kategori']) ?></td>
                      <td class="p-2 text-right font-semibold"><?= (int)$sm['stok'] ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Transaksi terakhir -->
    <div class="mt-6 bg-white rounded-lg border border-[var(--accent-color)]">
      <div class="p-4 border-b border-[var(--accent-color)] flex items-center justify-between">
        <h2 class="font-semibold text-[var(--text-primary)]">Transaksi Terakhir (Range)</h2>
        <div class="text-sm text-[var(--text-secondary)]">Menampilkan max 20</div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-[var(--secondary-color)] text-[var(--text-secondary)]">
            <tr>
              <th class="text-left p-3">Tanggal</th>
              <th class="text-left p-3">Waktu</th>
              <th class="text-left p-3">Kasir</th>
              <th class="text-left p-3">Metode</th>
              <th class="text-right p-3">Omzet</th>
              <th class="text-right p-3">Modal</th>
              <th class="text-right p-3">Profit</th>
              <th class="text-right p-3">Item</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $rows = array_slice($trxTerakhir, 0, 20);
              foreach ($rows as $trx):
            ?>
            <tr class="border-t border-[var(--accent-color)]">
              <td class="p-3"><?= htmlspecialchars((string)($trx['dmy'] ?? '')) ?></td>
              <td class="p-3"><?= htmlspecialchars((string)($trx['waktu'] ?? '00:00:00')) ?></td>
              <td class="p-3"><?= htmlspecialchars((string)($trx['kasir'] ?? 'unknown')) ?></td>
              <td class="p-3"><?= htmlspecialchars((string)($trx['method'] ?? 'cash')) ?></td>
              <td class="p-3 text-right"><?= rupiah(to_int($trx['omzet'] ?? 0)) ?></td>
              <td class="p-3 text-right"><?= rupiah(to_int($trx['modal'] ?? 0)) ?></td>
              <td class="p-3 text-right font-semibold"><?= rupiah(to_int($trx['profit'] ?? 0)) ?></td>
              <td class="p-3 text-right"><?= (int)to_int($trx['qty'] ?? 0) ?></td>
            </tr>
            <?php endforeach; ?>

            <?php if (count($rows) === 0): ?>
              <tr>
                <td class="p-4 text-[var(--text-secondary)]" colspan="8">Belum ada transaksi pada range ini.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<?php include __DIR__ . '/partials/sidebar_js.php'; ?>

</body>
</html>