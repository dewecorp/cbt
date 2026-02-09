<?php
include '../../config/database.php';
session_name('CBT_SISWA');
session_start();

if(isset($_POST['id_jawaban'])) {
    $id_jawaban = $_POST['id_jawaban'];
    $ragu = $_POST['ragu'];
    mysqli_query($koneksi, "UPDATE jawaban_siswa SET ragu='$ragu' WHERE id_jawaban='$id_jawaban'");
}
?>
