<?php
include '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['level'] == 'guru') {
    if (isset($_POST['add'])) {
        $kode_bank = mysqli_real_escape_string($koneksi, $_POST['kode_bank']);
        $id_mapel = $_POST['id_mapel'];
        $id_kelas = $_POST['id_kelas'];
        $id_guru = $_SESSION['user_id']; 
        
        mysqli_query($koneksi, "INSERT INTO bank_soal (kode_bank, id_mapel, id_kelas, id_guru) VALUES ('$kode_bank', '$id_mapel', '$id_kelas', '$id_guru')");
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Bank Soal berhasil dibuat',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'bank_soal.php';
            });
        </script>";
    }

    if (isset($_POST['edit'])) {
        $id_bank_soal = $_POST['id_bank_soal'];
        $kode_bank = mysqli_real_escape_string($koneksi, $_POST['kode_bank']);
        $id_mapel = $_POST['id_mapel'];
        $id_kelas = $_POST['id_kelas'];
        
        mysqli_query($koneksi, "UPDATE bank_soal SET kode_bank='$kode_bank', id_mapel='$id_mapel', id_kelas='$id_kelas' WHERE id_bank_soal='$id_bank_soal'");
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Bank Soal berhasil diperbarui',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'bank_soal.php';
            });
        </script>";
    }
}

if (isset($_GET['delete']) && $_SESSION['level'] == 'guru') {
    $id = $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM bank_soal WHERE id_bank_soal='$id'");
    echo "<script>window.location.href = 'bank_soal.php';</script>";
}

// Get Mapel Options
$mapel_opt = "";
if ($_SESSION['level'] == 'guru') {
    $uid = $_SESSION['user_id'];
    $q_u = mysqli_query($koneksi, "SELECT mengajar_mapel FROM users WHERE id_user='$uid'");
    $d_u = mysqli_fetch_assoc($q_u);
    
    $where_mapel = "";
    if (!empty($d_u['mengajar_mapel'])) {
        $mapel_ids = $d_u['mengajar_mapel'];
        $where_mapel = "WHERE id_mapel IN ($mapel_ids)";
    } else {
        $where_mapel = "WHERE 1=0"; // No mapel assigned
    }
    $q_mapel = mysqli_query($koneksi, "SELECT * FROM mapel $where_mapel ORDER BY nama_mapel ASC");
} else {
    $q_mapel = mysqli_query($koneksi, "SELECT * FROM mapel ORDER BY nama_mapel ASC");
}

while($m = mysqli_fetch_assoc($q_mapel)) {
    $mapel_opt .= "<option value='".$m['id_mapel']."'>".$m['nama_mapel']."</option>";
}

// Get Guru Options (if admin)
$guru_opt = "";
if($_SESSION['level'] == 'admin') {
    $q_guru = mysqli_query($koneksi, "SELECT * FROM users WHERE level='guru' ORDER BY nama_lengkap ASC");
    while($g = mysqli_fetch_assoc($q_guru)) {
        $guru_opt .= "<option value='".$g['id_user']."'>".$g['nama_lengkap']."</option>";
    }
}

// Get Kelas Options
$guru_kelas_ids = [];
$guru_kelas_opts = "";
$single_kelas_id = "";
$single_kelas_nama = "";

