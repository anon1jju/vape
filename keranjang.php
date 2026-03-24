<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/lib/json.php';
require_once __DIR__ . '/lib/auth.php';
require_login();

$active = 'keranjang';

$keranjangFile = __DIR__ . '/data/keranjang.json';
$keranjang = read_json_file($keranjangFile, ['items'=>[]]);
$trxList = is_array($keranjang['items'] ?? null) ? $keranjang['items'] : [];

function rupiah($n) { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }

$flash = $_GET['msg'] ?? '';
$flash = is_string($flash) ? $flash : '';

// Ringkasan total keseluruhan
$totalTrx = count($trxList);
$sumOmzet = 0;
$sumDiskon = 0;
$sumGrand = 0;
$sumLaba = 0;
$payCashAll = 0; $payQrisAll = 0; $payTfAll = 0;

foreach ($trxList as $trx) {
  if (!is_array($trx)) continue;

  $sumOmzet  += to_int($trx['total_pemasukan'] ?? 0);
  $sumDiskon += to_int($trx['diskon'] ?? 0);
  $sumGrand  += to_int($trx['grand_total'] ?? ($trx['total_pemasukan'] ?? 0));
  $sumLaba   += to_int($trx['total_laba'] ?? 0);

  $p = $trx['pembayaran'] ?? [];
  if (is_array($p)) {
    $payCashAll += to_int($p['cash'] ?? 0);
    $payQrisAll += to_int($p['qris'] ?? 0);
    $payTfAll   += to_int($p['transfer'] ?? 0);
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />
<link as="style" href="https://fonts.googleapis.com/css2?display=swap&amp;family=Inter%3Awght%40400%3B500%3B700%3B900&amp;family=Noto+Sans%3Awght%40400%3B500%3B700%3B900" onload="this.rel='stylesheet'" rel="stylesheet" />
<title>Keranjang</title>
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
      <div class="flex items-center gap-4">
        <button id="menu-toggle" class="p-2 rounded-md text-[var(--text-secondary)] hover:bg-[var(--secondary-color)] lg:hidden">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-[var(--text-primary)]">Keranjang</h1>
          <p class="text-sm text-[var(--text-secondary)]">Edit/hapus transaksi sebelum “Tutup Hari”</p>
        </div>
      </div>

      <form method="POST" action="api/tutup_hari.php"
            onsubmit="return confirm('Tutup hari? Semua transaksi di keranjang akan dipindahkan ke checkout.json dan keranjang dikosongkan.');">
        <button class="w-full sm:w-auto rounded-lg bg-green-600 text-white px-4 py-2 text-sm font-semibold <?= $totalTrx===0 ? 'opacity-50 pointer-events-none' : '' ?>">
          Tutup Hari
        </button>
      </form>
    </div>

    <?php if ($flash !== ''): ?>
      <div class="mb-4 rounded-lg border border-[var(--accent-color)] bg-white p-3 text-sm">
        <?= htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
        <div class="text-sm text-[var(--text-secondary)]">Jumlah Transaksi</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= (int)$totalTrx ?></div>
      </div>

      <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
        <div class="text-sm text-[var(--text-secondary)]">Grand Total (Keseluruhan)</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= rupiah($sumGrand) ?></div>
        <div class="text-xs text-[var(--text-secondary)] mt-2">Diskon: <?= rupiah($sumDiskon) ?></div>
      </div>

      <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
        <div class="text-sm text-[var(--text-secondary)]">Laba (Keseluruhan)</div>
        <div class="text-2xl font-bold text-[var(--text-primary)] mt-1"><?= rupiah($sumLaba) ?></div>
      </div>

      <div class="bg-white rounded-lg border border-[var(--accent-color)] p-4">
        <div class="text-sm text-[var(--text-secondary)]">Total Pembayaran</div>
        <div class="text-xs text-[var(--text-secondary)] mt-2">Cash: <span class="font-semibold text-[var(--text-primary)]"><?= rupiah($payCashAll) ?></span></div>
        <div class="text-xs text-[var(--text-secondary)] mt-1">QRIS: <span class="font-semibold text-[var(--text-primary)]"><?= rupiah($payQrisAll) ?></span></div>
        <div class="text-xs text-[var(--text-secondary)] mt-1">Transfer: <span class="font-semibold text-[var(--text-primary)]"><?= rupiah($payTfAll) ?></span></div>
      </div>
    </div>

    <!-- Ubah Tanggal Semua Transaksi -->
    <?php if ($totalTrx > 0): ?>
    <div class="mt-4 bg-white rounded-lg border border-[var(--accent-color)] p-4">
      <form method="POST" action="api/keranjang_update_tanggal_semua.php" class="flex flex-col sm:flex-row sm:items-center gap-3"
            onsubmit="return confirm('Ubah tanggal SEMUA transaksi di keranjang?');">
        <div class="flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[var(--primary-color)]">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          <span class="text-sm font-semibold text-[var(--text-primary)]">Ubah Tanggal Semua Transaksi</span>
        </div>
        <div class="flex items-center gap-2">
          <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required
                 class="rounded border border-[var(--accent-color)] px-3 py-2 text-sm">
          <button class="rounded-lg bg-[var(--primary-color)] text-white px-4 py-2 text-sm font-semibold hover:opacity-90">
            Terapkan ke Semua
          </button>
        </div>
        <span class="text-xs text-[var(--text-secondary)]">Akan mengubah tanggal di <?= (int)$totalTrx ?> transaksi sekaligus</span>
      </form>
    </div>
    <?php endif; ?>

    <!-- Daftar transaksi -->
    <div class="mt-6 bg-white rounded-lg border border-[var(--accent-color)]">
      <div class="p-4 border-b border-[var(--accent-color)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div class="font-semibold text-[var(--text-primary)]">Daftar Transaksi</div>
        <div class="text-sm text-[var(--text-secondary)]">Ada warning jika pembayaran ≠ grand total</div>
      </div>

      <div class="p-2 sm:p-4 space-y-3">
        <?php if ($totalTrx === 0): ?>
          <div class="text-sm text-[var(--text-secondary)] p-2">Belum ada transaksi di keranjang.</div>
        <?php else: ?>
          <?php foreach ($trxList as $tIndex => $trx): ?>
            <?php
              if (!is_array($trx)) continue;

              $items = is_array($trx['items'] ?? null) ? $trx['items'] : [];
              $firstName = (string)(($items[0]['nama'] ?? '') ?: 'Transaksi');
              $extraCount = max(0, count($items) - 1);

              $trxId = (string)($trx['trx_id'] ?? ('TRX#'.$tIndex));
              $tanggal = (string)($trx['tanggal'] ?? '');
              $waktu = (string)($trx['waktu'] ?? '');
              $kasir = (string)($trx['kasir'] ?? '');
              $method = (string)($trx['method_bayar'] ?? '');
              $diskon = to_int($trx['diskon'] ?? 0);
              $grand  = to_int($trx['grand_total'] ?? ($trx['total_pemasukan'] ?? 0));

              $p = $trx['pembayaran'] ?? [];
              $payCash = is_array($p) ? to_int($p['cash'] ?? 0) : 0;
              $payQris = is_array($p) ? to_int($p['qris'] ?? 0) : 0;
              $payTf   = is_array($p) ? to_int($p['transfer'] ?? 0) : 0;

              $paid = $payCash + $payQris + $payTf;
              $mismatch = ($paid !== $grand);
              $diff = $paid - $grand; // >0 kelebihan, <0 kekurangan

              $qtyTotal = 0;
              foreach ($items as $it) $qtyTotal += to_int($it['jumlah'] ?? 0);

              $title = $firstName . ($extraCount > 0 ? " +$extraCount item" : "");
            ?>
            <details class="border border-[var(--accent-color)] rounded-lg overflow-hidden" <?= $tIndex === 0 ? 'open' : '' ?>>
              <summary class="cursor-pointer p-4 hover:bg-[var(--secondary-color)]">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                  <div class="min-w-0">
                    <div class="text-[var(--text-primary)] font-semibold truncate">
                      <?= htmlspecialchars($title) ?>
                    </div>

                    <div class="text-xs text-[var(--text-secondary)] mt-1">
                      <?= htmlspecialchars($trxId) ?> • <?= htmlspecialchars($tanggal) ?> <?= htmlspecialchars($waktu) ?>
                      • Kasir: <?= htmlspecialchars($kasir) ?>
                      • Metode: <?= htmlspecialchars($method) ?>
                      • Qty: <?= (int)$qtyTotal ?>
                    </div>

                    <div class="mt-2 text-xs text-[var(--text-secondary)]">
                      Pembayaran: Cash <?= rupiah($payCash) ?> • QRIS <?= rupiah($payQris) ?> • Transfer <?= rupiah($payTf) ?>
                      <?php if ($mismatch): ?>
                        <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold bg-red-100 text-red-700">
                          mismatch
                        </span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="flex flex-wrap items-center gap-3 justify-end">
                    <?php if ($mismatch): ?>
                      <div class="text-right">
                        <div class="text-xs text-red-600 font-semibold">Pembayaran tidak sesuai</div>
                        <div class="text-xs text-red-600">
                          <?= $diff < 0 ? 'Kurang ' . rupiah(abs($diff)) : 'Lebih ' . rupiah($diff) ?>
                        </div>
                      </div>

                      <form method="POST" action="api/keranjang_fix_pembayaran.php" onsubmit="return confirm('Auto-fix pembayaran jadi Cash = Grand Total?');">
                        <input type="hidden" name="trx_index" value="<?= (int)$tIndex ?>">
                        <button class="rounded-lg border border-red-200 bg-red-50 text-red-700 px-3 py-2 text-xs font-semibold hover:bg-red-100">
                          Auto Fix
                        </button>
                      </form>
                    <?php endif; ?>

                    <div class="text-right">
                      <div class="text-xs text-[var(--text-secondary)]">Diskon</div>
                      <div class="font-semibold"><?= rupiah($diskon) ?></div>
                    </div>

                    <div class="text-right">
                      <div class="text-xs text-[var(--text-secondary)]">Grand</div>
                      <div class="text-lg font-bold"><?= rupiah($grand) ?></div>
                    </div>

                    <form method="POST" action="api/keranjang_delete_trx.php" onsubmit="return confirm('Hapus transaksi ini? Stok akan dikembalikan.');">
                      <input type="hidden" name="trx_index" value="<?= (int)$tIndex ?>">
                      <button class="w-full sm:w-auto rounded-lg bg-red-600 text-white px-3 py-2 text-sm">Hapus</button>
                    </form>
                  </div>
                </div>
              </summary>

              <div class="p-4 border-t border-[var(--accent-color)] overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead class="bg-[var(--secondary-color)] text-[var(--text-secondary)]">
                    <tr>
                      <th class="text-left p-2">Produk</th>
                      <th class="text-left p-2">Kategori</th>
                      <th class="text-right p-2">Harga</th>
                      <th class="text-right p-2">Qty</th>
                      <th class="text-right p-2">Subtotal</th>
                      <th class="text-right p-2">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($items as $iIndex => $it): ?>
                      <?php
                        if (!is_array($it)) continue;
                        $nama = (string)($it['nama'] ?? '');
                        $kat  = (string)($it['kategori'] ?? 'umum');
                        $harga = to_int($it['harga_jual'] ?? 0);
                        $qty = to_int($it['jumlah'] ?? 0);
                        $sub = $harga * $qty;
                      ?>
                      <tr class="border-t border-[var(--accent-color)]">
                        <td class="p-2 font-medium text-[var(--text-primary)]"><?= htmlspecialchars($nama) ?></td>
                        <td class="p-2 text-[var(--text-secondary)]"><?= htmlspecialchars($kat) ?></td>
                        <td class="p-2 text-right"><?= rupiah($harga) ?></td>
                        <td class="p-2 text-right">
                          <form method="POST" action="api/keranjang_update_qty.php" class="inline-flex items-center gap-2 justify-end">
                            <input type="hidden" name="trx_index" value="<?= (int)$tIndex ?>">
                            <input type="hidden" name="item_index" value="<?= (int)$iIndex ?>">
                            <input type="number" name="jumlah" value="<?= (int)$qty ?>" min="1"
                                   class="w-20 rounded border border-[var(--accent-color)] p-1 text-right">
                            <button class="rounded border border-[var(--accent-color)] px-2 py-1 text-xs bg-white hover:bg-[var(--secondary-color)]">
                              Update
                            </button>
                          </form>
                        </td>
                        <td class="p-2 text-right font-semibold"><?= rupiah($sub) ?></td>
                        <td class="p-2 text-right">
                          <form method="POST" action="api/keranjang_remove_item.php" onsubmit="return confirm('Hapus item ini? Stok akan dikembalikan.');">
                            <input type="hidden" name="trx_index" value="<?= (int)$tIndex ?>">
                            <input type="hidden" name="item_index" value="<?= (int)$iIndex ?>">
                            <button class="rounded bg-red-600 text-white px-2 py-1 text-xs">Hapus</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>

                <div class="mt-3 text-xs text-[var(--text-secondary)]">
                  Qty/hapus item otomatis update total transaksi & stok produk.
                </div>
              </div>
            </details>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/partials/sidebar_js.php'; ?>
</body>
</html>