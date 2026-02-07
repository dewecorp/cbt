<?php
include 'config/database.php';

// Simulate Student Session
$uid = 4; // Bilqis
$course_id = 2; // Bahasa Indonesia
$level = 'siswa';

echo "<h2>Debug Student View - Bahasa Indonesia</h2>";
echo "User ID: $uid (Bilqis)<br>";
echo "Course ID: $course_id <br>";

// Logic from course_manage.php for student
echo "<h3>Query Test</h3>";
$query = "SELECT a.*, s.nama_siswa FROM absensi a JOIN siswa s ON a.id_siswa=s.id_siswa WHERE a.id_siswa='$uid' AND a.id_course='$course_id' ORDER BY a.tanggal DESC";
echo "Query: $query <br><br>";

$q_absen = mysqli_query($koneksi, $query);
if (!$q_absen) {
    echo "Query Error: " . mysqli_error($koneksi);
} else {
    $num = mysqli_num_rows($q_absen);
    echo "Rows found: $num <br>";
    if ($num > 0) {
        echo "<table border=1><tr><th>Date</th><th>Status</th></tr>";
        while ($row = mysqli_fetch_assoc($q_absen)) {
            echo "<tr>";
            echo "<td>" . $row['tanggal'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No data found.";
    }
}
?>