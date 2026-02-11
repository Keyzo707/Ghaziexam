<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login_admin.php"); exit; }

$mk_id = isset($_GET['mk_id']) ? $_GET['mk_id'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bank Soal - CBT</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .container { background: white; padding: 20px; border-radius: 8px; }
        .filter-box { margin-bottom: 20px; padding: 10px; background: #e9ecef; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #2c3e50; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h2>Bank Soal Per Mata Kuliah</h2>
    
    <div class="filter-box">
        <form method="GET">
            <label>Filter Mata Kuliah: </label>
            <select name="mk_id" onchange="this.form.submit()">
                <option value="">-- Pilih Mata Kuliah --</option>
                <?php
                $mk_list = mysqli_query($conn, "SELECT * FROM matakuliah");
                while($m = mysqli_fetch_assoc($mk_list)) {
                    $sel = ($mk_id == $m['id']) ? 'selected' : '';
                    echo "<option value='".$m['id']."' $sel>".$m['nama_mk']."</option>";
                }
                ?>
            </select>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Pertanyaan</th>
                <th>Tipe</th>
                <th>Media</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($mk_id != "") {
                $q_soal = mysqli_query($conn, "SELECT * FROM soal WHERE matakuliah_id = '$mk_id'");
                $no = 1;
                while($s = mysqli_fetch_assoc($q_soal)) {
                    echo "<tr>
                        <td>".$no++."</td>
                        <td>".substr($s['pertanyaan'], 0, 50)."...</td>
                        <td>".$s['tipe']."</td>
                        <td>".($s['media'] ? '✅' : '❌')."</td>
                        <td><a href='edit_soal.php?id=".$s['id']."'>Edit</a></td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='5' style='text-align:center;'>Silakan pilih mata kuliah untuk melihat soal</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <br>
    <a href="admin.php">Kembali ke Dashboard</a>
</div>
</body>
</html>