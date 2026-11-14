<?php
require_once __DIR__ . '/_bootstrap.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  http_response_code(403);
  response_error('Akses ditolak');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
  response_error('Data tidak valid');
}

$id = (int)($input['id'] ?? 0);
$password = (string)($input['password'] ?? '');

if ($id <= 0 || $password === '') {
  response_error('Data belum lengkap');
}

$hash = md5($password);
$stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
if (!$stmt) {
  response_error('Query gagal', mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, 'si', $hash, $id);
mysqli_stmt_execute($stmt);

response_ok(['updated' => mysqli_affected_rows($conn)]);
