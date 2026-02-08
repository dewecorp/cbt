<?php
include '../../config/database.php';
$page_title = 'Detail Jawaban Siswa';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Check Permissions
if (!isset($_SESSION['level']) || ($_SESSION['level'] != 'guru' && $_SESSION['level'] != 'admin')) {
    echo "<script>window.location='../../index.php';</script>";
    exit;
}

if (!isset($_GET['id'])) {
    echo "<script>window.location='hasil_ujian.php';</script>";
    exit;
}

$id_ujian_siswa = mysqli_real_escape_string($koneksi, $_GET['id']);

// Get Student & Exam Info
$query_info = "SELECT us.*, s.nama_siswa, s.nisn, k.nama_kelas, u.nama_ujian, b.kode_bank, m.nama_mapel, m.kktp, b.id_bank_soal
               FROM ujian_siswa us
               JOIN siswa s ON us.id_siswa = s.id_siswa
               JOIN kelas k ON s.id_kelas = k.id_kelas
               JOIN ujian u ON us.id_ujian = u.id_ujian
               JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal
               JOIN mapel m ON b.id_mapel = m.id_mapel
               WHERE us.id_ujian_siswa = '$id_ujian_siswa'";
$q_info = mysqli_query($koneksi, $query_info);
$info = mysqli_fetch_assoc($q_info);

if (!$info) {
    echo "<div class='container-fluid mt-4'><div class='alert alert-danger'>Data tidak ditemukan.</div></div>";
    include '../../includes/footer.php';
    exit;
}

// Get Questions and Answers
$id_bank_soal = $info['id_bank_soal'];
$query_soal = "SELECT s.*, js.jawaban as jawaban_siswa, js.ragu 
               FROM soal s 
               LEFT JOIN jawaban_siswa js ON s.id_soal = js.id_soal AND js.id_ujian_siswa = '$id_ujian_siswa'
               WHERE s.id_bank_soal = '$id_bank_soal'
               ORDER BY s.id_soal ASC";
