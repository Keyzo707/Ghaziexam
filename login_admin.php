<?php
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
</html>