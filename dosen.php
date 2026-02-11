<?php
session_start();
include 'koneksi.php';

// Fitur Monitoring: Ambil siswa yang sedang ujian
$query_monitor = mysqli_query($conn, "SELECT nama_lengkap, kelas, is_online, last_ping, waktu_mulai_ujian 
                                     FROM siswa WHERE waktu_mulai_ujian IS NOT NULL 
                                     ORDER BY last_ping DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Dosen - Ghaziexam</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">Ghaziexam Admin</a>
        <div class="d-flex">
            <a href="kelola_soal.php" class="btn btn-outline-light me-2">Kelola Soal</a>
            <a href="logout.php" class="btn btn-danger">Keluar</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">Atur Ujian Aktif</div>
                <div class="card-body">
                    <form action="update_pengaturan.php" method="POST">
                        <label>Mata Kuliah Aktif (ID):</label>
                        <input type="number" name="mk_id" class="form-control mb-2" required>
                        <label>Durasi (Menit):</label>
                        <input type="number" name="durasi" class="form-control mb-3" required>
                        <button class="btn btn-primary w-100">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">Monitoring Siswa Real-time</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nama Siswa</th>
                                <th>Status</th>
                                <th>Mulai Ujian</th>
                                <th>Aktivitas Terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($s = mysqli_fetch_assoc($query_monitor)): ?>
                            <tr>
                                <td><?= $s['nama_lengkap'] ?> <br><small class="text-muted"><?= $s['kelas'] ?></small></td>
                                <td>
                                    <span class="badge <?= $s['is_online'] ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $s['is_online'] ? 'Online' : 'Offline' ?>
                                    </span>
                                </td>
                                <td><?= $s['waktu_mulai_ujian'] ?></td>
                                <td><?= $s['last_ping'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>