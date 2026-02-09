<?php
include '../../config/database.php';
include '../../vendor/shuchkin/simplexlsxgen/src/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

include '../../includes/init_session.php';

if (!isset($_SESSION['level']) || ($_SESSION['level'] !== 'guru' && $_SESSION['level'] !== 'admin')) {
    exit;
}

$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$uid = $_SESSION['user_id'];
$level = $_SESSION['level'];

if ($assignment_id <= 0) {
    echo "ID Tugas tidak valid.";
    exit;
}

// Verify assignment
$q_assign = mysqli_query($koneksi, "
    SELECT a.*, c.id_kelas, c.nama_course, k.nama_kelas, m.nama_mapel 
    FROM assignments a 
    JOIN courses c ON a.course_id = c.id_course 
    JOIN kelas k ON c.id_kelas = k.id_kelas
    JOIN mapel m ON c.id_mapel = m.id_mapel
    WHERE a.id_assignment = '$assignment_id'
");
$assignment = mysqli_fetch_assoc($q_assign);

if (!$assignment) {
    echo "Tugas tidak ditemukan.";
    exit;
}

if ($level === 'guru' && $assignment['created_by'] != $uid) {
    echo "Anda tidak memiliki akses ke tugas ini.";
    exit;
}

// Fetch Submissions
$q_subs = mysqli_query($koneksi, "
    SELECT s.*
    FROM submissions s 
    WHERE s.assignment_id = '$assignment_id' 
");

// Map submissions
$submitted_data = [];
while($sub = mysqli_fetch_assoc($q_subs)) {
    $submitted_data[$sub['siswa_id']] = $sub;
}

// Fetch All Students
$q_students = mysqli_query($koneksi, "
    SELECT sw.id_siswa, sw.nama_siswa, sw.nisn 
    FROM siswa sw 
    WHERE sw.id_kelas = '".$assignment['id_kelas']."' AND sw.status='aktif'
    ORDER BY sw.nama_siswa ASC
");

// Get Setting Sekolah
$q_setting = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_setting);
if (!$setting) {
    $setting = [
        'nama_sekolah' => 'CBT MI SULTAN FATTAH SUKOSONO',
        'tahun_ajaran' => '-',
        'semester' => '-'
    ];
}

$filename = "Nilai_Tugas_" . preg_replace('/[^a-zA-Z0-9]/', '_', $assignment['judul']) . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $assignment['nama_kelas']) . "_" . date('Ymd') . ".xlsx";

$data = [];
$data[] = ['REKAP NILAI TUGAS'];
$data[] = [$setting['nama_sekolah']];
$data[] = [''];
$data[] = ['Judul Tugas', ': ' . $assignment['judul']];
$data[] = ['Mata Pelajaran', ': ' . $assignment['nama_mapel']];
$data[] = ['Kelas', ': ' . $assignment['nama_kelas']];
$data[] = ['Tahun Ajaran', ': ' . $setting['tahun_ajaran']];
$data[] = ['Semester', ': ' . $setting['semester']];
$data[] = [''];

$data[] = ['No', 'NISN', 'Nama Siswa', 'Status', 'Waktu Kirim', 'Nilai', 'Catatan Guru'];

$no = 1;
while($r = mysqli_fetch_assoc($q_students)) {
    $sid = $r['id_siswa'];
    $sub = isset($submitted_data[$sid]) ? $submitted_data[$sid] : null;
    
    $status = $sub ? 'Sudah Mengumpulkan' : 'Belum Mengumpulkan';
    $waktu = $sub ? date('d/m/Y H:i', strtotime($sub['submitted_at'])) : '-';
    $nilai = ($sub && $sub['nilai'] !== null) ? $sub['nilai'] : '-';
    $catatan = ($sub && $sub['catatan']) ? $sub['catatan'] : '-';
    
    $data[] = [
        $no++,
        $r['nisn'],
        $r['nama_siswa'],
        $status,
        $waktu,
        $nilai,
        $catatan
    ];
}

$xlsx = SimpleXLSXGen::fromArray($data);
$xlsx->downloadAs($filename);
exit;
?>