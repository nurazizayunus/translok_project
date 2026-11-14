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
<title>Manajemen User | Translok</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/users.css">
</head>

<body class="users-page">

<div class="sidebar">
  <div class="logo">
    REKAPITULASI<br>TRANLOK
  </div>

  <ul class="wilker">
    <li onclick="window.location.href='dashboard.php'">Dashboard</li>
    <li class="active">Manajemen User</li>
    <li class="logout" onclick="logout()">Logout</li>
  </ul>
</div>

<div class="content">
  <div class="header">
    <div>
      <h2>Manajemen User</h2>
      <small>Kelola akun admin dan user</small>
    </div>
    <span id="userLabel"><?= $_SESSION['user']['username']; ?> (Admin)</span>
  </div>

  <div class="card" id="userManagement">
    <h3>Data User</h3>

    <div class="user-grid">
      <form id="userForm" class="user-form">
        <div class="grid">
          <div>
            <label>Nama (Opsional untuk User Wilker)</label>
            <input type="text" id="userNama">
          </div>
          <div>
            <label>Username</label>
            <input type="text" id="userUsername" required>
          </div>
          <div>
            <label>Role</label>
            <select id="userRole">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>

        <details class="user-details">
          <summary>Detail tambahan (Wilker &amp; Password)</summary>
          <div class="grid">
            <div>
              <label>Password</label>
              <input type="password" id="userPassword" placeholder="Isi untuk user baru">
            </div>
            <div>
              <label>Wilker</label>
              <select id="userWilker">
                <option value="">(Kosongkan untuk admin)</option>
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
            <div class="helper">
              <small>Untuk role user, username otomatis sama dengan wilker. Isi nama = mode personal, kosongkan nama = lihat semua data wilker.</small>
            </div>
          </div>
        </details>

        <div class="form-actions">
          <button id="btnUserSubmit" type="submit">Simpan</button>
          <button id="btnCreateWilkerAccount" type="button">Buat Akun Semua Wilker</button>
          <button id="btnUserCancel" type="button" class="btn-secondary">Batal</button>
        </div>
      </form>

      <div class="table-wrap">
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
          <tbody id="userBody">
            <tr>
              <td colspan="5">Memuat data...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function escapeHtml(text){
  const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'};
  return String(text).replace(/[&<>"']/g, (m)=>map[m]);
}

async function apiFetch(url, options){
  const response = await fetch(url, options);
  const payload = await response.json();
  if(!response.ok || !payload.ok){
    throw new Error(payload.message || 'Gagal memproses data.');
  }
  return payload.data;
}

function logout(){
  window.location.href = "logout.php";
}

let userMode = 'create';
let editUserId = null;
let usersCache = [];

const userForm = document.getElementById('userForm');
const userNama = document.getElementById('userNama');
const userUsername = document.getElementById('userUsername');
const userPassword = document.getElementById('userPassword');
const userRole = document.getElementById('userRole');
const userWilker = document.getElementById('userWilker');
const userBody = document.getElementById('userBody');
const btnUserSubmit = document.getElementById('btnUserSubmit');
const btnCreateWilkerAccount = document.getElementById('btnCreateWilkerAccount');
const btnUserCancel = document.getElementById('btnUserCancel');
const userDetails = document.querySelector('.user-details');

function renderUsers(){
  userBody.innerHTML = '';
  if(usersCache.length === 0){
    userBody.innerHTML = '<tr><td colspan="5">Belum ada user</td></tr>';
    return;
  }

  usersCache.forEach(function(u){
    const row = document.createElement('tr');
    const roleTag = u.role === 'admin' ? 'Admin' : 'User';
    const showNama = (u.role === 'user' && !String(u.nama || '').trim())
      ? '(Semua Wilker)'
      : (u.nama || '-');
    row.innerHTML =
      '<td>' + escapeHtml(showNama) + '</td>' +
      '<td>' + escapeHtml(u.username) + '</td>' +
      '<td><span class="role-badge ' + escapeHtml(u.role) + '">' + roleTag + '</span></td>' +
      '<td>' + escapeHtml(u.wilker || '-') + '</td>' +
      '<td>' +
        '<button class="btn-small btn-edit" data-id="' + u.id + '">Edit</button>' +
        '<button class="btn-small btn-reset" data-id="' + u.id + '">Reset</button>' +
        '<button class="btn-small btn-danger" data-id="' + u.id + '">Hapus</button>' +
      '</td>';
    userBody.appendChild(row);
  });
}

async function loadUsers(){
  try{
    usersCache = await apiFetch('api/users_list.php');
    renderUsers();
  } catch(err){
    userBody.innerHTML = '<tr><td colspan="5">Gagal memuat user</td></tr>';
    alert(err.message);
  }
}

function resetUserForm(){
  userMode = 'create';
  editUserId = null;
  userForm.reset();
  userUsername.readOnly = false;
  btnUserSubmit.innerText = 'Simpan';
  syncWilkerState();
}

async function createUser(){
  const payload = {
    nama: userNama.value.trim(),
    username: userUsername.value.trim(),
    password: userPassword.value,
    role: userRole.value,
    wilker: userWilker.value
  };

  if(!payload.password){
    alert('Password wajib diisi.');
    return;
  }
  if(payload.role === 'admin' && !payload.username){
    alert('Username wajib diisi untuk role admin.');
    return;
  }
  if(payload.role === 'admin' && !payload.nama){
    alert('Nama wajib diisi untuk role admin.');
    return;
  }
  if(payload.role === 'user' && !payload.wilker){
    alert('Wilker wajib diisi untuk role user.');
    return;
  }

  await apiFetch('api/users_create.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
}

async function createWilkerAccountQuick(){
  const payload = {
    nama: '',
    username: '',
    password: userPassword.value,
    role: 'user',
    wilker: userWilker.value
  };

  if(!payload.wilker){
    alert('Pilih wilker terlebih dahulu.');
    return;
  }
  if(!payload.password){
    alert('Isi password untuk akun semua wilker.');
    return;
  }

  await apiFetch('api/users_create.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
}

async function updateUser(){
  const payload = {
    id: editUserId,
    nama: userNama.value.trim(),
    username: userUsername.value.trim(),
    role: userRole.value,
    wilker: userWilker.value
  };

  if(!payload.id){
    alert('Data user tidak valid.');
    return;
  }
  if(payload.role === 'admin' && !payload.username){
    alert('Username wajib diisi untuk role admin.');
    return;
  }
  if(payload.role === 'admin' && !payload.nama){
    alert('Nama wajib diisi untuk role admin.');
    return;
  }
  if(payload.role === 'user' && !payload.wilker){
    alert('Wilker wajib diisi untuk role user.');
    return;
  }

  await apiFetch('api/users_update.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
}

function syncWilkerState(){
  const isAdmin = userRole.value === 'admin';
  userWilker.disabled = isAdmin;
  if(isAdmin){
    userWilker.value = '';
    if(userMode === 'create'){
      userUsername.value = '';
    }
    userUsername.readOnly = (userMode === 'edit');
  } else {
    userUsername.value = userWilker.value;
    userUsername.readOnly = true;
  }
  userNama.required = isAdmin;
}

async function resetPassword(userId){
  const newPass = prompt('Masukkan password baru');
  if(!newPass){
    return;
  }
  await apiFetch('api/users_reset_password.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: userId, password: newPass })
  });
  alert('Password berhasil direset.');
}

async function deleteUser(userId){
  await apiFetch('api/users_delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: userId })
  });
}

userForm.addEventListener('submit', async function(e){
  e.preventDefault();
  try{
    if(userMode === 'create'){
      await createUser();
    } else {
      await updateUser();
    }
    resetUserForm();
    loadUsers();
  } catch (err){
    alert(err.message);
  }
});

btnUserCancel.addEventListener('click', resetUserForm);
userRole.addEventListener('change', syncWilkerState);
userWilker.addEventListener('change', syncWilkerState);
btnCreateWilkerAccount.addEventListener('click', async function(){
  userRole.value = 'user';
  userNama.value = '';
  syncWilkerState();

  try{
    await createWilkerAccountQuick();
    resetUserForm();
    loadUsers();
    alert('Akun semua wilker berhasil dibuat.');
  } catch (err){
    alert(err.message);
  }
});

userBody.addEventListener('click', async function(e){
  const target = e.target;
  if(target.classList.contains('btn-edit')){
    const id = Number(target.getAttribute('data-id'));
    const found = usersCache.find(u => Number(u.id) === id);
    if(!found){
      return;
    }
    userMode = 'edit';
    editUserId = id;
    userNama.value = found.nama || '';
    userUsername.value = found.username || '';
    userUsername.readOnly = true;
    userRole.value = found.role || 'user';
    userWilker.value = found.wilker || '';
    userPassword.value = '';
    btnUserSubmit.innerText = 'Update';
    syncWilkerState();
    if(userDetails && !userDetails.open){
      userDetails.open = true;
    }
    window.scrollTo({ top: userForm.offsetTop - 80, behavior: 'smooth' });
  }

  if(target.classList.contains('btn-reset')){
    const id = Number(target.getAttribute('data-id'));
    try{
      await resetPassword(id);
    } catch (err){
      alert(err.message);
    }
  }

  if(target.classList.contains('btn-danger')){
    const id = Number(target.getAttribute('data-id'));
    try{
      await deleteUser(id);
      loadUsers();
    } catch (err){
      alert(err.message);
    }
  }
});

loadUsers();
syncWilkerState();
</script>

</body>
</html>
