<?php
include '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Generate Token Random
function generateToken($length = 6) {
    return strtoupper(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, $length));
}

// Handle Add/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['level'] == 'guru') {
    if (isset($_POST['add'])) {
        $nama_ujian = mysqli_real_escape_string($koneksi, $_POST['nama_ujian']);
        $id_bank_soal = $_POST['id_bank_soal'];
        $tgl_mulai = $_POST['tgl_mulai'];
        $tgl_selesai = $_POST['tgl_selesai'];
        $waktu = $_POST['waktu'];
        $token = generateToken();

        mysqli_query($koneksi, "INSERT INTO ujian (nama_ujian, id_bank_soal, tgl_mulai, tgl_selesai, waktu, token, status) VALUES ('$nama_ujian', '$id_bank_soal', '$tgl_mulai', '$tgl_selesai', '$waktu', '$token', 'aktif')");
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Jadwal ujian berhasil dibuat',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'jadwal_ujian.php';
            });
        </script>";
    }
}

if (isset($_GET['delete']) && $_SESSION['level'] == 'guru') {
    $id = $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM ujian WHERE id_ujian='$id'");
    echo "<script>window.location.href = 'jadwal_ujian.php';</script>";
}

// Get Bank Soal Options
$bank_opt = "";
$q_bank = mysqli_query($koneksi, "SELECT b.*, m.nama_mapel FROM bank_soal b JOIN mapel m ON b.id_mapel = m.id_mapel WHERE b.status='aktif' ORDER BY b.id_bank_soal DESC");
while($b = mysqli_fetch_assoc($q_bank)) {
    $bank_opt .= "<option value='".$b['id_bank_soal']."'>".$b['kode_bank']." - ".$b['nama_mapel']."</option>";
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Jadwal Ujian</h1>
        <?php if($_SESSION['level'] == 'guru'): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Tambah Jadwal
        </button>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-datatable" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Ujian</th>
                            <th>Bank Soal</th>
                            <th>Waktu</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                            <th>Token</th>
                            <th>Status</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where_guru = "";
                        if($_SESSION['level'] == 'guru') {
                            $where_guru = "WHERE b.id_guru = '".$_SESSION['user_id']."'";
                        }
                        
                        $query = mysqli_query($koneksi, "SELECT u.*, b.kode_bank, m.nama_mapel FROM ujian u JOIN bank_soal b ON u.id_bank_soal = b.id_bank_soal JOIN mapel m ON b.id_mapel = m.id_mapel $where_guru ORDER BY u.id_ujian DESC");
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($query)) :
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $row['nama_ujian']; ?></td>
                                <td><?php echo $row['kode_bank']; ?> (<?php echo $row['nama_mapel']; ?>)</td>
                                <td><?php echo $row['waktu']; ?> Menit</td>
                                <td><?php echo date('d-m-Y H:i', strtotime($row['tgl_mulai'])); ?></td>
                                <td><?php echo date('d-m-Y H:i', strtotime($row['tgl_selesai'])); ?></td>
                                <td><span class="badge bg-warning text-dark"><?php echo $row['token']; ?></span></td>
                                <td>
                                    <?php if($row['status'] == 'aktif'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Selesai</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($_SESSION['level'] == 'guru'): ?>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('jadwal_ujian.php?delete=<?php echo $row['id_ujian']; ?>')">
                                        <i class="fas fa-trash"></i>
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

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModalLabel">Tambah Jadwal Ujian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Ujian</label>
                        <input type="text" class="form-control" name="nama_ujian" required placeholder="Contoh: UAS Matematika Ganjil">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bank Soal</label>
                        <select class="form-select" name="id_bank_soal" required>
                            <option value="">Pilih Bank Soal</option>
                            <?php echo $bank_opt; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="datetime-local" class="form-control" name="tgl_mulai" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="datetime-local" class="form-control" name="tgl_selesai" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Waktu Pengerjaan (Menit)</label>
                        <input type="number" class="form-control" name="waktu" required value="60">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
