<?php
include 'config/database.php';

$course_id = 2; // Bahasa Indonesia
echo "DEBUGGING VIEW FOR COURSE ID: $course_id\n";

// 1. Raw Count in Absensi
$q_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE id_course='$course_id'");
$d_count = mysqli_fetch_assoc($q_count);
echo "Total records in absensi table for course $course_id: " . $d_count['total'] . "\n";

// 2. Dump Records
$q_dump = mysqli_query($koneksi, "SELECT * FROM absensi WHERE id_course='$course_id'");
while ($r = mysqli_fetch_assoc($q_dump)) {
    echo " - Record: ID_Siswa=" . $r['id_siswa'] . ", Tanggal=" . $r['tanggal'] . ", Status=" . $r['status'] . "\n";
}

// 3. Simulate Course Manage Query
echo "\nSimulating Teacher Query:\n";
$q_absen = mysqli_query($koneksi, "
    SELECT a.*, s.nama_siswa 
    FROM absensi a 
    JOIN siswa s ON a.id_siswa=s.id_siswa 
    WHERE a.id_course='$course_id'
    ORDER BY a.tanggal DESC, a.jam_masuk ASC
");

if (!$q_absen) {
    echo "Query Failed: " . mysqli_error($koneksi) . "\n";
} else {
    echo "Query Rows: " . mysqli_num_rows($q_absen) . "\n";
    while ($row = mysqli_fetch_assoc($q_absen)) {
        echo " - [Visible] " . $row['nama_siswa'] . " (ID: " . $row['id_siswa'] . ") - " . $row['status'] . "\n";
    }
}
