<?php
date_default_timezone_set('Asia/Jakarta');

$cartFile = __DIR__ . '/../data/cart.json';
file_put_contents($cartFile, json_encode([], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

header('Location: ../kasir.php?msg=' . urlencode('Keranjang dikosongkan.'));
exit;