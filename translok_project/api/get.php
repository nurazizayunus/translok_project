<?php
require_once __DIR__ . '/_bootstrap.php';
session_start();

if (!isset($_SESSION['user'])) {
  response_error('Unauthorized');
}

$id = $_GET['id'] ?? 0;

$sql = "SELECT id, nama, wilker, tanggal, kegiatan, nominal
        FROM translok_kegiatan
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
  response_error('Data tidak ditemukan');
}

if (($data['kegiatan'] ?? '') === 'Lay pengendalian faktor risiko penyakit dapa situasi khusus (PNBP)') {
  $data['nominal'] = 338000;
}

response_ok($data);
