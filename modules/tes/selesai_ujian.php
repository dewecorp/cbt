<?php
include '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    exit;
}

$id_ujian = $_GET['id'];
$id_siswa = $_SESSION['user_id'];

// Get ID Ujian Siswa
$us = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id_ujian_siswa FROM ujian_siswa WHERE id_ujian='$id_ujian' AND id_siswa='$id_siswa'"));
$id_ujian_siswa = $us['id_ujian_siswa'];

// Hitung Nilai (Sederhana - hanya untuk PG dan Isian Singkat yang exact match)
// Essay dan Kompleks butuh logika lebih rumit, disini kita skip dulu atau hitung simple
$q_jawab = mysqli_query($koneksi, "
    SELECT js.jawaban as jawab_siswa, s.kunci_jawaban, s.jenis 
    FROM jawaban_siswa js 
    JOIN soal s ON js.id_soal = s.id_soal 
    WHERE js.id_ujian_siswa='$id_ujian_siswa'
");

$total_skor = 0;
$total_soal = mysqli_num_rows($q_jawab);

while($r = mysqli_fetch_assoc($q_jawab)) {
    $skor_soal = 0;
    $j = strtoupper(trim($r['jawab_siswa']));
    $k = strtoupper(trim($r['kunci_jawaban']));

    if($r['jenis'] == 'pilihan_ganda') {
        if($j == $k) {
            $skor_soal = 1;
        }
    } elseif($r['jenis'] == 'isian_singkat' || $r['jenis'] == 'essay') {
        // Normalisasi teks untuk perbandingan yang lebih fair
        $clean_j = strtolower(trim($j));
        $clean_j = preg_replace('/[^\w\s]/', '', $clean_j); // Hapus tanda baca
        $clean_j = preg_replace('/\s+/', ' ', $clean_j);    // Spasi ganda jadi satu

        $clean_k = strtolower(trim($k));
        $clean_k = preg_replace('/[^\w\s]/', '', $clean_k);
        $clean_k = preg_replace('/\s+/', ' ', $clean_k);

        if($j != '' && $clean_j == $clean_k) {
            $skor_soal = 1;
        } elseif ($j != '') {
            // Cek kemiripan untuk nilai separuh
            $percent = 0;
            // Bandingkan teks yang sudah dibersihkan
            similar_text($clean_j, $clean_k, $percent);
            if($percent >= 50) {
                $skor_soal = 0.5;
            }
        }
    } elseif($r['jenis'] == 'pilihan_ganda_kompleks') {
        // Logika sederhana: exact match string (A,B == A,B)
        $kunci_arr = explode(',', $k);
        $jawab_arr = explode(',', $j);
        sort($kunci_arr);
        sort($jawab_arr);
        if($kunci_arr == $jawab_arr) {
            $skor_soal = 1;
        }
    } elseif($r['jenis'] == 'menjodohkan') {
        // Logika: exact match string pairs
        if($j == $k) {
            $skor_soal = 1;
        }
    }
    
    $total_skor += $skor_soal;
}

$nilai = ($total_soal > 0) ? ($total_skor / $total_soal) * 100 : 0;

mysqli_query($koneksi, "UPDATE ujian_siswa SET status='selesai', waktu_selesai=NOW(), nilai='$nilai' WHERE id_ujian_siswa='$id_ujian_siswa'");

// Ambil Detail Hasil untuk Ditampilkan
$q_hasil = mysqli_query($koneksi, "
    SELECT u.nama_ujian, u.waktu as durasi_ujian, m.nama_mapel, m.kktp,
           us.waktu_mulai, us.waktu_selesai, us.nilai 
    FROM ujian_siswa us 
    JOIN ujian u ON us.id_ujian=u.id_ujian 
    JOIN bank_soal b ON u.id_bank_soal=b.id_bank_soal 
    JOIN mapel m ON b.id_mapel=m.id_mapel 
    WHERE us.id_ujian_siswa='$id_ujian_siswa'
");
$hasil = mysqli_fetch_assoc($q_hasil);

$start = strtotime($hasil['waktu_mulai']);
$end = strtotime($hasil['waktu_selesai']);
$diff = $end - $start;

// Format durasi pengerjaan
$jam = floor($diff / (60 * 60));
$menit = floor(($diff - $jam * 60 * 60) / 60);
$detik = floor($diff % 60);
$waktu_pengerjaan = "";
if($jam > 0) $waktu_pengerjaan .= $jam . " jam ";
if($menit > 0) $waktu_pengerjaan .= $menit . " menit ";
$waktu_pengerjaan .= $detik . " detik";

// Include Header (sudah handle session)
$page_title = 'Asesmen Selesai';
include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-success text-white text-center py-4">
                    <h2 class="mb-0"><i class="fas fa-check-circle me-2"></i> Asesmen Selesai</h2>
                    <p class="mb-0 mt-2 text-white-50">Terima kasih telah mengerjakan ujian ini.</p>
                </div>
                <div class="card-body p-5">
                    
                    <?php if (isset($_GET['violation']) && $_GET['violation'] == 'true'): ?>
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'PELANGGARAN TERDETEKSI!',
                                text: 'Sistem mendeteksi Anda membuka tab/jendela lain atau keluar dari halaman asesmen. Asesmen otomatis dihentikan.',
                                confirmButtonText: 'Mengerti',
                                confirmButtonColor: '#d33',
                                allowOutsideClick: false
                            });
                        });
                    </script>
                    <?php endif; ?>

                    <h4 class="text-center mb-4 text-primary fw-bold"><?php echo $hasil['nama_ujian']; ?></h4>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th width="40%" class="bg-light">Mata Pelajaran</th>
                                <td class="fw-bold"><?php echo $hasil['nama_mapel']; ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Alokasi Waktu</th>
                                <td><?php echo $hasil['durasi_ujian']; ?> Menit</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Tanggal Pengerjaan</th>
                                <td><?php echo date('d F Y', strtotime($hasil['waktu_selesai'])); ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Waktu Mulai</th>
                                <td><?php echo date('H:i:s', strtotime($hasil['waktu_mulai'])); ?> WIB</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Waktu Selesai</th>
                                <td><?php echo date('H:i:s', strtotime($hasil['waktu_selesai'])); ?> WIB</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Lama Pengerjaan</th>
                                <td><?php echo $waktu_pengerjaan; ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="text-center mt-5">
                        <h5 class="text-muted mb-2">Nilai Akhir Anda</h5>
                        <?php $kktp = isset($hasil['kktp']) ? $hasil['kktp'] : 75; ?>
                        <h1 class="display-1 fw-bold <?php echo ($hasil['nilai'] >= $kktp) ? 'text-success' : 'text-danger'; ?>">
                            <?php echo number_format($hasil['nilai'], 2); ?>
                        </h1>
                        <?php if($hasil['nilai'] >= $kktp): ?>
                            <span class="badge bg-success rounded-pill px-3 py-2">LULUS / TUNTAS</span>
                        <?php else: ?>
                            <span class="badge bg-danger rounded-pill px-3 py-2">BELUM TUNTAS</span>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2 mt-5">
                        <a href="../../dashboard.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-footer text-center text-muted py-3 small">
                    CBT MI Sultan Fattah Sukosono
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
