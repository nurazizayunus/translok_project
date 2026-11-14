<?php
$conn = mysqli_connect('localhost', 'root', '', 'rekapitulasi_translok');

if (!$conn) {
  die('Koneksi database gagal');
}

