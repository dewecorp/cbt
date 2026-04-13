<?php
include '../../config/database.php';
include '../../includes/init_session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['level'] !== 'admin') {
    exit;
}

$q_setting = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_setting);
if (!$setting) {
    $setting = [
        'nama_sekolah' => 'CBT MI SULTAN FATTAH SUKOSONO',
        'alamat' => '',
        'logo' => '',
        'tahun_ajaran' => '-',
        'semester' => '-',
        'kepala_madrasah' => 'Nama Kepala Madrasah',
        'nip_kepala' => '-'
    ];
}

// Function for Indonesian date
function tgl_indo($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

// Get Kelas and Mapel names for mapping
$kelas_map = [];
$qk = mysqli_query($koneksi, "SELECT * FROM kelas");
while($rk = mysqli_fetch_assoc($qk)) $kelas_map[$rk['id_kelas']] = $rk['nama_kelas'];

$mapel_map = [];
$qm = mysqli_query($koneksi, "SELECT * FROM mapel");
while($rm = mysqli_fetch_assoc($qm)) $mapel_map[$rm['id_mapel']] = $rm['nama_mapel'];

$query = mysqli_query($koneksi, "SELECT * FROM users WHERE level='guru' ORDER BY nama_lengkap ASC");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Guru - <?php echo strtoupper($setting['nama_sekolah']); ?></title>
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 11pt; }
        .container { max-width: 100%; width: 98%; padding: 0; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th, td { border: 1px solid black; padding: 5px 8px; word-wrap: break-word; overflow-wrap: anywhere; vertical-align: middle; }
        th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center; }
        .header-table td { border: none !important; }
        @media print {
            .no-print { display: none; }
            @page { size: A4 landscape; margin: 1cm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container py-4">
        <table class="header-table" style="width: 100%; border: none; border-bottom: 2px solid black; margin-bottom: 20px;">
            <tr>
                <td style="width: 100px; text-align: center; padding: 10px;">
                    <?php if(!empty($setting['logo']) && file_exists('../../assets/img/'.$setting['logo'])): ?>
                        <img src="../../assets/img/<?php echo $setting['logo']; ?>" style="height: 80px; width: auto;">
                    <?php else: ?>
                        <img src="../../assets/img/logo_1770185899.png" style="height: 80px; width: auto;">
                    <?php endif; ?>
                </td>
                <td style="text-align: center;">
                    <h3 style="margin: 0;">DATA GURU</h3>
                    <h4 style="margin: 5px 0;"><?php echo strtoupper($setting['nama_sekolah']); ?></h4>
                    <p style="margin: 0; font-size: 10pt;"><?php echo $setting['alamat']; ?></p>
                </td>
                <td style="width: 100px;"></td>
            </tr>
        </table>

        <table class="header-table" style="border: none; margin-bottom: 15px;">
            <tr>
                <td style="width: 20%; vertical-align: top; white-space: nowrap;">Tahun Ajaran</td>
                <td style="width: 30%; vertical-align: top;">: <?php echo htmlspecialchars($setting['tahun_ajaran']); ?></td>
                <td style="width: 5%;"></td>
                <td style="width: 20%; vertical-align: top; white-space: nowrap;">Tanggal Cetak</td>
                <td style="width: 25%; vertical-align: top;">: <?php echo date('d F Y'); ?></td>
            </tr>
            <tr>
                <td style="vertical-align: top; white-space: nowrap;">Semester</td>
                <td style="vertical-align: top;">: <?php echo htmlspecialchars($setting['semester']); ?></td>
                <td colspan="3"></td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">NUPTK</th>
                    <th width="20%">Nama Lengkap</th>
                    <th width="5%">L/P</th>
                    <th width="10%">Password</th>
                    <th width="20%">Mengajar Kelas</th>
                    <th width="25%">Mengajar Mapel</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($query)) :
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
                ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td class="text-center"><?php echo $row['jk'] ? $row['jk'] : '-'; ?></td>
                        <td><?php echo htmlspecialchars($row['password_plain']); ?></td>
                        <td><?php echo implode(', ', $m_kelas); ?></td>
                        <td><?php echo implode(', ', $m_mapel); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Tanda Tangan -->
        <div style="margin-top: 30px; width: 100%;">
            <table class="header-table" style="width: 100%; border: none;">
                <tr>
                    <td style="width: 70%; border: none;"></td>
                    <td style="width: 30%; border: none; text-align: left;">
                        <p style="margin-bottom: 5px;">Sukosono, <?php echo tgl_indo(date('Y-m-d')); ?></p>
                        <p style="margin-bottom: 0;">Kepala Madrasah,</p>
                        <div style="margin: 10px 0;">
                            <!-- QR Code Placeholder (Using a simple border box as QR representation) -->
                            <div style="width: 80px; height: 80px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 8pt; color: #999;">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?php echo urlencode($setting['kepala_madrasah']); ?>" alt="QR Signature">
                            </div>
                        </div>
                        <p style="margin-bottom: 0; font-weight: bold; text-decoration: underline;"><?php echo $setting['kepala_madrasah']; ?></p>
                        <p style="margin-top: 0;">NIP. <?php echo $setting['nip_kepala']; ?></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