$q_soal = mysqli_query($koneksi, $query_soal);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Detail Jawaban Siswa</h1>
        <a href="hasil_ujian.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <!-- Student Info Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Informasi Asesmen</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">Nama Siswa</th>
                            <td>: <?php echo $info['nama_siswa']; ?></td>
                        </tr>
                        <tr>
                            <th>NISN</th>
                            <td>: <?php echo $info['nisn']; ?></td>
                        </tr>
                        <tr>
                            <th>Kelas</th>
                            <td>: <?php echo $info['nama_kelas']; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">Asesmen</th>
                            <td>: <?php echo $info['nama_ujian']; ?></td>
                        </tr>
                        <tr>
                            <th>Mapel</th>
                            <td>: <?php echo $info['nama_mapel']; ?></td>
                        </tr>
                        <tr>
                            <th>Nilai</th>
                            <td>: <span class="badge bg-<?php echo ($info['nilai'] >= ($info['kktp'] ?? 75)) ? 'success' : 'danger'; ?> fs-6"><?php echo number_format($info['nilai'], 2); ?></span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions & Answers List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Lembar Jawaban</h6>
        </div>
        <div class="card-body">
            <?php 
            $no = 1;
            while($s = mysqli_fetch_assoc($q_soal)): 
                $kunci = trim($s['kunci_jawaban']);
                $jawaban = trim($s['jawaban_siswa'] ?? '');
                
                // Logic Check Answer & Scoring
                $skor_soal = 0;
                $bobot = ($s['bobot'] > 0) ? $s['bobot'] : 1;
                
                $j = strtoupper(trim($jawaban));
                $k = strtoupper(trim($kunci));

                if ($s['jenis'] == 'pilihan_ganda') {
                    if($j == $k) $skor_soal = $bobot;
                } elseif ($s['jenis'] == 'pilihan_ganda_kompleks') {
                    $k_arr = explode(',', $k);
                    $j_arr = explode(',', $j);
                    sort($k_arr);
                    sort($j_arr);
                    if($k_arr == $j_arr) $skor_soal = $bobot;
                } elseif ($s['jenis'] == 'isian_singkat' || $s['jenis'] == 'essay') {
                    // Normalisasi teks
                    $clean_j = strtolower(trim($j));
                    $clean_j = preg_replace('/[^\w\s]/', '', $clean_j);
                    $clean_j = preg_replace('/\s+/', ' ', $clean_j);

                    $clean_k = strtolower(trim($k));
                    $clean_k = preg_replace('/[^\w\s]/', '', $clean_k);
                    $clean_k = preg_replace('/\s+/', ' ', $clean_k);

                    if($j != '' && $clean_j == $clean_k) {
                        $skor_soal = $bobot;
                    } elseif ($j != '') {
                         $percent = 0;
                         similar_text($clean_j, $clean_k, $percent);
                         if($percent >= 50) $skor_soal = $bobot * 0.5;
                    }
                } elseif ($s['jenis'] == 'menjodohkan') {
                    if($j == $k) $skor_soal = $bobot;
                }
                
                $bg_class = '';
                $icon_status = '';
                
                if ($jawaban == '') {
                    $bg_class = 'border-warning border-3 border-start'; 
                    $icon_status = '<span class="badge bg-warning text-dark">Tidak Dijawab</span>';
                } elseif ($skor_soal == $bobot) {
                    $bg_class = 'border-success border-3 border-start';
                    $icon_status = '<span class="badge bg-success"><i class="fas fa-check"></i> Benar</span>';
                } elseif ($skor_soal > 0) {
                    $bg_class = 'border-warning border-3 border-start';
                    $icon_status = '<span class="badge bg-warning text-dark"><i class="fas fa-check-circle"></i> Setengah Benar</span>';
                } else {
                    $bg_class = 'border-danger border-3 border-start';
                    $icon_status = '<span class="badge bg-danger"><i class="fas fa-times"></i> Salah</span>';
                }
            ?>
            <div class="card mb-3 <?php echo $bg_class; ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h6 class="font-weight-bold text-secondary">
                            Soal No. <?php echo $no++; ?> (<?php echo ucwords(str_replace('_', ' ', $s['jenis'])); ?>)
                            <span class="badge bg-secondary ms-2">Bobot: <?php echo $bobot; ?></span>
                            <?php if($skor_soal > 0 && $skor_soal < $bobot): ?>
                                <span class="badge bg-warning text-dark ms-1">Skor: <?php echo floatval($skor_soal); ?></span>
                            <?php endif; ?>
                        </h6>
                        <?php echo $icon_status; ?>
                    </div>
                    
                    <div class="mb-3">
                        <?php echo $s['pertanyaan']; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded h-100">
                                <small class="text-muted d-block mb-1">Jawaban Siswa:</small>
                                <div class="fw-bold <?php echo ($skor_soal == $bobot) ? 'text-success' : (($skor_soal > 0) ? 'text-warning' : 'text-danger'); ?>">
                                    <?php 
                                    if($jawaban == '') {
                                        echo '<span class="text-muted fst-italic">- Tidak menjawab -</span>';
                                    } else {
                                        echo $jawaban; 
                                        if ($s['jenis'] == 'pilihan_ganda') {
                                            // Show option text if available? 
                                            // Usually keys are A, B, C...
                                            // Let's just show the key.
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-success bg-opacity-10 rounded h-100">
                                <small class="text-muted d-block mb-1">Kunci Jawaban:</small>
                                <div class="fw-bold text-success">
                                    <?php echo $kunci; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if($s['jenis'] == 'pilihan_ganda'): ?>
                    <div class="mt-3 small text-muted">
                        <strong>Opsi:</strong><br>
                        A. <?php echo strip_tags($s['opsi_a']); ?><br>
                        B. <?php echo strip_tags($s['opsi_b']); ?><br>
                        C. <?php echo strip_tags($s['opsi_c']); ?><br>
                        D. <?php echo strip_tags($s['opsi_d']); ?><br>
                        <?php if(!empty($s['opsi_e'])): ?>
                        E. <?php echo strip_tags($s['opsi_e']); ?><br>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>