<?php
include '../../config/database.php';
include '../../includes/init_session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['level'] !== 'admin') {
    exit;
}

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
        'alamat' => '',
        'logo' => '',
        'tahun_ajaran' => '-',
        'semester' => '-'
    ];
}

$query = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.id_kelas = '$id_kelas' ORDER BY s.nama_siswa ASC");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Siswa - <?php echo htmlspecialchars($nama_kelas); ?></title>
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 12pt; }
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
                    <h3 style="margin: 0;">DATA SISWA</h3>
                    <h4 style="margin: 5px 0;"><?php echo strtoupper($setting['nama_sekolah']); ?></h4>
                    <p style="margin: 0; font-size: 10pt;"><?php echo $setting['alamat']; ?></p>
                </td>
                <td style="width: 100px; border: none;"></td>
            </tr>
        </table>

        <table style="border: none; margin-bottom: 15px;">
            <tr>
                <td style="border: none; width: 20%; vertical-align: top; white-space: nowrap;">Kelas</td>
                <td style="border: none; width: 30%; vertical-align: top;">: <?php echo htmlspecialchars($nama_kelas); ?></td>
                <td style="border: none; width: 5%;"></td>
                <td style="border: none; width: 20%; vertical-align: top; white-space: nowrap;">Tahun Ajaran</td>
                <td style="border: none; width: 25%; vertical-align: top;">: <?php echo htmlspecialchars($setting['tahun_ajaran']); ?></td>
            </tr>
            <tr>
                <td style="border: none; vertical-align: top; white-space: nowrap;">Semester</td>
                <td style="border: none; vertical-align: top;">: <?php echo htmlspecialchars($setting['semester']); ?></td>
                <td style="border: none;"></td>
                <td style="border: none; vertical-align: top; white-space: nowrap;">Tanggal Cetak</td>
                <td style="border: none; vertical-align: top;">: <?php echo date('d F Y'); ?></td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">NISN</th>
                    <th width="25%">Nama Siswa</th>
                    <th width="5%">L/P</th>
                    <th width="25%">Tempat, Tanggal Lahir</th>
                    <th width="15%">Kelas</th>
                    <th width="10%">Password</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($row = mysqli_fetch_assoc($query)): 
                    $password_display = $row['password'];
                    if (strlen($row['password']) == 60 && substr($row['password'], 0, 4) === '$2y$') {
                        $password_display = 'Ter-enkripsi';
                    }
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['nisn']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_siswa']); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($row['jk']); ?></td>
                        <td><?php echo htmlspecialchars($row['tempat_lahir'] . ', ' . date('d-m-Y', strtotime($row['tanggal_lahir']))); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_kelas']); ?></td>
                        <td><?php echo htmlspecialchars($password_display); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

