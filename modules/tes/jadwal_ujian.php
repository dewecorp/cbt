<?php
include '../../config/database.php';
$page_title = 'Jadwal Asesmen';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Generate Token Random
function generateToken($length = 6) {
    return strtoupper(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, $length));
}

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['level'] == 'guru') {
    if (isset($_POST['add'])) {
        $id_bank_soal = $_POST['id_bank_soal'];
        $tgl_mulai = $_POST['tgl_mulai'];
        $tgl_selesai = $_POST['tgl_selesai'];
        $waktu = $_POST['waktu'];
        $token = generateToken();

        // Get Name from Bank Soal
        $q_bs = mysqli_query($koneksi, "SELECT kode_bank, m.nama_mapel FROM bank_soal b JOIN mapel m ON b.id_mapel=m.id_mapel WHERE id_bank_soal='$id_bank_soal'");
        $d_bs = mysqli_fetch_assoc($q_bs);
        $nama_ujian = $d_bs['kode_bank'] . " - " . $d_bs['nama_mapel'];

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

    if (isset($_POST['edit'])) {
        $id_ujian = $_POST['id_ujian'];
        $id_bank_soal = $_POST['id_bank_soal'];
        $tgl_mulai = $_POST['tgl_mulai'];
        $tgl_selesai = $_POST['tgl_selesai'];
        $waktu = $_POST['waktu'];

        // Get Name from Bank Soal
        $q_bs = mysqli_query($koneksi, "SELECT kode_bank, m.nama_mapel FROM bank_soal b JOIN mapel m ON b.id_mapel=m.id_mapel WHERE id_bank_soal='$id_bank_soal'");
        $d_bs = mysqli_fetch_assoc($q_bs);
        $nama_ujian = $d_bs['kode_bank'] . " - " . $d_bs['nama_mapel'];

        mysqli_query($koneksi, "UPDATE ujian SET nama_ujian='$nama_ujian', id_bank_soal='$id_bank_soal', tgl_mulai='$tgl_mulai', tgl_selesai='$tgl_selesai', waktu='$waktu' WHERE id_ujian='$id_ujian'");
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Jadwal ujian berhasil diperbarui',
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

// Filter Kelas Logic
$guru_kelas_ids = [];
$single_class_id = null;
$q_kelas_filter = false;

if ($_SESSION['level'] == 'guru') {
    $uid = $_SESSION['user_id'];
    $q_u = mysqli_query($koneksi, "SELECT mengajar_kelas FROM users WHERE id_user='$uid'");
    $d_u = mysqli_fetch_assoc($q_u);
    if ($d_u['mengajar_kelas']) {
        $guru_kelas_ids = explode(',', $d_u['mengajar_kelas']);
        if(count($guru_kelas_ids) == 1){
            $single_class_id = $guru_kelas_ids[0];
            if(!isset($_GET['id_kelas'])) {
                $_GET['id_kelas'] = $single_class_id;
            }
        }
        $ids_str = implode(',', $guru_kelas_ids);
        $q_kelas_filter = mysqli_query($koneksi, "SELECT * FROM kelas WHERE id_kelas IN ($ids_str) ORDER BY nama_kelas ASC");
    }
} else {
    // Admin
    $q_kelas_filter = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Jadwal Asesmen</h1>
        <?php if($_SESSION['level'] == 'guru'): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Tambah Jadwal
        </button>
        <?php endif; ?>
    </div>

    <!-- Filter Kelas -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Pilih Kelas</label>
                    <select name="id_kelas" class="form-select" onchange="this.form.submit()" <?php echo ($single_class_id) ? 'disabled' : ''; ?>>
                        <?php if(!$single_class_id): ?>
                        <option value="">-- Pilih Kelas --</option>
                        <?php endif; ?>
                        <?php 
                        if($q_kelas_filter) {
                            while($k = mysqli_fetch_assoc($q_kelas_filter)): 
                        ?>
                        <option value="<?php echo $k['id_kelas']; ?>" <?php echo (isset($_GET['id_kelas']) && $_GET['id_kelas'] == $k['id_kelas']) ? 'selected' : ''; ?>>
                            <?php echo $k['nama_kelas']; ?>
                        </option>
                        <?php 
                            endwhile; 
                        }
                        ?>
                    </select>
                    <?php if($single_class_id): ?>
                    <input type="hidden" name="id_kelas" value="<?php echo $single_class_id; ?>">
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php if(isset($_GET['id_kelas']) && !empty($_GET['id_kelas'])): ?>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-datatable" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Asesmen</th>
                            <th>Bank Soal</th>
                            <th>Waktu</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                            <th>Token</th>
                            <th>Status</th>
                            <?php if($_SESSION['level'] == 'guru'): ?>
                            <th width="15%">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where_guru = "";
                        $id_kelas_sel = $_GET['id_kelas'];
                        
                        if($_SESSION['level'] == 'guru') {
                            $where_guru = "WHERE b.id_guru = '".$_SESSION['user_id']."' AND b.id_kelas = '$id_kelas_sel'";
                        } else {
                            $where_guru = "WHERE b.id_kelas = '$id_kelas_sel'";
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
                                    <?php 
                                    $now = time();
                                    $start = strtotime($row['tgl_mulai']);
                                    $end = strtotime($row['tgl_selesai']);
                                    
                                    if ($now > $end) {
                                        echo '<span class="badge bg-secondary">Selesai</span>';
                                    } elseif ($now >= $start && $now <= $end) {
                                        echo '<span class="badge bg-success">Aktif</span>';
                                    } else {
                                        echo '<span class="badge bg-warning text-dark">Belum Mulai</span>';
                                    }
                                    ?>
                                </td>
                                <?php if($_SESSION['level'] == 'guru'): ?>
                                <td>
                                    <a href="monitoring_ujian.php?id=<?php echo $row['id_ujian']; ?>" class="btn btn-info btn-sm text-white" title="Monitoring Asesmen">
                                        <i class="fas fa-desktop"></i>
                                    </a>
                                    <button type="button" class="btn btn-warning btn-sm text-white" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editModal"
                                        data-id="<?php echo $row['id_ujian']; ?>"
                                        data-bank="<?php echo $row['id_bank_soal']; ?>"
                                        data-mulai="<?php echo date('Y-m-d\TH:i', strtotime($row['tgl_mulai'])); ?>"
                                        data-selesai="<?php echo date('Y-m-d\TH:i', strtotime($row['tgl_selesai'])); ?>"
                                        data-waktu="<?php echo $row['waktu']; ?>"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('jadwal_ujian.php?delete=<?php echo $row['id_ujian']; ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModalLabel">Tambah Jadwal Asesmen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
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

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Jadwal Asesmen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id_ujian" id="edit_id_ujian">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Bank Soal</label>
                        <select class="form-select" name="id_bank_soal" id="edit_id_bank_soal" required>
                            <option value="">Pilih Bank Soal</option>
                            <?php echo $bank_opt; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="datetime-local" class="form-control" name="tgl_mulai" id="edit_tgl_mulai" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="datetime-local" class="form-control" name="tgl_selesai" id="edit_tgl_selesai" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Waktu Pengerjaan (Menit)</label>
                        <input type="number" class="form-control" name="waktu" id="edit_waktu" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var bank = button.getAttribute('data-bank');
        var mulai = button.getAttribute('data-mulai');
        var selesai = button.getAttribute('data-selesai');
        var waktu = button.getAttribute('data-waktu');
        
        var inputId = editModal.querySelector('#edit_id_ujian');
        var selectBank = editModal.querySelector('#edit_id_bank_soal');
        var inputMulai = editModal.querySelector('#edit_tgl_mulai');
        var inputSelesai = editModal.querySelector('#edit_tgl_selesai');
        var inputWaktu = editModal.querySelector('#edit_waktu');
        
        inputId.value = id;
        selectBank.value = bank;
        inputMulai.value = mulai;
        inputSelesai.value = selesai;
        inputWaktu.value = waktu;
    });
</script>

<?php include '../../includes/footer.php'; ?>
