<?php
// Jalankan: php migrate_produk_kategori.php
date_default_timezone_set('Asia/Jakarta');

$produkFile = __DIR__ . '/data/produk.json';
if (!file_exists($produkFile)) { fwrite(STDERR, "Missing: $produkFile\n"); exit(1); }

$raw = file_get_contents($produkFile);
$data = json_decode($raw, true);
if (!is_array($data)) { fwrite(STDERR, "produk.json invalid\n"); exit(1); }

$ts = date('Ymd-His');
$backup = __DIR__ . "/data/produk.backup-$ts.json";
file_put_contents($backup, $raw);

$changed = 0;
foreach ($data as $id => $p) {
  if (!is_array($p)) continue;
  if (!isset($p['kategori']) || $p['kategori'] === null || $p['kategori'] === '') {
    $data[$id]['kategori'] = 'umum';
    $changed++;
  }
}

file_put_contents($produkFile, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "OK produk.json: tambah kategori='umum' ke $changed produk. Backup: $backup\n";