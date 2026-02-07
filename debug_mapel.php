<?php
include 'config/database.php';

echo "DUMPING MAPEL TABLE\n";
$q = mysqli_query($koneksi, "SELECT * FROM mapel");
while ($r = mysqli_fetch_assoc($q)) {
    echo "ID: " . $r['id_mapel'] . " - " . $r['nama_mapel'] . "\n";
}

echo "\nDUMPING COURSES\n";
$q2 = mysqli_query($koneksi, "SELECT * FROM courses");
while ($c = mysqli_fetch_assoc($q2)) {
    echo "CourseID: " . $c['id_course'] . " - ClassID: " . $c['id_kelas'] . " - MapelID: " . $c['id_mapel'] . "\n";
}
