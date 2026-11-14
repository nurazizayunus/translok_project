<?php
include '../config.php'; 
header('Content-Type: application/json');

// Menggunakan $conn sesuai config.php Anda
$query = "SELECT * FROM ref_kegiatan ORDER BY nama_kegiatan ASC";
$result = mysqli_query($conn, $query);

if ($result) {
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if (($row['nama_kegiatan'] ?? '') === 'Lay pengendalian faktor risiko penyakit dapa situasi khusus (PNBP)') {
            $row['nominal'] = 338000;
        }
        $data[] = $row;
    }
    echo json_encode(['ok' => true, 'data' => $data]);
} else {
    echo json_encode(['ok' => false, 'message' => mysqli_error($conn)]);
}
