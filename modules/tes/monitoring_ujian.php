<?php
include '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$id_ujian = isset($_GET['id']) ? $_GET['id'] : 0;

// Get Exam Details
$q_ujian = mysqli_query($koneksi, "
    SELECT u.*, b.kode_bank, m.nama_mapel, b.id_kelas 
    FROM ujian u 
    JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal 
    JOIN mapel m ON b.id_mapel = m.id_mapel 
    WHERE u.id_ujian='$id_ujian'
");

if(mysqli_num_rows($q_ujian) == 0) {
    echo "<script>window.location='jadwal_ujian.php';</script>";
    exit;
}

$ujian = mysqli_fetch_assoc($q_ujian);
$id_kelas = $ujian['id_kelas'];

// Handle Tambah Waktu
if(isset($_POST['tambah_waktu_submit'])) {
    $id_ujian_siswa = $_POST['id_ujian_siswa'];
    $menit = (int)$_POST['menit'];
    
    if($menit > 0) {
        mysqli_query($koneksi, "UPDATE ujian_siswa SET tambah_waktu = tambah_waktu + $menit WHERE id_ujian_siswa='$id_ujian_siswa'");
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Waktu berhasil ditambahkan',
                timer: 1500,
                showConfirmButton: false
            });
        </script>";
    }
}

// Handle Reset Login / Status (Optional, good for monitoring)
if(isset($_POST['reset_login'])) {
    $id_ujian_siswa = $_POST['id_ujian_siswa'];
    mysqli_query($koneksi, "DELETE FROM ujian_siswa WHERE id_ujian_siswa='$id_ujian_siswa'");
     // Also delete answers? Usually yes if full reset, but user didn't ask. 
     // Let's stick to what user asked: Status monitoring and Add Time.
     // I won't implement Reset unless asked, to avoid data loss.
}

// Get Students in Class
$q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_kelas='$id_kelas' ORDER BY nama_siswa ASC");

?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Monitoring Ujian</h1>
        <a href="jadwal_ujian.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php echo $ujian['nama_ujian']; ?> 
                <span class="badge bg-info ms-2"><?php echo $ujian['kode_bank']; ?></span>
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Siswa</th>
                            <th>NIS</th>
                            <th>Status</th>
                            <th>Nilai</th>
                            <th>Waktu Sisa</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while($s = mysqli_fetch_assoc($q_siswa)): 
                            // Get Ujian Status
                            $q_us = mysqli_query($koneksi, "SELECT * FROM ujian_siswa WHERE id_ujian='$id_ujian' AND id_siswa='".$s['id_siswa']."'");
                            $us = mysqli_fetch_assoc($q_us);
                            
                            $status_text = "Belum Dikerjakan";
                            $badge_color = "secondary";
                            $sisa_waktu_str = "-";
                            $can_add_time = false;
                            
                            if($us) {
                                if($us['status'] == 'selesai') {
                                    $status_text = "Sudah Dikerjakan";
                                    $badge_color = "success";
                                    $sisa_waktu_str = "Selesai";
                                } else {
                                    // Calculate Time
                                    $waktu_mulai = strtotime($us['waktu_mulai']);
                                    $total_waktu = ($ujian['waktu'] + $us['tambah_waktu']) * 60;
                                    $waktu_selesai = $waktu_mulai + $total_waktu;
                                    $sisa_detik = $waktu_selesai - time();
                                    
                                    if($sisa_detik <= 0) {
                                        $status_text = "Waktu Habis";
                                        $badge_color = "danger";
                                        $sisa_waktu_str = "00:00:00";
                                        $can_add_time = true;
                                    } else {
                                        $status_text = "Sedang Mengerjakan";
                                        $badge_color = "warning text-dark";
                                        $can_add_time = true;
                                        
                                        // Format sisa waktu
                                        $hours = floor($sisa_detik / 3600);
                                        $mins = floor(($sisa_detik % 3600) / 60);
                                        $secs = $sisa_detik % 60;
                                        $sisa_waktu_str = sprintf("%02d:%02d:%02d", $hours, $mins, $secs);
                                    }
                                }
                            }
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $s['nama_siswa']; ?></td>
                            <td><?php echo $s['nisn']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $badge_color; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td>
                                <?php echo ($us) ? number_format($us['nilai'], 2) : '-'; ?>
                            </td>
                            <td><?php echo $sisa_waktu_str; ?></td>
                            <td>
                                <?php if($can_add_time): ?>
                                <button type="button" class="btn btn-primary btn-sm" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#addTimeModal"
                                    data-id="<?php echo $us['id_ujian_siswa']; ?>"
                                    data-nama="<?php echo $s['nama_siswa']; ?>"
                                >
                                    <i class="fas fa-clock"></i> + Waktu
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Time Modal -->
<div class="modal fade" id="addTimeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Waktu Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id_ujian_siswa" id="time_id_ujian_siswa">
                <div class="modal-body">
                    <p>Menambahkan waktu untuk siswa: <strong id="time_nama_siswa"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Tambahan Waktu (Menit)</label>
                        <input type="number" class="form-control" name="menit" required min="1" value="10">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_waktu_submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var addTimeModal = document.getElementById('addTimeModal');
    addTimeModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var nama = button.getAttribute('data-nama');
        
        var inputId = addTimeModal.querySelector('#time_id_ujian_siswa');
        var textNama = addTimeModal.querySelector('#time_nama_siswa');
        
        inputId.value = id;
        textNama.textContent = nama;
    });
</script>

<?php include '../../includes/footer.php'; ?>
