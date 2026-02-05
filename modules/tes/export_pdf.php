<?php
include '../../config/database.php';
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
        'alamat' => '',
        'logo' => '',
        'tahun_ajaran' => '-',
        'semester' => '-'
    ];
}

// Query Data
$query = "SELECT s.nisn, s.nama_siswa, us.nilai, us.status
          FROM siswa s
          LEFT JOIN ujian_siswa us ON s.id_siswa = us.id_siswa AND us.id_ujian = '$id_ujian'
          WHERE s.id_kelas = '$id_kelas'
          ORDER BY s.nama_siswa ASC";
$result = mysqli_query($koneksi, $query);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Rekap Nilai - <?php echo $info['nama_mapel']; ?></title>
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 12pt; }
        .header-section { text-align: center; margin-bottom: 20px; border-bottom: 2px solid black; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; box-sizing: border-box; }
        th, td { border: 1px solid black; padding: 5px 8px; box-sizing: border-box; word-wrap: break-word; overflow-wrap: anywhere; }
        th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center; }
        @media print {
            .no-print { display: none; }
            @page { size: A4 portrait; margin: 1cm; }
            .container { max-width: 100% !important; width: 97% !important; padding: 0 !important; margin: 0 auto !important; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container py-4">
        <table style="width: 100%; border: none; border-bottom: 2px solid black; margin-bottom: 20px;">
            <tr>
                <td style="width: 100px; text-align: center; border: none; padding: 10px;">
                    <?php if(!empty($setting['logo'])): ?>
                        <img src="../../assets/img/<?php echo $setting['logo']; ?>" style="height: 80px; width: auto;">
                    <?php endif; ?>
                </td>
                <td style="text-align: center; border: none;">
                    <h3 style="margin: 0;">REKAP NILAI HASIL ASESMEN</h3>
                    <h4 style="margin: 5px 0;"><?php echo strtoupper($setting['nama_sekolah']); ?></h4>
                    <p style="margin: 0; font-size: 10pt;"><?php echo $setting['alamat']; ?></p>
                </td>
                <td style="width: 100px; border: none;"></td>
            </tr>
        </table>

        <table style="border: none; margin-bottom: 15px;">
            <tr>
                <td style="border: none; width: 20%; vertical-align: top; white-space: nowrap;">Mata Pelajaran</td>
                <td style="border: none; width: 30%; vertical-align: top;">: <?php echo $info['nama_mapel']; ?></td>
                <td style="border: none; width: 5%;"></td>
                <td style="border: none; width: 20%; vertical-align: top; white-space: nowrap;">Tahun Ajaran</td>
                <td style="border: none; width: 25%; vertical-align: top;">: <?php echo $setting['tahun_ajaran']; ?></td>
            </tr>
            <tr>
                <td style="border: none; vertical-align: top; white-space: nowrap;">Nama Asesmen</td>
                <td style="border: none; vertical-align: top;">: <?php echo $info['nama_ujian']; ?></td>
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top; white-space: nowrap;">Semester</td>
                <td style="border: none; vertical-align: top;">: <?php echo $setting['semester']; ?></td>
            </tr>
            <tr>
                <td style="border: none; vertical-align: top; white-space: nowrap;">Kelas</td>
                <td style="border: none; vertical-align: top;">: <?php echo $info['nama_kelas']; ?></td>
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top; white-space: nowrap;">Tanggal</td>
                <td style="border: none; vertical-align: top;">: <?php echo date('d F Y', strtotime($info['tgl_mulai'])); ?></td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>NISN</th>
                    <th>Nama Siswa</th>
                    <th>Nilai</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($row = mysqli_fetch_assoc($result)): 
                    $nilai = $row['nilai'] ? $row['nilai'] : 0;
                    $status = $row['status'] ? $row['status'] : 'Belum Mengerjakan';
                    if ($status == 'selesai') {
                        $ket = ($nilai >= 75) ? 'TUNTAS' : 'BELUM TUNTAS';
                    } else {
                        $ket = '-';
                    }
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo $row['nisn']; ?></td>
                    <td><?php echo $row['nama_siswa']; ?></td>
                    <td class="text-center"><?php echo ($status == 'selesai') ? number_format($nilai, 2) : '-'; ?></td>
                    <td class="text-center">
                        <?php 
                        if($status == 'selesai') echo 'Selesai';
                        elseif($status == 'sedang_mengerjakan') echo 'Proses';
                        else echo 'Belum';
                        ?>
                    </td>
                    <td class="text-center"><?php echo $ket; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="mt-4" style="float: right; text-align: center; width: 200px;">
            <p>Jepara, <?php echo date('d F Y'); ?></p>
            <p>Guru Mapel</p>
            <br><br><br>
            <p>( <b><?php echo $info['nama_guru']; ?></b> )</p>
        </div>
    </div>
</body>
</html>
