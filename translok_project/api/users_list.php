<?php
require_once '../config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
  exit;
}

$res = mysqli_query($conn, "SELECT id, nama, username, role, wilker FROM users");

$data = [];
while ($row = mysqli_fetch_assoc($res)) {
  $data[] = $row;
}

echo json_encode(['ok' => true, 'data' => $data]);
