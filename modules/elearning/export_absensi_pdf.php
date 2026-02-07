<?php
include '../../config/database.php';
session_start();

if (!isset($_SESSION['level']) || $_SESSION['level'] == 'siswa') {
    exit;
}

$bulan = isset($_GET['bulan']) ? sprintf('%02d', $_GET['bulan']) : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '';

if (empty($id_kelas)) {
    die("Pilih Kelas Terlebih Dahulu");
}

// Get Info
$q_k = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id_kelas='$id_kelas'");
$nama_kelas = ($r = mysqli_fetch_assoc($q_k)) ? $r['nama_kelas'] : '-';

// Month Names
$month_names = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$nama_bulan = $month_names[$bulan];

// Get Setting Sekolah
$q_setting = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_setting);
if (!$setting) {
    $setting = [
        'nama_sekolah' => 'CBT E-Learning',
        'alamat' => '',
        'logo' => ''
    ];
}

// Get Data
$students = [];
$q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_kelas='$id_kelas' AND status='aktif' ORDER BY nama_siswa ASC");
while($r = mysqli_fetch_assoc($q_siswa)) {
    $students[] = $r;
}

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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rekap Absensi - <?php echo $nama_kelas; ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        .header-section { text-align: center; margin-bottom: 20px; border-bottom: 2px solid black; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid black; padding: 4px; text-align: center; font-size: 9pt; }
        th { background-color: #f2f2f2; }
        .text-left { text-align: left; }
        @media print {
            @page { size: landscape; margin: 1cm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header-section">
        <h3 style="margin: 0;"><?php echo strtoupper($setting['nama_sekolah']); ?></h3>
        <p style="margin: 0;"><?php echo $setting['alamat']; ?></p>
        <h4 style="margin: 10px 0;">REKAP ABSENSI SISWA</h4>
    </div>

    <div style="margin-bottom: 10px;">
        <strong>Kelas:</strong> <?php echo $nama_kelas; ?> &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Bulan:</strong> <?php echo $nama_bulan . ' ' . $tahun; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="30">No</th>
                <th rowspan="2" width="200">Nama Siswa</th>
                <th colspan="<?php echo $days_in_month; ?>">Tanggal</th>
                <th rowspan="2" width="25" style="background-color: #d4edda;">H</th>
                <th rowspan="2" width="25" style="background-color: #fff3cd;">S</th>
                <th rowspan="2" width="25" style="background-color: #d1ecf1;">I</th>
                <th rowspan="2" width="25" style="background-color: #f8d7da;">A</th>
            </tr>
            <tr>
                <?php for($d=1; $d<=$days_in_month; $d++): ?>
                    <th width="20"><?php echo $d; ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach($students as $s): 
                $sid = $s['id_siswa'];
                $t_h = 0; $t_s = 0; $t_i = 0; $t_a = 0;
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td class="text-left"><?php echo htmlspecialchars($s['nama_siswa']); ?></td>
                <?php for($d=1; $d<=$days_in_month; $d++): 
                    $status = isset($attendance_data[$sid][$d]) ? $attendance_data[$sid][$d] : '';
                    if ($status == 'H') $t_h++;
                    if ($status == 'S') $t_s++;
                    if ($status == 'I') $t_i++;
                    if ($status == 'A') $t_a++;
                ?>
                    <td><?php echo $status; ?></td>
                <?php endfor; ?>
                <td><strong><?php echo $t_h; ?></strong></td>
                <td><strong><?php echo $t_s; ?></strong></td>
                <td><strong><?php echo $t_i; ?></strong></td>
                <td><strong><?php echo $t_a; ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: right;">
        <p>Guru Wali Kelas,</p>
        <br><br><br>
        <p>______________________</p>
    </div>
</body>
</html>
