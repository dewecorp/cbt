<?php
include '../../config/database.php';
$page_title = 'Data Mapel';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Check if kktp column exists
$check_col = mysqli_query($koneksi, "SHOW COLUMNS FROM mapel LIKE 'kktp'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($koneksi, "ALTER TABLE mapel ADD COLUMN kktp INT(11) DEFAULT 70");
}

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $kode_mapel = mysqli_real_escape_string($koneksi, $_POST['kode_mapel']);
        $nama_mapel = mysqli_real_escape_string($koneksi, $_POST['nama_mapel']);
        $kktp = (int)$_POST['kktp'];
        
        $check = mysqli_query($koneksi, "SELECT * FROM mapel WHERE kode_mapel='$kode_mapel'");
        if(mysqli_num_rows($check) > 0) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Kode Mapel sudah digunakan!',
                });
            </script>";
        } else {
            mysqli_query($koneksi, "INSERT INTO mapel (kode_mapel, nama_mapel, kktp) VALUES ('$kode_mapel', '$nama_mapel', '$kktp')");
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data mapel berhasil ditambahkan',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'mapel.php';
                });
            </script>";
        }
    } elseif (isset($_POST['edit'])) {
        $id_mapel = $_POST['id_mapel'];
        $kode_mapel = mysqli_real_escape_string($koneksi, $_POST['kode_mapel']);
        $nama_mapel = mysqli_real_escape_string($koneksi, $_POST['nama_mapel']);
        $kktp = (int)$_POST['kktp'];
        
        mysqli_query($koneksi, "UPDATE mapel SET kode_mapel='$kode_mapel', nama_mapel='$nama_mapel', kktp='$kktp' WHERE id_mapel='$id_mapel'");
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Data mapel berhasil diperbarui',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'mapel.php';
            });
        </script>";
    } elseif (isset($_POST['set_bulk_kktp'])) {
        $kktp = (int)$_POST['bulk_kktp'];
        
        mysqli_query($koneksi, "UPDATE mapel SET kktp='$kktp'");
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Semua KKTP berhasil diperbarui menjadi $kktp',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'mapel.php';
            });
        </script>";
    }
}

// Handle Delete via GET
if (isset($_GET['delete'])) {
    $id_mapel = $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM mapel WHERE id_mapel='$id_mapel'");
    echo "<script>
        window.location.href = 'mapel.php';
    </script>";
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Mata Pelajaran</h1>
        <div>
            <button type="button" class="btn btn-success text-white me-2" data-bs-toggle="modal" data-bs-target="#bulkKktpModal">
                <i class="fas fa-tasks"></i> Set Semua KKTP
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Tambah Mapel
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-datatable" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th width="5%">No</th>
                            <th>Kode Mapel</th>
                            <th>Nama Mapel</th>
                            <th>KKTP</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = mysqli_query($koneksi, "SELECT * FROM mapel ORDER BY nama_mapel ASC");
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($query)) :
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $row['kode_mapel']; ?></td>
                                <td><?php echo $row['nama_mapel']; ?></td>
                                <td><?php echo isset($row['kktp']) ? $row['kktp'] : '70'; ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                        data-id="<?php echo $row['id_mapel']; ?>" 
                                        data-kode="<?php echo $row['kode_mapel']; ?>"
                                        data-nama="<?php echo $row['nama_mapel']; ?>"
                                        data-kktp="<?php echo isset($row['kktp']) ? $row['kktp'] : '70'; ?>"
                                        data-bs-toggle="modal" data-bs-target="#editModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('mapel.php?delete=<?php echo $row['id_mapel']; ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
                <h5 class="modal-title" id="addModalLabel">Tambah Mapel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Mapel</label>
                        <input type="text" class="form-control" name="kode_mapel" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Mapel</label>
                        <input type="text" class="form-control" name="nama_mapel" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">KKTP</label>
                        <input type="number" class="form-control" name="kktp" value="70" required>
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

<!-- Bulk KKTP Modal -->
<div class="modal fade" id="bulkKktpModal" tabindex="-1" aria-labelledby="bulkKktpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkKktpModalLabel">Set Semua KKTP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p class="text-danger">Perhatian: Tindakan ini akan mengubah nilai KKTP untuk <strong>SEMUA</strong> mata pelajaran.</p>
                    <div class="mb-3">
                        <label class="form-label">Nilai KKTP Baru</label>
                        <input type="number" class="form-control" name="bulk_kktp" value="70" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="set_bulk_kktp" class="btn btn-primary text-white">Update Semua</button>
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
                <h5 class="modal-title" id="editModalLabel">Edit Mapel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_mapel" id="edit_id_mapel">
                    <div class="mb-3">
                        <label class="form-label">Kode Mapel</label>
                        <input type="text" class="form-control" name="kode_mapel" id="edit_kode_mapel" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Mapel</label>
                        <input type="text" class="form-control" name="nama_mapel" id="edit_nama_mapel" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">KKTP</label>
                        <input type="number" class="form-control" name="kktp" id="edit_kktp" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Handle Edit Button Click
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        var kode = $(this).data('kode');
        var nama = $(this).data('nama');
        var kktp = $(this).data('kktp');
        
        $('#edit_id_mapel').val(id);
        $('#edit_kode_mapel').val(kode);
        $('#edit_nama_mapel').val(nama);
        $('#edit_kktp').val(kktp);
    });
</script>

<?php include '../../includes/footer.php'; ?>
