<?php
session_start();
include 'koneksi.php';

// Proteksi Admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin.php"); exit;
}

// Logika Tambah Mata Kuliah
if (isset($_POST['tambah_mk'])) {
    $nama_mk = mysqli_real_escape_string($conn, $_POST['nama_mk']);
    $kode_mk = mysqli_real_escape_string($conn, $_POST['kode_mk']);
    
    $query = "INSERT INTO matakuliah (nama_mk, kode_mk) VALUES ('$nama_mk', '$kode_mk')";
    mysqli_query($conn, $query);
}

// Logika Hapus Mata Kuliah
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM matakuliah WHERE id = '$id'");
    header("Location: tambah_mk.php");
}

$mk_list = mysqli_query($conn, "SELECT * FROM matakuliah");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Mata Kuliah</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .card { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        input { width: 100%; padding: 8px; margin: 5px 0; box-sizing: border-box; }
        .btn { background: #3498db; color: white; padding: 10px; border: none; cursor: pointer; width: 100%; }
        .btn-danger { color: red; text-decoration: none; font-size: 12px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Daftar Mata Kuliah</h2>
        <form method="POST">
            <input type="text" name="nama_mk" placeholder="Nama Mata Kuliah (ex: Ornithologi)" required>
            <input type="text" name="kode_mk" placeholder="Kode MK (ex: BIO01)" required>
            <button type="submit" name="tambah_mk" class="btn">Tambah Mata Kuliah</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Mata Kuliah</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($mk_list)): ?>
                <tr>
                    <td><?php echo $row['kode_mk']; ?></td>
                    <td><?php echo $row['nama_mk']; ?></td>
                    <td>
                        <a href="tambah_mk.php?hapus=<?php echo $row['id']; ?>" class="btn-danger" onclick="return confirm('Hapus mata kuliah ini?')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <br>
        <a href="admin.php">Kembali ke Dashboard</a>
    </div>
</body>
</html>