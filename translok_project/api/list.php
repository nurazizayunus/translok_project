<?php
require_once __DIR__ . '/_bootstrap.php';
session_start();

$wilker = $_GET['wilker'] ?? '';
if (!$wilker) {
  response_error('Wilker wajib');
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = max(1, min(50, (int) ($_GET['per_page'] ?? 20)));
$q = trim($_GET['q'] ?? '');
$offset = ($page - 1) * $perPage;

$role = $_SESSION['user']['role'] ?? '';
$nama = $_SESSION['user']['nama'] ?? '';
$mode = $_SESSION['user']['mode'] ?? '';

/*
LOGIKA:
- ADMIN → semua data wilker
- USER + nama → hanya data dirinya
- USER tanpa nama → semua data wilker
*/

if ($role === 'user' && $mode === 'personal' && $nama !== '') {
  if ($q !== '') {
    $like = '%' . $q . '%';
    $countSql = "SELECT COUNT(*) AS total_rows, COALESCE(SUM(CASE WHEN kegiatan = 'Lay pengendalian faktor risiko penyakit dapa situasi khusus (PNBP)' THEN 338000 ELSE nominal END), 0) AS total_nominal
                 FROM translok_kegiatan
                 WHERE wilker = ? AND nama = ? AND (nama LIKE ? OR kegiatan LIKE ? OR tanggal LIKE ? OR wilker LIKE ?)";
    $countStmt = mysqli_prepare($conn, $countSql);
    mysqli_stmt_bind_param($countStmt, 'ssssss', $wilker, $nama, $like, $like, $like, $like);
  } else {
    $countSql = "SELECT COUNT(*) AS total_rows, COALESCE(SUM(CASE WHEN kegiatan = 'Lay pengendalian faktor risiko penyakit dapa situasi khusus (PNBP)' THEN 338000 ELSE nominal END), 0) AS total_nominal
                 FROM translok_kegiatan
                 WHERE wilker = ? AND nama = ?";
    $countStmt = mysqli_prepare($conn, $countSql);
    mysqli_stmt_bind_param($countStmt, 'ss', $wilker, $nama);
  }
} else {
  if ($q !== '') {
    $like = '%' . $q . '%';
    $countSql = "SELECT COUNT(*) AS total_rows, COALESCE(SUM(CASE WHEN kegiatan = 'Lay pengendalian faktor risiko penyakit dapa situasi khusus (PNBP)' THEN 338000 ELSE nominal END), 0) AS total_nominal
                 FROM translok_kegiatan
                 WHERE wilker = ? AND (nama LIKE ? OR kegiatan LIKE ? OR tanggal LIKE ? OR wilker LIKE ?)";
    $countStmt = mysqli_prepare($conn, $countSql);
    mysqli_stmt_bind_param($countStmt, 'sssss', $wilker, $like, $like, $like, $like);
  } else {
    $countSql = "SELECT COUNT(*) AS total_rows, COALESCE(SUM(CASE WHEN kegiatan = 'Lay pengendalian faktor risiko penyakit dapa situasi khusus (PNBP)' THEN 338000 ELSE nominal END), 0) AS total_nominal
                 FROM translok_kegiatan
                 WHERE wilker = ?";
    $countStmt = mysqli_prepare($conn, $countSql);
    mysqli_stmt_bind_param($countStmt, 's', $wilker);
  }
}

mysqli_stmt_execute($countStmt);
$countRes = mysqli_stmt_get_result($countStmt);
$meta = mysqli_fetch_assoc($countRes) ?: ['total_rows' => 0, 'total_nominal' => 0];
$totalRows = (int) ($meta['total_rows'] ?? 0);
$totalNominal = (int) ($meta['total_nominal'] ?? 0);
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

if ($role === 'user' && $mode === 'personal' && $nama !== '') {
  if ($q !== '') {
    $sql = "SELECT * FROM translok_kegiatan
            WHERE wilker = ? AND nama = ? AND (nama LIKE ? OR kegiatan LIKE ? OR tanggal LIKE ? OR wilker LIKE ?)
            ORDER BY tanggal DESC, id DESC
            LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssssssii', $wilker, $nama, $like, $like, $like, $like, $perPage, $offset);
  } else {
    $sql = "SELECT * FROM translok_kegiatan
            WHERE wilker = ? AND nama = ?
            ORDER BY tanggal DESC, id DESC
            LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssii', $wilker, $nama, $perPage, $offset);
  }
} else {
  if ($q !== '') {
    $sql = "SELECT * FROM translok_kegiatan
            WHERE wilker = ? AND (nama LIKE ? OR kegiatan LIKE ? OR tanggal LIKE ? OR wilker LIKE ?)
            ORDER BY tanggal DESC, id DESC
            LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssssiii', $wilker, $like, $like, $like, $like, $perPage, $offset);
  } else {
    $sql = "SELECT * FROM translok_kegiatan
            WHERE wilker = ?
            ORDER BY tanggal DESC, id DESC
            LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sii', $wilker, $perPage, $offset);
  }
}

mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$data = [];
while ($row = mysqli_fetch_assoc($res)) {
  if (($row['kegiatan'] ?? '') === 'Lay pengendalian faktor risiko penyakit dapa situasi khusus (PNBP)') {
    $row['nominal'] = 338000;
  }
  $data[] = $row;
}

response_ok([
  'rows' => $data,
  'pagination' => [
    'page' => $page,
    'per_page' => $perPage,
    'total_rows' => $totalRows,
    'total_pages' => $totalPages,
    'total_nominal' => $totalNominal
  ]
]);
