<?php
include 'config/database.php';

echo "<h2>Schema Check</h2>";
echo "<h3>Absensi</h3>";
$q = mysqli_query($koneksi, "DESCRIBE absensi");
while($r = mysqli_fetch_assoc($q)) {
    echo $r['Field'] . " - " . $r['Type'] . "<br>";
}

echo "<h3>Siswa</h3>";
$q = mysqli_query($koneksi, "DESCRIBE siswa");
while($r = mysqli_fetch_assoc($q)) {
    echo $r['Field'] . " - " . $r['Type'] . "<br>";
}

echo "<h3>Check IDs</h3>";
$q = mysqli_query($koneksi, "SELECT id_siswa FROM absensi WHERE id_course='1'");
while($r = mysqli_fetch_assoc($q)) {
    echo "Absensi ID Siswa: '" . $r['id_siswa'] . "'<br>";
    $sid = $r['id_siswa'];
    $qs = mysqli_query($koneksi, "SELECT id_siswa FROM siswa WHERE id_siswa='$sid'");
    if(mysqli_num_rows($qs) > 0) echo " -> Found in Siswa<br>";
    else echo " -> NOT FOUND in Siswa<br>";
}
?>