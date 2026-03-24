<?php
require_once __DIR__ . '/lib/auth.php';

auth_start();

$error = '';
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $usernameVal = trim((string)($_POST['username'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  if ($usernameVal === '' || $password === '') {
    $error = 'Username dan password wajib diisi.';
  } else {
    if (auth_login($usernameVal, $password)) {
      $u = auth_user();
      // admin -> dashboard, kasir -> kasir
      if (($u['role'] ?? '') === 'admin') {
        header('Location: dashboard.php');
      } else {
        header('Location: kasir.php');
      }
      exit;
    } else {
      $error = 'Login gagal. Username/password salah atau akun nonaktif.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />
  <link as="style" href="https://fonts.googleapis.com/css2?display=swap&amp;family=Inter%3Awght%40400%3B500%3B600%3B700&amp;family=Noto+Sans%3Awght%40400%3B500%3B700%3B900" onload="this.rel='stylesheet'" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <style type="text/tailwindcss">
    :root {
      --primary-50: #eef7ff;
      --primary-100: #d9edff;
      --primary-200: #bce1ff;
      --primary-300: #8ed0ff;
      --primary-400: #5ab8ff;
      --primary-500: #309eff;
      --primary-600: #0d7ff2;
      --primary-700: #0269d3;
      --primary-800: #0355ad;
      --primary-900: #06478a;
      --primary-950: #0b2d54;
    }
  </style>
  <title>Admin Panel - Sign In</title>
  <link href="data:image/x-icon;base64," rel="icon" type="image/x-icon" />
</head>
<body class="bg-gray-50 dark:bg-gray-900">
  <div class="flex min-h-screen flex-col items-center justify-center px-6 py-8" style='font-family: Inter, "Noto Sans", sans-serif;'>
    <a class="mb-6 flex items-center gap-3 text-2xl font-semibold text-gray-900 dark:text-white" href="#">
      <div class="flex h-8 w-8 items-center justify-center rounded-md bg-[var(--primary-600)] text-white">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
          <path d="M42.4379 44C42.4379 44 36.0744 33.9038 41.1692 24C46.8624 12.9336 42.2078 4 42.2078 4L7.01134 4C7.01134 4 11.6577 12.932 5.96912 23.9969C0.876273 33.9029 7.27094 44 7.27094 44L42.4379 44Z" fill="currentColor"></path>
        </svg>
      </div>
      <span class="font-bold">Admin Panel</span>
    </a>

    <div class="w-full rounded-lg bg-white shadow-md dark:border dark:border-gray-700 dark:bg-gray-800 sm:max-w-md md:mt-0 xl:p-0">
      <div class="space-y-4 p-6 sm:p-8 md:space-y-6">
        <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 dark:text-white md:text-2xl">
          Sign in to your account
        </h1>

        <?php if ($error !== ''): ?>
          <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-200">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-4 md:space-y-6">
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white" for="username">Username</label>
            <input
              class="form-input block w-full rounded-md border border-gray-300 bg-gray-50 p-2.5 text-gray-900 focus:border-[var(--primary-600)] focus:ring-[var(--primary-600)] dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500 sm:text-sm"
              id="username"
              name="username"
              value="<?= htmlspecialchars($usernameVal) ?>"
              placeholder="admin / kasir1"
              required
              autocomplete="username"
              type="text"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white" for="password">Password</label>
            <input
              class="form-input block w-full rounded-md border border-gray-300 bg-gray-50 p-2.5 text-gray-900 focus:border-[var(--primary-600)] focus:ring-[var(--primary-600)] dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500 sm:text-sm"
              id="password"
              name="password"
              placeholder="••••••••"
              required
              autocomplete="current-password"
              type="password"
            />
          </div>

          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start">
              <div class="flex h-5 items-center">
                <input aria-describedby="remember"
                       class="form-checkbox h-4 w-4 rounded border border-gray-300 bg-gray-50 text-[var(--primary-600)] focus:ring-3 focus:ring-[var(--primary-300)] dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-[var(--primary-600)]"
                       id="remember"
                       type="checkbox"
                       disabled
                />
              </div>
              <div class="ml-3 text-sm">
                <label class="text-gray-500 dark:text-gray-300" for="remember">Remember me (disabled)</label>
              </div>
            </div>
            <span class="text-sm font-medium text-gray-400 dark:text-gray-500">Forgot password? (coming soon)</span>
          </div>

          <button class="w-full rounded-md bg-[var(--primary-600)] px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-[var(--primary-700)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-300)] dark:bg-[var(--primary-600)] dark:hover:bg-[var(--primary-700)] dark:focus:ring-[var(--primary-800)]" type="submit">
            Sign in
          </button>

          <p class="text-sm font-light text-gray-500 dark:text-gray-400">
            Don’t have an account yet?
            <span class="font-medium text-[var(--primary-600)] dark:text-[var(--primary-500)]">Hubungi admin</span>
          </p>
        </form>
      </div>
    </div>
  </div>
</body>
</html>