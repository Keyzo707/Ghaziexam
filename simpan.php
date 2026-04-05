<?php
session_start();
include 'koneksi.php';

// ==========================================
// 1. LOGIKA UNTUK SISWA (SIMPAN JAWABAN VIA AJAX)
// ==========================================
if (isset($_POST['soal_id']) && isset($_POST['jawaban'])) {
    
    // ✅ CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Invalid CSRF token']);
        exit;
    }

    // Cek Login Siswa
    if (!isset($_SESSION['siswa_id'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Sesi habis']);
        exit;
    }

    // ✅ SQL Injection Protection - Integer Validation
    $siswa_id = intval($_SESSION['siswa_id']);
    $soal_id = intval($_POST['soal_id']);
    $jawaban = mysqli_real_escape_string($conn, $_POST['jawaban']);

    // ✅ SERVER-SIDE TIMER VALIDATION
    $q_ujian_time = mysqli_query($conn, "SELECT waktu_mulai, mk_id FROM ujian_mahasiswa WHERE siswa_id='$siswa_id' LIMIT 1");
    
    if (mysqli_num_rows($q_ujian_time) == 0) {
        echo json_encode(['status' => 'error', 'msg' => 'Data ujian tidak ditemukan']);
        exit;
    }

    $ujian_time = mysqli_fetch_assoc($q_ujian_time);
    $mk_id = intval($ujian_time['mk_id']);
    
    $q_durasi = mysqli_query($conn, "SELECT durasi_menit FROM pengaturan WHERE mk_aktif_id='$mk_id'");
    $durasi_data = mysqli_fetch_assoc($q_durasi);
    $durasi_menit = intval($durasi_data['durasi_menit']);
    
    $start_time = strtotime($ujian_time['waktu_mulai']);
    $end_time = $start_time + ($durasi_menit * 60);
    $current_time = time();
    $sisa_waktu = $end_time - $current_time;

    if ($sisa_waktu <= 0) {
        echo json_encode(['status' => 'error', 'msg' => 'Waktu ujian telah habis!']);
        mysqli_query($conn, "UPDATE ujian_mahasiswa SET status='selesai' WHERE siswa_id='$siswa_id' AND mk_id='$mk_id'");
        exit;
    }

    $cek = mysqli_query($conn, "SELECT id FROM log_ujian WHERE siswa_id='$siswa_id' AND soal_id='$soal_id'");

    if (mysqli_num_rows($cek) > 0) {
        $query = "UPDATE log_ujian SET jawaban_siswa='$jawaban', waktu_simpan=NOW() WHERE siswa_id='$siswa_id' AND soal_id='$soal_id'";
    } else {
        $query = "INSERT INTO log_ujian (siswa_id, soal_id, jawaban_siswa, waktu_simpan) VALUES ('$siswa_id', '$soal_id', '$jawaban', NOW())";
    }

    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success', 'sisa_waktu' => $sisa_waktu]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => mysqli_error($conn)]);
    }
    exit;
}

// ==========================================
// 2. LOGIKA UNTUK ADMIN (SETTING UJIAN)
// ==========================================
if (isset($_POST['simpan_pengaturan'])) {
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("<script>alert('Invalid CSRF token'); window.location='simpan.php';</script>");
    }

    if (!isset($_SESSION['admin_logged_in'])) {
        header("Location: login_admin.php");
        exit;
    }

    $mk_id = intval($_POST['mk_aktif_id']);
    $durasi = intval($_POST['durasi_menit']);

    if ($durasi < 1 || $durasi > 480) {
        die("<script>alert('Durasi harus antara 1-480 menit'); window.location='simpan.php';</script>");
    }

    $cek = mysqli_query($conn, "SELECT id FROM pengaturan WHERE id = 1");
    
    if (mysqli_num_rows($cek) > 0) {
        $query = "UPDATE pengaturan SET mk_aktif_id = '$mk_id', durasi_menit = '$durasi' WHERE id = 1";
    } else {
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
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
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
                            <input type="number" name="durasi_menit" class="form-control" value="<?= $cur_durasi ?>" min="1" max="480" required>
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