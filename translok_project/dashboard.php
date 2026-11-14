<?php
session_start();
if(!isset($_SESSION['user'])){
  header("Location: login.php");
  exit;
}
if($_SESSION['user']['role'] !== 'admin'){
  header("Location: user.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekapitulasi Translok</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/dashboard.css">
<style>
/* CSS Tambahan untuk Tombol & Modal */
.btn-edit{background:#f1c40f;border:none;padding:6px 10px;border-radius:4px;cursor:pointer}
.btn-lihat{background:#2563eb;border:none;padding:6px 10px;border-radius:4px;color:#fff;cursor:pointer}
.btn-hapus{background:#e74c3c;border:none;padding:6px 10px;border-radius:4px;color:#fff;cursor:pointer}

/* Modal Styling */
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(3px); }
.modal-content { background: #fff; margin: 12% auto; padding: 25px; width: 350px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: none; }
.modal-content h3 { margin-top: 0; color: #1e293b; font-size: 1.2rem; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.modal-content label { display: block; margin-top: 15px; font-weight: 600; font-size: 13px; color: #64748b; }
.modal-content input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
.modal-footer { display: flex; gap: 10px; margin-top: 25px; }
.btn-save-master { background: #10b981; color: white; border: none; padding: 10px; flex: 1; border-radius: 6px; font-weight: bold; cursor: pointer; }
.btn-close-modal { background: #94a3b8; color: white; border: none; padding: 10px; flex: 1; border-radius: 6px; font-weight: bold; cursor: pointer; }

/* Highlight opsi tambah di dropdown */
option.add-new-opt { background-color: #f0f9ff; color: #0369a1; font-weight: bold; }
</style>
</head>

<body>

<div id="modalMaster" class="modal">
  <div class="modal-content">
    <h3>Tambah Jenis Kegiatan</h3>
    <label>Nama Kegiatan Baru</label>
    <input type="text" id="m_nama" placeholder="Masukkan nama kegiatan...">
    
    <label>Nominal (Rp)</label>
    <input type="number" id="m_nominal" placeholder="Contoh: 170000">
    
    <div class="modal-footer">
      <button class="btn-save-master" onclick="simpanKeDatabaseMaster()">Simpan</button>
      <button class="btn-close-modal" onclick="tutupModal()">Batal</button>
    </div>
  </div>
</div>

<div id="modalPdf" class="modal">
  <div class="modal-content">
    <h3>Export Rekapan PDF</h3>
    <label>Wilker</label>
    <select id="pdfWilker">
      <option value="ALL">Semua Wilker</option>
      <option value="Kantor Induk">Kantor Induk</option>
      <option value="Bandara Frans Kaisiepo">Bandara Frans Kaisiepo</option>
      <option value="Bandara Mozes Kilangin Mimika">Bandara Mozes Kilangin Mimika</option>
      <option value="Pelabuhan Laut Biak">Pelabuhan Laut Biak</option>
      <option value="Pelabuhan Laut Amamapare">Pelabuhan Laut Amamapare</option>
      <option value="Pelabuhan Laut Pomako">Pelabuhan Laut Pomako</option>
      <option value="Pelabuhan Laut Nabire">Pelabuhan Laut Nabire</option>
      <option value="Pelabuhan Laut Serui">Pelabuhan Laut Serui</option>
      <option value="Pelabuhan Laut Waren">Pelabuhan Laut Waren</option>
    </select>

    <label>Bulan Rekap</label>
    <input type="month" id="pdfBulan">

    <div class="modal-footer">
      <button class="btn-save-master" onclick="exportPdf()">Unduh</button>
      <button class="btn-close-modal" onclick="tutupModalPdf()">Batal</button>
    </div>
  </div>
</div>

<div class="sidebar">
  <div class="logo">REKAPITULASI<br>TRANLOK</div>
  <ul class="wilker">
    <li class="active" onclick="pilihWilker(this,'Kantor Induk')">Kantor Induk</li>
    <li onclick="pilihWilker(this,'Bandara Frans Kaisiepo')">Bandara Frans Kaisiepo</li>
    <li onclick="pilihWilker(this,'Bandara Mozes Kilangin Mimika')">Bandara Mozes Kilangin Mimika</li>
    <li onclick="pilihWilker(this,'Pelabuhan Laut Biak')">Pelabuhan Laut Biak</li>
    <li onclick="pilihWilker(this,'Pelabuhan Laut Amamapare')">Pelabuhan Laut Amamapare</li>
    <li onclick="pilihWilker(this,'Pelabuhan Laut Pomako')">Pelabuhan Laut Pomako</li>
    <li onclick="pilihWilker(this,'Pelabuhan Laut Nabire')">Pelabuhan Laut Nabire</li>
    <li onclick="pilihWilker(this,'Pelabuhan Laut Serui')">Pelabuhan Laut Serui</li>
    <li onclick="pilihWilker(this,'Pelabuhan Laut Waren')">Pelabuhan Laut Waren</li>
    <li class="menu-user" onclick="location.href='users.php'">Manajemen User</li>
    <li class="logout" onclick="logout()">Logout</li>
  </ul>
</div>

<div class="content">

  <div class="header">
    <div>
      <h2 id="judulWilker">Kantor Induk</h2>
      <small>Rekapitulasi Kegiatan Translok</small>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
      <span><?= $_SESSION['user']['username']; ?> (Admin)</span>
    </div>
  </div>

  <div class="card">
    <h3>Tambah / Edit Kegiatan</h3>
    <input type="hidden" id="editId">

    <label>Nama Pegawai</label>
    <input type="text" id="nama">

    <label>Wilker</label>
    <input type="text" id="wilker" readonly>

    <label>Tanggal</label>
    <input type="date" id="tanggal">

    <label>Jenis Kegiatan</label>
    <select id="kegiatan" onchange="handleDropdownKegiatan()">
      <option value="">-- Memuat Data... --</option>
    </select>

    <label>Nominal</label>
    <input type="text" id="uang" readonly>

    <button onclick="simpan()" id="btnSimpan">Simpan</button>
  </div>

  <div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <h3 style="margin:0;">Data Kegiatan</h3>
      <button type="button" class="btn-secondary" onclick="bukaModalPdf()" style="padding:8px 12px;border:none;border-radius:8px;cursor:pointer;">Export PDF</button>
    </div>
    <div style="margin: 0 0 12px 0;">
      <label style="display:block;margin-bottom:6px;font-weight:600;color:#475569;">Cari Data</label>
      <input type="text" id="searchInput" placeholder="Cari nama, kegiatan, tanggal, atau wilker..." style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;box-sizing:border-box;">
    </div>
    <div class="stats-card">
      <div class="stats-grid">
        <div class="stat">
          <div class="stat-label">Jumlah Kegiatan</div>
          <div class="stat-value" id="totalKegiatanAdmin">0</div>
        </div>
        <div class="stat">
          <div class="stat-label">Total Nominal</div>
          <div class="stat-value" id="totalNominalAdmin">Rp 0</div>
        </div>
        <div class="stat">
          <div class="stat-label">Wilker Aktif</div>
          <div class="stat-value" id="wilkerAktifAdmin">Kantor Induk</div>
        </div>
      </div>
    </div>
    <table>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Tanggal</th>
          <th>Kegiatan</th>
          <th>Wilker</th>
          <th>Nominal</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody id="dataBody"></tbody>
    </table>
    
    <div class="pagination-bar">
      <div class="pagination-inline">
        <div class="pagination-info" id="paginationInfo">Halaman 1 dari 1</div>
        <div class="pagination-actions">
          <button type="button" class="btn-secondary pagination-btn" id="prevPageBtn" onclick="changePage(-1)">&lt;</button>
          <button type="button" class="btn-secondary pagination-btn" id="nextPageBtn" onclick="changePage(1)">&gt;</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let currentWilker = 'Kantor Induk';
let currentPage = 1;
const perPage = 20;
let totalPages = 1;
const searchInput = document.getElementById('searchInput');

// --- FUNGSI DINAMIS DROPDOWN & MODAL ---

async function loadMasterKegiatan() {
  try {
    const list = await api('api/get_kegiatan.php');
    const select = document.getElementById('kegiatan');
    select.innerHTML = '<option value="">-- Pilih Jenis Kegiatan --</option>';
    
    list.forEach(item => {
      const opt = document.createElement('option');
      opt.value = item.nominal;
      opt.text = item.nama_kegiatan;
      select.appendChild(opt);
    });

    // Tambahkan opsi pemicu modal di paling bawah
    const optAdd = document.createElement('option');
    optAdd.value = "TRIGGER_MODAL";
    optAdd.text = "++ TAMBAH JENIS KEGIATAN BARU ++";
    optAdd.className = "add-new-opt";
    select.appendChild(optAdd);
  } catch (e) {
    console.error("Gagal memuat list kegiatan");
  }
}

function handleDropdownKegiatan() {
  const select = document.getElementById('kegiatan');
  if(select.value === "TRIGGER_MODAL") {
    document.getElementById('modalMaster').style.display = 'block';
    select.value = ""; // Reset dropdown agar tidak tertahan di opsi tambah
    document.getElementById('uang').value = "";
  } else {
    updateUang();
  }
}

function tutupModal() {
  document.getElementById('modalMaster').style.display = 'none';
  document.getElementById('m_nama').value = "";
  document.getElementById('m_nominal').value = "";
}

async function simpanKeDatabaseMaster() {
  const nama = document.getElementById('m_nama').value;
  const nominal = document.getElementById('m_nominal').value;

  if(!nama || !nominal) return alert("Nama dan Nominal wajib diisi!");

  try {
    await api('api/create_master.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ nama_kegiatan: nama, nominal: nominal })
    });

    alert("Jenis kegiatan berhasil ditambahkan ke database!");
    tutupModal();
    await loadMasterKegiatan(); // Refresh dropdown otomatis
  } catch (e) {
    alert("Gagal simpan master: " + e);
  }
}

// --- FUNGSI STANDAR REKAPITULASI ---

function formatRupiah(v){
  return 'Rp ' + Number(v).toLocaleString('id-ID');
}

function updateUang(){
  const v = document.getElementById('kegiatan').value;
  document.getElementById('uang').value = v ? formatRupiah(v) : '';
}

async function api(url, opt){
  const r = await fetch(url, opt);
  const j = await r.json();
  if(!j.ok) throw j.message;
  return j.data;
}

async function render(){
  const body = document.getElementById('dataBody');
  body.innerHTML='<tr><td colspan="6">Memuat...</td></tr>';

  const q = searchInput.value.trim();
  
  const result = await api(
    'api/list.php?wilker=' + encodeURIComponent(currentWilker) +
    '&page=' + encodeURIComponent(currentPage) +
    '&per_page=' + encodeURIComponent(perPage) +
    '&q=' + encodeURIComponent(q)
  );
  
  const data = result.rows || [];
  const pagination = result.pagination || {};

  body.innerHTML='';
  if(data.length===0){
    body.innerHTML='<tr><td colspan="6">Belum ada data</td></tr>';
  } else {
    data.forEach(d=>{
      body.innerHTML+=`
        <tr>
          <td>${d.nama}</td>
          <td>${d.tanggal}</td>
          <td>${d.kegiatan}</td>
          <td>${d.wilker}</td>
          <td>${formatRupiah(d.nominal)}</td>
          <td class="aksi-cell">
            <button class="btn-lihat" onclick="location.href='detail.php?id=${d.id}'">Lihat</button>
            <button class="btn-edit" onclick="editData(${d.id})">Edit</button>
            <button class="btn-hapus" onclick="hapusData(${d.id})">Hapus</button>
          </td>
        </tr>
      `;
    });
  }
  setSummaryAdmin(pagination.total_rows || 0, pagination.total_nominal || 0);
  updatePagination(pagination);
}

function setSummaryAdmin(totalRows, totalNominal){
  document.getElementById('totalKegiatanAdmin').innerText = Number(totalRows || 0).toLocaleString('id-ID');
  document.getElementById('totalNominalAdmin').innerText = formatRupiah(totalNominal || 0);
  document.getElementById('wilkerAktifAdmin').innerText = currentWilker;
}

function updatePagination(pagination){
  const page = Number(pagination.page || 1);
  totalPages = Number(pagination.total_pages || 1);
  document.getElementById('paginationInfo').innerText = 'Halaman ' + page + ' dari ' + totalPages;
  document.getElementById('prevPageBtn').disabled = page <= 1;
  document.getElementById('nextPageBtn').disabled = page >= totalPages;
  currentPage = page;
}

function changePage(step){
  const target = currentPage + step;
  if (target < 1 || target > totalPages) return;
  currentPage = target;
  render();
}

async function simpan(){
  const sel = document.getElementById('kegiatan');
  const payload={
    id:document.getElementById('editId').value,
    nama:document.getElementById('nama').value,
    wilker:currentWilker,
    tanggal:document.getElementById('tanggal').value,
    kegiatan: sel.options[sel.selectedIndex].text,
    nominal: Number(sel.value)
  };

  if(!payload.nama || !payload.kegiatan || !payload.tanggal) return alert("Lengkapi semua field!");

  const url = payload.id ? 'api/update.php' : 'api/create.php';
  await api(url, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(payload)
  });

  resetForm();
  render();
}

async function editData(id){
  const d = await api('api/get.php?id='+id);
  document.getElementById('editId').value=id;
  document.getElementById('nama').value=d.nama;
  document.getElementById('tanggal').value=d.tanggal;
  document.getElementById('wilker').value=d.wilker;

  const select = document.getElementById('kegiatan');
  for(let i=0; i<select.options.length; i++){
    if(select.options[i].text === d.kegiatan){
      select.selectedIndex = i;
      break;
    }
  }
  updateUang();
  document.getElementById('btnSimpan').innerText='Update';
}

async function hapusData(id){
  if(confirm('Yakin hapus data ini?')){
    await api('api/delete.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({id})
    });
    render();
  }
}

function resetForm(){
  document.getElementById('editId').value='';
  document.getElementById('nama').value='';
  document.getElementById('tanggal').value='';
  document.getElementById('kegiatan').value='';
  document.getElementById('uang').value='';
  document.getElementById('btnSimpan').innerText='Simpan';
}

function pilihWilker(el,n){
  document.querySelectorAll('.wilker li').forEach(l=>l.classList.remove('active'));
  el.classList.add('active');
  currentWilker=n;
  currentPage=1;
  document.getElementById('judulWilker').innerText=n;
  document.getElementById('wilker').value=n;
  render();
}

let searchTimer = null;
searchInput.addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    currentPage = 1;
    render();
  }, 300);
});

function logout(){location.href='logout.php'}

function bukaModalPdf(){
  document.getElementById('modalPdf').style.display = 'block';
}

function tutupModalPdf(){
  document.getElementById('modalPdf').style.display = 'none';
}

function exportPdf(){
  const wilker = document.getElementById('pdfWilker').value;
  const month = document.getElementById('pdfBulan').value;
  if(!wilker || !month) return alert('Pilih wilker dan bulan terlebih dahulu.');
  tutupModalPdf();
  window.open('api/report_pdf.php?wilker=' + encodeURIComponent(wilker) + '&month=' + encodeURIComponent(month), '_blank');
}

// Inisialisasi awal
document.getElementById('wilker').value=currentWilker;
document.getElementById('pdfWilker').value='ALL';
document.getElementById('pdfBulan').value = new Date().toISOString().slice(0,7);
loadMasterKegiatan().then(() => render());

</script>
</body>
</html>
