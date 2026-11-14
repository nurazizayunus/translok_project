<?php
include "config.php";
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Kegiatan | Translok</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
<div class="content edit-page">
  <div class="header">
    <div>
      <h2>Edit Kegiatan</h2>
      <small id="wilkerLabel">Wilker</small>
    </div>
    <span id="userLabel">User</span>
  </div>

  <div class="card edit-card">
    <h3>Perbarui Data</h3>
    <form id="editForm">
      <div class="grid">
        <div>
          <label>Nama Pegawai</label>
          <input type="text" id="nama" required>
        </div>
        <div>
          <label>Wilker (Auto)</label>
          <input type="text" id="username" required>
        </div>
        <div>
          <label>Tanggal</label>
          <input type="date" id="tanggal" required>
        </div>
      </div>

      <label>Jenis Kegiatan</label>
      <select id="kegiatan" onchange="updateUang()" required>
        <option value="">-- Pilih Kegiatan --</option>
        <option value="170000">Pemeriksaan sanitasi lingkungan air, TTU, makanan (PNBP)</option>
        <option value="170000">Uji resistensi insektisida (PNBP)</option>
        <option value="170000">Pengawasan lalu lintas jenasah (PNBP)</option>
        <option value="170000">Pemeriksaan alat angkut, orang dan barang pada situasi KLB/ wabah/ KKM (RM)</option>
        <option value="170000">Lay pengendalian faktor risiko penyakit di pelabuhan penyebrangan (PNBP)</option>
        <option value="170000">Lay pengendalian faktor risiko penyakit di bandar udara (PNBP)</option>
        <option value="338000">Lay pengendalian faktor risiko penyakit dapa situasi khusus (PNBP)</option>
        <option value="170000">Pengendalian vektor DBD/ fogging (RM)</option>
        <option value="170000">Pemasangan perangkap (RM)</option>
        <option value="170000">Pengendalian faktor risiko penyakit diare / spraying (RM)</option>
        <option value="170000">Lay pengendalian faktor risiko penyakit malaria / IRS (RM)</option>
        <option value="170000">Layanan survei faktor risiko penyakit DBD (RM)</option>
        <option value="170000">Layanan survei faktor risiko penyakit malaria (RM)</option>
        <option value="170000">Layanan survei faktor risiko penyakit diare (RM)</option>
        <option value="170000">Layanan survei faktor risiko penyakit HIV / AIDS (RM)</option>
        <option value="170000">Layanan survei faktor risiko penyakit TB (RM)</option>
        <option value="170000">Layanan kegawatdaruratan dan rujukan (RM)</option>
        <option value="170000">Layanan penemuan aktif surveilans migrasi malaria (RM)</option>
        <option value="170000">Penyusunan dokumen perbendaharaan dan pertanggungjawaban keuangan</option>
      </select>

      <label>Jumlah Uang</label>
      <input type="text" id="uang" readonly>

      <div class="form-actions">
        <button type="submit">Simpan Perubahan</button>
        <button type="button" class="btn-secondary" id="btnBatal">Batal</button>
      </div>
    </form>
  </div>
</div>

<script>
const userKey = 'translok_user';
let recordId = '';
let currentWilker = '';
let returnUrl = 'dashboard.php';
let currentUser = null;

function formatRupiah(value){
  return 'Rp ' + Number(value).toLocaleString('id-ID');
}

function getUser(){
  const raw = localStorage.getItem(userKey);
  return raw ? JSON.parse(raw) : null;
}

async function apiFetch(url, options){
  const response = await fetch(url, options);
  const payload = await response.json();
  if(!response.ok || !payload.ok){
    throw new Error(payload.message || 'Gagal memproses data.');
  }
  return payload.data;
}

function updateUang(){
  const val = document.getElementById('kegiatan').value;
  document.getElementById('uang').value = val ? formatRupiah(val) : '';
}

function getParam(name){
  const params = new URLSearchParams(window.location.search);
  return params.get(name);
}

function isAllowed(item){
  if(!currentUser){
    return false;
  }
  if(currentUser.role === 'admin'){
    return true;
  }
  return item && item.user === currentUser.username && currentWilker === currentUser.wilker;
}

async function init(){
  currentUser = getUser();
  if(!currentUser || !currentUser.username){
    window.location.href = 'login.php';
    return;
  }

  const wilkerParam = getParam('wilker') || '';
  const idParam = getParam('id') || '';
  const fromParam = getParam('from');

  if(fromParam === 'user'){
    returnUrl = 'user.php';
  }

  if(!wilkerParam || !idParam){
    alert('Data edit tidak ditemukan.');
    window.location.href = returnUrl;
    return;
  }

  currentWilker = wilkerParam;
  recordId = idParam;
  document.getElementById('wilkerLabel').innerText = currentWilker;
  document.getElementById('userLabel').innerText = currentUser.username + ' (' + currentUser.role + ')';

  try{
    const item = await apiFetch('api/get.php?wilker=' + encodeURIComponent(currentWilker) +
      '&id=' + encodeURIComponent(recordId));

    if(!item || !isAllowed(item)){
      alert('Anda tidak memiliki akses untuk mengedit data ini.');
      window.location.href = returnUrl;
      return;
    }

    document.getElementById('nama').value = item.nama || '';
    document.getElementById('username').value = currentWilker;
    document.getElementById('tanggal').value = item.tanggal || '';

    const select = document.getElementById('kegiatan');
    let found = false;

    for(let i = 0; i < select.options.length; i++){
      if(select.options[i].text.trim().toLowerCase() === (item.kegiatan || '').trim().toLowerCase()){
        select.selectedIndex = i;
        found = true;
        break;
      }
    }

    // 🔥 FIX: kalau tidak ditemukan → tambahkan ke dropdown
    if(!found && item.kegiatan){
      const opt = document.createElement('option');
      opt.value = item.nominal || 0;
      opt.text = item.kegiatan;
      select.appendChild(opt);
      select.selectedIndex = select.options.length - 1;
    }

    updateUang();

    document.getElementById('username').readOnly = true;

  } catch (err){
    alert(err.message);
    window.location.href = returnUrl;
  }
}

document.getElementById('editForm').addEventListener('submit', async function(e){
  e.preventDefault();

  const nama = document.getElementById('nama').value.trim();
  const tanggal = document.getElementById('tanggal').value;
  const select = document.getElementById('kegiatan');
  const nominal = select.value;
  const kegiatanText = select.options[select.selectedIndex].text;
  const username = currentWilker || '';

  if(!nama || !username || !tanggal || !nominal){
    alert('Lengkapi semua data.');
    return;
  }

  try{
    await apiFetch('api/update.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        wilker: currentWilker,
        id: recordId,
        nama: nama,
        tanggal: tanggal,
        kegiatan: kegiatanText,
        nominal: Number(nominal),
        user: username
      })
    });

    window.location.href = returnUrl;

  } catch (err){
    alert(err.message);
  }
});

document.getElementById('btnBatal').addEventListener('click', function(){
  window.location.href = returnUrl;
});

init();
</script>

</body>
</html>
