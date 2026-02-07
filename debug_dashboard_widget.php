<?php
include 'config/database.php';

// Simulate Student Session
$uid = 4; // Bilqis
$today = date('Y-m-d');

echo "<h2>Simulating Student Dashboard Widget Content</h2>";

// 1. General Attendance
$attendance_today_dash = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM absensi WHERE id_siswa='$uid' AND (id_course='0' OR id_course IS NULL) AND tanggal='$today'"));

if ($attendance_today_dash) {
    echo "General Status: " . $attendance_today_dash['status'] . "<br>";
    
    // 2. Subject Attendance (The new code)
    $q_sub_att = mysqli_query($koneksi, "
        SELECT a.*, m.nama_mapel 
        FROM absensi a 
        JOIN courses c ON a.id_course = c.id_course 
        JOIN mapel m ON c.id_mapel = m.id_mapel 
        WHERE a.id_siswa='$uid' AND a.tanggal='$today' AND a.id_course > 0
    ");

    if ($q_sub_att && mysqli_num_rows($q_sub_att) > 0) {
        echo "<h3>Subject Attendance Found:</h3>";
        while ($row_sub = mysqli_fetch_assoc($q_sub_att)) {
            echo "Subject: " . $row_sub['nama_mapel'] . " - Status: " . $row_sub['status'] . "<br>";
        }
    } else {
        echo "No Subject Attendance Found.<br>";
    }
} else {
    echo "General Attendance Not Found.<br>";
}
?>