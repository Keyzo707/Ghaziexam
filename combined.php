UwAmp\www>copy *.php combined.php
admin.php
daftar_soal.php
dosen.php
edit_soal.php
get_soal.php
index.php
kelola_soal.php
koneksi.php
laporkan_pelanggaran.php
login_admin.php
logout.php
pengaturan_ujian.php
ping.php
simpan.php
tambah_mk.php
tambah_soal.php
ujian.php
ujian22.php
update_pengaturan.php


<?php
session_start();
// Proteksi Admin: Memastikan hanya admin yang login bisa akses
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin.php");
    exit;
}
include 'koneksi.php';

// 1. LOGIKA RESET JAWABAN & STATUS UJIAN
if (isset($_GET['reset_id'])) {
    $id_siswa = mysqli_real_escape_string($conn, $_GET['reset_id']);
    // Menghapus log jawaban dan meriset waktu mulai agar siswa bisa mengulang
    $query_reset_log = "DELETE FROM log_ujian WHERE siswa_id = '$id_siswa'";
    $query_reset_siswa = "UPDATE siswa SET waktu_mulai_ujian = NULL WHERE id = '$id_siswa'";
    
    mysqli_query($conn, $query_reset_log);
    if (mysqli_query($conn, $query_reset_siswa)) {
        echo "<script>alert('Data ujian siswa berhasil direset!'); window.location.href='admin.php';</script>";
    }
}

// 2. LOGIKA UPDATE UJIAN AKTIF
if (isset($_POST['update_aktif'])) {
    $mk_id = mysqli_real_escape_string($conn, $_POST['mk_aktif_id']);
    $cek_p = mysqli_query($conn, "SELECT * FROM pengaturan WHERE id = 1");
    if (mysqli_num_rows($cek_p) > 0) {
        mysqli_query($conn, "UPDATE pengaturan SET mk_aktif_id = '$mk_id' WHERE id = 1");
    } else {
        mysqli_query($conn, "INSERT INTO pengaturan (id, mk_aktif_id) VALUES (1, '$mk_id')");
    }
    echo "<script>alert('Mata kuliah aktif diperbarui!'); window.location.href='admin.php';</script>";
}

// 3. AMBIL STATUS UJIAN AKTIF SAAT INI
$q_aktif = mysqli_query($conn, "SELECT mk_aktif_id FROM pengaturan WHERE id = 1");
$r_aktif = mysqli_fetch_assoc($q_aktif);
$current_mk_id = $r_aktif ? $r_aktif['mk_aktif_id'] : 0;

