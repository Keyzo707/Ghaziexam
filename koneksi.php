<?php
// Pengaturan Database
$host = "localhost";
$user = "root"; 
$pass = "root"; // Password default UwAmp
$db   = "ujian_db";

// Melakukan Koneksi
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek Koneksi
if (!$conn) {
    // Jika gagal, kirim pesan error dalam format JSON agar tidak memutus alur JavaScript
    header('Content-Type: application/json');
    die(json_encode([
        "error" => "Gagal konek database: " . mysqli_connect_error()
    ]));
}

// Set charset ke utf8 agar karakter khusus dalam soal (misal: simbol biologi) tampil benar
mysqli_set_charset($conn, "utf8");
?>