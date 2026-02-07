<?php
include 'config/database.php';

$id_kelas = 6;
$hari = 'Sabtu'; // Today is Saturday

echo "<h2>Check Schedule Class 6 - Saturday</h2>";
$q = mysqli_query($koneksi, "SELECT * FROM jadwal_pelajaran WHERE id_kelas='$id_kelas' AND hari='$hari'");
if (mysqli_num_rows($q) > 0) {
    $r = mysqli_fetch_assoc($q);
    echo "Schedule Found!<br>";
    echo "Mapel IDs: " . $r['mapel_ids'] . "<br>";
} else {
    echo "No Schedule Found for Class 6 on Saturday.<br>";
}

echo "<h3>Absensi General Check</h3>";
$uid = 4; // Bilqis
$today = date('Y-m-d');
$q_gen = mysqli_query($koneksi, "SELECT * FROM absensi WHERE id_siswa='$uid' AND (id_course='0' OR id_course IS NULL) AND tanggal='$today'");
if (mysqli_num_rows($q_gen) > 0) {
    echo "General Attendance Found.<br>";
} else {
    echo "General Attendance NOT Found.<br>";
}
?>