// 4. QUERY MONITORING REAL-TIME
// Mengambil data progres, status online, dan total pelanggaran per mahasiswa
$sql = "SELECT 
            s.id, 
            s.nim, 
            s.nama_lengkap, 
            s.last_ping,
            TIMESTAMPDIFF(SECOND, s.last_ping, NOW()) as selisih_detik,
            (SELECT SUM(pelanggaran) FROM log_ujian WHERE siswa_id = s.id AND soal_id IN (SELECT id FROM soal WHERE matakuliah_id = '$current_mk_id')) as total_pelanggaran,
            (SELECT COUNT(*) FROM log_ujian WHERE siswa_id = s.id AND jawaban_siswa IS NOT NULL AND jawaban_siswa != '' AND soal_id IN (SELECT id FROM soal WHERE matakuliah_id = '$current_mk_id')) as total_jawab,
            (SELECT COUNT(*) FROM soal WHERE matakuliah_id = '$current_mk_id') as total_soal
        FROM siswa s 
        GROUP BY s.id ORDER BY s.last_ping DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Dosen - Ghaziexam</title>
    <meta http-equiv="refresh" content="10"> 
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background-color: #f0f2f5; margin: 0; color: #333; }
        .container { max-width: 1100px; margin: auto; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 25px; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        
        /* Form & Nav */
        select, button { padding: 12px; border-radius: 6px; border: 1px solid #ddd; font-size: 14px; }
        .btn-blue { background: #3498db; color: white; cursor: pointer; border: none; font-weight: bold; transition: 0.3s; }
        .btn-blue:hover { background: #2980b9; }
        .nav-link { text-decoration: none; padding: 8px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; }
        
        /* Monitoring Table */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background-color: #2c3e50; color: white; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: #f9f9f9; }
        
        /* Status & Progress */
        .dot { height: 12px; width: 12px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .online-dot { background-color: #2ecc71; box-shadow: 0 0 10px rgba(46, 204, 113, 0.6); }
        .offline-dot { background-color: #bdc3c7; }
        .badge-warn { background: #e74c3c; color: white; padding: 4px 8px; border-radius: 50px; font-size: 11px; font-weight: bold; display: inline-block; margin-top: 5px; }
        
        .progress-bg { width: 100%; background: #e9ecef; height: 10px; border-radius: 5px; margin-top: 8px; overflow: hidden; }
        .progress-fill { background: linear-gradient(90deg, #3498db, #2ecc71); height: 100%; transition: width 0.6s ease; }
        
        .btn-reset { background-color: #fff; color: #e74c3c; border: 1px solid #e74c3c; padding: 6px 14px; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: bold; transition: 0.3s; }
        .btn-reset:hover { background: #e74c3c; color: #fff; }
    </style>
</head>
<body>

<div class="container">
    <div class="card" style="border-left: 8px solid #3498db;">
        <div class="header-flex">
            <h3 style="margin:0;">Konfigurasi Ujian Aktif</h3>
            <a href="tambah_mk.php" class="nav-link" style="color: #3498db; border: 1px solid #3498db;">+ Kelola Mata Kuliah</a>
        </div>
        <form method="POST" style="display: flex; gap: 12px;">
            <select name="mk_aktif_id" style="flex-grow: 1;">
                <?php 
                $mk_list = mysqli_query($conn, "SELECT * FROM matakuliah");
                while($m = mysqli_fetch_assoc($mk_list)): 
                    $selected = ($m['id'] == $current_mk_id) ? 'selected' : '';
                ?>
                    <option value="<?php echo $m['id']; ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($m['nama_mk']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="update_aktif" class="btn-blue">Update Mata Kuliah Ujian</button>
        </form>
    </div>

    <div class="card">
        <div class="header-flex">
            <h2 style="margin:0;">Monitoring Mahasiswa</h2>
            <div style="display: flex; gap: 10px;">
                <a href="kelola_soal.php" class="nav-link" style="background: #f39c12; color: white;">Bank Soal</a>
                <a href="tambah_soal.php" class="nav-link" style="background: #27ae60; color: white;">+ Tambah Soal</a>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Identitas Mahasiswa</th>
                    <th>Koneksi & Keamanan</th>
                    <th>Progres Jawaban</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <?php 
                    // Siswa dianggap online jika ping terakhir < 15 detik
                    $is_online = ($row['selisih_detik'] !== null && $row['selisih_detik'] <= 15);
                    $total_p = $row['total_pelanggaran'] ?? 0;
                    $total_jawab = $row['total_jawab'];
                    $total_soal = $row['total_soal'];
                    $percent = ($total_soal > 0) ? round(($total_jawab / $total_soal) * 100) : 0;
                ?>
                <tr>
                    <td>
                        <strong style="color: #2c3e50;"><?php echo htmlspecialchars($row['nim']); ?></strong><br>
                        <span style="font-size: 14px; color: #7f8c8d;"><?php echo htmlspecialchars($row['nama_lengkap']); ?></span>
                    </td>
                    <td>
                        <?php if ($is_online): ?>
                            <span class="dot online-dot"></span> <small style="color: #2ecc71; font-weight:bold;">Online</small>
                        <?php else: ?>
                            <span class="dot offline-dot"></span> <small style="color: #95a5a6;">Offline</small>
                        <?php endif; ?>
                        
                        <?php if ($total_p > 0): ?>
                            <br><span class="badge-warn">⚠️ <?php echo $total_p; ?> Pelanggaran</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <small><?php echo $total_jawab; ?> / <?php echo $total_soal; ?> Soal</small>
                            <small style="font-weight: bold;"><?php echo $percent; ?>%</small>
                        </div>
                        <div class="progress-bg">
                            <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
                        </div>
                    </td>
                    <td>
                        <a href="admin.php?reset_id=<?php echo $row['id']; ?>" 
                           class="btn-reset" 
                           onclick="return confirm('Hapus semua jawaban dan reset waktu mulai mahasiswa ini?')">
                            Reset Ujian
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <p style="color: #7f8c8d; font-size: 12px;">Ghaziexam Monitoring Engine v2.0 | 2026</p>
        <a href="logout_admin.php" style="color: #e74c3c; text-decoration: none; font-size: 14px; font-weight: bold;">Logout Panel Admin</a>
    </div>
</div>

</body>
</html><?php
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
</html><?php
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
</html><?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login_admin.php"); exit; }

$id = mysqli_real_escape_string($conn, $_GET['id']);
$q = mysqli_query($conn, "SELECT * FROM soal WHERE id = '$id'");
$data = mysqli_fetch_assoc($q);

if (isset($_POST['update_soal'])) {
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $opsi_a = mysqli_real_escape_string($conn, $_POST['opsi_a']);
    $opsi_b = mysqli_real_escape_string($conn, $_POST['opsi_b']);
    $opsi_c = mysqli_real_escape_string($conn, $_POST['opsi_c']);
    $opsi_d = mysqli_real_escape_string($conn, $_POST['opsi_d']);
    $kunci = $_POST['kunci'];

    $sql = "UPDATE soal SET 
            pertanyaan='$pertanyaan', opsi_a='$opsi_a', opsi_b='$opsi_b', 
            opsi_c='$opsi_c', opsi_d='$opsi_d', kunci='$kunci' 
            WHERE id='$id'";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Soal berhasil diperbarui!'); window.location.href='kelola_soal.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Soal</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .card { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: auto; }
        input, textarea { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { background: #3498db; color: white; border: none; padding: 10px; cursor: pointer; width: 100%; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Edit Soal</h2>
        <form method="POST">
            <label>Pertanyaan:</label>
            <textarea name="pertanyaan" rows="4"><?php echo $data['pertanyaan']; ?></textarea>
            
            <label>Opsi A:</label><input type="text" name="opsi_a" value="<?php echo $data['opsi_a']; ?>">
            <label>Opsi B:</label><input type="text" name="opsi_b" value="<?php echo $data['opsi_b']; ?>">
            <label>Opsi C:</label><input type="text" name="opsi_c" value="<?php echo $data['opsi_c']; ?>">
            <label>Opsi D:</label><input type="text" name="opsi_d" value="<?php echo $data['opsi_d']; ?>">
            
            <label>Kunci Jawaban:</label>
            <select name="kunci" style="width: 100%; padding: 10px; margin: 10px 0;">
                <option value="A" <?php if($data['kunci']=='A') echo 'selected'; ?>>A</option>
                <option value="B" <?php if($data['kunci']=='B') echo 'selected'; ?>>B</option>
                <option value="C" <?php if($data['kunci']=='C') echo 'selected'; ?>>C</option>
                <option value="D" <?php if($data['kunci']=='D') echo 'selected'; ?>>D</option>
            </select>
            
            <button type="submit" name="update_soal">Simpan Perubahan</button>
        </form>
    </div>
</body>
</html><?php
session_start();
include 'koneksi.php';

// Set header agar outputnya dianggap JSON oleh browser
header('Content-Type: application/json');

// 1. Cek Sesi Siswa
if (!isset($_SESSION['siswa_id'])) { 
    echo json_encode(['error' => 'Sesi berakhir, silakan login ulang']); 
    exit;
}

$siswa_id = $_SESSION['siswa_id'];
$idx = isset($_GET['idx']) ? (int)$_GET['idx'] : 0;

// 2. Ambil Pengaturan Ujian (Mata Kuliah Aktif & Durasi)
$q_set = mysqli_query($conn, "SELECT mk_aktif_id, durasi_menit FROM pengaturan WHERE id = 1");
$r_set = mysqli_fetch_assoc($q_set);

if (!$r_set) {
    echo json_encode(['error' => 'Data pengaturan belum diisi di database']);
    exit;
}

$mk_id = $r_set['mk_aktif_id'];
$durasi_total_detik = (int)$r_set['durasi_menit'] * 60;

// 3. Ambil Data Siswa & Logika Proteksi Waktu Mulai
$q_siswa = mysqli_query($conn, "SELECT waktu_mulai_ujian FROM siswa WHERE id = '$siswa_id'");
$r_siswa = mysqli_fetch_assoc($q_siswa);

if (!$r_siswa) {
    echo json_encode(['error' => 'Data siswa tidak ditemukan']);
    exit;
}

// JIKA belum punya waktu mulai, BUAT BARU. JIKA sudah ada, JANGAN UPDATE LAGI.
if ($r_siswa['waktu_mulai_ujian'] == NULL || $r_siswa['waktu_mulai_ujian'] == '0000-00-00 00:00:00') {
    $waktu_sekarang_str = date('Y-m-d H:i:s');
    mysqli_query($conn, "UPDATE siswa SET waktu_mulai_ujian = '$waktu_sekarang_str' WHERE id = '$siswa_id'");
    
    $waktu_mulai = strtotime($waktu_sekarang_str);
    $sisa_detik = $durasi_total_detik;
} else {
    $waktu_mulai = strtotime($r_siswa['waktu_mulai_ujian']);
    $waktu_sekarang = time();
    $deadline = $waktu_mulai + $durasi_total_detik;
    $sisa_detik = $deadline - $waktu_sekarang;
}

// Pastikan sisa detik tidak negatif
if ($sisa_detik < 0) $sisa_detik = 0;

// 4. Ambil Daftar Semua ID Soal untuk Mata Kuliah Aktif
$all_soal = mysqli_query($conn, "SELECT id FROM soal WHERE matakuliah_id = '$mk_id' ORDER BY id ASC");
$all_ids = [];
while($r = mysqli_fetch_assoc($all_soal)) { 
    $all_ids[] = (int)$r['id']; 
}

// Proteksi jika bank soal kosong
if (empty($all_ids)) {
    echo json_encode(['error' => 'Belum ada soal untuk mata kuliah ini', 'soal' => null]);
    exit;
}

// 5. Ambil Detail Soal Berdasarkan Indeks (idx)
$soal_id = isset($all_ids[$idx]) ? $all_ids[$idx] : null;

if (!$soal_id) {
    echo json_encode(['error' => 'Indeks soal tidak ditemukan']);
    exit;
}

$q_detail = mysqli_query($conn, "SELECT * FROM soal WHERE id = '$soal_id'");
$soal = mysqli_fetch_assoc($q_detail);

// 6. Ambil Jawaban yang Sudah Pernah Disimpan (agar tidak hilang saat navigasi)
$cek_log = mysqli_query($conn, "SELECT jawaban_siswa FROM log_ujian WHERE siswa_id='$siswa_id' AND soal_id='$soal_id'");
$log = mysqli_fetch_assoc($cek_log);
$jawaban_sebelumnya = $log ? $log['jawaban_siswa'] : '';

// 7. Ambil Daftar ID Soal yang Sudah Dijawab (untuk indikator warna navigasi)
$ans_q = mysqli_query($conn, "SELECT soal_id FROM log_ujian WHERE siswa_id='$siswa_id' AND (jawaban_siswa IS NOT NULL AND jawaban_siswa != '')");
$answered_ids = [];
while($a = mysqli_fetch_assoc($ans_q)) { 
    $answered_ids[] = (int)$a['soal_id']; 
}

// 8. Kirim Output JSON ke Frontend (ujian.php)
echo json_encode([
    'soal' => $soal,
    'sisa_detik' => (int)$sisa_detik,
    'jawaban_sebelumnya' => $jawaban_sebelumnya,
    'total_soal' => count($all_ids),
    'all_ids' => $all_ids,
    'answered_ids' => $answered_ids
]);<?php
session_start();
include 'koneksi.php';

// 1. Cek apakah siswa sudah login? Kalau sudah, langsung lempar ke ujian
if (isset($_SESSION['siswa_id'])) {
    header("Location: ujian.php");
    exit;
}

$pesan_error = "";

// 2. Proses saat tombol Login ditekan
if (isset($_POST['tombol_login'])) {
    // Sanitasi input untuk mencegah SQL Injection sederhana
    $nim_input = mysqli_real_escape_string($conn, $_POST['username']);
    $password  = mysqli_real_escape_string($conn, $_POST['password']);

    // PERBAIKAN: Query menggunakan kolom 'nim' sesuai struktur DB baru
    $sql = "SELECT * FROM siswa WHERE nim='$nim_input' AND password='$password'";
    $query = mysqli_query($conn, $sql);

    // Cek apakah query berhasil dijalankan
    if ($query) {
        $cek = mysqli_num_rows($query);

        if ($cek > 0) {
            // Login Berhasil
            $data = mysqli_fetch_assoc($query);
            
            // Simpan data penting ke Session
            $_SESSION['siswa_id']    = $data['id'];
            $_SESSION['nama_siswa']  = $data['nama_lengkap'];
            $_SESSION['nim_siswa']   = $data['nim'];
            
            // FITUR TAMBAHAN: Catat IP Login untuk mencegah joki/login ganda
            $ip_address = $_SERVER['REMOTE_ADDR'];
            mysqli_query($conn, "UPDATE siswa SET last_ip = '$ip_address', is_online = 1 WHERE id = '".$data['id']."'");
            
            // Pindah ke halaman ujian
            header("Location: ujian.php");
            exit;
        } else {
            // Login Gagal
            $pesan_error = "NIM atau Password salah!";
        }
    } else {
        // Tampilkan pesan jika tabel atau kolom tidak ditemukan
        $pesan_error = "Kesalahan Sistem: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Ujian CBT - UNY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            width: 320px;
            text-align: center;
        }
        .login-box h2 {
            margin-bottom: 5px;
            color: #1a73e8;
        }
        .login-box p.subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 25px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #1a73e8;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
        }
        button:hover {
            background-color: #1557b0;
        }
        .error {
            background-color: #fce8e6;
            color: #d93025;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 15px;
            border: 1px solid #f5c2c7;
        }
    </style>
</head>
<body>

    <div class="login-box">
        <h2>CBT ONLINE</h2>
        <p class="subtitle">Silakan login menggunakan NIM</p>
        
        <?php if($pesan_error != ""): ?>
            <div class="error"><?php echo $pesan_error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="username" placeholder="Masukkan NIM" required autocomplete="off" autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="tombol_login">Mulai Ujian</button>
        </form>
        
        <p style="font-size: 11px; color: #999; margin-top: 25px;">
            Sistem Ujian Lokal &copy; 2026<br>
            Pastikan NIM dan Password sudah benar.
        </p>
    </div>

</body>
</html><?php
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
</html><?php
// Pengaturan Database
$host = "localhost";
$user = "root"; 
$pass = "root"; // Password default UwAmp
$db   = "ujian_db";

// Melakukan Koneksi
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek Koneksi
if (!$conn) {
    // Jika gagal, kirim pesan error dalam format JSON agar tidak memutus alur JavaScript
    header('Content-Type: application/json');
    die(json_encode([
        "error" => "Gagal konek database: " . mysqli_connect_error()
    ]));
}

// Set charset ke utf8 agar karakter khusus dalam soal (misal: simbol biologi) tampil benar
mysqli_set_charset($conn, "utf8");
?><?php
session_start();
include 'koneksi.php';

if (isset($_SESSION['siswa_id'])) {
    $siswa_id = $_SESSION['siswa_id'];
    
    // Jika soal_id dikirim lewat URL (misal: laporkan_pelanggaran.php?soal_id=5)
    $soal_id = isset($_GET['soal_id']) ? (int)$_GET['soal_id'] : 0;

    if ($soal_id > 0) {
        // UPDATE: Menambah 1 dari nilai yang sudah ada di database
        $query = "UPDATE log_ujian SET pelanggaran = pelanggaran + 1 
                  WHERE siswa_id = '$siswa_id' AND soal_id = '$soal_id'";
        mysqli_query($conn, $query);
    } else {
        // Jika soal_id tidak spesifik (misal saat ESC atau pindah tab umum)
        // Kita update di soal yang paling terakhir diakses mahasiswa tersebut
        $query = "UPDATE log_ujian SET pelanggaran = pelanggaran + 1 
                  WHERE siswa_id = '$siswa_id' 
                  ORDER BY id DESC LIMIT 1";
        mysqli_query($conn, $query);
    }
}
?><?php
session_start();
include 'koneksi.php';

// Jika sudah login admin, langsung lempar ke dashboard admin
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin.php");
    exit;
}

$pesan_error = "";

if (isset($_POST['login_admin'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Menggunakan Prepared Statement untuk keamanan dari SQL Injection
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Login Berhasil
        session_regenerate_id(true); // Keamanan tambahan
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $user;
        
        header("Location: admin.php");
        exit;
    } else {
        $pesan_error = "Username atau Password Admin Salah!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Pengawas - CBT UNY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #2c3e50; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 350px; }
        .login-card h2 { margin-top: 0; color: #2c3e50; text-align: center; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .error { background: #ff7675; color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #2980b9; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #95a5a6; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Admin Login</h2>
    
    <?php if($pesan_error != ""): ?>
        <div class="error"><?php echo $pesan_error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username Admin" required autofocus>
        <input type="password" name="password" placeholder="Password Admin" required>
        <button type="submit" name="login_admin">Masuk Dashboard</button>
    </form>

    <div class="footer">
        Panel Pengawas Ujian &copy; 2026
    </div>
</div>

</body>
</html><?php
session_start();
session_destroy();
header("Location: index.php");
?><?php
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
</html><?php
session_start();
include 'koneksi.php';
if (isset($_SESSION['siswa_id'])) {
    $id = $_SESSION['siswa_id'];
    mysqli_query($conn, "UPDATE siswa SET last_ping = NOW(), is_online = 1 WHERE id = '$id'");
}
?><?php
session_start();
include 'koneksi.php';

$siswa_id = $_SESSION['siswa_id'];
$soal_id  = $_POST['soal_id'];
$jawaban  = $_POST['jawaban'];

// Cek apakah sudah pernah menjawab soal ini
$cek = mysqli_query($conn, "SELECT id FROM log_ujian WHERE siswa_id='$siswa_id' AND soal_id='$soal_id'");

if (mysqli_num_rows($cek) > 0) {
    $query = "UPDATE log_ujian SET jawaban_siswa='$jawaban' WHERE siswa_id='$siswa_id' AND soal_id='$soal_id'";
} else {
    $query = "INSERT INTO log_ujian (siswa_id, soal_id, jawaban_siswa) VALUES ('$siswa_id', '$soal_id', '$jawaban')";
}

if (mysqli_query($conn, $query)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'msg' => mysqli_error($conn)]);
}
?><?php
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
</html><?php
session_start();
include 'koneksi.php';

// Proteksi Admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin.php"); exit;
}

if (isset($_POST['simpan_soal'])) {
    // Ambil input dan bersihkan (SQL Injection Protection)
    $matakuliah_id = $_POST['matakuliah_id'];
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $tipe = $_POST['tipe'];
    $opsi_a = mysqli_real_escape_string($conn, $_POST['opsi_a']);
    $opsi_b = mysqli_real_escape_string($conn, $_POST['opsi_b']);
    $opsi_c = mysqli_real_escape_string($conn, $_POST['opsi_c']);
    $opsi_d = mysqli_real_escape_string($conn, $_POST['opsi_d']);
    $kunci  = $_POST['kunci'];
    $durasi = $_POST['durasi_detik'];
    
    $nama_file = "";

    // 1. CEK & BUAT DIREKTORI JIKA BELUM ADA
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // 2. LOGIKA UPLOAD FILE
    if (!empty($_FILES['media']['name'])) {
        $ekstensi_diperbolehkan = array('png', 'jpg', 'jpeg', 'mp3', 'wav');
        $nama_asli = $_FILES['media']['name'];
        $x = explode('.', $nama_asli);
        $ekstensi = strtolower(end($x));
        $file_tmp = $_FILES['media']['tmp_name'];
        
        $nama_file = "soal_" . time() . "_" . str_replace(' ', '_', $nama_asli);

        if (in_array($ekstensi, $ekstensi_diperbolehkan)) {
            move_uploaded_file($file_tmp, $target_dir . $nama_file);
        }
    }

    // PERBAIKAN: Sertakan matakuliah_id dalam query INSERT
    $query = "INSERT INTO soal (matakuliah_id, tipe, pertanyaan, media, opsi_a, opsi_b, opsi_c, opsi_d, kunci, durasi_detik) 
              VALUES ('$matakuliah_id', '$tipe', '$pertanyaan', '$nama_file', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$kunci', '$durasi')";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Soal Berhasil Ditambah!'); window.location.href='admin.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Soal - CBT UNY</title>
    <style>
        body { font-family: 'Segoe UI', Arial; background: #f4f4f4; padding: 20px; }
        .form-card { background: white; padding: 25px; max-width: 700px; margin: auto; border-radius: 8px; shadow: 0 2px 10px rgba(0,0,0,0.1); }
        label { font-weight: bold; display: block; margin-top: 15px; }
        input, textarea, select { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .grid-opsi { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        button { background: #27ae60; color: white; border: none; padding: 12px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; margin-top: 20px; }
        button:hover { background: #219150; }
    </style>
</head>
<body>
    <div class="form-card">
        <h2>Tambah Soal Baru</h2>
        <form method="POST" enctype="multipart/form-data">
            
            <label>Pilih Mata Kuliah:</label>
            <select name="matakuliah_id" required>
                <?php
                $mk_query = mysqli_query($conn, "SELECT * FROM matakuliah");
                while($mk = mysqli_fetch_assoc($mk_query)) {
                    echo "<option value='".$mk['id']."'>".$mk['nama_mk']." (".$mk['kode_mk'].")</option>";
                }
                ?>
            </select>

            <label>Pertanyaan:</label>
            <textarea name="pertanyaan" rows="4" required placeholder="Tulis soal di sini..."></textarea>

            <label>Media (Gambar/Audio - Opsional):</label>
            <input type="file" name="media" accept=".jpg,.jpeg,.png,.mp3,.wav">

            <label>Tipe Soal:</label>
            <select name="tipe" id="tipe_soal" onchange="toggleOpsi()">
                <option value="pilgan">Pilihan Ganda</option>
                <option value="esai">Esai / Isian</option>
            </select>

            <div id="section_pilgan">
                <div class="grid-opsi">
                    <div>
                        <label>Opsi A:</label>
                        <input type="text" name="opsi_a" placeholder="Jawaban A">
                    </div>
                    <div>
                        <label>Opsi B:</label>
                        <input type="text" name="opsi_b" placeholder="Jawaban B">
                    </div>
                    <div>
                        <label>Opsi C:</label>
                        <input type="text" name="opsi_c" placeholder="Jawaban C">
                    </div>
                    <div>
                        <label>Opsi D:</label>
                        <input type="text" name="opsi_d" placeholder="Jawaban D">
                    </div>
                </div>
                <label>Kunci Jawaban:</label>
                <select name="kunci">
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>

            <label>Durasi Pengerjaan (Detik):</label>
            <input type="number" name="durasi_detik" value="60">

            <button type="submit" name="simpan_soal">Simpan Soal ke Bank Data</button>
            <p style="text-align: center;"><a href="admin.php">Batal dan Kembali</a></p>
        </form>
    </div>

    <script>
        function toggleOpsi() {
            var tipe = document.getElementById('tipe_soal').value;
            var pilgan = document.getElementById('section_pilgan');
            pilgan.style.display = (tipe === 'esai') ? 'none' : 'block';
        }
    </script>
</body>
</html><?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['siswa_id'])) { die("Silakan Login Dulu"); }
$siswa_id = $_SESSION['siswa_id'];


// --- AMBIL DATA PROFIL ---
$profil_query = mysqli_query($conn, "SELECT nim, nama_lengkap, kelas FROM siswa WHERE id = '$siswa_id'");
$profil = mysqli_fetch_assoc($profil_query);

// --- AMBIL INFO UJIAN (Opsional, sesuaikan nama table Anda) ---
// $info_ujian = mysqli_query($conn, "SELECT * FROM pengaturan WHERE id = 1");
// $r_ujian = mysqli_fetch_assoc($info_ujian);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ujian CBT - <?php echo $profil['nama_lengkap']; ?></title>
    <style>
        body { display: flex; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f0f2f5; height: 100vh; overflow: hidden; }
        
        /* TAMPILAN INTRO/PERATURAN */
        #halaman-intro {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #f0f2f5; z-index: 20000; display: flex; align-items: center; justify-content: center;
        }
        .box-peraturan {
            background: white; width: 90%; max-width: 600px; padding: 40px;
            border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center;
        }
        .box-peraturan h2 { color: #2c3e50; margin-bottom: 20px; }
        .list-peraturan { text-align: left; background: #fdfdfd; padding: 20px; border: 1px solid #eee; border-radius: 10px; margin-bottom: 30px; }
        .list-peraturan li { margin-bottom: 10px; line-height: 1.5; color: #555; }
        .btn-mulai { 
            background: #28a745; color: white; border: none; padding: 15px 40px; 
            font-size: 18px; font-weight: bold; border-radius: 8px; cursor: pointer; transition: 0.3s;
        }
        .btn-mulai:hover { background: #218838; transform: translateY(-2px); }

        /* AREA UJIAN (Hidden by default) */
        #konten-utama { display: none; width: 100%; height: 100%; display: flex; }
        .area-soal { width: 75%; padding: 30px; box-sizing: border-box; background: white; height: 100vh; overflow-y: auto; }
        .area-navigasi { width: 25%; background: #f8f9fa; padding: 20px; height: 100vh; overflow-y: auto; box-sizing: border-box; border-left: 1px solid #ddd; }
        
        /* Modal & UI Elements */
        .img-soal { max-width: 280px; cursor: zoom-in; border-radius: 8px; border: 2px solid #ddd; }
        .modal { display: none; position: fixed; z-index: 10000; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); justify-content: center; align-items: center; }
        .modal-content { max-width: 90%; max-height: 90%; border: 4px solid white; animation: zoom 0.3s; }
        @keyframes zoom { from {transform:scale(0.8)} to {transform:scale(1)} }
        
        .btn-soal { display: inline-block; width: 40px; height: 40px; line-height: 40px; text-align: center; margin: 5px; border: 1px solid #ccc; background: white; border-radius: 6px; cursor: pointer; }
        .btn-soal.active { border: 2px solid #007bff; background: #e7f3ff; color: #007bff; font-weight: bold; }
        .btn-soal.done { background-color: #28a745; color: white; border-color: #28a745; }
        
        #timer-box { font-size: 22px; font-weight: bold; color: #dc3545; text-align: center; padding: 15px; border: 2px solid #dc3545; border-radius: 10px; margin-bottom: 20px; background: #fff5f5; }
        #notif-status { position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 12px 25px; border-radius: 8px; color: white; display: none; font-weight: bold; }
        .progress-info { font-size: 13px; color: #666; font-weight: 700; margin-bottom: 8px; }
        .soal-header { background: #e8f4fd; padding: 15px; border-left: 5px solid #3498db; border-radius: 4px; margin-bottom: 25px; font-weight: bold; }
        label { display: block; margin: 12px 0; padding: 15px; border: 1px solid #eee; border-radius: 8px; cursor: pointer; }
        label:hover { background-color: #f8f9fa; }
    </style>
</head>
<body oncontextmenu="return false">

    <div id="halaman-intro">
        <div class="box-peraturan">
            <img src="https://cdn-icons-png.flaticon.com/512/3503/3503827.png" width="80" alt="icon">
            <h2>Konfirmasi Data & Peraturan</h2>
            <div style="margin-bottom: 20px; color: #333;">
                <p>Halo, <b><?php echo $profil['nama_lengkap']; ?></b> (<?php echo $profil['nim']; ?>)</p>
            </div>
            <div class="list-peraturan">
                <ul style="padding-left: 20px;">
                    <li>Dilarang keluar dari mode <b>Fullscreen</b> selama ujian.</li>
                    <li>Sistem akan mencatat jika Anda membuka tab atau aplikasi lain.</li>
                    <li>Jawaban tersimpan secara otomatis setiap kali Anda memilih.</li>
                    <li>Klik gambar soal jika ingin memperbesar.</li>
                    <li>Pastikan koneksi internet stabil hingga akhir ujian.</li>
                </ul>
            </div>
            <button class="btn-mulai" onclick="mulaiUjian()">MULAI UJIAN SEKARANG</button>
        </div>
    </div>

<div id="overlay-fullscreen" 
     onclick="openFullscreen()" 
     style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 30000; flex-direction: column; align-items: center; justify-content: center; color: white; text-align: center; padding: 20px; cursor: pointer;">
    
    <h1 style="font-size: 50px; margin-bottom: 10px;">⚠️</h1>
    <h2>MODE FULLSCREEN TERPUTUS</h2>
    <p>Sistem mendeteksi Anda keluar dari mode ujian.</p>
    <p style="font-size: 20px; background: #dc3545; padding: 10px 20px; border-radius: 5px; margin-top: 20px;">
        KLIK DI MANA SAJA UNTUK KEMBALI KE UJIAN
    </p>
    <p style="color: #ffc107; margin-top: 15px; font-size: 14px;">* Pelanggaran dicatat secara otomatis oleh sistem.</p>
</div>

    <div id="konten-utama">
        <div class="area-soal">
            <div id="notif-status"></div>
            <div id="konten-soal">
                </div>
        </div>

        <div class="area-navigasi">
            <div id="timer-box">00:00:00</div>
            <h4 style="margin-top:0;">Navigasi Soal</h4>
            <div id="navigasi-box"></div>
            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #ddd;">
            <a href="logout.php" style="display:block; text-align:center; padding:12px; background:#dc3545; color:white; text-decoration:none; border-radius:6px; font-weight:bold;" onclick="return confirm('Selesai Ujian?')">Selesai Ujian</a>
        </div>
    </div>

    <div id="imageModal" class="modal" onclick="closeImageOutside(event)">
        <img class="modal-content" id="imgFull">
    </div>

    <script>
let indexAktif = 0;
let timeoutPelanggaran = null;
let timerInterval = null; // Variabel global untuk timer

// --- FUNGSI UTAMA ---
function mulaiUjian() {
    openFullscreen();
    document.getElementById('halaman-intro').style.display = 'none';
    document.getElementById('konten-utama').style.display = 'flex';
    muatSoal(0);
}

function formatWaktu(detik) {
    let h = Math.floor(detik / 3600);
    let m = Math.floor((detik % 3600) / 60);
    let s = detik % 60;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

function jalankanTimerGlobal(sisa) {
    if (timerInterval !== null) return; // Cegah timer double

    let sisaWaktu = sisa;
    // Tampilkan waktu awal segera tanpa tunggu 1 detik
    document.getElementById('timer-box').innerText = formatWaktu(sisaWaktu);

    timerInterval = setInterval(() => {
        if (sisaWaktu <= 0) {
            clearInterval(timerInterval);
            alert("Waktu Ujian Telah Habis!");
            window.location.href = "logout.php";
            return;
        }
        sisaWaktu--;
        document.getElementById('timer-box').innerText = formatWaktu(sisaWaktu);
    }, 1000);
}

function muatSoal(idx) {
    indexAktif = idx;
    fetch('get_soal.php?idx=' + idx)
    .then(res => res.json())
    .then(data => {
        if(data.error) return alert(data.error);
        
        // Jalankan Timer berdasarkan sisa waktu dari server
        jalankanTimerGlobal(data.sisa_detik);

        let s = data.soal;
        
        // Render Media (Gambar/Audio)
        let mediaHtml = '';
        if(s.media) {
            let ext = s.media.split('.').pop().toLowerCase();
            if(['mp3','wav'].includes(ext)) {
                mediaHtml = `<div style="margin-bottom:20px;"><audio controls src="uploads/${s.media}"></audio></div>`;
            } else {
                mediaHtml = `<div><img src="uploads/${s.media}" class="img-soal" onclick="popImage(this.src)"></div><p style="font-size:11px; color:#888;">* Klik untuk zoom</p>`;
            }
        }

        // Render Input Jawaban
        let inputHtml = '';
        if(s.tipe == 'esai') {
            inputHtml = `<textarea id="jawab" onblur="simpanJawaban(${s.id}, this.value)" rows="6" style="width:100%; padding:15px; border-radius:8px; border:1px solid #ccc;">${data.jawaban_sebelumnya || ''}</textarea>`;
        } else {
            ['A','B','C','D'].forEach(opt => {
                let cek = (data.jawaban_sebelumnya == opt) ? 'checked' : '';
                inputHtml += `
                    <label>
                        <input type="radio" name="pilihan" value="${opt}" ${cek} onchange="simpanJawaban(${s.id}, this.value)"> 
                        <span style="margin-left:10px;"><b>${opt}.</b> ${s['opsi_'+opt.toLowerCase()]}</span>
                    </label>`;
            });
        }

        // Update Tampilan HTML
        document.getElementById('konten-soal').innerHTML = `
            <div class="progress-info">SOAL KE ${idx + 1} DARI ${data.total_soal}</div>
            <div class="soal-header">Pertanyaan No. ${idx + 1}</div>
            ${mediaHtml}
            <div style="font-size:19px; line-height:1.7; margin-bottom:30px; color:#2c3e50;">${s.pertanyaan}</div>
            <div id="form-jawaban">${inputHtml}</div>
        `;
        
        bikinNavigasi(data.total_soal, data.answered_ids, data.all_ids);
    });
}

function simpanJawaban(soalId, nilai) {
    let fd = new FormData();
    fd.append('soal_id', soalId);
    fd.append('jawaban', nilai);
    fetch('simpan.php', { method: 'POST', body: fd })
    .then(() => {
        tampilkanNotif("Tersimpan", "success");
        updateWarnaNavigasi(soalId);
    });
}

// --- FUNGSI UI & NAVIGASI ---
function bikinNavigasi(total, answeredIds, allIds) {
    let box = document.getElementById('navigasi-box');
    box.innerHTML = '';
    for(let i=0; i<total; i++) {
        let btn = document.createElement('button');
        btn.className = 'btn-soal';
        btn.id = "btn-nav-" + allIds[i];
        if(i == indexAktif) btn.className += ' active';
        if(answeredIds && answeredIds.includes(allIds[i])) btn.className += ' done';
        btn.innerText = i + 1;
        btn.onclick = () => muatSoal(i);
        box.appendChild(btn);
    }
}

function updateWarnaNavigasi(soalId) {
    let btn = document.getElementById("btn-nav-" + soalId);
    if(btn) btn.classList.add("done");
}

function tampilkanNotif(pesan, tipe) {
    let n = document.getElementById('notif-status');
    n.innerText = pesan; n.style.display = 'block';
    n.style.backgroundColor = (tipe === "success") ? "#28a745" : "#dc3545";
    setTimeout(() => { n.style.display = 'none'; }, 1000);
}

function popImage(src) {
    document.getElementById("imgFull").src = src;
    document.getElementById("imageModal").style.display = "flex";
}

function closeImageOutside(e) {
    if(e.target !== document.getElementById("imgFull")) {
        document.getElementById("imageModal").style.display = "none";
    }
}

// --- FULLSCREEN & SECURITY ---
function openFullscreen() {
    let e = document.documentElement;
    if (e.requestFullscreen) e.requestFullscreen();
    else if (e.webkitRequestFullscreen) e.webkitRequestFullscreen();
}

function handleFullscreenChange() {
    let intro = document.getElementById('halaman-intro');
    let overlay = document.getElementById('overlay-fullscreen');

    // Hanya aktif jika ujian sudah dimulai (intro tertutup)
    if (intro.style.display === 'none') {
        if (!document.fullscreenElement && !document.webkitIsFullScreen) {
            // Tampilkan Pop-up satu halaman
            overlay.style.display = 'flex';
            
            tampilkanNotif("PERINGATAN: KEMBALI KE FULLSCREEN!", "error");
            
            // Catat pelanggaran jika tidak kembali dalam 5 detik
            if (!timeoutPelanggaran) {
                timeoutPelanggaran = setTimeout(() => {
                    fetch(`laporkan_pelanggaran.php?soal_id=0&tipe=escape`);
                    alert("Pelanggaran serius: Anda keluar dari mode ujian!");
                }, 5000);
            }
        } else {
            // Sembunyikan Pop-up jika sudah masuk fullscreen lagi
            overlay.style.display = 'none';
            if(timeoutPelanggaran) { 
                clearTimeout(timeoutPelanggaran); 
                timeoutPelanggaran = null; 
            }
        }
    }
	}
	// Deteksi penekanan tombol keyboard
	document.addEventListener('keyup', (e) => {
	if (e.key === 'PrintScreen') {
		// Salin teks kosong ke clipboard untuk merusak hasil screenshot (hanya di beberapa browser)
		navigator.clipboard.writeText('y'); 
		
		tampilkanNotif("DILARANG SCREENSHOT!", "error");
		
		// Opsional: Laporkan ke database
		fetch(`laporkan_pelanggaran.php?soal_id=${indexAktif}&tipe=screenshot`);
		alert("Percobaan screenshot terdeteksi dan dicatat!");
	}
});



document.addEventListener('fullscreenchange', handleFullscreenChange);
document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
document.addEventListener('contextmenu', e => e.preventDefault());
    </script>
</body>
</html><?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['siswa_id'])) { die("Silakan Login Dulu"); }
$siswa_id = $_SESSION['siswa_id'];

// --- AMBIL DATA PROFIL ---
$profil_query = mysqli_query($conn, "SELECT nim, nama_lengkap, kelas FROM siswa WHERE id = '$siswa_id'");
$profil = mysqli_fetch_assoc($profil_query);

// --- AMBIL INFO UJIAN (Opsional, sesuaikan nama table Anda) ---
// $info_ujian = mysqli_query($conn, "SELECT * FROM pengaturan WHERE id = 1");
// $r_ujian = mysqli_fetch_assoc($info_ujian);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ujian CBT - <?php echo $profil['nama_lengkap']; ?></title>
    <style>
        body { display: flex; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f0f2f5; height: 100vh; overflow: hidden; }
        
        /* TAMPILAN INTRO/PERATURAN */
        #halaman-intro {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #f0f2f5; z-index: 20000; display: flex; align-items: center; justify-content: center;
        }
        .box-peraturan {
            background: white; width: 90%; max-width: 600px; padding: 40px;
            border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center;
        }
        .box-peraturan h2 { color: #2c3e50; margin-bottom: 20px; }
        .list-peraturan { text-align: left; background: #fdfdfd; padding: 20px; border: 1px solid #eee; border-radius: 10px; margin-bottom: 30px; }
        .list-peraturan li { margin-bottom: 10px; line-height: 1.5; color: #555; }
        .btn-mulai { 
            background: #28a745; color: white; border: none; padding: 15px 40px; 
            font-size: 18px; font-weight: bold; border-radius: 8px; cursor: pointer; transition: 0.3s;
        }
        .btn-mulai:hover { background: #218838; transform: translateY(-2px); }

        /* AREA UJIAN (Hidden by default) */
        #konten-utama { display: none; width: 100%; height: 100%; display: flex; }
        .area-soal { width: 75%; padding: 30px; box-sizing: border-box; background: white; height: 100vh; overflow-y: auto; }
        .area-navigasi { width: 25%; background: #f8f9fa; padding: 20px; height: 100vh; overflow-y: auto; box-sizing: border-box; border-left: 1px solid #ddd; }
        
        /* Modal & UI Elements */
        .img-soal { max-width: 280px; cursor: zoom-in; border-radius: 8px; border: 2px solid #ddd; }
        .modal { display: none; position: fixed; z-index: 10000; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); justify-content: center; align-items: center; }
        .modal-content { max-width: 90%; max-height: 90%; border: 4px solid white; animation: zoom 0.3s; }
        @keyframes zoom { from {transform:scale(0.8)} to {transform:scale(1)} }
        
        .btn-soal { display: inline-block; width: 40px; height: 40px; line-height: 40px; text-align: center; margin: 5px; border: 1px solid #ccc; background: white; border-radius: 6px; cursor: pointer; }
        .btn-soal.active { border: 2px solid #007bff; background: #e7f3ff; color: #007bff; font-weight: bold; }
        .btn-soal.done { background-color: #28a745; color: white; border-color: #28a745; }
        
        #timer-box { font-size: 22px; font-weight: bold; color: #dc3545; text-align: center; padding: 15px; border: 2px solid #dc3545; border-radius: 10px; margin-bottom: 20px; background: #fff5f5; }
        #notif-status { position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 12px 25px; border-radius: 8px; color: white; display: none; font-weight: bold; }
        .progress-info { font-size: 13px; color: #666; font-weight: 700; margin-bottom: 8px; }
        .soal-header { background: #e8f4fd; padding: 15px; border-left: 5px solid #3498db; border-radius: 4px; margin-bottom: 25px; font-weight: bold; }
        label { display: block; margin: 12px 0; padding: 15px; border: 1px solid #eee; border-radius: 8px; cursor: pointer; }
        label:hover { background-color: #f8f9fa; }
    </style>
</head>
<body oncontextmenu="return false">

    <div id="halaman-intro">
        <div class="box-peraturan">
            <img src="https://cdn-icons-png.flaticon.com/512/3503/3503827.png" width="80" alt="icon">
            <h2>Konfirmasi Data & Peraturan</h2>
            <div style="margin-bottom: 20px; color: #333;">
                <p>Halo, <b><?php echo $profil['nama_lengkap']; ?></b> (<?php echo $profil['nim']; ?>)</p>
            </div>
            <div class="list-peraturan">
                <ul style="padding-left: 20px;">
                    <li>Dilarang keluar dari mode <b>Fullscreen</b> selama ujian.</li>
                    <li>Sistem akan mencatat jika Anda membuka tab atau aplikasi lain.</li>
                    <li>Jawaban tersimpan secara otomatis setiap kali Anda memilih.</li>
                    <li>Klik gambar soal jika ingin memperbesar.</li>
                    <li>Pastikan koneksi internet stabil hingga akhir ujian.</li>
                </ul>
            </div>
            <button class="btn-mulai" onclick="mulaiUjian()">MULAI UJIAN SEKARANG</button>
        </div>
    </div>

<div id="overlay-fullscreen" 
     onclick="openFullscreen()" 
     style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 30000; flex-direction: column; align-items: center; justify-content: center; color: white; text-align: center; padding: 20px; cursor: pointer;">
    
    <h1 style="font-size: 50px; margin-bottom: 10px;">⚠️</h1>
    <h2>MODE FULLSCREEN TERPUTUS</h2>
    <p>Sistem mendeteksi Anda keluar dari mode ujian.</p>
    <p style="font-size: 20px; background: #dc3545; padding: 10px 20px; border-radius: 5px; margin-top: 20px;">
        KLIK DI MANA SAJA UNTUK KEMBALI KE UJIAN
    </p>
    <p style="color: #ffc107; margin-top: 15px; font-size: 14px;">* Pelanggaran dicatat secara otomatis oleh sistem.</p>
</div>

    <div id="konten-utama">
        <div class="area-soal">
            <div id="notif-status"></div>
            <div id="konten-soal">
                </div>
        </div>

        <div class="area-navigasi">
            <div id="timer-box">00:00:00</div>
            <h4 style="margin-top:0;">Navigasi Soal</h4>
            <div id="navigasi-box"></div>
            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #ddd;">
            <a href="logout.php" style="display:block; text-align:center; padding:12px; background:#dc3545; color:white; text-decoration:none; border-radius:6px; font-weight:bold;" onclick="return confirm('Selesai Ujian?')">Selesai Ujian</a>
        </div>
    </div>

    <div id="imageModal" class="modal" onclick="closeImageOutside(event)">
        <img class="modal-content" id="imgFull">
    </div>

    <script>
let indexAktif = 0;
let timeoutPelanggaran = null;
let timerInterval = null; // Variabel global untuk timer

// --- FUNGSI UTAMA ---
function mulaiUjian() {
    openFullscreen();
    document.getElementById('halaman-intro').style.display = 'none';
    document.getElementById('konten-utama').style.display = 'flex';
    muatSoal(0);
}

function formatWaktu(detik) {
    let h = Math.floor(detik / 3600);
    let m = Math.floor((detik % 3600) / 60);
    let s = detik % 60;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

function jalankanTimerGlobal(sisa) {
    if (timerInterval !== null) return; // Cegah timer double

    let sisaWaktu = sisa;
    // Tampilkan waktu awal segera tanpa tunggu 1 detik
    document.getElementById('timer-box').innerText = formatWaktu(sisaWaktu);

    timerInterval = setInterval(() => {
        if (sisaWaktu <= 0) {
            clearInterval(timerInterval);
            alert("Waktu Ujian Telah Habis!");
            window.location.href = "logout.php";
            return;
        }
        sisaWaktu--;
        document.getElementById('timer-box').innerText = formatWaktu(sisaWaktu);
    }, 1000);
}

function muatSoal(idx) {
    indexAktif = idx;
    fetch('get_soal.php?idx=' + idx)
    .then(res => res.json())
    .then(data => {
        if(data.error) return alert(data.error);
        
        // Jalankan Timer berdasarkan sisa waktu dari server
        jalankanTimerGlobal(data.sisa_detik);

        let s = data.soal;
        
        // Render Media (Gambar/Audio)
        let mediaHtml = '';
        if(s.media) {
            let ext = s.media.split('.').pop().toLowerCase();
            if(['mp3','wav'].includes(ext)) {
                mediaHtml = `<div style="margin-bottom:20px;"><audio controls src="uploads/${s.media}"></audio></div>`;
            } else {
                mediaHtml = `<div><img src="uploads/${s.media}" class="img-soal" onclick="popImage(this.src)"></div><p style="font-size:11px; color:#888;">* Klik untuk zoom</p>`;
            }
        }

        // Render Input Jawaban
        let inputHtml = '';
        if(s.tipe == 'esai') {
            inputHtml = `<textarea id="jawab" onblur="simpanJawaban(${s.id}, this.value)" rows="6" style="width:100%; padding:15px; border-radius:8px; border:1px solid #ccc;">${data.jawaban_sebelumnya || ''}</textarea>`;
        } else {
            ['A','B','C','D'].forEach(opt => {
                let cek = (data.jawaban_sebelumnya == opt) ? 'checked' : '';
                inputHtml += `
                    <label>
                        <input type="radio" name="pilihan" value="${opt}" ${cek} onchange="simpanJawaban(${s.id}, this.value)"> 
                        <span style="margin-left:10px;"><b>${opt}.</b> ${s['opsi_'+opt.toLowerCase()]}</span>
                    </label>`;
            });
        }

        // Update Tampilan HTML
        document.getElementById('konten-soal').innerHTML = `
            <div class="progress-info">SOAL KE ${idx + 1} DARI ${data.total_soal}</div>
            <div class="soal-header">Pertanyaan No. ${idx + 1}</div>
            ${mediaHtml}
            <div style="font-size:19px; line-height:1.7; margin-bottom:30px; color:#2c3e50;">${s.pertanyaan}</div>
            <div id="form-jawaban">${inputHtml}</div>
        `;
        
        bikinNavigasi(data.total_soal, data.answered_ids, data.all_ids);
    });
}

function simpanJawaban(soalId, nilai) {
    let fd = new FormData();
    fd.append('soal_id', soalId);
    fd.append('jawaban', nilai);
    fetch('simpan.php', { method: 'POST', body: fd })
    .then(() => {
        tampilkanNotif("Tersimpan", "success");
        updateWarnaNavigasi(soalId);
    });
}

// --- FUNGSI UI & NAVIGASI ---
function bikinNavigasi(total, answeredIds, allIds) {
    let box = document.getElementById('navigasi-box');
    box.innerHTML = '';
    for(let i=0; i<total; i++) {
        let btn = document.createElement('button');
        btn.className = 'btn-soal';
        btn.id = "btn-nav-" + allIds[i];
        if(i == indexAktif) btn.className += ' active';
        if(answeredIds && answeredIds.includes(allIds[i])) btn.className += ' done';
        btn.innerText = i + 1;
        btn.onclick = () => muatSoal(i);
        box.appendChild(btn);
    }
}

function updateWarnaNavigasi(soalId) {
    let btn = document.getElementById("btn-nav-" + soalId);
    if(btn) btn.classList.add("done");
}

function tampilkanNotif(pesan, tipe) {
    let n = document.getElementById('notif-status');
    n.innerText = pesan; n.style.display = 'block';
    n.style.backgroundColor = (tipe === "success") ? "#28a745" : "#dc3545";
    setTimeout(() => { n.style.display = 'none'; }, 1000);
}

function popImage(src) {
    document.getElementById("imgFull").src = src;
    document.getElementById("imageModal").style.display = "flex";
}

function closeImageOutside(e) {
    if(e.target !== document.getElementById("imgFull")) {
        document.getElementById("imageModal").style.display = "none";
    }
}

// --- FULLSCREEN & SECURITY ---
function openFullscreen() {
    let e = document.documentElement;
    if (e.requestFullscreen) e.requestFullscreen();
    else if (e.webkitRequestFullscreen) e.webkitRequestFullscreen();
}

function handleFullscreenChange() {
    let intro = document.getElementById('halaman-intro');
    let overlay = document.getElementById('overlay-fullscreen');

    // Hanya aktif jika ujian sudah dimulai (intro tertutup)
    if (intro.style.display === 'none') {
        if (!document.fullscreenElement && !document.webkitIsFullScreen) {
            // Tampilkan Pop-up satu halaman
            overlay.style.display = 'flex';
            
            tampilkanNotif("PERINGATAN: KEMBALI KE FULLSCREEN!", "error");
            
            // Catat pelanggaran jika tidak kembali dalam 5 detik
            if (!timeoutPelanggaran) {
                timeoutPelanggaran = setTimeout(() => {
                    fetch(`laporkan_pelanggaran.php?soal_id=0&tipe=escape`);
                    alert("Pelanggaran serius: Anda keluar dari mode ujian!");
                }, 5000);
            }
        } else {
            // Sembunyikan Pop-up jika sudah masuk fullscreen lagi
            overlay.style.display = 'none';
            if(timeoutPelanggaran) { 
                clearTimeout(timeoutPelanggaran); 
                timeoutPelanggaran = null; 
            }
        }
    }
	}
	// Deteksi penekanan tombol keyboard
	document.addEventListener('keyup', (e) => {
	if (e.key === 'PrintScreen') {
		// Salin teks kosong ke clipboard untuk merusak hasil screenshot (hanya di beberapa browser)
		navigator.clipboard.writeText('y'); 
		
		tampilkanNotif("DILARANG SCREENSHOT!", "error");
		
		// Opsional: Laporkan ke database
		fetch(`laporkan_pelanggaran.php?soal_id=${indexAktif}&tipe=screenshot`);
		alert("Percobaan screenshot terdeteksi dan dicatat!");
	}
});



document.addEventListener('fullscreenchange', handleFullscreenChange);
document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
document.addEventListener('contextmenu', e => e.preventDefault());
    </script>
</body>
</html><?php
include 'koneksi.php';
$mk_id = $_POST['mk_id'];
$durasi = $_POST['durasi'];

$query = "UPDATE pengaturan SET mk_aktif_id = '$mk_id', durasi_menit = '$durasi' WHERE id = 1";
mysqli_query($conn, $query);

header("Location: admin.php");
?>