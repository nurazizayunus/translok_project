<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Manajemen User | Translok</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<div class="sidebar">
  <div class="logo">REKAPITULASI<br>TRANLOK</div>
  <ul class="wilker">
    <li onclick="location.href='dashboard.php'">Dashboard</li>
    <li class="active">Manajemen User</li>
    <li class="logout" onclick="logout()">Logout</li>
  </ul>
</div>

<div class="content">

  <div class="header">
    <div>
      <h2>Manajemen User</h2>
      <small>Kelola akun pengguna sistem</small>
    </div>
    <span><?= htmlspecialchars($_SESSION['user']['username']) ?> (Admin)</span>
  </div>

  <div class="card">
    <h3>Data User</h3>

    <table>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Username</th>
          <th>Role</th>
          <th>Wilker</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody id="dataBody">
        <tr>
          <td colspan="5">Memuat data...</td>
        </tr>
      </tbody>
    </table>
  </div>

</div>

<script>
function escapeHtml(text){
  const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'};
  return String(text ?? '').replace(/[&<>"']/g, m => map[m]);
}

function logout(){
  location.href = 'logout.php';
}

/* ================= LOAD USERS ================= */
async function loadUsers(){
  const tbody = document.getElementById('dataBody');
  tbody.innerHTML = '<tr><td colspan="5">Memuat data...</td></tr>';

  try {
    const res = await fetch('api/users_list.php');
    const json = await res.json();

    if (!json.ok) {
      tbody.innerHTML = `<tr><td colspan="5">${json.message || 'Gagal memuat data'}</td></tr>`;
      return;
    }

    tbody.innerHTML = '';

    if (json.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5">Belum ada user</td></tr>';
      return;
    }

    json.data.forEach(u => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${escapeHtml(u.nama || '-')}</td>
        <td>${escapeHtml(u.username)}</td>
        <td>${escapeHtml(u.role)}</td>
        <td>${escapeHtml(u.wilker || '-')}</td>
        <td>
          ${u.role === 'admin'
            ? '<em>-</em>'
            : `<button onclick="hapusUser(${u.id})">Hapus</button>`
          }
        </td>
      `;
      tbody.appendChild(tr);
    });

  } catch (err) {
    console.error(err);
    tbody.innerHTML = '<tr><td colspan="5">Error JavaScript</td></tr>';
  }
}

/* ================= DELETE USER ================= */
async function hapusUser(id){
  if (!confirm('Hapus user ini?')) return;

  try {
    const res = await fetch('api/users_delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });

    const json = await res.json();

    if (!json.ok) {
      alert(json.message || 'Gagal menghapus user');
      return;
    }

    loadUsers();
  } catch (err) {
    alert('Terjadi kesalahan (JS)');
  }
}

loadUsers();
</script>
