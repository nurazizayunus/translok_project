<?php
require_once __DIR__ . '/_bootstrap.php';
session_start();

if (!isset($_SESSION['user'])) {
  response_error('Unauthorized');
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;

if (!$id) {
  response_error('ID tidak valid');
}

$sql = "DELETE FROM translok_kegiatan WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

response_ok(true);
