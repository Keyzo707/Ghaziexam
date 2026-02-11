<?php
session_start();
include 'koneksi.php';
if (isset($_SESSION['siswa_id'])) {
    $id = $_SESSION['siswa_id'];
    mysqli_query($conn, "UPDATE siswa SET last_ping = NOW(), is_online = 1 WHERE id = '$id'");
}
?>