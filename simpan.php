<?php
session_start();
include 'koneksi.php';

// ==========================================
// 1. LOGIKA UNTUK SISWA (SIMPAN JAWABAN VIA AJAX)
// ==========================================
if (isset($_POST['soal_id']) && isset($_POST['jawaban'])) {
    
    // Cek Login Siswa
    if (!isset($_SESSION['siswa_id'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Sesi habis']);
        exit;
    }

    $siswa_id = $_SESSION['siswa_id'];
    $soal_id  = $_POST['soal_id'];
    $jawaban  = $_POST['jawaban'];

    // Cek apakah sudah ada jawaban sebelumnya untuk soal ini?
    $cek = mysqli_query($conn, "SELECT id FROM log_ujian WHERE siswa_id='$siswa_id' AND soal_id='$soal_id'");

    if (mysqli_num_rows($cek) > 0) {
        // Jika sudah ada, UPDATE
        $query = "UPDATE log_ujian SET jawaban_siswa='$jawaban', waktu_simpan=NOW() WHERE siswa_id='$siswa_id' AND soal_id='$soal_id'";
    } else {
        // Jika belum ada, INSERT (BARU)
        $query = "INSERT INTO log_ujian (siswa_id, soal_id, jawaban_siswa, waktu_simpan) VALUES ('$siswa_id', '$soal_id', '$jawaban', NOW())";
    }

    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => mysqli_error($conn)]);
    }
    exit; // Stop agar tidak lanjut ke logika admin
}


// ==========================================
// 2. LOGIKA UNTUK ADMIN (SETTING UJIAN)
// ==========================================
if (isset($_POST['simpan_pengaturan'])) {
    
    // Cek Login Admin
    if (!isset($_SESSION['admin_logged_in'])) {
        header("Location: login_admin.php");
        exit;
    }

    $mk_id  = $_POST['mk_aktif_id'];
    $durasi = $_POST['durasi_menit'];

    // Cek apakah data pengaturan sudah ada (ID=1)
    $cek = mysqli_query($conn, "SELECT id FROM pengaturan WHERE id = 1");
    
    if (mysqli_num_rows($cek) > 0) {
        // Update
        $query = "UPDATE pengaturan SET mk_aktif_id = '$mk_id', durasi_menit = '$durasi' WHERE id = 1";
    } else {
        // Insert
        $query = "INSERT INTO pengaturan (id, mk_aktif_id, durasi_menit) VALUES (1, '$mk_id', '$durasi')";
    }

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Pengaturan Ujian Berhasil Disimpan!'); window.location='admin.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan: " . mysqli_error($conn) . "'); window.location='simpan.php';</script>";
    }
    exit;
}

// ==========================================
// 3. TAMPILAN HALAMAN ADMIN (FORM SETTING)
// ==========================================
// Bagian ini hanya muncul jika Admin membuka simpan.php di browser
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin.php");
    exit;
}

// Ambil data saat ini untuk ditampilkan di form
$q_set = mysqli_query($conn, "SELECT * FROM pengaturan WHERE id = 1");
$current = mysqli_fetch_assoc($q_set);
$cur_mk = isset($current['mk_aktif_id']) ? $current['mk_aktif_id'] : '';
$cur_durasi = isset($current['durasi_menit']) ? $current['durasi_menit'] : 60;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Setting Ujian - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Konfigurasi Ujian Aktif</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Mata Kuliah Ujian</label>
                            <select name="mk_aktif_id" class="form-select" required>
                                <option value="">-- Pilih Mata Kuliah --</option>
                                <?php
                                $q_mk = mysqli_query($conn, "SELECT * FROM matakuliah ORDER BY nama_mk ASC");
                                while($row = mysqli_fetch_assoc($q_mk)):
                                    $selected = ($row['id'] == $cur_mk) ? 'selected' : '';
                                ?>
                                    <option value="<?= $row['id'] ?>" <?= $selected ?>>
                                        <?= $row['nama_mk'] ?> (<?= $row['kode_mk'] ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Durasi Pengerjaan (Menit)</label>
                            <input type="number" name="durasi_menit" class="form-control" value="<?= $cur_durasi ?>" min="1" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="simpan_pengaturan" class="btn btn-success">SIMPAN & AKTIFKAN</button>
                            <a href="admin.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>