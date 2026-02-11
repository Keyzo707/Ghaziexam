<?php
session_start();
include 'koneksi.php';

if (isset($_POST['simpan'])) {
    $mk_id = $_POST['mk_id'];
    $durasi = $_POST['durasi'];
    mysqli_query($conn, "UPDATE pengaturan SET mk_aktif_id = '$mk_id', durasi_menit = '$durasi' WHERE id = 1");
    $msg = "Pengaturan berhasil diperbarui!";
}

$set = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan WHERE id = 1"));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pengaturan Ujian - Ghaziexam</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 40px; background: #f4f4f4; }
        .box { background: white; max-width: 500px; margin: auto; padding: 30px; border-radius: 8px; shadow: 0 4px 6px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 10px; margin: 10px 0 20px; box-sizing: border-box; }
        button { background: #3498db; color: white; border: none; padding: 12px; width: 100%; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Konfigurasi Ujian</h2>
        <?php if(isset($msg)) echo "<p style='color:green;'>$msg</p>"; ?>
        <form method="POST">
            <label>Pilih Mata Kuliah yang Diujikan:</label>
            <select name="mk_id">
                <?php
                $mks = mysqli_query($conn, "SELECT * FROM matakuliah");
                while($m = mysqli_fetch_assoc($mks)) {
                    $s = ($m['id'] == $set['mk_aktif_id']) ? 'selected' : '';
                    echo "<option value='".$m['id']."' $s>".$m['nama_mk']."</option>";
                }
                ?>
            </select>

            <label>Durasi Ujian (Menit):</label>
            <input type="number" name="durasi" value="<?php echo $set['durasi_menit']; ?>">

            <button type="submit" name="simpan">Aktifkan Ujian</button>
        </form>
        <br>
        <a href="admin.php">← Kembali</a>
    </div>
</body>
</html>