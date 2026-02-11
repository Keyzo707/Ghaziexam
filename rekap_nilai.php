<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin.php");
    exit;
}

// 1. Ambil Semua Mata Kuliah untuk Dropdown
$q_mk_list = mysqli_query($conn, "SELECT * FROM matakuliah ORDER BY nama_mk ASC");

// 2. Tentukan MK mana yang sedang dipilih
// Jika ada $_GET['mk_id'], pakai itu. Jika tidak, pakai MK yang sedang aktif di pengaturan.
$mk_id_selected = "";
if (isset($_GET['mk_id'])) {
    $mk_id_selected = $_GET['mk_id'];
} else {
    $q_set = mysqli_query($conn, "SELECT mk_aktif_id FROM pengaturan WHERE id=1");
    $set = mysqli_fetch_assoc($q_set);
    $mk_id_selected = $set['mk_aktif_id'];
}

// Ambil Nama MK Terpilih
$q_nama_mk = mysqli_query($conn, "SELECT nama_mk FROM matakuliah WHERE id='$mk_id_selected'");
$d_mk = mysqli_fetch_assoc($q_nama_mk);
$nama_mk_judul = $d_mk ? $d_mk['nama_mk'] : "Pilih Mata Kuliah";

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Rekap Nilai - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4 bg-white p-4 rounded shadow">
    
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h3>Rekapitulasi Nilai</h3>
        <div>
            <button onclick="window.print()" class="btn btn-primary me-2">Cetak PDF</button>
            <a href="admin.php" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <div class="card mb-4 bg-light no-print">
        <div class="card-body py-3">
            <form method="GET" class="row align-items-center">
                <div class="col-auto">
                    <label class="fw-bold">Filter Mata Kuliah:</label>
                </div>
                <div class="col-auto">
                    <select name="mk_id" class="form-select" onchange="this.form.submit()">
                        <?php while($mk = mysqli_fetch_assoc($q_mk_list)): ?>
                            <option value="<?= $mk['id'] ?>" <?= ($mk['id'] == $mk_id_selected) ? 'selected' : '' ?>>
                                <?= $mk['nama_mk'] ?> (<?= $mk['kode_mk'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <h4 class="text-center mb-4">Hasil Ujian: <?= $nama_mk_judul ?></h4>

    <table class="table table-bordered table-hover">
        <thead class="table-dark text-center">
            <tr>
                <th width="5%">No</th>
                <th>NIM</th>
                <th>Nama Mahasiswa</th>
                <th>Kelas</th>
                <th>Status</th>
                <th>Nilai (Skor)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // A. Siapkan Kunci Jawaban MK Terpilih (Optimasi Query)
            $kunci_jawaban = [];
            $q_soal = mysqli_query($conn, "SELECT id, kunci FROM soal WHERE matakuliah_id='$mk_id_selected' AND tipe='pilgan'");
            $total_soal = mysqli_num_rows($q_soal);
            while($k = mysqli_fetch_assoc($q_soal)) {
                $kunci_jawaban[$k['id']] = strtoupper($k['kunci']);
            }

            // B. Ambil Semua Siswa
            $q_siswa = mysqli_query($conn, "SELECT * FROM siswa ORDER BY kelas ASC, nama_lengkap ASC");
            $no = 1;

            while($siswa = mysqli_fetch_assoc($q_siswa)):
                $id_siswa = $siswa['id'];
                
                // C. Cek Status di tabel ujian_mahasiswa
                $q_status = mysqli_query($conn, "SELECT status, waktu_mulai FROM ujian_mahasiswa WHERE siswa_id='$id_siswa' AND mk_id='$mk_id_selected'");
                $data_status = mysqli_fetch_assoc($q_status);
                
                $status_teks = "Belum Mengerjakan";
                $badge_color = "bg-secondary"; // Abu-abu
                $nilai_akhir = "-";

                if ($data_status) {
                    if ($data_status['status'] == 'sedang_mengerjakan') {
                        $status_teks = "Sedang Mengerjakan";
                        $badge_color = "bg-warning text-dark"; // Kuning
                        $nilai_akhir = "<i>Proses...</i>";
                    } elseif ($data_status['status'] == 'selesai') {
                        $status_teks = "Selesai";
                        $badge_color = "bg-success"; // Hijau
                        
                        // D. Hitung Nilai Hanya Jika Status Selesai
                        $jml_benar = 0;
                        if($total_soal > 0) {
                            $q_jawab = mysqli_query($conn, "SELECT soal_id, jawaban_siswa FROM log_ujian WHERE siswa_id='$id_siswa'");
                            while($j = mysqli_fetch_assoc($q_jawab)) {
                                $sid = $j['soal_id'];
                                $ans = strtoupper($j['jawaban_siswa']);
                                if(isset($kunci_jawaban[$sid]) && $kunci_jawaban[$sid] == $ans) {
                                    $jml_benar++;
                                }
                            }
                            $nilai_akhir = round(($jml_benar / $total_soal) * 100, 2);
                        } else {
                            $nilai_akhir = 0;
                        }
                    }
                }
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= $siswa['nim'] ?></td>
                <td><?= $siswa['nama_lengkap'] ?></td>
                <td><?= $siswa['kelas'] ?></td>
                <td class="text-center">
                    <span class="badge <?= $badge_color ?>"><?= $status_teks ?></span>
                </td>
                <td class="text-center fw-bold" style="font-size: 1.1em;">
                    <?= $nilai_akhir ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <div class="mt-3 text-muted" style="font-size: 12px;">
        * Total Soal dalam Mata Kuliah ini: <strong><?= $total_soal ?></strong> Butir.<br>
        * Nilai dihitung otomatis berdasarkan kunci jawaban soal Pilihan Ganda.<br>
        * Siswa yang berstatus "Belum Mengerjakan" tidak memiliki nilai (strip -).
    </div>
</div>

</body>
</html>