<?php
session_start();
include 'koneksi.php';

// 1. Cek Sesi Login Siswa
if (!isset($_SESSION['siswa_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu!'); window.location='index.php';</script>";
    exit;
}

$siswa_id = $_SESSION['siswa_id'];

// 2. Ambil Data Siswa
$q_siswa = mysqli_query($conn, "SELECT * FROM siswa WHERE id = '$siswa_id'");
$siswa = mysqli_fetch_assoc($q_siswa);

// 3. Ambil Pengaturan Ujian Aktif
$q_ujian = mysqli_query($conn, "
    SELECT p.mk_aktif_id, p.durasi_menit, m.nama_mk, m.kode_mk 
    FROM pengaturan p
    JOIN matakuliah m ON p.mk_aktif_id = m.id
    WHERE p.id = 1
");
$ujian = mysqli_fetch_assoc($q_ujian);

if (!$ujian) {
    die("<h3>Maaf, belum ada ujian yang diaktifkan oleh Admin.</h3><br><a href='logout.php'>Logout</a>");
}

$mk_id = $ujian['mk_aktif_id'];
$durasi_menit = $ujian['durasi_menit'];

// --- LOGIKA BARU: CEK TABEL UJIAN_MAHASISWA ---
// Cek apakah siswa ini SUDAH PERNAH mulai ujian untuk MK ini?
$q_cek_mulai = mysqli_query($conn, "SELECT * FROM ujian_mahasiswa WHERE siswa_id='$siswa_id' AND mk_id='$mk_id'");
$data_ujian = mysqli_fetch_assoc($q_cek_mulai);

$waktu_mulai = null;

if ($data_ujian) {
    // A. SUDAH PERNAH MULAI
    if ($data_ujian['status'] == 'selesai') {
        echo "<script>alert('Anda sudah menyelesaikan ujian mata kuliah ini!'); window.location='logout.php';</script>";
        exit;
    }
    $waktu_mulai = $data_ujian['waktu_mulai'];
} else {
    // B. BELUM PERNAH MULAI (BARU PERTAMA KALI KLIK)
    // Kita set waktu mulainya SEKARANG, tapi simpan di tabel ujian_mahasiswa (bukan tabel siswa)
    $waktu_sekarang = date('Y-m-d H:i:s');
    mysqli_query($conn, "INSERT INTO ujian_mahasiswa (siswa_id, mk_id, waktu_mulai, status) VALUES ('$siswa_id', '$mk_id', '$waktu_sekarang', 'sedang_mengerjakan')");
    $waktu_mulai = $waktu_sekarang;
}

// HITUNG SISA WAKTU
$start_time = strtotime($waktu_mulai);
$end_time   = $start_time + ($durasi_menit * 60);
$now        = time();
$sisa_detik = $end_time - $now;

if ($sisa_detik < 0) {
    // Jika waktu habis, update status jadi selesai
    mysqli_query($conn, "UPDATE ujian_mahasiswa SET status='selesai' WHERE siswa_id='$siswa_id' AND mk_id='$mk_id'");
    echo "<script>alert('Waktu Ujian Habis!'); window.location='logout.php';</script>";
    exit;
}

// Update Last Ping Siswa (tanda online)
mysqli_query($conn, "UPDATE siswa SET is_online=1, last_ping=NOW() WHERE id='$siswa_id'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian: <?php echo $ujian['nama_mk']; ?></title>
    <style>
        /* GAYA CSS SAMA SEPERTI SEBELUMNYA */
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; height: 100vh; overflow: hidden; display: flex; }
        
        #halaman-intro { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #f4f6f9; z-index: 20000; display: flex; align-items: center; justify-content: center; }
        .box-intro { background: white; width: 90%; max-width: 750px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column; }
        .intro-header { background: #3498db; color: white; padding: 25px; text-align: center; }
        .intro-body { padding: 30px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 25px; }
        .table-detail { width: 100%; font-size: 14px; }
        .table-detail td { padding: 6px 0; vertical-align: top; }
        .btn-mulai { background: #27ae60; color: white; border: none; padding: 15px; width: 100%; font-size: 18px; font-weight: bold; cursor: pointer; border-radius: 6px; }

        #konten-utama { display: none; width: 100%; height: 100%; display: flex; }
        .area-soal { width: 75%; padding: 30px; background: white; height: 100vh; overflow-y: auto; }
        .area-navigasi { width: 25%; background: #2c3e50; color: white; padding: 20px; height: 100vh; overflow-y: auto; }
        
        /* Modal & UI Lain */
        .nomor-soal { background: #e8f4fd; color: #2980b9; padding: 12px; border-left: 5px solid #3498db; font-weight: bold; margin-bottom: 20px; }
        .img-soal { max-width: 100%; max-height: 300px; cursor: zoom-in; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .opsi-label { display: flex; padding: 12px; margin-bottom: 10px; border: 1px solid #e0e0e0; border-radius: 8px; cursor: pointer; }
        .opsi-label:hover { background-color: #f0f8ff; }
        .opsi-label input { margin-right: 15px; }
        
        .btn-nav { padding: 10px; margin: 2px; border: none; background: #34495e; color: white; cursor: pointer; width: 40px; }
        .btn-nav.active { background: #3498db; border: 2px solid white; }
        .btn-nav.done { background: #27ae60; }
        
        #timer-box { font-size: 28px; font-weight: bold; color: #f1c40f; text-align: center; margin-bottom: 20px; border: 2px solid #f39c12; padding: 10px; border-radius: 5px;}
        
        .modal { display: none; position: fixed; z-index: 50000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); justify-content: center; align-items: center; }
        .modal-content { max-width: 90%; max-height: 90vh; border: 3px solid white; }
        .close-modal { position: absolute; top: 20px; right: 30px; color: white; font-size: 40px; cursor: pointer; }
        #overlay-fullscreen { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 30000; color: white; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
    </style>
</head>
<body oncontextmenu="return false">

    <div id="halaman-intro">
        <div class="box-intro">
            <div class="intro-header"><h2>Ujian Sedang Berlangsung</h2></div>
            <div class="intro-body">
                <div class="info-grid">
                    <div>
                        <h4>Peserta</h4>
                        <table class="table-detail">
                            <tr><td>Nama</td><td>: <?php echo $siswa['nama_lengkap']; ?></td></tr>
                            <tr><td>NIM</td><td>: <?php echo $siswa['nim']; ?></td></tr>
                        </table>
                    </div>
                    <div>
                        <h4>Mata Kuliah</h4>
                        <table class="table-detail">
                            <tr><td>MK</td><td>: <?php echo $ujian['nama_mk']; ?></td></tr>
                            <tr><td>Durasi</td><td>: <?php echo $ujian['durasi_menit']; ?> Menit</td></tr>
                            <tr><td>Soal</td><td>: <span id="intro-total-soal">...</span></td></tr>
                        </table>
                    </div>
                </div>
                <button class="btn-mulai" onclick="mulaiUjian()">MULAI / LANJUTKAN UJIAN</button>
            </div>
        </div>
    </div>

    <div id="overlay-fullscreen" onclick="openFullscreen()">
        <h1>⛔</h1><h2>MODE LAYAR PENUH</h2><p>Klik layar untuk kembali mengerjakan.</p>
    </div>

    <div id="konten-utama">
        <div class="area-soal">
            <div id="konten-soal"></div>
        </div>
        <div class="area-navigasi">
            <div id="timer-box">Loading...</div>
            <h4>Navigasi</h4>
            <div id="navigasi-box"></div>
            <br>
            <button onclick="selesaiUjian()" style="width:100%; padding:15px; background:#e74c3c; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer;">SELESAI UJIAN</button>
        </div>
    </div>

    <div id="imageModal" class="modal" onclick="document.getElementById('imageModal').style.display='none'">
        <span class="close-modal">&times;</span>
        <img class="modal-content" id="imgFull">
    </div>

    <script>
    let indexAktif = 0;
    let sisaDetik = <?php echo $sisa_detik; ?>; // Ambil sisa detik dari PHP
    let timerInterval = null;

    // Load jumlah soal di awal
    fetch('get_soal.php?idx=0').then(r=>r.json()).then(d => {
        document.getElementById('intro-total-soal').innerText = d.total_soal + " Butir";
    });

    function mulaiUjian() {
        openFullscreen();
        document.getElementById('halaman-intro').style.display = 'none';
        document.getElementById('konten-utama').style.display = 'flex';
        muatSoal(0);
        jalankanTimer();
    }

    function muatSoal(idx) {
        indexAktif = idx;
        document.getElementById('konten-soal').innerHTML = '<p style="text-align:center; margin-top:50px;">Memuat...</p>';
        
        fetch('get_soal.php?idx=' + idx).then(r=>r.json()).then(data => {
            let s = data.soal;
            
            // Media
            let media = '';
            if(s.media && s.media !== "NULL" && s.media !== "") {
                let src = "uploads/" + s.media;
                if(s.media.includes('mp3')) media = `<audio controls src="${src}" style="width:100%"></audio>`;
                else media = `<img src="${src}" class="img-soal" onclick="popImage('${src}')">`;
            }

            // Opsi
            let opsi = '';
            ['A','B','C','D'].forEach(o => {
                let cek = (data.jawaban_sebelumnya == o) ? 'checked' : '';
                opsi += `<label class="opsi-label"><input type="radio" name="j" value="${o}" ${cek} onchange="simpan(${s.id}, this.value)"> <b>${o}.</b> ${s['opsi_'+o.toLowerCase()]}</label>`;
            });

            document.getElementById('konten-soal').innerHTML = `
                <div class="nomor-soal">Soal No. ${idx+1}</div>
                <div style="text-align:center; margin-bottom:15px;">${media}</div>
                <div style="font-size:18px; margin-bottom:20px;">${s.pertanyaan}</div>
                <div>${opsi}</div>
            `;
            
            updateNav(data.total_soal, data.answered_ids, data.all_ids);
        });
    }

    function simpan(soalId, val) {
        let fd = new FormData(); fd.append('soal_id', soalId); fd.append('jawaban', val);
        fetch('simpan.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
            if(d.status=='success') document.getElementById('btn-nav-'+soalId).classList.add('done');
        });
    }

    function updateNav(total, answered, ids) {
        let box = document.getElementById('navigasi-box');
        box.innerHTML = '';
        for(let i=0; i<total; i++) {
            let btn = document.createElement('button');
            btn.className = `btn-nav ${i===indexAktif?'active':''} ${answered.includes(ids[i])?'done':''}`;
            btn.innerText = i+1;
            btn.id = 'btn-nav-'+ids[i];
            btn.onclick = () => muatSoal(i);
            box.appendChild(btn);
        }
    }

    function jalankanTimer() {
        if(timerInterval) clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            sisaDetik--;
            let h = Math.floor(sisaDetik/3600), m = Math.floor((sisaDetik%3600)/60), s = sisaDetik%60;
            document.getElementById('timer-box').innerText = `${h}:${m}:${s}`;
            if(sisaDetik <= 0) selesaiUjian();
        }, 1000);
    }

    function selesaiUjian() {
        if(confirm("Yakin ingin mengakhiri ujian?")) window.location.href = 'logout.php';
    }

    function popImage(src) {
        document.getElementById('imgFull').src = src;
        document.getElementById('imageModal').style.display = 'flex';
    }

    function openFullscreen() {
        let e = document.documentElement;
        if(e.requestFullscreen) e.requestFullscreen();
    }
    
    document.addEventListener("fullscreenchange", function() {
        let introHidden = document.getElementById('halaman-intro').style.display === 'none';
        document.getElementById('overlay-fullscreen').style.display = (introHidden && !document.fullscreenElement) ? 'flex' : 'none';
    });
    </script>
</body>
</html>