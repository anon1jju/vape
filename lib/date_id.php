<?php
function today_ddmmyyyy(): string {
  return date('d-m-Y');
}

function ddmmyyyy_is_valid(string $s): bool {
  $p = explode('-', $s);
  if (count($p) !== 3) return false;
  [$d,$m,$y] = $p;
  if (!ctype_digit($d) || !ctype_digit($m) || !ctype_digit($y)) return false;
  if (strlen($y) !== 4) return false;
  return checkdate((int)$m, (int)$d, (int)$y);
}