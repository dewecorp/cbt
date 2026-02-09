<?php
include '../../config/database.php';
include '../../vendor/shuchkin/simplexlsxgen/src/SimpleXLSXGen.php';

include '../../includes/init_session.php';
if (!isset($_SESSION['level']) || $_SESSION['level'] == 'siswa') {
    exit;
}

$bulan = isset($_GET['bulan']) ? sprintf('%02d', $_GET['bulan']) : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '';

if (empty($id_kelas)) {
    die("Pilih Kelas Terlebih Dahulu");
}

// Get Class Name & Wali Kelas
$nama_kelas = '';
$wali_kelas = '';
$q_k = mysqli_query($koneksi, "SELECT kelas.nama_kelas, users.nama_lengkap AS nama_wali FROM kelas LEFT JOIN users ON kelas.wali_kelas = users.id_user WHERE kelas.id_kelas='$id_kelas'");
if ($r = mysqli_fetch_assoc($q_k)) {
    $nama_kelas = $r['nama_kelas'];
    $wali_kelas = $r['nama_wali'];
}

// Month Names
$month_names = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$nama_bulan = $month_names[$bulan];

// Get Students
$students = [];
$q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_kelas='$id_kelas' AND status='aktif' ORDER BY nama_siswa ASC");
while($r = mysqli_fetch_assoc($q_siswa)) {
    $students[] = $r;
}

// Get Attendance
$attendance_data = [];
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$start_date = "$tahun-$bulan-01";
$end_date = "$tahun-$bulan-$days_in_month";

$q_att = mysqli_query($koneksi, "
    SELECT a.id_siswa, DAY(a.tanggal) as day, a.status 
    FROM absensi a 
    JOIN siswa s ON a.id_siswa = s.id_siswa
    WHERE s.id_kelas = '$id_kelas' 
    AND (a.id_course = '0' OR a.id_course IS NULL)
    AND a.tanggal BETWEEN '$start_date' AND '$end_date'
");

while($row = mysqli_fetch_assoc($q_att)) {
    $code = '-';
    if ($row['status'] == 'Hadir') $code = 'H';
    elseif ($row['status'] == 'Sakit') $code = 'S';
    elseif ($row['status'] == 'Izin') $code = 'I';
    elseif ($row['status'] == 'Alpha') $code = 'A';
    
    $attendance_data[$row['id_siswa']][$row['day']] = $code;
}

// Prepare Excel Data
$data = [];

// Title Rows
$data[] = ["REKAP ABSENSI SISWA"];
$data[] = ["Kelas: $nama_kelas"];
$data[] = ["Wali Kelas: $wali_kelas"];
$data[] = ["Bulan: $nama_bulan $tahun"];
$data[] = [""]; // Empty row

// Header Row
$header = ['No', 'Nama Siswa'];
for($d=1; $d<=$days_in_month; $d++) {
    $header[] = $d;
}
$header[] = 'H';
$header[] = 'S';
$header[] = 'I';
$header[] = 'A';
$data[] = $header;

// Data Rows
$no = 1;
foreach($students as $s) {
    $sid = $s['id_siswa'];
    $row = [$no++, $s['nama_siswa']];
    $t_h = 0; $t_s = 0; $t_i = 0; $t_a = 0;
    
    for($d=1; $d<=$days_in_month; $d++) {
        $status = isset($attendance_data[$sid][$d]) ? $attendance_data[$sid][$d] : '';
        if ($status == 'H') $t_h++;
        if ($status == 'S') $t_s++;
        if ($status == 'I') $t_i++;
        if ($status == 'A') $t_a++;
        $row[] = $status;
    }
    $row[] = $t_h;
    $row[] = $t_s;
    $row[] = $t_i;
    $row[] = $t_a;
    $data[] = $row;
}

// Generate
$filename = "Rekap_Absensi_{$nama_kelas}_{$nama_bulan}_{$tahun}.xlsx";
$xlsx = Shuchkin\SimpleXLSXGen::fromArray($data);
$xlsx->downloadAs($filename);
exit;
?>