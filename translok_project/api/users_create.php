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

$nama = trim($input['nama'] ?? '');
$username = trim($input['username'] ?? '');
$password = (string)($input['password'] ?? '');
$role = $input['role'] ?? 'user';
$wilker = trim($input['wilker'] ?? '');

if (!$password) {
  response_error('Password wajib diisi');
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

if ($role === 'user') {
  $username = $wilker;
}

if ($username === '') {
  response_error('Username wajib diisi untuk admin');
}

if ($role === 'admin') {
  $check = mysqli_prepare($conn, "SELECT id FROM users WHERE role='admin' AND username = ?");
} elseif ($nama !== '') {
  $check = mysqli_prepare($conn, "SELECT id FROM users WHERE role='user' AND username = ? AND LOWER(nama)=LOWER(?)");
} else {
  $check = mysqli_prepare($conn, "SELECT id FROM users WHERE role='user' AND username = ? AND TRIM(COALESCE(nama,''))=''");
}
if (!$check) {
  response_error('Query gagal', mysqli_error($conn));
}
if ($role === 'admin') {
  mysqli_stmt_bind_param($check, 's', $username);
} elseif ($nama !== '') {
  mysqli_stmt_bind_param($check, 'ss', $username, $nama);
} else {
  mysqli_stmt_bind_param($check, 's', $username);
}
mysqli_stmt_execute($check);
$exists = mysqli_stmt_get_result($check);
if ($exists && mysqli_fetch_assoc($exists)) {
  if ($role === 'admin') {
    response_error('Username admin sudah digunakan');
  }
  if ($nama !== '') {
    response_error('Nama ini sudah ada untuk wilker tersebut');
  }
  response_error('Akun wilker tanpa nama sudah ada');
}

$hash = md5($password);
$sql = "INSERT INTO users (nama, username, password, role, wilker)
        VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
  response_error('Query gagal', mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, 'sssss', $nama, $username, $hash, $role, $wilker);
if (!mysqli_stmt_execute($stmt)) {
  $err = mysqli_stmt_error($stmt);
  if (stripos($err, 'Duplicate entry') !== false) {
    if ($role === 'admin') {
      response_error('Username admin sudah digunakan');
    }
    if ($nama !== '') {
      response_error('Nama ini sudah ada untuk wilker tersebut');
    }
    response_error('Akun wilker tanpa nama sudah ada');
  }
  response_error('Gagal menyimpan user', $err);
}

response_ok(['id' => mysqli_insert_id($conn)]);
