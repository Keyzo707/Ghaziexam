<?php
session_start();
include 'koneksi.php';

if (isset($_SESSION['siswa_id'])) {
    $siswa_id = $_SESSION['siswa_id'];
    
    // Jika soal_id dikirim lewat URL (misal: laporkan_pelanggaran.php?soal_id=5)
    $soal_id = isset($_GET['soal_id']) ? (int)$_GET['soal_id'] : 0;

    if ($soal_id > 0) {
        // UPDATE: Menambah 1 dari nilai yang sudah ada di database
        $query = "UPDATE log_ujian SET pelanggaran = pelanggaran + 1 
                  WHERE siswa_id = '$siswa_id' AND soal_id = '$soal_id'";
        mysqli_query($conn, $query);
    } else {
        // Jika soal_id tidak spesifik (misal saat ESC atau pindah tab umum)
        // Kita update di soal yang paling terakhir diakses mahasiswa tersebut
        $query = "UPDATE log_ujian SET pelanggaran = pelanggaran + 1 
                  WHERE siswa_id = '$siswa_id' 
                  ORDER BY id DESC LIMIT 1";
        mysqli_query($conn, $query);
    }
}
?>