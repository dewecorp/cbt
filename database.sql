-- Database: cbt_sultan_fattah

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- Tabel Users (Admin & Guru)
CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `level` enum('admin','guru') NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`)
);

-- Default Admin: admin / admin123
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `level`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Tabel Kelas
CREATE TABLE `kelas` (
  `id_kelas` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(50) NOT NULL,
  PRIMARY KEY (`id_kelas`)
);

-- Tabel Siswa
CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL AUTO_INCREMENT,
  `nis` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  PRIMARY KEY (`id_siswa`),
  UNIQUE KEY `nis` (`nis`),
  FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE
);

-- Tabel Mapel (Mata Pelajaran)
CREATE TABLE `mapel` (
  `id_mapel` int(11) NOT NULL AUTO_INCREMENT,
  `kode_mapel` varchar(20) NOT NULL,
  `nama_mapel` varchar(100) NOT NULL,
  PRIMARY KEY (`id_mapel`)
);

-- Tabel Bank Soal
CREATE TABLE `bank_soal` (
  `id_bank_soal` int(11) NOT NULL AUTO_INCREMENT,
  `id_mapel` int(11) NOT NULL,
  `id_guru` int(11) NOT NULL,
  `kode_bank` varchar(50) NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  PRIMARY KEY (`id_bank_soal`)
);

-- Tabel Soal
CREATE TABLE `soal` (
  `id_soal` int(11) NOT NULL AUTO_INCREMENT,
  `id_bank_soal` int(11) NOT NULL,
  `jenis` enum('pilihan_ganda','pilihan_ganda_kompleks','menjodohkan','isian_singkat','essay') NOT NULL DEFAULT 'pilihan_ganda',
  `pertanyaan` text NOT NULL,
  `opsi_a` text,
  `opsi_b` text,
  `opsi_c` text,
  `opsi_d` text,
  `opsi_e` text,
  `kunci_jawaban` text NOT NULL,
  PRIMARY KEY (`id_soal`),
  FOREIGN KEY (`id_bank_soal`) REFERENCES `bank_soal` (`id_bank_soal`) ON DELETE CASCADE
);

-- Tabel Ujian
CREATE TABLE `ujian` (
  `id_ujian` int(11) NOT NULL AUTO_INCREMENT,
  `nama_ujian` varchar(100) NOT NULL,
  `id_bank_soal` int(11) NOT NULL,
  `tgl_mulai` datetime NOT NULL,
  `tgl_selesai` datetime NOT NULL,
  `waktu` int(11) NOT NULL COMMENT 'dalam menit',
  `token` varchar(10) NOT NULL,
  `status` enum('aktif','selesai') NOT NULL DEFAULT 'aktif',
  PRIMARY KEY (`id_ujian`)
);

-- Tabel Ujian Siswa (Untuk mencatat siswa yang sedang/sudah ujian)
CREATE TABLE `ujian_siswa` (
  `id_ujian_siswa` int(11) NOT NULL AUTO_INCREMENT,
  `id_ujian` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `waktu_mulai` datetime DEFAULT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `nilai` decimal(5,2) DEFAULT 0,
  `status` enum('sedang_mengerjakan','selesai') NOT NULL DEFAULT 'sedang_mengerjakan',
  PRIMARY KEY (`id_ujian_siswa`),
  FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id_ujian`) ON DELETE CASCADE,
  FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
);

-- Tabel Jawaban Siswa
CREATE TABLE `jawaban_siswa` (
  `id_jawaban` int(11) NOT NULL AUTO_INCREMENT,
  `id_ujian_siswa` int(11) NOT NULL,
  `id_soal` int(11) NOT NULL,
  `jawaban` varchar(5) DEFAULT NULL,
  `ragu` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_jawaban`),
  FOREIGN KEY (`id_ujian_siswa`) REFERENCES `ujian_siswa` (`id_ujian_siswa`) ON DELETE CASCADE
);

-- Tabel Setting
CREATE TABLE `setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_sekolah` varchar(100) NOT NULL,
  `alamat` text,
  `logo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `setting` (`nama_sekolah`, `alamat`) VALUES ('MI Sultan Fattah Sukosono', 'Sukosono, Jepara');

COMMIT;
