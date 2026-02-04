<?php
include '../../config/database.php';
include '../../vendor/shuchkin/simplexlsxgen/src/SimpleXLSXGen.php';

$id_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$nama_kelas = '';

// Get class name if id is provided
if ($id_kelas) {
    $query = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id_kelas='$id_kelas'");
    if ($row = mysqli_fetch_assoc($query)) {
        $nama_kelas = $row['nama_kelas'];
    }
}

// Define Header
$header = ['No', 'NISN', 'Nama Siswa', 'Tempat Lahir', 'Tanggal Lahir (YYYY-MM-DD)', 'JK (L/P)', 'ID Kelas'];

// Define Example Data
// If class is selected, pre-fill the ID Kelas column
$example_row = ['1', '1234567890', 'Contoh Siswa', 'Jakarta', '2010-01-01', 'L', $id_kelas];

$data = [
    $header,
    $example_row
];

$xlsx = Shuchkin\SimpleXLSXGen::fromArray($data);
$filename = 'Template_Siswa' . ($nama_kelas ? '_'.$nama_kelas : '') . '.xlsx';

$xlsx->downloadAs($filename);
exit;
?>