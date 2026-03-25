<?php
require_once __DIR__ . '/lib/auth.php';
require_admin();
require_once __DIR__ . '/lib/json.php';

$active = 'products';

$produkFile = __DIR__ . '/data/produk.json';
$kategoriFile = __DIR__ . '/data/kategori.json';

$produk = read_json_file($produkFile, []);
if (!is_array($produk)) $produk = [];

$kategoriData = read_json_file($kategoriFile, ['items'=>['umum']]);
$kategoriList = $kategoriData['items'] ?? ['umum'];
if (!is_array($kategoriList) || count($kategoriList) === 0) $kategoriList = ['umum'];

$msg = $_GET['msg'] ?? '';
$msg = is_string($msg) ? $msg : '';
$err = $_GET['err'] ?? '';
$err = is_string($err) ? $err : '';

// Hitung total produk valid dan total stok
$totalProdukValid = 0;
$totalStokAll = 0;
foreach ($produk as $pid => $p) {
  if (!is_array($p)) continue;
  $totalProdukValid++;
  $totalStokAll += to_int($p['stok'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin - Produk</title>
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
    body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
  </style>
</head>
<body class="bg-[var(--background-color)]">
<div class="relative min-h-screen lg:flex">

  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div id="overlay" class="fixed inset-0 bg-black/60 z-30 hidden lg:hidden"></div>

  <!-- pb-40 supaya konten bawah tidak ketutup sticky save bar -->
  <main class="flex-1 p-4 sm:p-8 pb-40">
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
          <h1 class="text-2xl sm:text-3xl font-bold text-[var(--text-primary)]">Produk & Stok</h1>
          <p class="text-sm text-[var(--text-secondary)]">Edit banyak produk sekaligus, lalu simpan sekali</p>
        </div>
      </div>
    </div>

    <?php if ($msg !== ''): ?>
      <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err !== ''): ?>
      <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- Search + quick info -->
    <div class="bg-white rounded-2xl border border-[var(--accent-color)] p-4 shadow-sm">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <div class="lg:col-span-2">
          <label class="block text-xs text-[var(--text-secondary)]">Cari produk realtime</label>
          <input id="searchBox" class="mt-1 w-full rounded-xl border border-[var(--accent-color)] p-3 text-sm"
                 placeholder="Ketik nama / kategori / id...">
        </div>
        <div class="rounded-2xl border border-[var(--accent-color)] bg-[var(--secondary-color)] p-4">
          <div class="text-xs text-[var(--text-secondary)]">Total Produk</div>
          <div class="text-2xl font-extrabold text-[var(--text-primary)] mt-1"><?= (int)$totalProdukValid ?></div>
          <div class="text-xs text-[var(--text-secondary)] mt-2">Total Stok: <?= number_format($totalStokAll, 0, ',', '.') ?> pcs • Kategori: <?= count($kategoriList) ?></div>
        </div>
      </div>
      <div class="text-xs text-[var(--text-secondary)] mt-2">
        Harga auto-format (10.000). Nilai yang tersimpan tetap angka.
      </div>
    </div>

    <!-- Category management -->
    <div class="mt-6 bg-white rounded-2xl border border-[var(--accent-color)] p-4 shadow-sm">
      <div class="font-semibold text-[var(--text-primary)]">Kelola Kategori</div>
      <div class="mt-1 text-xs text-[var(--text-secondary)]">Kategori dipakai untuk dropdown saat input produk.</div>

      <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <form method="POST" action="api/admin_category_add.php" class="rounded-2xl border border-[var(--accent-color)] p-3">
          <div class="text-sm font-semibold text-[var(--text-primary)]">Tambah kategori</div>
          <div class="mt-2 flex gap-2">
            <input name="nama" class="flex-1 rounded-xl border border-[var(--accent-color)] p-2 text-sm" placeholder="mis: pod">
            <button class="rounded-xl bg-[var(--primary-color)] text-white px-3 py-2 text-sm font-semibold hover:opacity-95">
              Tambah
            </button>
          </div>
        </form>

        <form method="POST" action="api/admin_category_rename.php" class="rounded-2xl border border-[var(--accent-color)] p-3">
          <div class="text-sm font-semibold text-[var(--text-primary)]">Update nama kategori</div>
          <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2">
            <select name="from" class="rounded-xl border border-[var(--accent-color)] p-2 text-sm">
              <?php foreach ($kategoriList as $c): ?>
                <option value="<?= htmlspecialchars((string)$c) ?>"><?= htmlspecialchars((string)$c) ?></option>
              <?php endforeach; ?>
            </select>
            <input name="to" class="rounded-xl border border-[var(--accent-color)] p-2 text-sm" placeholder="nama baru">
            <button class="rounded-xl bg-amber-600 text-white px-3 py-2 text-sm font-semibold hover:bg-amber-700"
                    onclick="return confirm('Update kategori? Semua produk yang memakai kategori lama akan ikut berubah.');">
              Update
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Create product -->
    <div class="mt-6 bg-white rounded-2xl border border-[var(--accent-color)] p-4 shadow-sm">
      <div class="font-semibold text-[var(--text-primary)]">Tambah Produk</div>

      <form method="POST" action="api/admin_product_create.php" class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3" id="createForm">
        <div class="md:col-span-2">
          <label class="block text-xs text-[var(--text-secondary)]">Nama</label>
          <input name="nama" required class="mt-1 w-full rounded-xl border border-[var(--accent-color)] p-2 text-sm" placeholder="Liquid ...">
        </div>

        <div class="md:col-span-1">
          <label class="block text-xs text-[var(--text-secondary)]">Kategori</label>
          <select name="kategori" class="mt-1 w-full rounded-xl border border-[var(--accent-color)] p-2 text-sm">
            <?php foreach ($kategoriList as $c): ?>
              <option value="<?= htmlspecialchars((string)$c) ?>"><?= htmlspecialchars((string)$c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="md:col-span-1">
          <label class="block text-xs text-[var(--text-secondary)]">Harga Modal</label>
          <input class="mt-1 w-full rounded-xl border border-[var(--accent-color)] p-2 text-sm text-right price-view" inputmode="numeric" placeholder="0">
          <input type="hidden" name="harga_modal" class="price-hidden" value="0">
        </div>

        <div class="md:col-span-1">
          <label class="block text-xs text-[var(--text-secondary)]">Harga Jual</label>
          <input class="mt-1 w-full rounded-xl border border-[var(--accent-color)] p-2 text-sm text-right price-view" inputmode="numeric" placeholder="0">
          <input type="hidden" name="harga_jual" class="price-hidden" value="0">
        </div>

        <div class="md:col-span-1">
          <label class="block text-xs text-[var(--text-secondary)]">Stok</label>
          <input name="stok" type="number" min="0" value="0" class="mt-1 w-full rounded-xl border border-[var(--accent-color)] p-2 text-sm text-right">
        </div>

        <div class="md:col-span-6">
          <button class="w-full md:w-auto rounded-xl bg-[var(--primary-color)] text-white px-4 py-2 text-sm font-semibold hover:opacity-95">
            Simpan Produk Baru
          </button>
        </div>
      </form>
    </div>

    <!-- Bulk edit table -->
    <div class="mt-6 bg-white rounded-2xl border border-[var(--accent-color)] overflow-hidden shadow-sm">
      <div class="p-4 border-b border-[var(--accent-color)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <div class="font-semibold text-[var(--text-primary)]">Daftar Produk</div>
          <div class="text-xs text-[var(--text-secondary)]">Edit banyak produk → tombol Simpan ikut scroll (sticky)</div>
        </div>
        <div class="text-xs text-[var(--text-secondary)]">Tip: gunakan search di atas</div>
      </div>

      <form id="bulkForm" method="POST" action="api/admin_products_bulk_update.php">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-[var(--secondary-color)] text-[var(--text-secondary)]">
              <tr>
                <th class="text-left p-3">ID</th>
                <th class="text-left p-3">Nama</th>
                <th class="text-left p-3">Kategori</th>
                <th class="text-right p-3">Modal</th>
                <th class="text-right p-3">Jual</th>
                <th class="text-right p-3">Stok</th>
                <th class="text-right p-3">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($produk as $pid => $p): ?>
                <?php
                  if (!is_array($p)) continue;
                  $nama = (string)($p['nama'] ?? '');
                  $kategori = (string)($p['kategori'] ?? 'umum');
                  $modal = to_int($p['harga_modal'] ?? 0);
                  $jual  = to_int($p['harga_jual'] ?? 0);
                  $stok  = to_int($p['stok'] ?? 0);
                ?>
                <tr class="border-t border-[var(--accent-color)] productRow hover:bg-sky-50/40"
                    data-id="<?= htmlspecialchars(mb_strtolower((string)$pid)) ?>"
                    data-nama="<?= htmlspecialchars(mb_strtolower($nama)) ?>"
                    data-kategori="<?= htmlspecialchars(mb_strtolower($kategori)) ?>">
                  <td class="p-3 font-mono text-xs text-slate-600 whitespace-nowrap"><?= htmlspecialchars((string)$pid) ?></td>

                  <td class="p-3 min-w-[240px]">
                    <input name="nama[<?= htmlspecialchars((string)$pid) ?>]" value="<?= htmlspecialchars($nama) ?>"
                           class="w-full rounded-xl border border-[var(--accent-color)] p-2 text-sm bg-white">
                  </td>

                  <td class="p-3 min-w-[160px]">
                    <select name="kategori[<?= htmlspecialchars((string)$pid) ?>]" class="w-full rounded-xl border border-[var(--accent-color)] p-2 text-sm bg-white">
                      <?php foreach ($kategoriList as $c): ?>
                        <option value="<?= htmlspecialchars((string)$c) ?>" <?= (mb_strtolower((string)$c)===mb_strtolower($kategori)) ? 'selected' : '' ?>>
                          <?= htmlspecialchars((string)$c) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>

                  <td class="p-3 text-right min-w-[140px]">
                    <input class="w-full rounded-xl border border-[var(--accent-color)] p-2 text-sm text-right price-view bg-white"
                           inputmode="numeric" value="<?= htmlspecialchars(number_format($modal, 0, ',', '.')) ?>">
                    <input type="hidden" name="harga_modal[<?= htmlspecialchars((string)$pid) ?>]" class="price-hidden" value="<?= (int)$modal ?>">
                  </td>

                  <td class="p-3 text-right min-w-[140px]">
                    <input class="w-full rounded-xl border border-[var(--accent-color)] p-2 text-sm text-right price-view bg-white"
                           inputmode="numeric" value="<?= htmlspecialchars(number_format($jual, 0, ',', '.')) ?>">
                    <input type="hidden" name="harga_jual[<?= htmlspecialchars((string)$pid) ?>]" class="price-hidden" value="<?= (int)$jual ?>">
                  </td>

                  <td class="p-3 text-right min-w-[120px]">
                    <input name="stok[<?= htmlspecialchars((string)$pid) ?>]" type="number" min="0" value="<?= (int)$stok ?>"
                           class="w-full rounded-xl border border-[var(--accent-color)] p-2 text-sm text-right bg-white">
                  </td>

                  <td class="p-3 text-right">
                    <div class="flex justify-end">
                      <!-- Hapus tetap per-item -->
                      <form method="POST" action="api/admin_product_delete.php" class="inline">
                        <input type="hidden" name="produk_id" value="<?= htmlspecialchars((string)$pid) ?>">
                        <button class="rounded-xl bg-red-600 text-white px-3 py-2 text-xs font-semibold hover:bg-red-700"
                                onclick="return confirm('Hapus produk ini? Jika masih dipakai di cart/keranjang akan ditolak.');">
                          Hapus
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div id="noResult" class="hidden p-4 text-sm text-[var(--text-secondary)]">Produk tidak ditemukan.</div>
      </form>
    </div>

    <!-- Sticky Save Bar -->
    <div id="saveBar" class="fixed bottom-4 left-4 right-4 lg:left-[calc(16rem+2rem)] lg:right-8 z-40">
      <div class="max-w-[1200px] mx-auto rounded-2xl border border-[var(--accent-color)] bg-white/95 backdrop-blur shadow-lg px-4 py-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="min-w-0">
            <div class="text-sm font-semibold text-[var(--text-primary)]">Simpan cepat</div>
            <div id="saveHint" class="text-xs text-[var(--text-secondary)] truncate">
              Edit produk lalu klik “Simpan Semua Perubahan”.
            </div>
          </div>

          <div class="flex gap-2">
            <button type="button" id="scrollTopBtn"
                    class="rounded-xl px-4 py-2 text-sm font-semibold border border-[var(--accent-color)] bg-white hover:bg-[var(--secondary-color)]">
              Ke Atas
            </button>

            <button form="bulkForm" id="saveAllBtn"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-sm
                           bg-gradient-to-r from-indigo-500 to-violet-600 hover:from-indigo-600 hover:to-violet-700"
                    onclick="return confirm('Simpan semua perubahan produk?');">
              Simpan Semua Perubahan
            </button>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<?php include __DIR__ . '/partials/sidebar_js.php'; ?>

<script>
(() => {
  // search realtime rows
  const searchBox = document.getElementById('searchBox');
  const rows = Array.from(document.querySelectorAll('.productRow'));
  const noResult = document.getElementById('noResult');

  function filter() {
    const q = (searchBox.value || '').trim().toLowerCase();
    let shown = 0;
    for (const r of rows) {
      const id = r.getAttribute('data-id') || '';
      const nama = r.getAttribute('data-nama') || '';
      const kat  = r.getAttribute('data-kategori') || '';
      const ok = q === '' || id.includes(q) || nama.includes(q) || kat.includes(q);
      r.style.display = ok ? '' : 'none';
      if (ok) shown++;
    }
    noResult.classList.toggle('hidden', shown !== 0);
  }
  searchBox?.addEventListener('input', filter);
  filter();

  // autoformat price inputs: .price-view -> sibling .price-hidden (dalam cell yang sama)
  function digitsOnly(s){ return (s || '').toString().replace(/[^\d]/g, ''); }
  function toInt(v){ v = parseInt(v, 10); return isNaN(v) ? 0 : v; }
  function formatIDR(n){ n = toInt(n); return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

  function bindPair(viewEl, hiddenEl){
    const sync = () => {
      const raw = digitsOnly(viewEl.value);
      const n = raw === '' ? 0 : toInt(raw);
      hiddenEl.value = n;
      viewEl.value = (raw === '' ? '' : formatIDR(n));
    };

    viewEl.addEventListener('input', () => {
      const pos = viewEl.selectionStart || 0;
      const before = viewEl.value.length;
      sync();
      const after = viewEl.value.length;
      const diff = after - before;
      viewEl.setSelectionRange(pos + diff, pos + diff);
    });
    viewEl.addEventListener('blur', sync);
    sync();
  }

  document.querySelectorAll('.price-view').forEach(view => {
    const hidden = view.parentElement?.querySelector('.price-hidden');
    if (hidden) bindPair(view, hidden);
  });

  // Sticky bar: detect dirty + scroll to top
  const saveHint = document.getElementById('saveHint');
  const saveAllBtn = document.getElementById('saveAllBtn');
  const scrollTopBtn = document.getElementById('scrollTopBtn');

  let dirty = false;
  function setDirty() {
    if (dirty) return;
    dirty = true;
    saveHint.textContent = 'Perubahan belum disimpan.';
    // jadi hijau saat ada perubahan
    saveAllBtn.classList.remove('from-indigo-500','to-violet-600');
    saveAllBtn.classList.add('from-emerald-500','to-green-600');
  }

  document.getElementById('bulkForm')?.addEventListener('input', (e) => {
    const t = e.target;
    if (!t) return;
    if (t.tagName === 'INPUT' || t.tagName === 'SELECT' || t.tagName === 'TEXTAREA') setDirty();
  });

  scrollTopBtn?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // UX: disable save saat submit
  document.getElementById('bulkForm')?.addEventListener('submit', () => {
    saveAllBtn.disabled = true;
    saveAllBtn.classList.add('opacity-70');
    saveHint.textContent = 'Menyimpan...';
  });
})();
</script>
</body>
</html>