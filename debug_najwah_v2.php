<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config/database.php';
echo "Connected to database.\n";

$nisn = '3141710676';
echo "Searching for Student with NISN: $nisn\n";

$q = mysqli_query($koneksi, "SELECT * FROM siswa WHERE nisn='$nisn'");
if (!$q) {
    die("Query failed: " . mysqli_error($koneksi));
}

if (mysqli_num_rows($q) == 0) {
    die("Student not found.\n");
}

$siswa = mysqli_fetch_assoc($q);
print_r($siswa);

$id_siswa = $siswa['id_siswa'];
$id_kelas = $siswa['id_kelas'];

echo "\nSearching for assignments for Class ID: $id_kelas\n";

// Get assignments
$sql_assign = "
    SELECT a.id_assignment, a.judul, m.nama_mapel 
    FROM assignments a 
    JOIN courses c ON a.course_id = c.id_course 
    JOIN mapel m ON c.id_mapel = m.id_mapel
    WHERE c.id_kelas = '$id_kelas'
";
$q_assign = mysqli_query($koneksi, $sql_assign);
$assignments = [];
while ($r = mysqli_fetch_assoc($q_assign)) {
    echo "Assignment ID: " . $r['id_assignment'] . " - " . $r['judul'] . " (" . $r['nama_mapel'] . ")\n";
    $assignments[] = $r['id_assignment'];
}

if (empty($assignments)) {
    die("No assignments found for this class.\n");
}

echo "\nChecking ALL submissions for this student:\n";
$q_subs = mysqli_query($koneksi, "SELECT * FROM submissions WHERE siswa_id = '$id_siswa'");
while ($sub = mysqli_fetch_assoc($q_subs)) {
    print_r($sub);
}

?>