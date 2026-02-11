-- phpMyAdmin SQL Dump
-- version 4.2.7.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 11, 2026 at 11:27 PM
-- Server version: 5.6.20-log
-- PHP Version: 7.2.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `ujian_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE IF NOT EXISTS `admin` (
`id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, '123', '123');

-- --------------------------------------------------------

--
-- Table structure for table `hasil_akhir`
--

CREATE TABLE IF NOT EXISTS `hasil_akhir` (
`id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `skor_pilgan` decimal(10,2) DEFAULT '0.00',
  `skor_esai` decimal(10,2) DEFAULT '0.00' COMMENT 'Diisi manual oleh dosen',
  `waktu_submit` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `log_ujian`
--

CREATE TABLE IF NOT EXISTS `log_ujian` (
`id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `soal_id` int(11) NOT NULL,
  `jawaban_siswa` text,
  `waktu_simpan` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `log_ujian`
--

INSERT INTO `log_ujian` (`id`, `siswa_id`, `soal_id`, `jawaban_siswa`, `waktu_simpan`) VALUES
(5, 0, 0, '', '2026-02-08 20:03:06'),
(6, 1, 7, 'C', '2026-02-11 23:22:57'),
(7, 1, 8, 'B', '2026-02-11 23:23:03'),
(8, 1, 9, 'C', '2026-02-11 23:23:05');

-- --------------------------------------------------------

--
-- Table structure for table `matakuliah`
--

CREATE TABLE IF NOT EXISTS `matakuliah` (
`id` int(11) NOT NULL,
  `nama_mk` varchar(100) NOT NULL,
  `kode_mk` varchar(20) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `matakuliah`
--

INSERT INTO `matakuliah` (`id`, `nama_mk`, `kode_mk`) VALUES
(1, 'Ornithologi', 'BIO101'),
(2, 'Fotografi Biologi', 'BIO102'),
(3, 'Ekowisata Jatimulyo', 'BIO103'),
(4, 'Ornitologi Quiz 1', 'ORN-Q1');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE IF NOT EXISTS `pengaturan` (
`id` int(11) NOT NULL,
  `mk_aktif_id` int(11) DEFAULT NULL,
  `kategori_aktif` varchar(50) DEFAULT 'UTS',
  `durasi_menit` int(11) DEFAULT '60'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `mk_aktif_id`, `kategori_aktif`, `durasi_menit`) VALUES
(1, 4, 'UTS', 15);

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE IF NOT EXISTS `siswa` (
`id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `waktu_mulai_ujian` datetime DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT '0',
  `last_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_ping` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id`, `nim`, `password`, `nama_lengkap`, `kelas`, `waktu_mulai_ujian`, `is_online`, `last_ip`, `created_at`, `last_ping`) VALUES
(1, '1', '1', '1', '1', '2026-02-11 23:22:41', 1, '::1', '2026-02-09 06:36:37', '2026-02-11 23:22:41');

-- --------------------------------------------------------

--
-- Table structure for table `soal`
--

CREATE TABLE IF NOT EXISTS `soal` (
`id` int(11) NOT NULL,
  `matakuliah_id` int(11) NOT NULL,
  `kategori` varchar(50) DEFAULT 'UTS',
  `pertanyaan` text NOT NULL,
  `opsi_a` text NOT NULL,
  `opsi_b` text NOT NULL,
  `opsi_c` text NOT NULL,
  `opsi_d` text NOT NULL,
  `kunci` varchar(5) DEFAULT NULL,
  `durasi_detik` int(11) DEFAULT '60',
  `media` varchar(255) DEFAULT NULL,
  `tipe` enum('pilgan','esai') DEFAULT 'pilgan'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;

--
-- Dumping data for table `soal`
--

INSERT INTO `soal` (`id`, `matakuliah_id`, `kategori`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci`, `durasi_detik`, `media`, `tipe`) VALUES
(1, 1, 'UTS', 'Manakah organ pada burung yang berfungsi sebagai sumber utama produksi suara (vokalisasi)?', 'Laring', 'Siring (Syrinx)', 'Faring', 'Trakea', NULL, 60, NULL, 'pilgan'),
(2, 1, 'UTS', 'Apa keuntungan utama dari struktur tulang pneumatik (berongga) pada burung?', 'Mempercepat pencernaan', 'Meningkatkan kekuatan otot', 'Meringankan beban tubuh saat terbang', 'Menyimpan cadangan kalsium', NULL, 60, NULL, 'pilgan'),
(3, 1, 'UTS', 'Dalam studi bioakustik, istilah "Sonogram" digunakan untuk visualisasi apa?', 'Struktur bulu burung', 'Frekuensi dan intensitas suara terhadap waktu', 'Kecepatan terbang burung', 'Peta migrasi tahunan', NULL, 60, NULL, 'pilgan'),
(4, 1, 'UTS', 'Jelaskan perbedaan antara panggilan (call) dan nyanyian (song) pada burung dari sisi fungsi biologisnya!', '', '', '', '', NULL, 60, NULL, 'esai'),
(5, 1, 'UTS', 'asd', '', '', '', '', 'A', 60, 'soal_1770581178_logo-uny.png', 'pilgan'),
(6, 1, 'UTS', 'asdwda', '', '', '', '', 'C', 60, '', 'esai'),
(7, 4, 'UTS', 'Saat melakukan pengamatan, Anda melihat burung Cekakak Jawa sedang memberi makan anaknya di sarang yang rendah. Tindakan paling etis adalah?', 'Mendekat perlahan untuk memotret close-up.', 'Memotong ranting yang menghalangi pandangan agar foto jelas.', 'Mengamati dari jarak jauh menggunakan binokuler dan segera pergi agar induk tidak stres.', 'Menggunakan playback agar anak burung keluar.', 'C', 60, NULL, 'pilgan'),
(8, 4, 'UTS', 'Mengapa kita dilarang menggunakan pakaian berwarna merah menyala saat pengamatan burung (Birdwatching)?', 'Karena warna merah menarik perhatian predator buas.', 'Karena warna merah menyerap panas matahari.', 'Karena burung memiliki penglihatan tajam dan warna mencolok dianggap ancaman.', 'Karena warna merah tidak estetik di foto.', 'C', 60, NULL, 'pilgan'),
(9, 4, 'UTS', 'Apa dampak negatif penggunaan playback (suara pancingan) dari aplikasi Merlin yang dilakukan secara berlebihan dan terus menerus pada satu individu burung?', 'Burung menjadi jinak.', 'Burung mengalami kelelahan energi (energy depletion) karena mempertahankan teritori semu.', 'Burung akan memanggil teman-temannya.', 'Kualitas suara rekaman menjadi lebih bagus.', 'B', 60, NULL, 'pilgan');

-- --------------------------------------------------------

--
-- Table structure for table `ujian_mahasiswa`
--

CREATE TABLE IF NOT EXISTS `ujian_mahasiswa` (
`id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `mk_id` int(11) NOT NULL,
  `waktu_mulai` datetime NOT NULL,
  `status` enum('sedang_mengerjakan','selesai') DEFAULT 'sedang_mengerjakan'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `ujian_mahasiswa`
--

INSERT INTO `ujian_mahasiswa` (`id`, `siswa_id`, `mk_id`, `waktu_mulai`, `status`) VALUES
(1, 2, 4, '2026-02-09 01:06:24', 'sedang_mengerjakan'),
(2, 2, 1, '2026-02-09 01:07:41', 'selesai'),
(3, 1, 1, '2026-02-09 06:36:46', 'selesai'),
(4, 1, 4, '2026-02-11 23:22:41', 'sedang_mengerjakan');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hasil_akhir`
--
ALTER TABLE `hasil_akhir`
 ADD PRIMARY KEY (`id`), ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `log_ujian`
--
ALTER TABLE `log_ujian`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `matakuliah`
--
ALTER TABLE `matakuliah`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `kode_mk` (`kode_mk`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `soal`
--
ALTER TABLE `soal`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ujian_mahasiswa`
--
ALTER TABLE `ujian_mahasiswa`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `hasil_akhir`
--
ALTER TABLE `hasil_akhir`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `log_ujian`
--
ALTER TABLE `log_ujian`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `matakuliah`
--
ALTER TABLE `matakuliah`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `soal`
--
ALTER TABLE `soal`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `ujian_mahasiswa`
--
ALTER TABLE `ujian_mahasiswa`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
