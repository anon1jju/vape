<?php
// Wajib: halaman yang include file ini sudah memanggil auth_start() / require_login()
$u = auth_user();
$role = (string)($u['role'] ?? '');
$username = (string)($u['username'] ?? '');

$active = (string)($active ?? ''); // set di halaman: $active='dashboard'|'kasir'|'keranjang'|'users'|'settings'
function nav_class(string $key, string $active): string {
  if ($key === $active) {
    return 'flex items-center gap-3 px-3 py-2 rounded-md bg-[var(--secondary-color)] text-[var(--primary-color)] font-medium';
  }
  return 'flex items-center gap-3 px-3 py-2 rounded-md text-[var(--text-secondary)] hover:bg-[var(--secondary-color)] hover:text-[var(--primary-color)] transition-colors';
}
?>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 flex-shrink-0 bg-white p-6 border-r border-[var(--accent-color)] flex flex-col justify-between transform -translate-x-full lg:relative lg:translate-x-0">
  <div>
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-[var(--primary-color)] rounded-full"></div>
        <div class="min-w-0">
          <h1 class="text-xl font-bold text-[var(--text-primary)] truncate">Vape POS</h1>
          <div class="text-xs text-[var(--text-secondary)] truncate">
            <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($role) ?>)
          </div>
        </div>
      </div>
      <button id="close-menu" class="lg:hidden text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <nav class="flex flex-col gap-2 mt-8">
      <!-- Dashboard (admin only biasanya, tapi link tetap ada; proteksi tetap di dashboard.php) -->
      <a class="<?= nav_class('dashboard', $active) ?>" href="dashboard.php">
        <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
          <path d="M224,115.55V208a16,16,0,0,1-16,16H168a16,16,0,0,1-16-16V168a8,8,0,0,0-8-8H112a8,8,0,0,0-8,8v40a16,16,0,0,1-16,16H48a16,16,0,0,1-16-16V115.55a16,16,0,0,1,5.17-11.78l80-75.48.11-.11a16,16,0,0,1,21.53,0,1.14,1.14,0,0,0,.11.11l80,75.48A16,16,0,0,1,224,115.55Z"></path>
        </svg>
        <span class="text-sm">Dashboard</span>
      </a>

      <!-- Kasir -->
      <a class="<?= nav_class('kasir', $active) ?>" href="kasir.php">
        <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
          <path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,160H40V56H216V200Z"></path>
        </svg>
        <span class="text-sm font-medium">Kasir</span>
      </a>

      <!-- Keranjang -->
      <a class="<?= nav_class('keranjang', $active) ?>" href="keranjang.php">
        <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
          <path d="M230.14,70.54A8,8,0,0,0,224,64H56.88L53.2,43.43A16,16,0,0,0,37.46,32H16a8,8,0,0,0,0,16H37.46l3.6,20.17s0,0,0,.05l18.8,105.23A16,16,0,0,0,75.6,224H200a16,16,0,0,0,15.74-13.14l14.2-80A8,8,0,0,0,224,112H78.24l-2.86-16H224A8,8,0,0,0,230.14,70.54Z"></path>
        </svg>
        <span class="text-sm font-medium">Keranjang</span>
      </a>
      
      <a class="<?= nav_class('products', $active) ?>" href="admin_products.php">
          <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
            <path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,160H40V56H216V200ZM176,88a48,48,0,0,1-96,0,8,8,0,0,1,16,0,32,32,0,0,0,64,0,8,8,0,0,1,16,0Z"></path>
          </svg>
          <span class="text-sm font-medium">Products</span>
        </a>
        
    <a class="<?= nav_class('reports', $active) ?>" href="admin_reports.php">
          <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
            <path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,160H40V56H216V200ZM176,88a48,48,0,0,1-96,0,8,8,0,0,1,16,0,32,32,0,0,0,64,0,8,8,0,0,1,16,0Z"></path>
          </svg>
          <span class="text-sm font-medium">Laporan</span>
        </a>

      <!-- Kelola User (admin only) -->
      <?php if ($role === 'admin'): ?>
        <a class="<?= nav_class('users', $active) ?>" href="admin_users.php">
          <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
            <path d="M117.25,157.92a60,60,0,1,0-66.5,0A95.83,95.83,0,0,0,3.53,195.63a8,8,0,1,0,13.4,8.74,80,80,0,0,1,134.14,0,8,8,0,0,0,13.4-8.74A95.83,95.83,0,0,0,117.25,157.92ZM40,108a44,44,0,1,1,44,44A44.05,44.05,0,0,1,40,108Zm210.14,98.7a8,8,0,0,1-11.07-2.33A79.83,79.83,0,0,0,172,168a8,8,0,0,1,0-16,44,44,0,1,0-16.34-84.87,8,8,0,1,1-5.94-14.85,60,60,0,0,1,55.53,105.64,95.83,95.83,0,0,1,47.22,37.71A8,8,0,0,1,250.14,206.7Z"></path>
          </svg>
          <span class="text-sm font-medium">Kelola User</span>
        </a>
      <?php endif; ?>
    </nav>
  </div>

  <div class="flex flex-col gap-2">
    <a class="<?= nav_class('logout', $active) ?>" href="logout.php">
      <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
        <path d="M116,216a12,12,0,0,1-12,12H56a20,20,0,0,1-20-20V48A20,20,0,0,1,56,28h48a12,12,0,0,1,0,24H60V204h44A12,12,0,0,1,116,216Zm117.66-96.49-40-40A12,12,0,0,0,176,88v28H116a12,12,0,0,0,0,24h60v28a12,12,0,0,0,20.49,8.49l40-40A12,12,0,0,0,233.66,119.51Z"></path>
      </svg>
      <span class="text-sm font-medium">Logout</span>
    </a>
  </div>
</aside>