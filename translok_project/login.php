<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
session_start();

if (isset($_SESSION['user'])) {
  header("Location: " . ($_SESSION['user']['role'] === 'admin' ? 'dashboard.php' : 'user.php'));
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nama     = trim($_POST['nama'] ?? '');
  $wilker   = trim($_POST['wilker'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');

  if ($username === '' || $password === '') {
    $error = 'Username dan password wajib diisi';
  } else {
    $isPersonalMode = ($nama !== '');
    $admin = null;
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE role='admin' AND username=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($res);

    if ($admin) {
      $passHash = $admin['password'] ?? '';
      $isPasswordValid = ($password === $passHash || md5($password) === $passHash);
      if (!$isPasswordValid) {
        $error = 'Password salah';
      } else {
        $_SESSION['user'] = [
          'id'       => $admin['id'],
          'nama'     => $admin['nama'],
          'username' => $admin['username'],
          'role'     => 'admin'
        ];
        header("Location: dashboard.php");
        exit;
      }
    } else {
      if ($wilker === '') {
        $error = 'Wilker wajib dipilih untuk user';
      } else {
        if ($isPersonalMode) {
          $stmt = mysqli_prepare(
            $conn,
            "SELECT * FROM users WHERE role='user' AND username=? AND wilker=? AND LOWER(nama)=LOWER(?) LIMIT 1"
          );
          mysqli_stmt_bind_param($stmt, 'sss', $username, $wilker, $nama);
        } else {
          $stmt = mysqli_prepare(
            $conn,
            "SELECT * FROM users WHERE role='user' AND username=? AND wilker=? AND TRIM(COALESCE(nama,''))='' LIMIT 1"
          );
          mysqli_stmt_bind_param($stmt, 'ss', $username, $wilker);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);

        if (!$user) {
          $error = 'Akun user tidak ditemukan';
        } else {
          $passHash = $user['password'] ?? '';
          $isPasswordValid = ($password === $passHash || md5($password) === $passHash);
          if (!$isPasswordValid) {
            $error = 'Password salah';
          } else {
            $_SESSION['user'] = [
              'id'       => $user['id'],
              'nama'     => $isPersonalMode ? $nama : '',
              'username' => $user['username'],
              'role'     => 'user',
              'wilker'   => $user['wilker'] ?: $wilker,
              'mode'     => $isPersonalMode ? 'personal' : 'wilker'
            ];
            header("Location: user.php");
            exit;
          }
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="shell">
  <div class="panel">

    <div class="brand">
      <div class="logo">TL</div>
      <div>
        <h1>Translok</h1>
        <p>Masuk untuk melanjutkan</p>
      </div>
    </div>

    <div class="card">
      <h2>Login</h2>

      <?php if ($error): ?>
        <div class="err"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post">

        <label class="field">
          Nama (opsional)
          <input type="text" name="nama" placeholder="Isi jika ingin lihat data sendiri">
        </label>

        <label class="field">
          Wilker (User)
          <select name="wilker" id="wilker">
            <option value="">Pilih Wilker</option>
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
        </label>

        <label class="field">
          Username
          <input type="text" name="username" id="username" required>
        </label>

        <label class="field">
          Password
          <input type="password" name="password" required>
        </label>

        <button type="submit">Login</button>
      </form>
    </div>

  </div>
</div>

<script>
const wilkerSelect = document.getElementById('wilker');
const usernameInp  = document.getElementById('username');

wilkerSelect.addEventListener('change', () => {
  if (wilkerSelect.value) {
    usernameInp.value = wilkerSelect.value;
  }
});
</script>

</body>
</html>
