<?php
require_once __DIR__ . '/_bootstrap.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
  response_error('Data tidak valid');
}

$wilker   = trim($input['wilker']   ?? '');
$nama     = trim($input['nama']     ?? '');
$tanggal  = trim($input['tanggal']  ?? '');
$kegiatan = trim($input['kegiatan'] ?? '');
$nominal  = (int)($input['nominal'] ?? 0);

if ($wilker === '' || $nama === '' || $tanggal === '' || $kegiatan === '' || $nominal <= 0) {
  response_error('Data belum lengkap');
}

$sql = "INSERT INTO translok_kegiatan
        (wilker, nama, tanggal, kegiatan, nominal)
        VALUES (?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
  response_error('Query gagal', mysqli_error($conn));
}

mysqli_stmt_bind_param(
  $stmt,
  'ssssi',
  $wilker,
  $nama,
  $tanggal,
  $kegiatan,
  $nominal
);

if (!mysqli_stmt_execute($stmt)) {
  response_error('Gagal menyimpan data', mysqli_stmt_error($stmt));
}

response_ok([
  'id' => mysqli_insert_id($conn)
]);
