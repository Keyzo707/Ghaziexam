<?php
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
</html>