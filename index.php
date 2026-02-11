<?php
session_start();
include 'koneksi.php';

// 1. Cek Sesi: Jika sudah login, langsung ke ujian
if (isset($_SESSION['siswa_id'])) {
    header("Location: ujian.php");
    exit;
}

$pesan_error = "";

// 2. Proses Login
if (isset($_POST['btn_login'])) {
    $nim      = mysqli_real_escape_string($conn, $_POST['nim']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Cari data berdasarkan NIM
    $query = mysqli_query($conn, "SELECT * FROM siswa WHERE nim = '$nim'");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);

        // Cek Password (Tanpa Enkripsi / Plain Text sesuai permintaan)
        if ($data['password'] == $password) {
            
            // Login Berhasil -> Set Session
            $_SESSION['siswa_id']   = $data['id'];
            $_SESSION['nama_siswa'] = $data['nama_lengkap'];
            $_SESSION['nim_siswa']  = $data['nim'];
            $_SESSION['kelas']      = $data['kelas'];

            // Update Status Online
            mysqli_query($conn, "UPDATE siswa SET is_online = 1, last_ip = '{$_SERVER['REMOTE_ADDR']}' WHERE id = '{$data['id']}'");

            header("Location: ujian.php");
            exit;
        } else {
            $pesan_error = "Password salah!";
        }
    } else {
        $pesan_error = "NIM tidak ditemukan / belum terdaftar.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login CBT Ujian Online</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            background: white;
        }
        .login-header {
            background: #2c3e50;
            padding: 30px;
            text-align: center;
            color: white;
        }
        .login-header h3 { margin: 0; font-weight: 700; }
        .login-header p { margin: 5px 0 0; opacity: 0.8; font-size: 14px; }
        .btn-login {
            background: #2c3e50; 
            border: none; 
            padding: 12px; 
            font-weight: bold; 
            width: 100%;
            transition: 0.3s;
        }
        .btn-login:hover { background: #1a252f; }
        .link-admin {
            font-size: 12px;
            color: #bdc3c7;
            text-decoration: none;
            margin-top: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <h3>CBT EXAM</h3>
            <p>Silakan login untuk memulai ujian</p>
        </div>
        <div class="card-body p-4">

            <?php if ($pesan_error): ?>
                <div class="alert alert-danger alert-dismissible fade show text-center" style="font-size:14px;">
                    <?php echo $pesan_error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">NIM</label>
                    <input type="number" name="nim" class="form-control form-control-lg" placeholder="Masukkan NIM" required autofocus>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Password</label>
                    <input type="password" name="password" class="form-control form-control-lg" placeholder="Masukkan Password" required>
                </div>

                <button type="submit" name="btn_login" class="btn btn-primary btn-login btn-lg">MASUK / LOGIN</button>
            </form>

            <div class="text-center mt-3 pt-3 border-top">
                <p class="mb-2 text-muted" style="font-size: 14px;">Belum punya akun?</p>
                <a href="register.php" class="btn btn-outline-primary w-100 fw-bold">Daftar Akun Baru</a>
            </div>
            
            <div class="text-center">
                <a Login sebagai Pengawas / Admin</a>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>