<?php
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
</html>