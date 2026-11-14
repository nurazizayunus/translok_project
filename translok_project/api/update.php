<?php
require_once __DIR__ . '/_bootstrap.php';
session_start();

if (!isset($_SESSION['user'])) {
  response_error('Unauthorized');
}

$input = json_decode(file_get_contents('php://input'), true);

$id       = $input['id']       ?? 0;
$nama     = $input['nama']     ?? '';
$wilker   = $input['wilker']   ?? '';
$tanggal  = $input['tanggal']  ?? '';
$kegiatan = $input['kegiatan'] ?? '';
$nominal  = $input['nominal']  ?? 0;

if (!$id) {
  response_error('ID tidak valid');
}

$sql = "UPDATE translok_kegiatan
        SET nama=?, wilker=?, tanggal=?, kegiatan=?, nominal=?
        WHERE id=?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ssssii',
  $nama, $wilker, $tanggal, $kegiatan, $nominal, $id
);

mysqli_stmt_execute($stmt);

response_ok(true);
