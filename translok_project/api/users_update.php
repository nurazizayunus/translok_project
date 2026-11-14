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
$nama = trim($input['nama'] ?? '');
$role = $input['role'] ?? 'user';
$wilker = trim($input['wilker'] ?? '');
$username = trim($input['username'] ?? '');

if ($id <= 0) {
  response_error('Data tidak valid');
}

if (!in_array($role, ['admin', 'user'], true)) {
  response_error('Role tidak valid');
}

if ($role === 'admin' && !$nama) {
  response_error('Nama wajib diisi untuk admin');
}

if ($role === 'user' && $wilker === '') {
  response_error('Wilker wajib diisi untuk user');
}

if ($role === 'admin') {
  if ($username === '') {
    response_error('Username wajib diisi untuk admin');
  }
  $check = mysqli_prepare($conn, "SELECT id FROM users WHERE role='admin' AND username = ? AND id <> ?");
  if (!$check) {
    response_error('Query gagal', mysqli_error($conn));
  }
  mysqli_stmt_bind_param($check, 'si', $username, $id);
  mysqli_stmt_execute($check);
  $exists = mysqli_stmt_get_result($check);
  if ($exists && mysqli_fetch_assoc($exists)) {
    response_error('Username admin sudah digunakan');
  }

  $sql = "UPDATE users SET nama = ?, username = ?, role = ?, wilker = '' WHERE id = ?";
  $stmt = mysqli_prepare($conn, $sql);
  if (!$stmt) {
    response_error('Query gagal', mysqli_error($conn));
  }
  mysqli_stmt_bind_param($stmt, 'sssi', $nama, $username, $role, $id);
} else {
  $username = $wilker;

  if ($nama !== '') {
    $check = mysqli_prepare(
      $conn,
      "SELECT id FROM users WHERE role='user' AND username = ? AND LOWER(nama)=LOWER(?) AND id <> ?"
    );
    if (!$check) {
      response_error('Query gagal', mysqli_error($conn));
    }
    mysqli_stmt_bind_param($check, 'ssi', $username, $nama, $id);
  } else {
    $check = mysqli_prepare(
      $conn,
      "SELECT id FROM users WHERE role='user' AND username = ? AND TRIM(COALESCE(nama,''))='' AND id <> ?"
    );
    if (!$check) {
      response_error('Query gagal', mysqli_error($conn));
    }
    mysqli_stmt_bind_param($check, 'si', $username, $id);
  }
  mysqli_stmt_execute($check);
  $exists = mysqli_stmt_get_result($check);
  if ($exists && mysqli_fetch_assoc($exists)) {
    if ($nama !== '') {
      response_error('Nama ini sudah ada untuk wilker tersebut');
    }
    response_error('Akun wilker tanpa nama sudah ada');
  }

  $sql = "UPDATE users SET nama = ?, username = ?, role = ?, wilker = ? WHERE id = ?";
  $stmt = mysqli_prepare($conn, $sql);
  if (!$stmt) {
    response_error('Query gagal', mysqli_error($conn));
  }
  mysqli_stmt_bind_param($stmt, 'ssssi', $nama, $username, $role, $wilker, $id);
}

if (!mysqli_stmt_execute($stmt)) {
  $err = mysqli_stmt_error($stmt);
  if (stripos($err, 'Duplicate entry') !== false) {
    response_error('Data user duplikat');
  }
  response_error('Gagal memperbarui user', $err);
}

response_ok(['updated' => mysqli_affected_rows($conn)]);
