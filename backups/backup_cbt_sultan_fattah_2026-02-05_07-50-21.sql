-- Backup Database CBT Sultan Fattah
-- Tanggal: 05-02-2026 07:50:21
-- 

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";



CREATE TABLE `activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `level` varchar(20) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `module` varchar(50) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO activity_log VALUES
("1","1","admin","admin","update","guru","edit guru 7841746648200002","2026-02-05 07:50:07"),
("2","1","admin","admin","update","siswa","edit siswa 3137563185","2026-02-05 07:50:16");




CREATE TABLE `bank_soal` (
  `id_bank_soal` int NOT NULL AUTO_INCREMENT,
  `id_mapel` int NOT NULL,
  `id_kelas` int DEFAULT NULL,
  `id_guru` int NOT NULL,
  `kode_bank` varchar(50) NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bank_soal`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO bank_soal VALUES
("1","2","","7","UH Bahasa Indonesia","aktif","2026-02-04 15:07:01"),
("2","5","6","19","Ulangan Harian","aktif","2026-02-04 15:07:01"),
("3","2","6","19","Ulangan Harian","aktif","2026-02-04 20:34:50");




CREATE TABLE `jawaban_siswa` (
  `id_jawaban` int NOT NULL AUTO_INCREMENT,
  `id_ujian_siswa` int NOT NULL,
  `id_soal` int NOT NULL,
  `jawaban` text,
  `ragu` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_jawaban`),
  KEY `id_ujian_siswa` (`id_ujian_siswa`),
  CONSTRAINT `jawaban_siswa_ibfk_1` FOREIGN KEY (`id_ujian_siswa`) REFERENCES `ujian_siswa` (`id_ujian_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO jawaban_siswa VALUES
("1","1","2","A","0"),
("2","2","2","","0"),
("3","3","2","A","0"),
("4","4","11","","0"),
("5","4","7","D","0"),
("6","4","8","","0"),
("7","4","9","","0"),
("8","4","5","D","0"),
("9","4","10","","0"),
("10","4","3","C","0"),
("11","4","12","tema","0"),
("12","4","6","D","0"),
("13","4","4","B","0"),
("14","5","10","","1"),
("15","5","3","","1"),
("16","5","6","","1"),
("17","5","8","","0"),
("18","5","11","","1"),
("19","5","7","D","0"),
("20","5","5","D","0"),
("21","5","4","B","0"),
("22","5","12","","1"),
("23","5","9","","0"),
("24","6","7","D","0"),
("25","6","10","","0"),
("26","6","12","tema","0"),
("27","6","8","","0"),
("28","6","9","","0"),
("29","6","11","","0"),
("30","6","6","D","0"),
("31","6","5","D","0"),
("32","6","4","B","0"),
("33","6","3","C","0"),
("34","7","12","tema","0"),
("35","7","6","D","0"),
("36","7","8","orator","0"),
("37","7","3","C","0"),
("38","7","7","D","0"),
("39","7","10","kata kunci","0"),
("40","7","4","B","0"),
("41","7","11","detail dan rapi","0"),
("42","7","5","D","0"),
("43","7","9","membaca","0"),
("44","8","2","A","0");




CREATE TABLE `kelas` (
  `id_kelas` int NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(50) NOT NULL,
  PRIMARY KEY (`id_kelas`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO kelas VALUES
("1","1"),
("2","2"),
("3","3"),
("4","4"),
("5","5"),
("6","6");




CREATE TABLE `mapel` (
  `id_mapel` int NOT NULL AUTO_INCREMENT,
  `kode_mapel` varchar(20) NOT NULL,
  `nama_mapel` varchar(100) NOT NULL,
  PRIMARY KEY (`id_mapel`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO mapel VALUES
("1","BA","Bahasa Arab"),
("2","BI","Bahasa Indonesia"),
("3","BJ ","Bahasa Jawa"),
("4","AH","Al-Quran Hadis"),
("5","AA","Akidah Akhlak"),
("6","SKI","Sejarah Kebudayaan Islam"),
("7","SB","Seni Budaya "),
("8","FK","Fikih"),
("9","IPAS","IPAS"),
("10","PP","Pendidikan Pancasila"),
("11","PJOK","PJOK"),
("12","BTA","BTA"),
("13","NU","Ke-NU-an"),
("14","TAJ","Tajwid");




CREATE TABLE `setting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_sekolah` varchar(100) NOT NULL,
  `alamat` text,
  `logo` varchar(255) DEFAULT NULL,
  `tahun_ajaran` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `kepala_madrasah` varchar(100) DEFAULT NULL,
  `nip_kepala` varchar(50) DEFAULT NULL,
  `panitia_ujian` varchar(100) DEFAULT NULL,
  `admin_welcome_text` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO setting VALUES
("1","MI Sultan Fattah Sukosono","Jalan Kauman RT. 10 RW. 04 Sukosono Kedung Jepara 59463","logo_1770185899.png","2025/2026","1","Musriah, S.Pd.I.","-","Ali Yasin, S.Pd.I","<p>Aplikasi Computer Based Test (CBT) ini dirancang untuk memudahkan pelaksanaan ujian dan asesmen di MI Sultan Fattah Sukosono. Silahkan gunakan menu di samping untuk mengelola data dan ujian.</p>\n");




CREATE TABLE `siswa` (
  `id_siswa` int NOT NULL AUTO_INCREMENT,
  `nisn` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jk` enum('L','P') DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `id_kelas` int NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  PRIMARY KEY (`id_siswa`),
  UNIQUE KEY `nis` (`nisn`),
  KEY `id_kelas` (`id_kelas`),
  CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO siswa VALUES
("1","3146588936","ADIBA NUHA AZZAHRA","Jepara","2014-06-19","P","8PQ3D4","6","aktif"),
("2","3137563185","Amrina Rosyada","Jepara","2013-10-15","P","admin123","6","aktif"),
("3","3135628625","Aqilah Khoirurrosyadah","Jepara","2013-10-22","P","CM8YNQ","6","aktif"),
("4","3132163433","Bilqis Fahiya Rifda","Jepara","2013-12-29","P","E5BJ0U","6","aktif"),
("5","3138275600","Dewi Khuzaimah Annisa","Jepara","2013-11-29","P","RG8QNE","6","aktif"),
("6","0137840437","Diyah Ayu Prawesti","Jepara","2013-03-10","P","9FRXYC","6","aktif"),
("7","3137847985","Dzakira Talita Azzahra","Jepara","2013-11-22","P","1MI4P3","6","aktif"),
("8","3133372371","Fabregas Alviano","Jepara","2013-01-26","L","1OY9U3","6","aktif"),
("9","3146510193","Indana Zulfa","Jepara","2014-01-29","P","CQA5FT","6","aktif"),
("10","3133041280","Lidia Aura Citra","Jepara","2013-07-15","P","3LSU2R","6","aktif"),
("11","3149297726","Muhammad Agung Susilo Sugiono","Jepara","2014-05-07","L","A0TD2N","6","aktif"),
("12","3130180823","Muhammad Daris Alfurqon Aqim","Jepara","2013-07-24","L","SH5YTZ","6","aktif"),
("13","3140702123","Muhammad Egi Ferdiansyah","Jepara","2014-05-13","L","I9PDJH","6","aktif"),
("14","3130250384","Muhammad Elga Saputra","Jepara","2013-08-15","L","U3L56M","6","aktif"),
("15","3141710676","Najwah Fadia Amalia Fitri","Jepara","2014-03-29","P","4TSAQ3","6","aktif"),
("16","3136264986","Putra Sadewa Saifunnawas","Jepara","2013-02-11","L","X93F8W","6","aktif"),
("17","3137207114","Rizquna Halalan Thoyyiba","Jepara","2013-12-22","P","IMXECP","6","aktif"),
("18","3131634863","Siti Afifah Nauvalyn Fikriyah","Jepara","2013-02-06","P","KIOAZG","6","aktif"),
("19","3139561428","Siti Mei Listiana","Jepara","2013-02-05","P","HJ2C81","6","aktif"),
("20","3184602457","ABDULLAH HASAN","JEPARA","2018-07-15","L","PL1G9M","1","aktif"),
("21","3184275775","ABIZAR HABIBILLAH","JEPARA","2018-11-28","L","K43MX5","1","aktif"),
("22","3180229036","ADHITAMA ELVAN SYAHREZA","JEPARA","2018-12-15","L","35WPX4","1","aktif"),
("23","3182663303","AHMAD MANUTHO MUHAMMAD","JEPARA","2018-06-03","L","LCM251","1","aktif"),
("24","3194980092","AIRA ZAHWA SAFIRA","JEPARA","2019-05-11","P","7S6VFO","1","aktif"),
("25","3182355082","ARFAN MIYAZ ALINDRA","JEPARA","2018-03-03","L","OQWAL2","1","aktif"),
("26","3195153075","DELISA ALYA SAFIQNA","JEPARA","2019-08-19","P","T75UA2","1","aktif"),
("27","3195813730","DIAN AIRA","JEPARA","2019-01-12","P","ETNPF4","1","aktif"),
("28","3184245017","DHIRA QALESYA","JEPARA","2018-05-27","P","8SNPTZ","1","aktif"),
("29","3183882033","HIBAT ALMALIK","JEPARA","2018-08-14","L","M9OUYW","1","aktif"),
("30","3194274202","JIHAN FADHILLAH","JEPARA","2019-04-26","P","3JEN7D","1","aktif"),
("31","3177681680","KAYLA PUTRI AMALIA","JEPARA","2017-11-26","P","3QGKEY","1","aktif"),
("32","3190992049","LAILATUL JANNATU AZZA","JEPARA","2018-08-28","P","WUBXFK","1","aktif"),
("33","3172404776","MAUWAFIQ KHOIRUL FAJAR","JEPARA","2017-12-04","L","4SFBKC","1","aktif"),
("34","3198116081","NORREIN NABIHA","JEPARA","2019-07-19","P","984Q3G","1","aktif"),
("35","3186829907","RHEVA PUTRI RAMADHANI","JEPARA","2018-05-28","P","EWZYD5","1","aktif"),
("36","3188013385","SALMA SHAFIRA RAYYANA","JEPARA","2018-12-01","P","A0WZDQ","1","aktif"),
("37","3184514039","TUSAMMA SALSABILA","JEPARA","2018-05-04","P","CEYWUR","1","aktif");




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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO soal VALUES
("1","1","pilihan_ganda","siapa nama asli nyi roro kidul","siti","endang","atun","nor","","A"),
("2","2","pilihan_ganda","siapa nama asli nyi roro kidul","siti","endang","roro ayu","nor","","A"),
("3","3","pilihan_ganda","Riwayat hidup seseorang yang ditulis oleh orang lain disebut ....","biodata","daftar riwayat hidup","biografi","autobiografi","","C"),
("4","3","pilihan_ganda","Inti dari informasi yang akan disampaikan dalam pidato terdapat pada bagian ....","pembukaan","isi","kesimpulan","penutup","","B"),
("5","3","pilihan_ganda","Peta pikiran digunakan untuk ....","menyajikan gambar","membuat ringkasan","menulis laporan","menyajikaninformasi berupa kata kunci","","D"),
("6","3","pilihan_ganda","Arti kata aktivis adalah ....","pelajar","orang yang memimpin","orang yang peduli","orang yang bekerja aktif mendorong pelaksanaan sesuatu","","D"),
("7","3","pilihan_ganda","Berikut yang tidak termasuk bagian pembukaan dalam pidato adalah ....","salam pembuka","sapaan untuk pendengar","ucapan syukur","kesimpulan ","","D"),
("8","3","isian_singkat","Orang yang berpidato disebut ....","","","","","","orator"),
("9","3","isian_singkat","Sebelum menganalisis bacaan, kita harus ... bacaan dengan seksama ","","","","","","membaca"),
("10","3","isian_singkat","peta pikiran disajikan dalam bentuk ....","","","","","","kata kunci"),
("11","3","isian_singkat","Teks biografi menceritakan peristiwa secara ... dan ...","","","","","","detail dan kronologis"),
("12","3","isian_singkat","Isi pidato harus sama dengan ... pidato","","","","","","tema");




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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO ujian VALUES
("1","UH Bahasa Indonesia","1","2026-02-04 11:54:00","2026-02-05 11:54:00","60","L8WKDQ","aktif"),
("2","UH AKIDAH AKHLAK - Akidah Akhlak","2","2026-02-04 08:00:00","2026-02-05 10:00:00","60","0XQ3DT","aktif"),
("3","UH Bahasa Indonesia - Bahasa Indonesia","3","2026-02-04 20:52:00","2026-02-05 08:52:00","60","VA3KTB","aktif");




CREATE TABLE `ujian_siswa` (
  `id_ujian_siswa` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `id_siswa` int NOT NULL,
  `waktu_mulai` datetime DEFAULT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `nilai` decimal(5,2) DEFAULT '0.00',
  `status` enum('sedang_mengerjakan','selesai') NOT NULL DEFAULT 'sedang_mengerjakan',
  `tambah_waktu` int DEFAULT '0',
  PRIMARY KEY (`id_ujian_siswa`),
  KEY `id_ujian` (`id_ujian`),
  KEY `id_siswa` (`id_siswa`),
  CONSTRAINT `ujian_siswa_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id_ujian`) ON DELETE CASCADE,
  CONSTRAINT `ujian_siswa_ibfk_2` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO ujian_siswa VALUES
("1","2","1","2026-02-04 19:31:39","2026-02-04 19:31:50","100.00","selesai","0"),
("2","2","3","2026-02-04 19:38:38","2026-02-04 19:38:50","0.00","selesai","0"),
("3","2","4","2026-02-04 19:40:14","2026-02-04 19:40:22","100.00","selesai","0"),
("4","3","4","2026-02-04 20:53:37","2026-02-04 20:59:58","60.00","selesai","0"),
("5","3","1","2026-02-04 21:00:36","2026-02-04 21:02:25","30.00","selesai","0"),
("6","3","5","2026-02-04 21:14:13","2026-02-04 21:15:32","60.00","selesai","0"),
("7","3","15","2026-02-04 21:22:24","2026-02-04 21:23:28","90.00","selesai","0"),
("8","2","15","2026-02-04 21:24:00","2026-02-04 21:24:05","100.00","selesai","0");




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
  `mengajar_kelas` text,
  `mengajar_mapel` text,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO users VALUES
("1","admin","$2y$10$YP.Bho/SSXyyluUev823p.rX6C05lX7UcDUY3CsF6LG.usENchYlS","","Proktor","admin","","aktif","2026-02-04 10:20:53","",""),
("8","2444764667200003","$2y$10$1fuJLltLOAdGaPO4pTn67.eSwSD6fDbMaTISmRSS5j84QepQu96Ve","sultanfattah25","Abdul Ghofur, S.Pd.I","guru","","aktif","2026-02-04 12:06:32","5","4,2"),
("9","7841746648200002","$2y$10$HG3gxMUaFwracWCTjrVgNObEyDvpEgZCixbm8Zlg8NYWu.tE4Ak7W","sultanfattah25","Ah. Mustaqim Isom, A.Ma.","guru","","aktif","2026-02-04 12:06:32","5,6",""),
("10","33200111","$2y$10$ASu5IyFdjKDqQKU9TxhIIOf6Ryozm5xHwzZ03JSe/a3Pj8h4v2Wt.","sultanfattah25","Alfina Martha Sintya, S.Pd.","guru","","aktif","2026-02-04 12:06:32","4",""),
("11","9547746647110022","$2y$10$fj0p0rWM0f53WYJP7Ap0ve3BRh0cmffAwTIl68eqttLxCF1sUep0e","sultanfattah25","Ali Yasin, S.Pd.I","guru","","aktif","2026-02-04 12:06:32","3",""),
("12","4444747649200002","$2y$10$WFbFuN2QhFPKiv/81ufq7eY/2pncjAFK8TZaM5VP3WjTqSIDqTPje","sultanfattah25","Hamidah, A.Ma.","guru","","aktif","2026-02-04 12:06:33","2",""),
("13","2640755657300002","$2y$10$d.kx5b/L.GNN.OaaV8sVEuuaMAX1DcP836/0rEvC3.tZS1MrJ5zwq","sultanfattah25","Indasah, A.Ma.","guru","","aktif","2026-02-04 12:06:33","2,3",""),
("14","ID20318581190001","$2y$10$d3ckEpunMx1t.A0D3pXLk.IfxdeM0N65tbsQZfPQHY3ifgcPi/s4C","sultanfattah25","Khoiruddin, S.Pd.","guru","","aktif","2026-02-04 12:06:33","5",""),
("15","8552750652200002","$2y$10$LmcZQmY5aKSnofvaSaMYh.i.6E0dYttmpDKNVzXQlZTDWUPMDRs72","sultanfattah25","Muhamad Junaedi","guru","","aktif","2026-02-04 12:06:33","5,6",""),
("16","6956748651300002","$2y$10$8kWqCitntsSA7avMTnkKsusxMi9aD6.uubWffGSpTdoiDf2xL9PIy","sultanfattah25","Musri`ah, S.Pd.I","guru","","aktif","2026-02-04 12:06:33","3",""),
("17","6556755656300002","$2y$10$Jbkp2Wwhf8zXBeUF4Lu7suU./9MlswroqKmUM3MIr3k0HyqQ7yKlG","sultanfattah25","Nanik Purwati, S.Pd.I","guru","","aktif","2026-02-04 12:06:33","4",""),
("18","7357760661300003","$2y$10$5dwsO1f8BkuVioHZDHB7W.IQbbKdhdCpF0/hdyr119GteqyE/l6BW","sultanfattah25","Nur Hidah, S.Pd.I.","guru","","aktif","2026-02-04 12:06:33","2",""),
("19","5436757658200002","$2y$10$W8ClyVq41AuQOwLtamuHMuIh6bdH6EMKDJWIj8zLI3bT7rEExNQpy","sultanfattah25","Nur Huda, S.Pd.I.","guru","","aktif","2026-02-04 12:06:33","6","5,1,2,9,10,11,6,7"),
("20","8041756657300003","$2y$10$M8UZtVsB8.3gyQrtgr4Bhuv5DI0UQSxVTavrXf1z6TD/PQiDwm.Oa","sultanfattah25","Zama`ah, S.Pd.I.","guru","","aktif","2026-02-04 12:06:33","1","");



COMMIT;