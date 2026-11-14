<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

$viewWilker = $_SESSION['view_wilker'] ?? '';
$userWilker = $viewWilker !== ''
  ? $viewWilker
  : ($_SESSION['user']['wilker'] ?? '');

$userNama = $_SESSION['user']['nama'] ?? '';
$namaHeader = $userWilker ?: 'Wilker Tidak Diketahui';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rekapitulasi Translok - User</title>
  <link rel="stylesheet" href="css/user.css">
</head>
<body>

<!-- ===== TOPBAR ===== -->
<header class="topbar">
  <div>
    <div class="brand">Translok</div>
    <h1>Monitoring Kegiatan</h1>
    <p>Melihat data yang diinput admin secara realtime</p>
  </div>
  <div class="user-meta">
    <span><?= htmlspecialchars($namaHeader, ENT_QUOTES, 'UTF-8'); ?> (User)</span>
    <a class="logout" href="logout.php">Logout</a>
  </div>
</header>

<div id="modalPdf" class="modal" style="display:none;">
  <div class="modal-content">
    <h3>Export Rekapan PDF</h3>
    <label>Wilker</label>
    <input type="text" id="pdfWilker" value="<?= htmlspecialchars($userWilker, ENT_QUOTES, 'UTF-8'); ?>" readonly>

    <label>Bulan Rekap</label>
    <input type="month" id="pdfBulan">

    <div class="modal-footer">
      <button class="btn-save-master" onclick="exportPdf()">Unduh</button>
      <button class="btn-close-modal" onclick="tutupModalPdf()">Batal</button>
    </div>
  </div>
</div>

<!-- ===== CONTENT ===== -->
<main class="container">
  <section class="panel">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
      <h2 style="margin:0;">Data Kegiatan</h2>
      <button type="button" onclick="bukaModalPdf()" style="padding:8px 12px;border:none;border-radius:8px;cursor:pointer;background:#0f766e;color:#fff;">Export PDF</button>
    </div>

    <!-- ===== FILTER BAR ===== -->
    <div class="filters">

      <div class="field">
        <label>Wilker</label>
        <select id="filterWilker" <?= $userWilker ? 'disabled' : '' ?>>
          <option value="Kantor Induk">Kantor Induk</option>
          <option value="Bandara Frans Kaisiepo">Bandara Frans Kaisiepo</option>
          <option value="Bandara Mozes Kilangin Mimika">Bandara Mozes Kilangin Mimika</option>
          <option value="Pelabuhan Laut Biak">Pelabuhan Laut Biak</option>
          <option value="Pelabuhan Laut Amamapare">Pelabuhan Laut Amamapare</option>
          <option value="Pelabuhan Laut Pomako">Pelabuhan Laut Pomako</option>
          <option value="Pelabuhan Laut Nabire">Pelabuhan Laut Nabire</option>
          <option value="Pelabuhan Laut Serui">Pelabuhan Laut Serui</option>
          <option value="Pelabuhan Laut Waropen">Pelabuhan Laut Waropen</option>
        </select>
      </div>

      <div class="field">
        <label>Cari Data</label>
        <input type="text" id="searchInput" placeholder="Cari nama, kegiatan, tanggal, atau wilker...">
      </div>

      <div class="field">
        <label>Nama Pegawai</label>
        <input type="text"
               value="<?= $userNama ? htmlspecialchars($userNama, ENT_QUOTES, 'UTF-8') : 'Semua pegawai'; ?>"
               readonly>
      </div>

      <div class="status-box">
        <span class="status-label">Status</span>
        <span id="statusText" class="status-value">Memuat data...</span>
      </div>

    </div>
    <!-- ===== END FILTER ===== -->

    <div class="summary-grid">
      <div class="summary-card">
        <span class="summary-label">Jumlah Kegiatan</span>
        <strong id="totalKegiatan" class="summary-value">0</strong>
      </div>
      <div class="summary-card">
        <span class="summary-label">Total Nominal</span>
        <strong id="totalNominal" class="summary-value">Rp 0</strong>
      </div>
    </div>

    <!-- ===== TABLE ===== -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nama</th>
            <th>Tanggal</th>
            <th>Kegiatan</th>
            <th>Uang</th>
          </tr>
        </thead>
        <tbody id="dataBody">
          <tr>
            <td colspan="4">Belum ada data</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="pagination-bar">
      <div class="pagination-inline">
        <div class="pagination-info" id="paginationInfo">Halaman 1 dari 1</div>
        <div class="pagination-actions">
          <button type="button" class="pagination-btn" id="prevPageBtn">&lt;</button>
          <button type="button" class="pagination-btn" id="nextPageBtn">&gt;</button>
        </div>
      </div>
    </div>
    <!-- ===== END TABLE ===== -->

  </section>
