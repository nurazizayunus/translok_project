<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
  header("Location: dashboard.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail Kegiatan | Translok</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<div class="content edit-page">
  <div class="header">
    <div>
      <h2>Detail Kegiatan</h2>
      <small>Informasi lengkap data kegiatan translok</small>
    </div>
    <span><?= htmlspecialchars($_SESSION['user']['username'], ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars(ucfirst($_SESSION['user']['role']), ENT_QUOTES, 'UTF-8'); ?>)</span>
  </div>

  <div class="card edit-card detail-page-card">
    <div class="detail-page-top">
      <div>
        <h3>Ringkasan Data</h3>
        <p class="detail-page-subtitle">Halaman ini menampilkan detail lengkap kegiatan yang dipilih dari dashboard.</p>
      </div>
      <button type="button" class="btn-secondary detail-back-btn" onclick="window.location.href='dashboard.php'">Kembali</button>
    </div>

    <div class="table-wrap detail-table-wrap">
      <table class="detail-table">
        <tbody>
          <tr>
            <th>Nama Pegawai</th>
            <td id="detailNama">Memuat...</td>
          </tr>
          <tr>
            <th>Wilker</th>
            <td id="detailWilker">-</td>
          </tr>
          <tr>
            <th>Tanggal</th>
            <td id="detailTanggal">-</td>
          </tr>
          <tr>
            <th>Nominal</th>
            <td id="detailNominal">Rp 0</td>
          </tr>
          <tr>
            <th>Jenis Kegiatan</th>
            <td id="detailKegiatan">-</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const recordId = <?= json_encode($id) ?>;

function formatRupiah(v){
  return 'Rp ' + Number(v || 0).toLocaleString('id-ID');
}

async function loadDetail(){
  try {
    const response = await fetch('api/get.php?id=' + encodeURIComponent(recordId));
    const payload = await response.json();

    if (!response.ok || !payload.ok) {
      throw new Error(payload.message || 'Gagal memuat detail kegiatan');
    }

    const data = payload.data || {};
    document.getElementById('detailNama').innerText = data.nama || '-';
    document.getElementById('detailWilker').innerText = data.wilker || '-';
    document.getElementById('detailTanggal').innerText = data.tanggal || '-';
    document.getElementById('detailNominal').innerText = formatRupiah(data.nominal || 0);
    document.getElementById('detailKegiatan').innerText = data.kegiatan || '-';
  } catch (error) {
    alert(error.message);
    window.location.href = 'dashboard.php';
  }
}

loadDetail();
</script>
</body>
</html>
