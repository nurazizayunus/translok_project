<?php
require_once '../config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if ($id <= 0) {
  echo json_encode(['ok' => false, 'message' => 'ID tidak valid']);
  exit;
}

$res = mysqli_query($conn, "SELECT role FROM users WHERE id=$id");
$row = mysqli_fetch_assoc($res);

if (!$row) {
  echo json_encode(['ok' => false, 'message' => 'User tidak ditemukan']);
  exit;
}

if ($row['role'] === 'admin') {
  echo json_encode(['ok' => false, 'message' => 'Admin tidak boleh dihapus']);
  exit;
}

mysqli_query($conn, "DELETE FROM users WHERE id=$id");
echo json_encode(['ok' => true]);
