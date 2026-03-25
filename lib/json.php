<?php
function read_json_file(string $path, $default = []) {
  if (!file_exists($path)) return $default;
  $raw = file_get_contents($path);
  $data = json_decode($raw, true);
  return is_array($data) ? $data : $default;
}

function to_int($v): int {
  if (is_int($v)) return $v;
  if (is_float($v)) return (int)$v;
  if (is_string($v)) return (int)preg_replace('/[^\d\-]/', '', $v);
  return 0;
}