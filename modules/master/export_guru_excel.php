<?php
include '../../config/database.php';
include '../../vendor/shuchkin/simplexlsxgen/src/SimpleXLSXGen.php';
include '../../includes/init_session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['level'] !== 'admin') {
    exit;
}

use Shuchkin\SimpleXLSXGen;

// Get school setting
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
$data[] = ['DATA GURU'];
$data[] = [$setting['nama_sekolah']];
$data[] = [''];
$data[] = ['Tahun Ajaran', ': ' . $setting['tahun_ajaran']];
$data[] = ['Semester', ': ' . $setting['semester']];
$data[] = ['Tanggal Export', ': ' . date('d-m-Y')];
$data[] = [''];

$data[] = ['No', 'NUPTK', 'Nama Lengkap', 'L/P', 'Password', 'Mengajar Kelas', 'Mengajar Mapel'];

// Get Kelas and Mapel names for mapping
$kelas_map = [];
$qk = mysqli_query($koneksi, "SELECT * FROM kelas");
while($rk = mysqli_fetch_assoc($qk)) $kelas_map[$rk['id_kelas']] = $rk['nama_kelas'];

$mapel_map = [];
$qm = mysqli_query($koneksi, "SELECT * FROM mapel");
while($rm = mysqli_fetch_assoc($qm)) $mapel_map[$rm['id_mapel']] = $rm['nama_mapel'];

$query = mysqli_query($koneksi, "SELECT * FROM users WHERE level='guru' ORDER BY nama_lengkap ASC");
$no = 1;
while ($row = mysqli_fetch_assoc($query)) {
    // Process mengajar kelas
    $m_kelas = [];
    if(!empty($row['mengajar_kelas'])) {
        foreach(explode(',', $row['mengajar_kelas']) as $idk) {
            if(isset($kelas_map[$idk])) $m_kelas[] = $kelas_map[$idk];
        }
    }
    
    // Process mengajar mapel
    $m_mapel = [];
    if(!empty($row['mengajar_mapel'])) {
        foreach(explode(',', $row['mengajar_mapel']) as $idm) {
            if(isset($mapel_map[$idm])) $m_mapel[] = $mapel_map[$idm];
        }
    }

    $data[] = [
        $no++,
        $row['username'],
        $row['nama_lengkap'],
        $row['jk'] ? $row['jk'] : '-',
        $row['password_plain'],
        implode(', ', $m_kelas),
        implode(', ', $m_mapel)
    ];
}

$filename = "Data_Guru_" . date('Ymd') . ".xlsx";

$xlsx = SimpleXLSXGen::fromArray($data);
$xlsx->downloadAs($filename);
exit;
