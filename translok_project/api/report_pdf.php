<?php
require_once __DIR__ . '/_bootstrap.php';
session_start();

$role = $_SESSION['user']['role'] ?? '';
$userWilker = $_SESSION['user']['wilker'] ?? '';
$mode = $_SESSION['user']['mode'] ?? '';
$nama = $_SESSION['user']['nama'] ?? '';

$wilker = trim($_GET['wilker'] ?? '');
$month = trim($_GET['month'] ?? '');

if ($wilker === '' || $month === '') {
  response_error('Wilker dan bulan wajib diisi');
}

if ($role !== 'admin') {
  if ($mode === 'personal' && $nama !== '') {
    if ($wilker !== $userWilker && $wilker !== 'ALL') {
      response_error('Anda tidak memiliki akses ke wilker ini');
    }
  } elseif ($wilker !== $userWilker && $wilker !== 'ALL') {
    response_error('Anda tidak memiliki akses ke wilker ini');
  }
}

if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
  response_error('Format bulan tidak valid');
}

$startDate = $month . '-01';
$endDate = date('Y-m-t', strtotime($startDate));

function pdf_escape($text) {
  $text = (string) $text;
  $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
  if ($text === false) {
    $text = '';
  }
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function fetch_rows($conn, $wilker, $startDate, $endDate) {
  if ($wilker === 'ALL') {
    $sql = "SELECT id, nama, wilker, tanggal, kegiatan, nominal
            FROM translok_kegiatan
            WHERE tanggal BETWEEN ? AND ?
            ORDER BY wilker ASC, tanggal ASC, id ASC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
      response_error('Gagal menyiapkan query', mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'ss', $startDate, $endDate);
  } else {
    $sql = "SELECT id, nama, wilker, tanggal, kegiatan, nominal
            FROM translok_kegiatan
            WHERE wilker = ? AND tanggal BETWEEN ? AND ?
            ORDER BY tanggal ASC, id ASC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
      response_error('Gagal menyiapkan query', mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'sss', $wilker, $startDate, $endDate);
  }

  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $rows = [];
  while ($row = mysqli_fetch_assoc($res)) {
    if (($row['kegiatan'] ?? '') === 'Lay pengendalian faktor risiko penyakit dapa situasi khusus (PNBP)') {
      $row['nominal'] = 338000;
    }
    $row['nominal'] = (int) ($row['nominal'] ?? 0);
    $rows[] = $row;
  }
  return $rows;
}

$rows = fetch_rows($conn, $wilker, $startDate, $endDate);

$monthNames = [
  '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
  '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
  '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
];
$titleMonth = ($monthNames[substr($month, 5, 2)] ?? $month) . ' ' . substr($month, 0, 4);

$grouped = [];
foreach ($rows as $row) {
  $key = $row['wilker'] ?? 'Tidak Diketahui';
  if (!isset($grouped[$key])) {
    $grouped[$key] = [
      'rows' => [],
      'total' => 0,
    ];
  }
  $grouped[$key]['rows'][] = $row;
  $grouped[$key]['total'] += (int) $row['nominal'];
}

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="rekap-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $wilker) . '-' . $month . '.html"');

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rekap PDF</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 24px;
      color: #111827;
    }
    .title {
      text-align: center;
      margin-bottom: 20px;
    }
    .meta {
      margin-bottom: 12px;
      font-size: 14px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 18px;
      font-size: 13px;
    }
    th, td {
      border: 1px solid #111827;
      padding: 8px;
      vertical-align: top;
    }
    th {
      background: #e5e7eb;
      text-align: left;
    }
    .subtotal {
      font-weight: bold;
      background: #f3f4f6;
    }
    .grandtotal {
      font-weight: bold;
      font-size: 14px;
      margin-top: 16px;
    }
    @media print {
      body {
        margin: 12mm;
      }
      .no-print {
        display: none;
      }
      table {
        page-break-inside: auto;
      }
      tr {
        page-break-inside: avoid;
        page-break-after: auto;
      }
    }
  </style>
</head>
<body onload="window.print()">
  <div class="title">
    <h2>Rekapitulasi Data Kegiatan</h2>
    <div>Periode: <?= pdf_escape($titleMonth); ?></div>
    <div>Wilker: <?= $wilker === 'ALL' ? 'Semua Wilker' : pdf_escape($wilker); ?></div>
  </div>

  <div class="meta">
    Total data: <?= count($rows); ?><br>
    Total nominal: Rp <?= number_format(array_sum(array_column($rows, 'nominal')), 0, ',', '.'); ?>
  </div>

  <?php if ($wilker === 'ALL'): ?>
    <?php foreach ($grouped as $wilkerName => $group): ?>
      <h3><?= pdf_escape($wilkerName); ?></h3>
      <table>
        <thead>
          <tr>
            <th style="width:40px;">No</th>
            <th style="width:130px;">Tanggal</th>
            <th>Nama</th>
            <th>Kegiatan</th>
            <th style="width:120px;">Nominal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($group['rows'] as $i => $row): ?>
            <tr>
              <td><?= $i + 1; ?></td>
              <td><?= pdf_escape($row['tanggal']); ?></td>
              <td><?= pdf_escape($row['nama']); ?></td>
              <td><?= pdf_escape($row['kegiatan']); ?></td>
              <td>Rp <?= number_format((int) $row['nominal'], 0, ',', '.'); ?></td>
            </tr>
          <?php endforeach; ?>
          <tr class="subtotal">
            <td colspan="4">Subtotal <?= pdf_escape($wilkerName); ?></td>
            <td>Rp <?= number_format($group['total'], 0, ',', '.'); ?></td>
          </tr>
        </tbody>
      </table>
    <?php endforeach; ?>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th style="width:40px;">No</th>
          <th style="width:130px;">Tanggal</th>
          <th>Nama</th>
          <th>Kegiatan</th>
          <th style="width:120px;">Nominal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $row): ?>
          <tr>
            <td><?= $i + 1; ?></td>
            <td><?= pdf_escape($row['tanggal']); ?></td>
            <td><?= pdf_escape($row['nama']); ?></td>
            <td><?= pdf_escape($row['kegiatan']); ?></td>
            <td>Rp <?= number_format((int) $row['nominal'], 0, ',', '.'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div class="grandtotal">
    Grand Total: Rp <?= number_format(array_sum(array_column($rows, 'nominal')), 0, ',', '.'); ?>
  </div>
</body>
</html>