</main>

<script>
const statusText   = document.getElementById('statusText');
const dataBody     = document.getElementById('dataBody');
const filterWilker = document.getElementById('filterWilker');
const totalKegiatan = document.getElementById('totalKegiatan');
const totalNominal = document.getElementById('totalNominal');
const userWilker   = <?= json_encode($userWilker) ?>;
const paginationInfo = document.getElementById('paginationInfo');
const prevPageBtn = document.getElementById('prevPageBtn');
const nextPageBtn = document.getElementById('nextPageBtn');
const searchInput = document.getElementById('searchInput');

let loading = false;
let currentPage = 1;
let totalPages = 1;
const perPage = 20;

function formatRupiah(value){
  return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
}

function escapeHtml(text){
  const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'};
  return String(text).replace(/[&<>"']/g, m => map[m]);
}

function setStatus(message){
  statusText.innerText = message;
}

function setSummary(totalRows, totalValue){
  totalKegiatan.innerText = Number(totalRows || 0).toLocaleString('id-ID');
  totalNominal.innerText = formatRupiah(totalValue || 0);
}

async function loadData(){
  if (loading) return;
  loading = true;
  setStatus('Memuat data...');

  const wilker = userWilker || filterWilker.value;
  const q = searchInput.value.trim();

  try {
    const res = await fetch(
      'api/list.php?wilker=' + encodeURIComponent(wilker) +
      '&page=' + encodeURIComponent(currentPage) +
      '&per_page=' + encodeURIComponent(perPage) +
      '&q=' + encodeURIComponent(q)
    );
    const payload = await res.json();

    if (!res.ok || !payload.ok) {
      throw new Error(payload.message || 'Gagal memuat data');
    }

    const result = payload.data || {};
    const data = result.rows || [];
    const pagination = result.pagination || {};
    dataBody.innerHTML = '';

    if (data.length === 0) {
      dataBody.innerHTML = '<tr><td colspan="4">Data tidak ditemukan</td></tr>';
      setSummary(pagination.total_rows || 0, pagination.total_nominal || 0);
      updatePagination(pagination);
      setStatus('Data kosong');
      return;
    }

    data.forEach(item => {
      const tr = document.createElement('tr');
      tr.innerHTML =
        '<td>' + escapeHtml(item.nama) + '</td>' +
        '<td>' + escapeHtml(item.tanggal) + '</td>' +
        '<td>' + escapeHtml(item.kegiatan) + '</td>' +
        '<td>' + formatRupiah(item.nominal) + '</td>';
      dataBody.appendChild(tr);
    });

    setSummary(pagination.total_rows || 0, pagination.total_nominal || 0);
    updatePagination(pagination);
    setStatus('Data terbaru tampil');
  } catch (err) {
    dataBody.innerHTML = '<tr><td colspan="4">Gagal memuat data</td></tr>';
    setSummary(0, 0);
    setStatus(err.message);
  } finally {
    loading = false;
  }
}

function updatePagination(pagination){
  const page = Number(pagination.page || 1);
  totalPages = Number(pagination.total_pages || 1);
  currentPage = page;
  paginationInfo.innerText = 'Halaman ' + page + ' dari ' + totalPages;
  prevPageBtn.disabled = page <= 1;
  nextPageBtn.disabled = page >= totalPages;
}

function changePage(step){
  const target = currentPage + step;
  if (target < 1 || target > totalPages || loading) {
    return;
  }
  currentPage = target;
  loadData();
}

function exportPdf(){
  const wilker = document.getElementById('pdfWilker').value;
  const month = document.getElementById('pdfBulan').value;
  if(!wilker || !month) return alert('Pilih bulan rekap terlebih dahulu.');
  tutupModalPdf();
  window.open('api/report_pdf.php?wilker=' + encodeURIComponent(wilker) + '&month=' + encodeURIComponent(month), '_blank');
}

function bukaModalPdf(){
  document.getElementById('modalPdf').style.display = 'block';
}

function tutupModalPdf(){
  document.getElementById('modalPdf').style.display = 'none';
}

let searchTimer = null;
searchInput.addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    currentPage = 1;
    loadData();
  }, 300);
});

filterWilker.addEventListener('change', () => {
  currentPage = 1;
  loadData();
});
prevPageBtn.addEventListener('click', () => changePage(-1));
nextPageBtn.addEventListener('click', () => changePage(1));

if (userWilker) {
  const opt = Array.from(filterWilker.options)
    .find(o => o.value === userWilker);
  if (opt) filterWilker.value = userWilker;
}

loadData();
setInterval(loadData, 10000);
document.getElementById('pdfBulan').value = new Date().toISOString().slice(0,7);
</script>

</body>
</html>
