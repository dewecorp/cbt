<?php
include '../../config/database.php';
session_name('CBT_SISWA');
session_start();

if(isset($_POST['id_jawaban'])) {
    $id_jawaban = $_POST['id_jawaban'];
    $jenis = $_POST['jenis'];
    $jawaban = "";

    if ($jenis == 'pilihan_ganda') {
        $jawaban = isset($_POST['jawaban']) ? $_POST['jawaban'] : '';
    } elseif ($jenis == 'pilihan_ganda_kompleks') {
        if(isset($_POST['jawaban_pgk'])) {
            $jawaban = implode(",", $_POST['jawaban_pgk']);
        }
    } elseif ($jenis == 'menjodohkan') {
        if(isset($_POST['match_pair'])) {
            $pairs = [];
            foreach($_POST['match_pair'] as $kiri => $kanan) {
                if($kanan !== "") {
                    $pairs[] = "$kiri:$kanan";
                }
            }
            $jawaban = implode(",", $pairs);
        }
    } elseif ($jenis == 'isian_singkat') {
        $jawaban = isset($_POST['jawaban_text']) ? $_POST['jawaban_text'] : '';
    } elseif ($jenis == 'essay') {
        $jawaban = isset($_POST['jawaban_essay']) ? $_POST['jawaban_essay'] : '';
    }

    $jawaban = mysqli_real_escape_string($koneksi, $jawaban);
    mysqli_query($koneksi, "UPDATE jawaban_siswa SET jawaban='$jawaban' WHERE id_jawaban='$id_jawaban'");
}
?>
