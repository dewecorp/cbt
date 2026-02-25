<?php
include '../../config/database.php';
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
        'alamat' => '',
        'logo' => '',
        'tahun_ajaran' => '-',
        'semester' => '-'
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rekap Nilai Tugas - <?php echo $assignment['judul']; ?></title>
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11pt; }
        .header-section { text-align: center; margin-bottom: 20px; border-bottom: 2px solid black; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th, td { border: 1px solid black; padding: 4px 6px; word-wrap: break-word; }
        th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center; }
        @media print {
            @page { size: A4 portrait; margin: 1cm 2cm 1cm 1cm; }
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
            .container { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container py-4 p-print-0">
        <table style="width: 100%; border: none; border-bottom: 2px solid black; margin-bottom: 20px;">
            <tr>
                <td style="width: 100px; text-align: center; border: none; padding: 10px;">
                    <?php if(!empty($setting['logo']) && file_exists('../../assets/img/'.$setting['logo'])): ?>
                        <img src="../../assets/img/<?php echo $setting['logo']; ?>" style="height: 80px; width: auto;">
                    <?php endif; ?>
                </td>
                <td style="text-align: center; border: none;">
                    <h3 style="margin: 0;">REKAP NILAI TUGAS</h3>
                    <h4 style="margin: 5px 0;"><?php echo strtoupper($setting['nama_sekolah']); ?></h4>
                    <p style="margin: 0; font-size: 10pt;"><?php echo $setting['alamat']; ?></p>
                </td>
                <td style="width: 100px; border: none;"></td>
            </tr>
        </table>

        <table style="border: none; margin-bottom: 15px;">
            <tr>
                <td style="border: none; width: 20%;">Judul Tugas</td>
                <td style="border: none; width: 30%;">: <?php echo $assignment['judul']; ?></td>
                <td style="border: none; width: 5%;"></td>
                <td style="border: none; width: 20%;">Kelas</td>
                <td style="border: none; width: 25%;">: <?php echo $assignment['nama_kelas']; ?></td>
            </tr>
            <tr>
                <td style="border: none;">Mata Pelajaran</td>
                <td style="border: none;">: <?php echo $assignment['nama_mapel']; ?></td>
                <td style="border: none;"></td>
                <td style="border: none;">Tahun Ajaran</td>
                <td style="border: none;">: <?php echo $setting['tahun_ajaran']; ?></td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">NISN</th>
                    <th width="30%">Nama Siswa</th>
                    <th width="15%">Waktu Kirim</th>
                    <th width="10%">Nilai</th>
                    <th width="25%">Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($r = mysqli_fetch_assoc($q_students)): 
                    $sid = $r['id_siswa'];
                    $sub = isset($submitted_data[$sid]) ? $submitted_data[$sid] : null;
                    $waktu = $sub ? date('d/m/Y H:i', strtotime($sub['submitted_at'])) : '-';
                    $nilai = ($sub && $sub['nilai'] !== null) ? $sub['nilai'] : '-';
                    $catatan = ($sub && $sub['catatan']) ? $sub['catatan'] : '-';
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-center"><?php echo $r['nisn']; ?></td>
                    <td><?php echo $r['nama_siswa']; ?></td>
                    <td class="text-center"><?php echo $waktu; ?></td>
                    <td class="text-center"><?php echo $nilai; ?></td>
                    <td><?php echo $catatan; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Tanda Tangan Section -->
        <div style="margin-top: 40px; page-break-inside: avoid;">
            <table style="width: 100%; border: none; border-collapse: collapse; table-layout: fixed;">
                <tr>
                    <td style="border: none; text-align: center; width: 45%; padding: 0;">
                        <?php if(!empty($setting['kepala_madrasah'])): ?>
                            <p style="margin-bottom: 5px;">Mengetahui,</p>
                            <p style="margin-bottom: 10px;">Kepala Madrasah</p>
                            <div style="margin: 10px 0;">
                                <?php 
                                $qr_data_kepala = "Dokumen Sah Elektronik: " . $setting['kepala_madrasah'] . " (NIP: " . $setting['nip_kepala'] . ")";
                                $qr_url_kepala = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode($qr_data_kepala);
                                ?>
                                <img src="<?php echo $qr_url_kepala; ?>" alt="QR Signature" style="width: 80px; height: 80px;">
                            </div>
                            <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0;"><?php echo $setting['kepala_madrasah']; ?></p>
                            <p style="margin-top: 0;">NIP. <?php echo $setting['nip_kepala']; ?></p>
                        <?php endif; ?>
                    </td>
                    <td style="border: none; width: 10%;"></td>
                    <td style="border: none; text-align: center; width: 45%; padding: 0;">
                        <p style="margin-bottom: 5px;">Jepara, <?php echo date('d F Y'); ?></p>
                        <p style="margin-bottom: 10px;">Guru Pengampu,</p>
                        <div style="margin: 10px 0;">
                            <?php 
                            $qr_data_guru = "Dokumen Sah Elektronik: " . $_SESSION['nama'];
                            $qr_url_guru = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode($qr_data_guru);
                            ?>
                            <img src="<?php echo $qr_url_guru; ?>" alt="QR Signature" style="width: 80px; height: 80px;">
                        </div>
                        <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0;"><?php echo $_SESSION['nama']; ?></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>