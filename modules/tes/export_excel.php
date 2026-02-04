<?php
include '../../config/database.php';
include '../../vendor/shuchkin/simplexlsxgen/src/SimpleXLSXGen.php'; // Adjust path if necessary

session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['level'] != 'admin' && $_SESSION['level'] != 'guru')) {
    exit;
}

$id_ujian = isset($_GET['id_ujian']) ? $_GET['id_ujian'] : '';
$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '';

if (empty($id_ujian) || empty($id_kelas)) {
    echo "Parameter tidak lengkap.";
    exit;
}

// Get Info Ujian & Kelas
$q_info = mysqli_query($koneksi, "
    SELECT b.kode_bank AS nama_ujian, m.nama_mapel, k.nama_kelas, u.tgl_mulai, users.nama_lengkap AS nama_guru 
    FROM ujian u 
    JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal
    JOIN mapel m ON b.id_mapel = m.id_mapel
    JOIN kelas k ON k.id_kelas = '$id_kelas'
    LEFT JOIN users ON b.id_guru = users.id_user
    WHERE u.id_ujian = '$id_ujian'
");
$info = mysqli_fetch_assoc($q_info);

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

// Filename
$filename = "Rekap_Nilai_" . preg_replace('/[^a-zA-Z0-9]/', '_', $info['nama_mapel']) . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $info['nama_kelas']) . "_" . date('Ymd') . ".xlsx";

// Query Data
$query = "SELECT s.nisn, s.nama_siswa, us.nilai, us.status
          FROM siswa s
          LEFT JOIN ujian_siswa us ON s.id_siswa = us.id_siswa AND us.id_ujian = '$id_ujian'
          WHERE s.id_kelas = '$id_kelas'
          ORDER BY s.nama_siswa ASC";
$result = mysqli_query($koneksi, $query);

// Prepare Data for Excel
$data = [];

// Header Info (merged cells simulation by empty columns)
$data[] = ['REKAP NILAI HASIL ASESMEN'];
$data[] = [$setting['nama_sekolah']];
$data[] = [''];
$data[] = ['Mata Pelajaran', ': ' . $info['nama_mapel']];
$data[] = ['Nama Asesmen', ': ' . $info['nama_ujian']];
$data[] = ['Kelas', ': ' . $info['nama_kelas']];
$data[] = ['Tahun Ajaran', ': ' . $setting['tahun_ajaran']];
$data[] = ['Semester', ': ' . $setting['semester']];
$data[] = ['Tanggal', ': ' . date('d F Y', strtotime($info['tgl_mulai']))];
$data[] = [''];

// Table Header
$data[] = ['No', 'NISN', 'Nama Siswa', 'Nilai', 'Status', 'Keterangan'];

// Table Data
$no = 1;
while($row = mysqli_fetch_assoc($result)) {
    $nilai = $row['nilai'] ? $row['nilai'] : 0;
    $status = $row['status'] ? $row['status'] : 'Belum Mengerjakan';
    
    if ($status == 'selesai') {
        $ket = ($nilai >= 75) ? 'TUNTAS' : 'BELUM TUNTAS';
        $status_text = 'Selesai';
    } elseif ($status == 'sedang_mengerjakan') {
        $ket = '-';
        $status_text = 'Sedang Mengerjakan';
        $nilai = 0; // Or keep as is? Usually 0 if not finished.
    } else {
        $ket = '-';
        $status_text = 'Belum Mengerjakan';
        $nilai = 0;
    }

    $data[] = [
        $no++,
        $row['nisn'],
        $row['nama_siswa'],
        $nilai,
        $status_text,
        $ket
    ];
}

// Generate Excel
$xlsx = Shuchkin\SimpleXLSXGen::fromArray($data);
$xlsx->downloadAs($filename);
exit;
?>