<?php
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
]);