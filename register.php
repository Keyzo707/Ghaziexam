<?php
session_start();
include 'koneksi.php';

$pesan = "";
$tipe = "";

// Jika tombol Daftar diklik
if (isset($_POST['btn_daftar'])) {
    // 1. Ambil input & bersihkan karakter khusus (SQL Injection Prevention)
    $nim            = mysqli_real_escape_string($conn, $_POST['nim']);
    $nama           = mysqli_real_escape_string($conn, $_POST['nama']);
    $kelas          = mysqli_real_escape_string($conn, $_POST['kelas']);
    $password       = mysqli_real_escape_string($conn, $_POST['password']);
    $pass_konfirmasi = mysqli_real_escape_string($conn, $_POST['password_confirm']);

    // 2. Cek Validasi: Apakah NIM sudah ada?
    $cek_nim = mysqli_query($conn, "SELECT id FROM siswa WHERE nim = '$nim'");

    if ($password != $pass_konfirmasi) {
        $pesan = "Password dan Konfirmasi Password tidak sama!";
        $tipe = "danger";
    } elseif (mysqli_num_rows($cek_nim) > 0) {
        $pesan = "NIM $nim sudah terdaftar! Silakan login.";
        $tipe = "warning";
    } else {
        // 3. Simpan ke Database (TANPA ENKRIPSI sesuai request)
        $query = "INSERT INTO siswa (nim, nama_lengkap, kelas, password) 
                  VALUES ('$nim', '$nama', '$kelas', '$password')";

        if (mysqli_query($conn, $query)) {
            $pesan = "Registrasi Berhasil! Silakan Login.";
            $tipe = "success";
        } else {
            $pesan = "Gagal mendaftar: " . mysqli_error($conn);
            $tipe = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun Baru</title>
    
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card-reg { width: 100%; max-width: 450px; border: none; shadow: 0 4px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="card card-reg">
    <div class="card-header bg-primary text-white text-center py-3">
        <h4 class="mb-0">Registrasi Mahasiswa</h4>
    </div>
    <div class="card-body p-4">
        
        <?php if($pesan): ?>
            <div class="alert alert-<?php echo $tipe; ?> alert-dismissible fade show">
                <?php echo $pesan; ?>
                <?php if($tipe == 'success'): ?>
                    <br><a href="index.php" class="fw-bold text-decoration-none">Klik disini untuk Login</a>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">NIM</label>
                <input type="number" name="nim" class="form-control" placeholder="Nomor Induk Mahasiswa" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" placeholder="Nama Sesuai Absen" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Kelas</label>
                <select name="kelas" class="form-select" required>
                    <option value="">-- Pilih Kelas --</option>
                    <option value="Biologi Murni">Biologi Murni</option>
                    <option value="Pendidikan Biologi">Pendidikan Biologi</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Password<br><p style="color: red;">password tidak terenkripsi, masukkan "123"</p></label>
                <input type="password" name="password" class="form-control" placeholder="Buat Password" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="password_confirm" class="form-control" placeholder="Ulangi Password" required>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" name="btn_daftar" class="btn btn-primary">Daftar Sekarang</button>
                <a href="index.php" class="btn btn-outline-secondary">Sudah punya akun? Login</a>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>