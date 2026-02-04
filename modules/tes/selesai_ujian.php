<?php
include '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    exit;
}

$id_ujian = $_GET['id'];
$id_siswa = $_SESSION['user_id'];

// Get ID Ujian Siswa
$us = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id_ujian_siswa FROM ujian_siswa WHERE id_ujian='$id_ujian' AND id_siswa='$id_siswa'"));
$id_ujian_siswa = $us['id_ujian_siswa'];

// Hitung Nilai (Sederhana - hanya untuk PG dan Isian Singkat yang exact match)
// Essay dan Kompleks butuh logika lebih rumit, disini kita skip dulu atau hitung simple
$q_jawab = mysqli_query($koneksi, "
    SELECT js.jawaban as jawab_siswa, s.kunci_jawaban, s.jenis 
    FROM jawaban_siswa js 
    JOIN soal s ON js.id_soal = s.id_soal 
    WHERE js.id_ujian_siswa='$id_ujian_siswa'
");

$total_benar = 0;
$total_soal = mysqli_num_rows($q_jawab);

while($r = mysqli_fetch_assoc($q_jawab)) {
    if($r['jenis'] == 'pilihan_ganda' || $r['jenis'] == 'isian_singkat') {
        if(strtoupper(trim($r['jawab_siswa'])) == strtoupper(trim($r['kunci_jawaban']))) {
            $total_benar++;
        }
    } elseif($r['jenis'] == 'pilihan_ganda_kompleks') {
        // Logika sederhana: exact match string (A,B == A,B)
        // Perlu sort dulu biar aman
        $kunci_arr = explode(',', $r['kunci_jawaban']);
        $jawab_arr = explode(',', $r['jawab_siswa']);
        sort($kunci_arr);
        sort($jawab_arr);
        if($kunci_arr == $jawab_arr) {
            $total_benar++;
        }
    } elseif($r['jenis'] == 'menjodohkan') {
        // Logika: exact match string pairs
        if($r['jawab_siswa'] == $r['kunci_jawaban']) {
            $total_benar++;
        }
    }
    // Essay dianggap 0 dulu (perlu koreksi manual)
}

$nilai = ($total_soal > 0) ? ($total_benar / $total_soal) * 100 : 0;

mysqli_query($koneksi, "UPDATE ujian_siswa SET status='selesai', waktu_selesai=NOW(), nilai='$nilai' WHERE id_ujian_siswa='$id_ujian_siswa'");

echo "<script>alert('Ujian Selesai! Nilai Sementara (PG/Otomatis): ".number_format($nilai, 2)."'); window.location='../../dashboard.php';</script>";
?>
