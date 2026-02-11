<?php
include 'koneksi.php';
$mk_id = $_POST['mk_id'];
$durasi = $_POST['durasi'];

$query = "UPDATE pengaturan SET mk_aktif_id = '$mk_id', durasi_menit = '$durasi' WHERE id = 1";
mysqli_query($conn, $query);

header("Location: admin.php");
?>