<?php
include '../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if(!empty($data['nama_kegiatan']) && !empty($data['nominal'])){
    $nama = mysqli_real_escape_string($conn, $data['nama_kegiatan']);
    $nominal = mysqli_real_escape_string($conn, $data['nominal']);

    $query = "INSERT INTO ref_kegiatan (nama_kegiatan, nominal) VALUES ('$nama', '$nominal')";
    
    if(mysqli_query($conn, $query)){
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'message' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['ok' => false, 'message' => 'Data tidak lengkap']);
}