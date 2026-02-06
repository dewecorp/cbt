<?php
include '../../config/database.php';
$page_title = 'Konfirmasi Asesmen';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$id_ujian = $_GET['id'];
$ujian = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT u.*, m.nama_mapel, b.kode_bank, 
    (SELECT COUNT(*) FROM soal WHERE id_bank_soal = u.id_bank_soal) as jml_soal
    FROM ujian u 
    JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal 
    JOIN mapel m ON b.id_mapel = m.id_mapel 
    WHERE u.id_ujian='$id_ujian'
"));

if (!$ujian) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Ujian tidak ditemukan!',
            confirmButtonText: 'Kembali ke Dashboard'
        }).then(() => {
            window.location='../../dashboard.php';
        });
    </script>";
    exit;
}

// Cek apakah sudah pernah mulai
$id_siswa = $_SESSION['user_id'];
$cek_ujian_siswa = mysqli_query($koneksi, "SELECT * FROM ujian_siswa WHERE id_ujian='$id_ujian' AND id_siswa='$id_siswa'");
$sudah_mulai = false;
if (mysqli_num_rows($cek_ujian_siswa) > 0) {
    $us = mysqli_fetch_assoc($cek_ujian_siswa);
    if ($us['status'] == 'selesai') {
        echo "<script>
            Swal.fire({
                icon: 'info',
                title: 'Selesai',
                text: 'Anda sudah menyelesaikan ujian ini.',
                confirmButtonText: 'Kembali'
            }).then(() => {
                window.location='../../dashboard.php';
            });
        </script>";
        exit;
    }
    $sudah_mulai = true;
}

// Handle Start
if (isset($_POST['mulai'])) {
    $token_input = strtoupper($_POST['token']);
    if ($token_input == $ujian['token'] || $sudah_mulai) {
        if (!$sudah_mulai) {
            mysqli_query($koneksi, "INSERT INTO ujian_siswa (id_ujian, id_siswa, waktu_mulai, status) VALUES ('$id_ujian', '$id_siswa', NOW(), 'sedang_mengerjakan')");
        }
        echo "<script>window.location='kerjakan.php?id=$id_ujian';</script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Token salah!', 'error');</script>";
    }
}
?>

<div class="container-fluid">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-kemenag text-white">
                    <h5 class="m-0 fw-bold"><i class="fas fa-file-alt me-2"></i> Konfirmasi Tes</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <tr>
                <td width="30%">Nama Asesmen</td>
                            <td width="5%">:</td>
                            <td class="fw-bold"><?php echo $ujian['nama_ujian']; ?></td>
                        </tr>
                        <tr>
                            <td>Mata Pelajaran</td>
                            <td>:</td>
                            <td><?php echo $ujian['nama_mapel']; ?></td>
                        </tr>
                        <tr>
                            <td>Jumlah Soal</td>
                            <td>:</td>
                            <td><?php echo $ujian['jml_soal']; ?> Soal</td>
                        </tr>
                        <tr>
                            <td>Waktu</td>
                            <td>:</td>
                            <td><?php echo $ujian['waktu']; ?> Menit</td>
                        </tr>
                    </table>

                    <div class="card bg-warning bg-opacity-10 border border-warning text-warning p-3 rounded mb-3">
                        <div class="d-flex">
                            <i class="fas fa-exclamation-triangle me-3 mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Perhatian</h6>
                                <p class="mb-0">Pastikan koneksi internet stabil. Jangan reload halaman saat ujian berlangsung.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST">
                        <?php if(!$sudah_mulai): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Masukkan Token</label>
                            <input type="text" name="token" class="form-control form-control-lg text-center text-uppercase" placeholder="TOKEN" required autocomplete="off">
                        </div>
                        <?php else: ?>
                        <div class="card bg-info bg-opacity-10 border border-info text-info p-3 rounded mb-3">
                            <div class="d-flex">
                                <i class="fas fa-info-circle me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Informasi</h6>
                                    <p class="mb-0">Anda sudah memulai ujian ini sebelumnya. Klik tombol di bawah untuk melanjutkan.</p>
                                </div>
                            </div>
                        </div>
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Lanjutkan Ujian',
                                    text: 'Anda sudah memulai ujian ini sebelumnya. Klik tombol "Lanjutkan Mengerjakan" untuk kembali ke ujian.',
                                    confirmButtonText: 'Oke'
                                });
                            });
                        </script>
                        <input type="hidden" name="token" value="<?php echo $ujian['token']; ?>">
                        <?php endif; ?>
                        
                        <div class="d-grid">
                            <button type="submit" name="mulai" class="btn btn-success btn-lg"><?php echo $sudah_mulai ? 'LANJUTKAN MENGERJAKAN' : 'MULAI MENGERJAKAN'; ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
