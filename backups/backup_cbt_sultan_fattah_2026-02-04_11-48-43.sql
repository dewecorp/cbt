-- Backup Database CBT Sultan Fattah
-- Tanggal: 04-02-2026 11:48:43
-- 

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";



CREATE TABLE `bank_soal` (
  `id_bank_soal` int NOT NULL AUTO_INCREMENT,
  `id_mapel` int NOT NULL,
  `id_guru` int NOT NULL,
  `kode_bank` varchar(50) NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  PRIMARY KEY (`id_bank_soal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;






CREATE TABLE `jawaban_siswa` (
  `id_jawaban` int NOT NULL AUTO_INCREMENT,
  `id_ujian_siswa` int NOT NULL,
  `id_soal` int NOT NULL,
  `jawaban` varchar(5) DEFAULT NULL,
  `ragu` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_jawaban`),
  KEY `id_ujian_siswa` (`id_ujian_siswa`),
  CONSTRAINT `jawaban_siswa_ibfk_1` FOREIGN KEY (`id_ujian_siswa`) REFERENCES `ujian_siswa` (`id_ujian_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;






CREATE TABLE `kelas` (
  `id_kelas` int NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(50) NOT NULL,
  PRIMARY KEY (`id_kelas`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO kelas VALUES
("1","1"),
("2","2"),
("3","3"),
("4","4");




CREATE TABLE `mapel` (
  `id_mapel` int NOT NULL AUTO_INCREMENT,
  `kode_mapel` varchar(20) NOT NULL,
  `nama_mapel` varchar(100) NOT NULL,
  PRIMARY KEY (`id_mapel`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO mapel VALUES
("1","BA","Bahasa Arab");




CREATE TABLE `setting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_sekolah` varchar(100) NOT NULL,
  `alamat` text,
  `logo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO setting VALUES
("1","MI Sultan Fattah Sukosono","Sukosono, Jepara","");




CREATE TABLE `siswa` (
  `id_siswa` int NOT NULL AUTO_INCREMENT,
  `nis` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_kelas` int NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  PRIMARY KEY (`id_siswa`),
  UNIQUE KEY `nis` (`nis`),
  KEY `id_kelas` (`id_kelas`),
  CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;






CREATE TABLE `soal` (
  `id_soal` int NOT NULL AUTO_INCREMENT,
  `id_bank_soal` int NOT NULL,
  `jenis` enum('pilihan_ganda','pilihan_ganda_kompleks','menjodohkan','isian_singkat','essay') NOT NULL DEFAULT 'pilihan_ganda',
  `pertanyaan` text NOT NULL,
  `opsi_a` text,
  `opsi_b` text,
  `opsi_c` text,
  `opsi_d` text,
  `opsi_e` text,
  `kunci_jawaban` text,
  PRIMARY KEY (`id_soal`),
  KEY `id_bank_soal` (`id_bank_soal`),
  CONSTRAINT `soal_ibfk_1` FOREIGN KEY (`id_bank_soal`) REFERENCES `bank_soal` (`id_bank_soal`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;






CREATE TABLE `ujian` (
  `id_ujian` int NOT NULL AUTO_INCREMENT,
  `nama_ujian` varchar(100) NOT NULL,
  `id_bank_soal` int NOT NULL,
  `tgl_mulai` datetime NOT NULL,
  `tgl_selesai` datetime NOT NULL,
  `waktu` int NOT NULL COMMENT 'dalam menit',
  `token` varchar(10) NOT NULL,
  `status` enum('aktif','selesai') NOT NULL DEFAULT 'aktif',
  PRIMARY KEY (`id_ujian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;






CREATE TABLE `ujian_siswa` (
  `id_ujian_siswa` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `id_siswa` int NOT NULL,
  `waktu_mulai` datetime DEFAULT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `nilai` decimal(5,2) DEFAULT '0.00',
  `status` enum('sedang_mengerjakan','selesai') NOT NULL DEFAULT 'sedang_mengerjakan',
  PRIMARY KEY (`id_ujian_siswa`),
  KEY `id_ujian` (`id_ujian`),
  KEY `id_siswa` (`id_siswa`),
  CONSTRAINT `ujian_siswa_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id_ujian`) ON DELETE CASCADE,
  CONSTRAINT `ujian_siswa_ibfk_2` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;






CREATE TABLE `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_plain` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `level` enum('admin','guru') NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO users VALUES
("1","admin","$2y$10$YP.Bho/SSXyyluUev823p.rX6C05lX7UcDUY3CsF6LG.usENchYlS","","Proktor","admin","","aktif","2026-02-04 10:20:53"),
("2","5436757658200002","$2y$10$VrKGzZQO0Y04xKj/RGq8yenNEoRiLNR1TbA6KfU2u6ijNnFwZ3P4u","sultanfattah25","Nur Huda","guru","","aktif","2026-02-04 11:07:52"),
("3","1234567890","$2y$10$cNYY3U3KXhW0IgLec7NQnuhJAj0IggaR6Q7VKXrY8GRpVgd5V2P3G","sultanfattah25","Guru Contoh 1","guru","","aktif","2026-02-04 11:12:16"),
("4","0987654321","$2y$10$lgrcCVFNsi9mUzCjdReHceBvh/VTsGkPrYrhxJJExy0qsQcymIIgm","sultanfattah25","Guru Contoh 2","guru","","aktif","2026-02-04 11:12:17"),
("5","12345","$2y$10$MTHMSI.FYHEUdjHs/jriT.RsioqxQZ4GK2N19/STN6tYThC5zHMIm","sultanfattah25","Udin","guru","","aktif","2026-02-04 11:12:17"),
("6","876544","$2y$10$pLqjs.Kk13DBUra7oRtfN.2OXPLa7irj8jq5nqxja.PDyv08.jnP2","sultanfattah25","Siti","guru","","aktif","2026-02-04 11:12:17"),
("7","88887777","$2y$10$DjT4S/utmX.kXeDsUkkmN.Q2w2ImIBb6YLVPGvt9Y1aNHhuGLBxjm","sultanfattah25","Atun","guru","","aktif","2026-02-04 11:12:17");



COMMIT;