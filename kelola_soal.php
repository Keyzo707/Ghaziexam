<?php
session_start();
include 'koneksi.php';

// Proteksi Admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin.php"); exit;
}

// Logika Hapus Soal
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    // Ambil nama file media agar bisa dihapus juga dari folder uploads
    $cari_file = mysqli_query($conn, "SELECT media FROM soal WHERE id = '$id'");
    $data_file = mysqli_fetch_assoc($cari_file);
    if (!empty($data_file['media'])) {
        unlink("uploads/" . $data_file['media']);
    }
    
    mysqli_query($conn, "DELETE FROM soal WHERE id = '$id'");
    echo "<script>alert('Soal berhasil dihapus!'); window.location.href='kelola_soal.php';</script>";
}

$mk_filter = isset($_GET['mk_id']) ? $_GET['mk_id'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Bank Soal - CBT UNY</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f4f4f4; }
        .container { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; font-size: 14px; }
        th { background: #2c3e50; color: white; }
        .btn-add { background: #27ae60; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; }
        .btn-del { color: #e74c3c; text-decoration: none; font-weight: bold; }
        .badge { padding: 3px 7px; border-radius: 4px; font-size: 11px; color: white; }
        .bg-blue { background: #3498db; }
        .bg-orange { background: #f39c12; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Bank Soal Mata Kuliah</h2>
        <a href="tambah_soal.php" class="btn-add">+ Tambah Soal Baru</a>
    </div>

    <form method="GET" style="margin-bottom: 20px; background: #eee; padding: 15px; border-radius: 5px;">
        <label>Filter per Mata Kuliah: </label>
        <select name="mk_id" onchange="this.form.submit()" style="padding: 5px;">
            <option value="">-- Semua Mata Kuliah --</option>
            <?php
            $mk_list = mysqli_query($conn, "SELECT * FROM matakuliah");
            while($m = mysqli_fetch_assoc($mk_list)) {
                $selected = ($mk_filter == $m['id']) ? 'selected' : '';
                echo "<option value='".$m['id']."' $selected>".$m['nama_mk']."</option>";
            }
            ?>
        </select>
    </form>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Mata Kuliah</th>
                <th>Pertanyaan</th>
                <th>Tipe</th>
                <th>Media</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query_str = "SELECT s.*, m.nama_mk FROM soal s JOIN matakuliah m ON s.matakuliah_id = m.id";
            if ($mk_filter != "") {
                $query_str .= " WHERE s.matakuliah_id = '$mk_filter'";
            }
            $query_str .= " ORDER BY s.id DESC";
            
            $res = mysqli_query($conn, $query_str);
            $no = 1;
            while($row = mysqli_fetch_assoc($res)):
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><strong><?php echo $row['nama_mk']; ?></strong></td>
                <td><?php echo substr(htmlspecialchars($row['pertanyaan']), 0, 80); ?>...</td>
                <td>
                    <span class="badge <?php echo ($row['tipe'] == 'pilgan') ? 'bg-blue' : 'bg-orange'; ?>">
                        <?php echo strtoupper($row['tipe']); ?>
                    </span>
                </td>
                <td><?php echo (!empty($row['media'])) ? "✅ ada file" : "❌ -"; ?></td>
                <td>
                    <a href="kelola_soal.php?hapus=<?php echo $row['id']; ?>" class="btn-del" onclick="return confirm('Hapus soal ini secara permanen?')">Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <br>
    <a href="admin.php"> Kembali ke Dashboard</a>
</div>
</body>
</html>