if ($_SESSION['level'] == 'guru') {
    $uid = $_SESSION['user_id'];
    $q_u = mysqli_query($koneksi, "SELECT mengajar_kelas FROM users WHERE id_user='$uid'");
    $d_u = mysqli_fetch_assoc($q_u);
    if ($d_u['mengajar_kelas']) {
        $guru_kelas_ids = explode(',', $d_u['mengajar_kelas']);
        
        // Fetch class details
        if (!empty($guru_kelas_ids)) {
            $ids_str = implode(',', $guru_kelas_ids);
            $q_k = mysqli_query($koneksi, "SELECT * FROM kelas WHERE id_kelas IN ($ids_str) ORDER BY nama_kelas ASC");
            while ($k = mysqli_fetch_assoc($q_k)) {
                $selected = (count($guru_kelas_ids) == 1) ? 'selected' : '';
                $guru_kelas_opts .= "<option value='".$k['id_kelas']."' $selected>".$k['nama_kelas']."</option>";
                if (count($guru_kelas_ids) == 1) {
                    $single_kelas_id = $k['id_kelas'];
                    $single_kelas_nama = $k['nama_kelas'];
                }
            }
        }
    }
} else {
    // Admin: Show all classes
    $q_k = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
    while ($k = mysqli_fetch_assoc($q_k)) {
        $guru_kelas_opts .= "<option value='".$k['id_kelas']."'>".$k['nama_kelas']."</option>";
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Bank Soal</h1>
        <?php if($_SESSION['level'] == 'guru'): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Buat Bank Soal
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
                            <th>Kode Bank</th>
                            <th>Mata Pelajaran</th>
                            <th>Kelas</th>
                            <th>Guru</th>
                            <th>Tanggal</th>
                            <th>Jumlah Soal</th>
                            <th>Status</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = "";
                        if($_SESSION['level'] == 'guru') {
                            $where = "WHERE b.id_guru = '".$_SESSION['user_id']."'";
                        }
                        
                        $query = mysqli_query($koneksi, "
                            SELECT b.*, m.nama_mapel, u.nama_lengkap, k.nama_kelas,
                            (SELECT COUNT(*) FROM soal WHERE id_bank_soal = b.id_bank_soal) as jml_soal 
                            FROM bank_soal b 
                            JOIN mapel m ON b.id_mapel = m.id_mapel 
                            JOIN users u ON b.id_guru = u.id_user 
                            LEFT JOIN kelas k ON b.id_kelas = k.id_kelas
                            $where
                            ORDER BY b.id_bank_soal DESC
                        ");
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($query)) :
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $row['kode_bank']; ?></td>
                                <td><?php echo $row['nama_mapel']; ?></td>
                                <td><?php echo $row['nama_kelas']; ?></td>
                                <td><?php echo $row['nama_lengkap']; ?></td>
                                <td><?php echo date('d-m-Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $row['jml_soal']; ?> Soal</span>
                                </td>
                                <td>
                                    <?php if($row['status'] == 'aktif'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="buat_soal.php?id=<?php echo $row['id_bank_soal']; ?>" class="btn btn-info btn-sm text-white">
                                        <i class="fas fa-list"></i> <?php echo ($_SESSION['level'] == 'admin') ? 'Lihat Soal' : 'Kelola Soal'; ?>
                                    </a>
                                    <?php if($_SESSION['level'] == 'guru'): ?>
                                    <button type="button" class="btn btn-warning btn-sm text-white" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editModal"
                                        data-id="<?php echo $row['id_bank_soal']; ?>"
                                        data-kode="<?php echo $row['kode_bank']; ?>"
                                        data-mapel="<?php echo $row['id_mapel']; ?>"
                                        data-kelas="<?php echo $row['id_kelas']; ?>"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('bank_soal.php?delete=<?php echo $row['id_bank_soal']; ?>')">
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
                <h5 class="modal-title" id="addModalLabel">Buat Bank Soal Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Bank Soal</label>
                        <input type="text" class="form-control" name="kode_bank" required placeholder="Contoh: UH-MATEMATIKA-7A">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mata Pelajaran</label>
                        <select class="form-select" name="id_mapel" required>
                            <option value="">Pilih Mapel</option>
                            <?php echo $mapel_opt; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <?php if($_SESSION['level'] == 'guru' && count($guru_kelas_ids) == 1): ?>
                            <input type="text" class="form-control" value="<?php echo $single_kelas_nama; ?>" disabled>
                            <input type="hidden" name="id_kelas" value="<?php echo $single_kelas_id; ?>">
                        <?php else: ?>
                            <select class="form-select" name="id_kelas" required>
                                <option value="">Pilih Kelas</option>
                                <?php echo $guru_kelas_opts; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <?php if($_SESSION['level'] == 'admin'): ?>
                    <div class="mb-3">
                        <label class="form-label">Guru Pengampu</label>
                        <select class="form-select" name="id_guru" required>
                            <option value="">Pilih Guru</option>
                            <?php echo $guru_opt; ?>
                        </select>
                    </div>
                    <?php endif; ?>
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
                <h5 class="modal-title" id="editModalLabel">Edit Bank Soal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id_bank_soal" id="edit_id_bank_soal">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Bank Soal</label>
                        <input type="text" class="form-control" name="kode_bank" id="edit_kode_bank" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mata Pelajaran</label>
                        <select class="form-select" name="id_mapel" id="edit_id_mapel" required>
                            <option value="">Pilih Mapel</option>
                            <?php echo $mapel_opt; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <?php if($_SESSION['level'] == 'guru' && count($guru_kelas_ids) == 1): ?>
                            <input type="text" class="form-control" value="<?php echo $single_kelas_nama; ?>" disabled>
                            <input type="hidden" name="id_kelas" value="<?php echo $single_kelas_id; ?>">
                        <?php else: ?>
                            <select class="form-select" name="id_kelas" id="edit_id_kelas" required>
                                <option value="">Pilih Kelas</option>
                                <?php echo $guru_kelas_opts; ?>
                            </select>
                        <?php endif; ?>
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
        var kode = button.getAttribute('data-kode');
        var mapel = button.getAttribute('data-mapel');
        var kelas = button.getAttribute('data-kelas');
        
        var modalTitle = editModal.querySelector('.modal-title');
        var inputId = editModal.querySelector('#edit_id_bank_soal');
        var inputKode = editModal.querySelector('#edit_kode_bank');
        var selectMapel = editModal.querySelector('#edit_id_mapel');
        var selectKelas = editModal.querySelector('#edit_id_kelas');
        
        inputId.value = id;
        inputKode.value = kode;
        selectMapel.value = mapel;
        if(selectKelas) {
            selectKelas.value = kelas;
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>
