<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/lib/json.php';
require_once __DIR__ . '/lib/auth.php';
require_login();

$u = auth_user();
$kasirName = (string)($u['username'] ?? 'unknown');

$produk = read_json_file(__DIR__ . '/data/produk.json', []);
$cart   = read_json_file(__DIR__ . '/data/cart.json', []);
if (!is_array($cart)) $cart = [];

function rupiah($n) { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }

$flash = $_GET['msg'] ?? '';
$flash = is_string($flash) ? $flash : '';

$cartTotal = 0;
$cartQty = 0;
foreach ($cart as $it) {
  if (!is_array($it)) continue;
  $harga = to_int($it['harga_jual'] ?? 0);
  $qty = to_int($it['jumlah'] ?? 0);
  $cartTotal += $harga * $qty;
  $cartQty += $qty;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />
<link as="style" href="https://fonts.googleapis.com/css2?display=swap&amp;family=Inter%3Awght%40400%3B500%3B700%3B900&amp;family=Noto+Sans%3Awght%40400%3B500%3B700%3B900" onload="this.rel='stylesheet'" rel="stylesheet" />
<title>Kasir</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<style type="text/tailwindcss">
  :root{
    --primary-color:#0d7ff2;
    --secondary-color:#f0f8ff;
    --background-color:#f7fafc;
    --text-primary:#1a202c;
    --text-secondary:#4a5568;
    --accent-color:#e2e8f0;
  }
  body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
</style>
</head>

<body class="bg-[var(--background-color)]">
  <div class="sticky top-0 z-10 bg-white border-b border-[var(--accent-color)]">
    <div class="max-w-7xl mx-auto p-4 flex items-center justify-between gap-3">
      <div class="min-w-0">
        <div class="text-xs text-[var(--text-secondary)]">
          Kasir: <span class="font-semibold text-[var(--text-primary)]"><?= htmlspecialchars($kasirName) ?></span>
          • Total: <span class="font-semibold text-[var(--text-primary)]"><?= rupiah($cartTotal) ?></span>
          • Qty: <span class="font-semibold text-[var(--text-primary)]"><?= (int)$cartQty ?></span>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <a href="keranjang.php" class="rounded-lg bg-[var(--primary-color)] text-white px-3 py-2 text-sm hover:opacity-95">Keranjang</a>
        <a href="dashboard.php" class="rounded-lg border border-[var(--accent-color)] bg-white px-3 py-2 text-sm hover:bg-[var(--secondary-color)]">Dashboard</a>
        <a href="logout.php" class="rounded-lg border border-[var(--accent-color)] bg-white px-3 py-2 text-sm hover:bg-[var(--secondary-color)]">Logout</a>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 pb-4">
      <input id="searchBox"
             placeholder="Cari produk realtime (nama / kategori)..."
             class="w-full rounded-lg border border-[var(--accent-color)] p-3 text-sm" />

      <?php if ($flash !== ''): ?>
        <div class="mt-3 rounded-lg border border-[var(--accent-color)] bg-[var(--secondary-color)] p-3 text-sm text-[var(--text-primary)]">
          <?= htmlspecialchars($flash) ?>
        </div>
      <?php endif; ?>

      <!--<div class="mt-2 text-xs text-[var(--text-secondary)]">
        Ketik untuk menampilkan produk. (Produk tidak akan tampil jika search kosong)
      </div>-->
    </div>
  </div>

  <div class="max-w-7xl mx-auto p-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Produk -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-[var(--accent-color)]">
      <div class="p-4 border-b border-[var(--accent-color)] flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-[var(--text-primary)]">Produk</h2>
          <div class="text-xs text-[var(--text-secondary)]">Klik tombol <b>+</b> untuk tambah 1 ke cart</div>
        </div>
        <div class="text-xs text-[var(--text-secondary)]">Stok merah = ≤ 2</div>
      </div>

      <div class="p-3 sm:p-4">
        <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <?php foreach ($produk as $pid => $p): ?>
            <?php
              if (!is_array($p)) continue;
              $nama = (string)($p['nama'] ?? '');
              $kategori = (string)($p['kategori'] ?? 'umum');
              $harga = to_int($p['harga_jual'] ?? 0);
              $stok = to_int($p['stok'] ?? 0);
            ?>
            <div class="productCard border border-[var(--accent-color)] rounded-xl p-3 flex items-center justify-between gap-3 hover:bg-[var(--secondary-color)] transition-colors"
                 data-nama="<?= htmlspecialchars(mb_strtolower($nama)) ?>"
                 data-kategori="<?= htmlspecialchars(mb_strtolower($kategori)) ?>"
                 data-visible="0"
                 style="display:none;">
              <div class="min-w-0">
                <div class="font-semibold text-[var(--text-primary)] truncate"><?= htmlspecialchars($nama) ?></div>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                  <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 border border-[var(--accent-color)] text-[var(--text-secondary)]">
                    <?= htmlspecialchars($kategori) ?>
                  </span>
                  <span class="text-xs <?= $stok <= 2 ? 'text-red-600 font-semibold' : 'text-[var(--text-secondary)]' ?>">
                    Stok: <?= (int)$stok ?>
                  </span>
                </div>
                <div class="text-sm mt-2 font-semibold"><?= rupiah($harga) ?></div>
              </div>

              <form method="POST" action="api/cart_add.php">
                <input type="hidden" name="produk_id" value="<?= htmlspecialchars((string)$pid) ?>">
                <input type="hidden" name="jumlah" value="1">
                <button class="w-11 h-11 rounded-full bg-[var(--primary-color)] text-white text-xl leading-none flex items-center justify-center shadow-sm hover:opacity-95">
                  +
                </button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>

        <div id="noResult" class="hidden text-sm text-[var(--text-secondary)] mt-3">
          Produk tidak ditemukan.
        </div>
      </div>
    </div>

    <!-- Cart aktif + Selesaikan -->
    <div class="bg-white rounded-xl border border-[var(--accent-color)]">
      <div class="p-4 border-b border-[var(--accent-color)]">
        <h2 class="font-semibold text-[var(--text-primary)]">Cart (Transaksi aktif)</h2>
        <div class="text-xs text-[var(--text-secondary)]">Selesaikan transaksi → masuk ke Keranjang</div>
      </div>

      <div class="p-4 space-y-4">
        <?php if (count($cart) === 0): ?>
          <div class="text-sm text-[var(--text-secondary)]">Cart kosong.</div>
        <?php else: ?>
          <div class="space-y-2 max-h-[320px] overflow-auto pr-1">
            <?php foreach ($cart as $idx => $it): ?>
              <?php
                if (!is_array($it)) continue;
                $nama = (string)($it['nama'] ?? '');
                $qty  = to_int($it['jumlah'] ?? 0);
                $harga= to_int($it['harga_jual'] ?? 0);
                $sub  = $qty * $harga;
              ?>
              <div class="border border-[var(--accent-color)] rounded-xl p-3">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <div class="font-semibold text-[var(--text-primary)] truncate"><?= htmlspecialchars($nama) ?></div>
                    <div class="text-xs text-[var(--text-secondary)]"><?= (int)$qty ?> × <?= rupiah($harga) ?></div>
                  </div>

                  <form method="POST" action="api/cart_remove.php" onsubmit="return confirm('Hapus item ini dari transaksi aktif?');">
                    <input type="hidden" name="index" value="<?= (int)$idx ?>">
                    <button class="text-xs rounded-lg bg-red-600 text-white px-2 py-1 hover:bg-red-700">Hapus</button>
                  </form>
                </div>

                <div class="mt-3 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <form method="POST" action="api/cart_update.php">
                      <input type="hidden" name="index" value="<?= (int)$idx ?>">
                      <input type="hidden" name="jumlah" value="<?= max(1, $qty - 1) ?>">
                      <button class="w-9 h-9 rounded-lg bg-white border border-[var(--accent-color)] hover:bg-[var(--secondary-color)]">-</button>
                    </form>

                    <div class="w-10 text-center font-semibold"><?= (int)$qty ?></div>

                    <form method="POST" action="api/cart_update.php">
                      <input type="hidden" name="index" value="<?= (int)$idx ?>">
                      <input type="hidden" name="jumlah" value="<?= $qty + 1 ?>">
                      <button class="w-9 h-9 rounded-lg bg-white border border-[var(--accent-color)] hover:bg-[var(--secondary-color)]">+</button>
                    </form>
                  </div>

                  <div class="text-sm font-bold text-[var(--text-primary)]"><?= rupiah($sub) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="rounded-xl border border-[var(--accent-color)] bg-[var(--secondary-color)] p-3">
            <div class="flex items-center justify-between">
              <div class="text-sm text-[var(--text-secondary)]">Total</div>
              <div class="text-lg font-bold text-[var(--text-primary)]"><?= rupiah($cartTotal) ?></div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Selesaikan transaksi -->
        <div class="border-t border-[var(--accent-color)] pt-4">
          <form id="finishForm" method="POST" action="api/checkout_to_keranjang.php" class="space-y-3"
                onsubmit="return confirm('Selesaikan transaksi ini? Transaksi akan masuk ke Keranjang dan cart akan dikosongkan.');">

            <input type="hidden" name="kasir" value="<?= htmlspecialchars($kasirName) ?>">

            <!-- DISKON: view formatted + hidden numeric -->
            <div>
              <label class="block text-sm text-[var(--text-secondary)]">Diskon Nominal (Rp)</label>
              <input id="diskonView" inputmode="numeric" autocomplete="off"
                     placeholder="0"
                     class="mt-1 w-full rounded-lg border border-[var(--accent-color)] p-2 text-sm text-right">
              <input type="hidden" name="diskon" id="diskonHidden" value="0">
              <div class="text-xs text-[var(--text-secondary)] mt-1">Contoh: 10.000</div>
            </div>

            <div class="border border-[var(--accent-color)] rounded-xl p-3">
              <div class="text-sm font-semibold text-[var(--text-primary)]">Metode Pembayaran</div>

              <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2">
                <label class="flex items-center gap-2 rounded-lg border border-[var(--accent-color)] bg-white px-3 py-2 text-sm cursor-pointer">
                  <input type="radio" name="pay_mode" value="cash" checked>
                  <span>Cash</span>
                </label>

                <label class="flex items-center gap-2 rounded-lg border border-[var(--accent-color)] bg-white px-3 py-2 text-sm cursor-pointer">
                  <input type="radio" name="pay_mode" value="qris">
                  <span>QRIS</span>
                </label>

                <label class="flex items-center gap-2 rounded-lg border border-[var(--accent-color)] bg-white px-3 py-2 text-sm cursor-pointer">
                  <input type="radio" name="pay_mode" value="partial">
                  <span>Partial</span>
                </label>
              </div>

              <div id="partialBox" class="hidden mt-3">
                <label class="block text-xs text-[var(--text-secondary)]">Transfer (Rp)</label>
                <input id="transferInput" type="number" value="0" min="0"
                       class="mt-1 w-full rounded-lg border border-[var(--accent-color)] p-2 text-sm text-right">
                <div class="text-xs text-[var(--text-secondary)] mt-1">
                  Sisa otomatis dianggap Cash.
                </div>
              </div>
            </div>

            <!-- Hidden payment fields to backend -->
            <input type="hidden" name="pay_cash" id="payCash" value="0">
            <input type="hidden" name="pay_qris" id="payQris" value="0">
            <input type="hidden" name="pay_transfer" id="payTransfer" value="0">

            <div class="rounded-xl border border-[var(--accent-color)] bg-[var(--secondary-color)] p-3">
              <div class="flex items-center justify-between">
                <div class="text-sm text-[var(--text-secondary)]">Grand Total</div>
                <div id="grandText" class="text-xl font-extrabold text-[var(--text-primary)]"><?= rupiah($cartTotal) ?></div>
              </div>
              <div id="payHint" class="text-xs text-[var(--text-secondary)] mt-1"></div>
            </div>

            <button <?= count($cart)===0 ? 'disabled' : '' ?>
                    class="w-full rounded-xl bg-green-600 text-white px-4 py-3 text-sm font-semibold disabled:opacity-50 hover:bg-green-700">
              Selesaikan Transaksi
            </button>
          </form>
        </div>

      </div>
    </div>
  </div>

<script>
(() => {
  // ---- realtime search ----
  const searchBox = document.getElementById('searchBox');
  const cards = Array.from(document.querySelectorAll('.productCard'));
  const noResult = document.getElementById('noResult');

  function hideAll() {
    for (const c of cards) c.style.display = 'none';
  }

  function filterProducts() {
    const q = (searchBox.value || '').trim().toLowerCase();

    // Jika search kosong: jangan tampilkan produk apa pun
    if (q === '') {
      hideAll();
      noResult.classList.add('hidden');
      return;
    }

    let shown = 0;
    for (const c of cards) {
      const nama = c.getAttribute('data-nama') || '';
      const kat  = c.getAttribute('data-kategori') || '';
      const ok = nama.includes(q) || kat.includes(q);
      c.style.display = ok ? '' : 'none';
      if (ok) shown++;
    }

    noResult.classList.toggle('hidden', shown !== 0);
  }

  searchBox?.addEventListener('input', filterProducts);
  // init: kosong => semua hidden
  filterProducts();

  // ---- rupiah input formatter (diskonView -> diskonHidden) ----
  const diskonView = document.getElementById('diskonView');
  const diskonHidden = document.getElementById('diskonHidden');

  function digitsOnly(s){ return (s || '').toString().replace(/[^\d]/g, ''); }
  function toInt(v){ v = parseInt(v, 10); return isNaN(v) ? 0 : v; }
  function formatIDR(n){
    n = toInt(n);
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function setDiskonFromView() {
    const raw = digitsOnly(diskonView.value);
    const n = raw === '' ? 0 : toInt(raw);
    diskonHidden.value = n;
    diskonView.value = (raw === '' ? '' : formatIDR(n));
    syncPayment();
  }

  diskonView?.addEventListener('input', () => {
    const pos = diskonView.selectionStart || 0;
    const beforeLen = diskonView.value.length;
    setDiskonFromView();
    const afterLen = diskonView.value.length;
    const diff = afterLen - beforeLen;
    diskonView.setSelectionRange(pos + diff, pos + diff);
  });

  diskonView?.addEventListener('blur', setDiskonFromView);

  // ---- payment UI logic ----
  const total = <?= (int)$cartTotal ?>;
  const grandText = document.getElementById('grandText');
  const payHint = document.getElementById('payHint');
  const partialBox = document.getElementById('partialBox');
  const transferInput = document.getElementById('transferInput');

  const payCash = document.getElementById('payCash');
  const payQris = document.getElementById('payQris');
  const payTransfer = document.getElementById('payTransfer');

  function rupiahClient(n){
    n = toInt(n);
    return 'Rp ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function grandTotal(){
    let d = toInt(diskonHidden.value);
    if (d < 0) d = 0;
    if (d > total) d = total;
    return total - d;
  }

  function currentMode(){
    const r = document.querySelector('input[name="pay_mode"]:checked');
    return r ? r.value : 'cash';
  }

  function syncPayment(){
    const g = grandTotal();
    grandText.textContent = rupiahClient(g);

    const mode = currentMode();
    if (mode === 'cash') {
      partialBox.classList.add('hidden');
      payCash.value = g; payQris.value = 0; payTransfer.value = 0;
      payHint.textContent = 'Cash otomatis = grand total.';
      return;
    }
    if (mode === 'qris') {
      partialBox.classList.add('hidden');
      payCash.value = 0; payQris.value = g; payTransfer.value = 0;
      payHint.textContent = 'QRIS otomatis = grand total.';
      return;
    }

    partialBox.classList.remove('hidden');
    let tf = toInt(transferInput.value);
    if (tf < 0) tf = 0;
    if (tf > g) tf = g;
    transferInput.value = tf;

    const cash = g - tf;
    payCash.value = cash;
    payQris.value = 0;
    payTransfer.value = tf;

    payHint.textContent = `Transfer ${rupiahClient(tf)} • Cash ${rupiahClient(cash)}`;
  }

  document.querySelectorAll('input[name="pay_mode"]').forEach(el => el.addEventListener('change', syncPayment));
  transferInput?.addEventListener('input', syncPayment);

  // init
  diskonView.value = '0';
  diskonHidden.value = '0';
  setDiskonFromView();
  syncPayment();

  document.getElementById('finishForm')?.addEventListener('submit', () => {
    setDiskonFromView();
    syncPayment();
  });
})();
</script>
</body>
</html>