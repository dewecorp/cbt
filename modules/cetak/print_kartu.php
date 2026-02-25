<?php
include '../../config/database.php';

$where = "";
if (isset($_GET['id_kelas'])) {
    $id_kelas = $_GET['id_kelas'];
    $where = "WHERE s.id_kelas='$id_kelas'";
} elseif (isset($_GET['id_siswa'])) {
    $id_siswa = $_GET['id_siswa'];
    $where = "WHERE s.id_siswa='$id_siswa'";
} else {
    die("Pilih data yang akan dicetak");
}

$q_siswa = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas $where ORDER BY s.nama_siswa ASC");

// Ambil data sekolah
$q_setting = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($q_setting);
if (!$setting) {
    $setting = [];
}
$page_title = 'Cetak Kartu Asesmen';
if (isset($id_kelas)) {
    // We don't have nama_kelas directly in variable, but it's in the first row of q_siswa
    // However, fetching it moves the pointer. Let's just use generic or fetch one row then reset.
    // Or simpler, just keep it generic "Cetak Kartu Ujian" or append ID.
    // Let's try to get class name if possible, but minimal impact is better.
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $page_title; ?> - CBT MI Sultan Fattah Sukosono</title>
    <style>
        @page {
            size: 215mm 330mm; /* F4 Portrait */
            margin: 10mm;
        }
        body { 
            font-family: Arial, sans-serif; 
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
            margin: 0;
            padding: 0;
        }
        .card-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            width: 100%;
        }
        .card-box {
            width: 46%; /* Approx 90mm width to fit 2 cols */
            border: 1px solid #000;
            margin: 1.5%; /* Spacing */
            padding: 0;
            page-break-inside: avoid;
            box-sizing: border-box;
            float: left;
            position: relative;
        }
        .header {
            border-bottom: 2px solid #000;
            padding: 5px 10px;
            text-align: center;
            background-color: #f0f0f0;
            height: 70px; /* Fixed height for consistency */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .header img {
            max-height: 50px;
            max-width: 50px;
            margin-right: 10px;
        }
        .header-text {
            text-align: center;
        }
        .header h3, .header h4 { margin: 2px 0; font-size: 14px; }
        .header small { font-size: 10px; }
        .content { padding: 10px; font-size: 12px; }
        .row-data { display: flex; margin-bottom: 3px; }
        .label { width: 80px; font-weight: bold; }
        .separator { width: 10px; }
        .value { flex: 1; }
        .footer {
            border-top: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 10px;
            background-color: #f0f0f0;
        }
        .signature {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
        .sig-box {
            text-align: center;
            width: 45%;
        }
        @media print {
            .no-print { display: none; }
            .card-box {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="no-print" style="padding: 10px; text-align: center; background: #eee; margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Cetak Kartu</button>
</div>

<div class="card-container">
    <?php while($s = mysqli_fetch_assoc($q_siswa)): ?>
    <div class="card-box">
        <div class="header">
            <?php if(!empty($setting['logo']) && file_exists('../../assets/img/'.$setting['logo'])): ?>
                <img src="../../assets/img/<?php echo $setting['logo']; ?>">
            <?php endif; ?>
            <div class="header-text">
                <h3>KARTU PESERTA ASESMEN</h3>
                <h4><?php echo isset($setting['nama_sekolah']) ? $setting['nama_sekolah'] : 'MI Sultan Fattah'; ?></h4>
                <small>
                    <?php echo isset($setting['tahun_ajaran']) ? 'Th. '.$setting['tahun_ajaran'] : ''; ?>
                    <?php echo isset($setting['semester']) ? ' - Sem. '.$setting['semester'] : ''; ?>
                </small>
            </div>
        </div>
        <div class="content">
            <div class="row-data">
                <div class="label">Nama</div>
                <div class="separator">:</div>
                <div class="value"><?php echo $s['nama_siswa']; ?></div>
            </div>
            <div class="row-data">
                <div class="label">NISN</div>
                <div class="separator">:</div>
                <div class="value"><?php echo $s['nisn']; ?></div>
            </div>
            <div class="row-data">
                <div class="label">Kelas</div>
                <div class="separator">:</div>
                <div class="value"><?php echo $s['nama_kelas']; ?></div>
            </div>
            <div class="row-data">
                <div class="label">Username</div>
                <div class="separator">:</div>
                <div class="value"><strong><?php echo $s['nisn']; ?></strong></div>
            </div>
            <div class="row-data">
                <div class="label">Password</div>
                <div class="separator">:</div>
                <div class="value">
                    <?php 
                    $password = $s['password'] ?? '';
                    if (strlen($password) == 60 && substr($password, 0, 4) === '$2y$') {
                        echo '<span style="font-style:italic; font-size:10px;">(Ter-enkripsi)</span>';
                    } else {
                        echo '<strong>'.$password.'</strong>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="signature">
                <div class="sig-box">
                    Mengetahui,<br>
                    Kepala Madrasah<br>
                    <div style="margin: 5px 0;">
                        <?php 
                        $qr_data_kepala = "Kartu Asesmen Sah: " . ($setting['kepala_madrasah'] ?? 'Kepala Madrasah');
                        $qr_url_kepala = "https://api.qrserver.com/v1/create-qr-code/?size=50x50&data=" . urlencode($qr_data_kepala);
                        ?>
                        <img src="<?php echo $qr_url_kepala; ?>" alt="QR" style="width: 45px; height: 45px;">
                    </div>
                    <u><strong><?php echo isset($setting['kepala_madrasah']) ? $setting['kepala_madrasah'] : '..................'; ?></strong></u><br>
                    NIP. <?php echo isset($setting['nip_kepala']) ? $setting['nip_kepala'] : '-'; ?>
                </div>
                <div class="sig-box">
                    Panitia Asesmen,<br>
                    Ketua<br>
                    <div style="margin: 5px 0;">
                        <?php 
                        $qr_data_panitia = "Panitia Asesmen Sah: " . ($setting['panitia_ujian'] ?? 'Ketua Panitia');
                        $qr_url_panitia = "https://api.qrserver.com/v1/create-qr-code/?size=50x50&data=" . urlencode($qr_data_panitia);
                        ?>
                        <img src="<?php echo $qr_url_panitia; ?>" alt="QR" style="width: 45px; height: 45px;">
                    </div>
                    <u><strong><?php echo isset($setting['panitia_ujian']) ? $setting['panitia_ujian'] : '..................'; ?></strong></u>
                </div>
            </div>
        </div>
        <div class="footer">
            <em>Kartu ini harap dibawa saat ujian berlangsung.</em>
        </div>
    </div>
    <?php endwhile; ?>
</div>

</body>
</html>
