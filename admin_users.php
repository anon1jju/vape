<?php
require_once __DIR__ . '/lib/auth.php';
require_admin();

$active = 'users';

$auth = auth_load();
$users = $auth['users'] ?? [];
if (!is_array($users)) $users = [];

$msg = $_GET['msg'] ?? '';
$msg = is_string($msg) ? $msg : '';
$err = $_GET['err'] ?? '';
$err = is_string($err) ? $err : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />
<link as="style" href="https://fonts.googleapis.com/css2?display=swap&amp;family=Inter%3Awght%40400%3B500%3B700%3B900&amp;family=Noto+Sans%3Awght%40400%3B500%3B700%3B900" onload="this.rel='stylesheet'" rel="stylesheet" />
  <title>Kelola User</title>
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
          <h1 class="text-2xl sm:text-3xl font-bold text-[var(--text-primary)]">Kelola User</h1>
          <p class="text-sm text-[var(--text-secondary)]">Tambah / reset password / aktif-nonaktif / hapus</p>
        </div>
      </div>
    </div>

    <?php if ($msg !== ''): ?>
      <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err !== ''): ?>
      <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- Create user -->
    <div class="bg-white rounded-xl border border-[var(--accent-color)] p-4">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <div class="font-semibold text-[var(--text-primary)]">Tambah User</div>
          <div class="text-xs text-[var(--text-secondary)]">User baru otomatis aktif.</div>
        </div>
      </div>

      <form method="POST" action="api/admin_user_create.php" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
          <label class="block text-xs text-[var(--text-secondary)]">Username</label>
          <input name="username" required class="mt-1 w-full rounded-lg border border-[var(--accent-color)] p-2 text-sm" placeholder="kasir2">
        </div>

        <div>
          <label class="block text-xs text-[var(--text-secondary)]">Role</label>
          <select name="role" class="mt-1 w-full rounded-lg border border-[var(--accent-color)] p-2 text-sm">
            <option value="kasir">kasir</option>
            <option value="admin">admin</option>
          </select>
        </div>

        <div>
          <label class="block text-xs text-[var(--text-secondary)]">Password</label>
          <input name="password" required type="password" class="mt-1 w-full rounded-lg border border-[var(--accent-color)] p-2 text-sm" placeholder="••••••••">
        </div>

        <div class="flex items-end">
          <button class="w-full rounded-lg bg-[var(--primary-color)] text-white py-2 text-sm font-semibold hover:opacity-95">
            Buat User
          </button>
        </div>
      </form>
    </div>

    <!-- Users list -->
    <div class="mt-6 bg-white rounded-xl border border-[var(--accent-color)] overflow-hidden">
      <div class="p-4 border-b border-[var(--accent-color)] flex items-center justify-between">
        <div class="font-semibold text-[var(--text-primary)]">Daftar User (<?= count($users) ?>)</div>
        <div class="text-xs text-[var(--text-secondary)]">Admin terakhir tidak boleh dihapus</div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-[var(--secondary-color)] text-[var(--text-secondary)]">
            <tr>
              <th class="text-left p-3">Username</th>
              <th class="text-left p-3">Role</th>
              <th class="text-left p-3">Status</th>
              <th class="text-right p-3">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $i => $u): ?>
              <?php
                if (!is_array($u)) continue;
                $username = (string)($u['username'] ?? '');
                $role = (string)($u['role'] ?? 'kasir');
                $aktif = (bool)($u['aktif'] ?? true);
              ?>
              <tr class="border-t border-[var(--accent-color)]">
                <td class="p-3 font-semibold text-[var(--text-primary)]"><?= htmlspecialchars($username) ?></td>
                <td class="p-3 text-[var(--text-secondary)]"><?= htmlspecialchars($role) ?></td>
                <td class="p-3">
                  <?php if ($aktif): ?>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-800">aktif</span>
                  <?php else: ?>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-slate-200 text-slate-700">nonaktif</span>
                  <?php endif; ?>
                </td>
                <td class="p-3">
                  <div class="flex flex-col lg:flex-row gap-2 justify-end">
                    <form method="POST" action="api/admin_user_toggle.php">
                      <input type="hidden" name="index" value="<?= (int)$i ?>">
                      <button class="rounded-lg border border-[var(--accent-color)] bg-white px-3 py-2 text-xs hover:bg-[var(--secondary-color)]"
                              onclick="return confirm('Ubah status user ini?');">
                        <?= $aktif ? 'Nonaktifkan' : 'Aktifkan' ?>
                      </button>
                    </form>

                    <form method="POST" action="api/admin_user_reset_password.php" class="flex gap-2">
                      <input type="hidden" name="index" value="<?= (int)$i ?>">
                      <input name="new_password" required type="password"
                             class="min-w-[140px] rounded-lg border border-[var(--accent-color)] px-3 py-2 text-xs"
                             placeholder="password baru">
                      <button class="rounded-lg bg-amber-600 text-white px-3 py-2 text-xs hover:bg-amber-700"
                              onclick="return confirm('Reset password user ini?');">
                        Reset PW
                      </button>
                    </form>

                    <form method="POST" action="api/admin_user_delete.php">
                      <input type="hidden" name="index" value="<?= (int)$i ?>">
                      <button class="rounded-lg bg-red-600 text-white px-3 py-2 text-xs hover:bg-red-700"
                              onclick="return confirm('Hapus user ini?');">
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
    </div>

  </main>
</div>

<?php include __DIR__ . '/partials/sidebar_js.php'; ?>
</body>
</html>