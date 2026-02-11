<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin.php");
    exit;
}

// --- LOGIKA TAMBAH SISWA ---
if (isset($_POST['tambah_siswa'])) {
    $nim   = mysqli_real_escape_string($conn, $_POST['nim']);
    $nama  = mysqli_real_escape_string($conn, $_POST['nama']);
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
    $pass  = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Cek NIM kembar
    $cek = mysqli_query($conn, "SELECT id FROM siswa WHERE nim = '$nim'");
    if(mysqli_num_rows($cek) > 0){
        echo "<script>alert('Gagal! NIM sudah terdaftar.');</script>";
    } else {
        $q = "INSERT INTO siswa (nim, nama_lengkap, kelas, password) VALUES ('$nim', '$nama', '$kelas', '$pass')";
        if(mysqli_query($conn, $q)) {
            echo "<script>alert('Siswa berhasil ditambahkan'); window.location='manajemen_siswa.php';</script>";
        }
    }
}

// --- LOGIKA HAPUS SISWA ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Hapus log ujiannya dulu agar bersih
    mysqli_query($conn, "DELETE FROM log_ujian WHERE siswa_id='$id'");
    // Baru hapus siswanya
    mysqli_query($conn, "DELETE FROM siswa WHERE id='$id'");
    echo "<script>window.location='manajemen_siswa.php';</script>";
}

// --- LOGIKA RESET UJIAN (PENTING) ---
// Digunakan jika siswa disconnect dan waktu habis padahal belum selesai
if (isset($_GET['reset_ujian'])) {
    $id = $_GET['reset_ujian'];
    // 1. Set waktu mulai jadi NULL & Offline
    mysqli_query($conn, "UPDATE siswa SET waktu_mulai_ujian = NULL, is_online = 0, last_ip = NULL WHERE id='$id'");
    // 2. Hapus jawaban sebelumnya (Opsional: kalau mau ulang dari 0)
    mysqli_query($conn, "DELETE FROM log_ujian WHERE siswa_id='$id'");
    
    echo "<script>alert('Ujian siswa berhasil di-reset! Siswa bisa login ulang.'); window.location='manajemen_siswa.php';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Data Mahasiswa / Peserta</h2>
        <a href="admin.php" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">Tambah Peserta Baru</div>
        <div class="card-body">
            <form method="POST" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="nim" class="form-control" placeholder="NIM (Username)" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="kelas" class="form-control" placeholder="Kelas" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="col-md-1">
                    <button type="submit" name="tambah_siswa" class="btn btn-success w-100">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>NIM</th>
                        <th>Nama Lengkap</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $sql = mysqli_query($conn, "SELECT * FROM siswa ORDER BY kelas ASC, nama_lengkap ASC");
                    while($row = mysqli_fetch_assoc($sql)):
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= $row['nim'] ?></strong></td>
                        <td><?= $row['nama_lengkap'] ?></td>
                        <td><?= $row['kelas'] ?></td>
                        <td>
                            <?php if($row['is_online'] == 1): ?>
                                <span class="badge bg-success">Online</span>
                            <?php elseif($row['waktu_mulai_ujian'] != NULL): ?>
                                <span class="badge bg-warning text-dark">Sedang Mengerjakan</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Belum Mulai</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="manajemen_siswa.php?reset_ujian=<?= $row['id'] ?>" 
                               class="btn btn-sm btn-warning" 
                               onclick="return confirm('PERINGATAN: Jawaban siswa ini akan dihapus dan waktu di-reset. Lanjutkan?')">
                               Reset Ujian
                            </a>
                            <a href="manajemen_siswa.php?hapus=<?= $row['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Hapus siswa ini secara permanen?')">
                               Hapus
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>