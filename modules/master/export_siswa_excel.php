<?php
include '../../config/database.php';
include '../../vendor/shuchkin/simplexlsxgen/src/SimpleXLSXGen.php';
include '../../includes/init_session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['level'] !== 'admin') {
    exit;
}

use Shuchkin\SimpleXLSXGen;

$id_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
if (empty($id_kelas)) {
    echo "Kelas tidak dipilih.";
    exit;
}

$q_kelas = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id_kelas='$id_kelas'");
$nama_kelas = '';
if ($q_kelas && mysqli_num_rows($q_kelas) > 0) {
    $row_k = mysqli_fetch_assoc($q_kelas);
    $nama_kelas = $row_k['nama_kelas'];
}

$q_setting = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_setting);
if (!$setting) {
    $setting = [
        'nama_sekolah' => 'CBT MI SULTAN FATTAH SUKOSONO',
        'tahun_ajaran' => '-',
        'semester' => '-'
    ];
}

$data = [];
$data[] = ['DATA SISWA'];
$data[] = [$setting['nama_sekolah']];
$data[] = [''];
$data[] = ['Kelas', ': ' . $nama_kelas];
$data[] = ['Tahun Ajaran', ': ' . $setting['tahun_ajaran']];
$data[] = ['Semester', ': ' . $setting['semester']];
$data[] = ['Tanggal Export', ': ' . date('d-m-Y')];
$data[] = [''];

$data[] = ['No', 'NISN', 'Nama Siswa', 'L/P', 'Tempat Lahir', 'Tanggal Lahir', 'Kelas', 'Password'];

$query = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.id_kelas = '$id_kelas' ORDER BY s.nama_siswa ASC");
$no = 1;
while ($row = mysqli_fetch_assoc($query)) {
    $password_display = $row['password'];
    if (strlen($row['password']) == 60 && substr($row['password'], 0, 4) === '$2y$') {
        $password_display = 'Ter-enkripsi';
    }
    $data[] = [
        $no++,
        $row['nisn'],
        $row['nama_siswa'],
        $row['jk'],
        $row['tempat_lahir'],
        date('d-m-Y', strtotime($row['tanggal_lahir'])),
        $row['nama_kelas'],
        $password_display
    ];
}

$nama_kelas_safe = $nama_kelas ? preg_replace('/[^a-zA-Z0-9]/', '_', $nama_kelas) : 'Semua';
$filename = "Data_Siswa_" . $nama_kelas_safe . "_" . date('Ymd') . ".xlsx";

$xlsx = SimpleXLSXGen::fromArray($data);
$xlsx->downloadAs($filename);
exit;

