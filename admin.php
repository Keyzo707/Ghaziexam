<?php
session_start();
include 'koneksi.php';

// Cek sesi admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin.php");
    exit;
}

// 1. Hitung Statistik Cepat
$jml_siswa = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM siswa"));
$jml_soal  = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM soal"));
$jml_mk    = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM matakuliah"));

// 2. Ambil Info Ujian yang Sedang Aktif
$query_aktif = mysqli_query($conn, "SELECT p.*, m.nama_mk, m.kode_mk 
                                    FROM pengaturan p 
                                    JOIN matakuliah m ON p.mk_aktif_id = m.id 
                                    WHERE p.id = 1");
$ujian_aktif = mysqli_fetch_assoc($query_aktif);

// 3. Reset Status Login (Fitur Darurat)
if (isset($_GET['reset_all_login'])) {
    mysqli_query($conn, "UPDATE siswa SET is_online = 0, last_ip = NULL");
    echo "<script>alert('Semua status login siswa berhasil di-reset!'); window.location='admin.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - CBT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            min-height: 100vh;
            background: #212529;
            color: white;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
            transition: 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #0d6efd;
            color: white;
        }
        .stat-card {
            border: none;
            border-radius: 10px;
            color: white;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3 d-none d-md-block" style="width: 250px; flex-shrink: 0;">
        <h4 class="text-center mb-4 border-bottom pb-3">Ghaziexam</h4>
        <a href="admin.php" class="active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
        <a href="manajemen_siswa.php"><i class="bi bi-people me-2"></i> Data Siswa</a>
        <a href="tambah_mk.php"><i class="bi bi-book me-2"></i> Mata Kuliah</a>
        <a href="kelola_soal.php"><i class="bi bi-file-earmark-text me-2"></i> Bank Soal</a>
        <a href="rekap_nilai.php"><i class="bi bi-trophy me-2"></i> Hasil Ujian</a>
        <a href="simpan.php" target="_blank"><i class="bi bi-gear me-2"></i> Setting Ujian</a>
        <div class="mt-5 border-top pt-3">
            <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
        </div>
    </div>

    <div class="flex-grow-1 p-4">
        <div class="d-md-none mb-3">
            <a href="manajemen_siswa.php" class="btn btn-sm btn-outline-dark">Menu Siswa</a>
            <a href="rekap_nilai.php" class="btn btn-sm btn-outline-dark">Menu Nilai</a>
            <a href="logout.php" class="btn btn-sm btn-danger float-end">Logout</a>
        </div>

        <h2 class="mb-4">Dashboard Administrator</h2>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stat-card bg-primary p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $jml_siswa; ?></h3>
                            <small>Total Siswa Terdaftar</small>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-success p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $jml_soal; ?></h3>
                            <small>Total Soal di Bank</small>
                        </div>
                        <i class="bi bi-collection fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-warning text-dark p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $jml_mk; ?></h3>
                            <small>Mata Kuliah</small>
                        </div>
                        <i class="bi bi-book fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Status Ujian Aktif</h5>
                <a href="simpan.php" class="btn btn-sm btn-light">Ubah Pengaturan</a>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="text-primary"><?php echo $ujian_aktif['nama_mk']; ?> <span class="badge bg-secondary fs-6"><?php echo $ujian_aktif['kode_mk']; ?></span></h4>
                        <p class="mb-1"><strong>Durasi:</strong> <?php echo $ujian_aktif['durasi_menit']; ?> Menit</p>
                        <p class="text-muted"><small>Pastikan siswa memilih mata kuliah ini saat login/ujian.</small></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="admin.php?reset_all_login=true" class="btn btn-outline-danger" onclick="return confirm('Ini akan mengeluarkan (logout) paksa semua siswa yang sedang online. Lanjutkan?')">
                            <i class="bi bi-exclamation-triangle"></i> Reset Semua Login
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>