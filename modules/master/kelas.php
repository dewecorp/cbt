<?php
include '../../config/database.php';
$page_title = 'Data Kelas';
include '../../includes/header.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<?php
include '../../includes/sidebar.php';

// Fetch Teachers for Dropdown
$q_guru = mysqli_query($koneksi, "SELECT id_user, nama_lengkap FROM users WHERE level='guru' ORDER BY nama_lengkap ASC");
$guru_opts = [];
while($g = mysqli_fetch_assoc($q_guru)) {
    $guru_opts[] = $g;
}

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $nama_kelas = mysqli_real_escape_string($koneksi, $_POST['nama_kelas']);
        $wali_kelas = !empty($_POST['wali_kelas']) ? $_POST['wali_kelas'] : 'NULL';
        
        mysqli_query($koneksi, "INSERT INTO kelas (nama_kelas, wali_kelas) VALUES ('$nama_kelas', $wali_kelas)");
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Data kelas berhasil ditambahkan',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'kelas.php';
            });
        </script>";
    } elseif (isset($_POST['edit'])) {
        $id_kelas = $_POST['id_kelas'];
        $nama_kelas = mysqli_real_escape_string($koneksi, $_POST['nama_kelas']);
        $wali_kelas = !empty($_POST['wali_kelas']) ? $_POST['wali_kelas'] : 'NULL';
        
        mysqli_query($koneksi, "UPDATE kelas SET nama_kelas='$nama_kelas', wali_kelas=$wali_kelas WHERE id_kelas='$id_kelas'");
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Data kelas berhasil diperbarui',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'kelas.php';
            });
        </script>";
    }
}

// Handle Delete via GET
if (isset($_GET['delete'])) {
    $id_kelas = $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM kelas WHERE id_kelas='$id_kelas'");
    echo "<script>
        window.location.href = 'kelas.php';
    </script>";
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Data Kelas</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Tambah Kelas
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-datatable" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Kelas</th>
                            <th>Wali Kelas</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = mysqli_query($koneksi, "SELECT kelas.*, users.nama_lengkap as nama_wali FROM kelas LEFT JOIN users ON kelas.wali_kelas = users.id_user ORDER BY nama_kelas ASC");
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($query)) :
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $row['nama_kelas']; ?></td>
                                <td><?php echo $row['nama_wali'] ? $row['nama_wali'] : '-'; ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                        data-id="<?php echo $row['id_kelas']; ?>" 
                                        data-nama="<?php echo $row['nama_kelas']; ?>"
                                        data-wali="<?php echo $row['wali_kelas']; ?>"
                                        data-bs-toggle="modal" data-bs-target="#editModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('kelas.php?delete=<?php echo $row['id_kelas']; ?>')">
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
                <h5 class="modal-title" id="addModalLabel">Tambah Kelas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nama_kelas" class="form-label">Nama Kelas</label>
                        <input type="text" class="form-control" name="nama_kelas" required>
                    </div>
                    <div class="mb-3">
                        <label for="wali_kelas" class="form-label">Wali Kelas</label>
                        <select class="form-select select2-add" name="wali_kelas" style="width: 100%;">
                            <option value="">-- Pilih Wali Kelas --</option>
                            <?php foreach($guru_opts as $g): ?>
                                <option value="<?php echo $g['id_user']; ?>"><?php echo $g['nama_lengkap']; ?></option>
                            <?php endforeach; ?>
                        </select>
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
                <h5 class="modal-title" id="editModalLabel">Edit Kelas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_kelas" id="edit_id_kelas">
                    <div class="mb-3">
                        <label for="edit_nama_kelas" class="form-label">Nama Kelas</label>
                        <input type="text" class="form-control" name="nama_kelas" id="edit_nama_kelas" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_wali_kelas" class="form-label">Wali Kelas</label>
                        <select class="form-select select2-edit" name="wali_kelas" id="edit_wali_kelas" style="width: 100%;">
                            <option value="">-- Pilih Wali Kelas --</option>
                            <?php foreach($guru_opts as $g): ?>
                                <option value="<?php echo $g['id_user']; ?>"><?php echo $g['nama_lengkap']; ?></option>
                            <?php endforeach; ?>
                        </select>
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

<?php include '../../includes/footer.php'; ?>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2 for Add Modal
        $('.select2-add').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#addModal')
        });

        // Initialize Select2 for Edit Modal
        $('.select2-edit').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#editModal')
        });
    });

    // Handle Edit Button Click
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var wali = $(this).data('wali');
        
        $('#edit_id_kelas').val(id);
        $('#edit_nama_kelas').val(nama);
        $('#edit_wali_kelas').val(wali).trigger('change'); // Trigger change for Select2
    });
</script>
