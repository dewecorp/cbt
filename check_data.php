<?php
include 'config/database.php';

echo "<h2>Check Data Bilqis</h2>";
$q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE nama_siswa LIKE '%Bilqis%'");
if ($row = mysqli_fetch_assoc($q_siswa)) {
    $id_siswa = $row['id_siswa'];
    $id_kelas_siswa = $row['id_kelas'];
    echo "ID Siswa: $id_siswa <br>";
    echo "Nama: " . $row['nama_siswa'] . "<br>";
    echo "ID Kelas Siswa: $id_kelas_siswa <br>";
    
    echo "<h3>Absensi Records</h3>";
    $q_abs = mysqli_query($koneksi, "SELECT * FROM absensi WHERE id_siswa='$id_siswa' ORDER BY tanggal DESC");
    echo "<table border=1><tr><th>ID</th><th>Date</th><th>Course ID</th><th>Status</th></tr>";
    while ($r = mysqli_fetch_assoc($q_abs)) {
        echo "<tr>";
        echo "<td>" . $r['id_absensi'] . "</td>";
        echo "<td>" . $r['tanggal'] . "</td>";
        echo "<td>" . $r['id_course'] . "</td>";
        echo "<td>" . $r['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>Courses for Class $id_kelas_siswa</h3>";
    $q_courses = mysqli_query($koneksi, "SELECT * FROM courses WHERE id_kelas='$id_kelas_siswa'");
    echo "<table border=1><tr><th>ID Course</th><th>Nama Mapel (ID)</th></tr>";
    while ($c = mysqli_fetch_assoc($q_courses)) {
        $mid = $c['id_mapel'];
        $mname = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_mapel FROM mapel WHERE id_mapel='$mid'"))['nama_mapel'];
        echo "<tr>";
        echo "<td>" . $c['id_course'] . "</td>";
        echo "<td>$mname ($mid)</td>";
        echo "</tr>";
    }
    echo "</table>";

} else {
    echo "Siswa 'Bilqis' not found.";
}
?>