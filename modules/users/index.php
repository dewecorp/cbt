<?php
include '../../config/database.php';
$page_title = 'Manajemen User';
include '../../includes/header.php';

// Cek Level Admin
if ($_SESSION['level'] != 'admin') {
    echo "<script>window.location='../../dashboard.php';</script>";
    exit;
}

// Handle Tambah User
if (isset($_POST['tambah_user'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $level = $_POST['level'];
    
    // Cek username
    $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>Swal.fire('Error', 'Username sudah digunakan!', 'error');</script>";
    } else {
        $query = "INSERT INTO users (nama_lengkap, username, password, level, status) VALUES ('$nama', '$username', '$password', '$level', 'aktif')";
        if (mysqli_query($koneksi, $query)) {
            echo "<script>Swal.fire('Berhasil', 'User berhasil ditambahkan', 'success').then(() => { window.location='index.php'; });</script>";
        } else {
            echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
        }
    }
}

// Handle Edit User
if (isset($_POST['edit_user'])) {
    $id = $_POST['id_user'];
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $level = $_POST['level'];
    $status = $_POST['status'];
    
    $pass_query = "";
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $pass_query = ", password='$password'";
    }
    
    $query = "UPDATE users SET nama_lengkap='$nama', username='$username', level='$level', status='$status' $pass_query WHERE id_user='$id'";
    
    if (mysqli_query($koneksi, $query)) {
        echo "<script>Swal.fire('Berhasil', 'Data user berhasil diupdate', 'success').then(() => { window.location='index.php'; });</script>";
    } else {
        echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
    }
}

// Handle Hapus
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Prevent delete self
    if ($id == $_SESSION['user_id']) {
        echo "<script>Swal.fire('Error', 'Tidak bisa menghapus akun sendiri!', 'error').then(() => { window.location='index.php'; });</script>";
    } else {
        mysqli_query($koneksi, "DELETE FROM users WHERE id_user='$id'");
        echo "<script>window.location='index.php';</script>";
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manajemen Pengguna</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fas fa-plus"></i> Tambah User
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $query = mysqli_query($koneksi, "SELECT * FROM users WHERE level='admin' ORDER BY nama_lengkap ASC");
                        while ($row = mysqli_fetch_assoc($query)):
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $row['nama_lengkap']; ?></td>
                            <td><?php echo $row['username']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($row['level'] == 'admin') ? 'danger' : 'info'; ?>">
                                    <?php echo ucfirst($row['level']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo ($row['status'] == 'aktif') ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit<?php echo $row['id_user']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if($row['id_user'] != $_SESSION['user_id']): ?>
                                <a href="index.php?hapus=<?php echo $row['id_user']; ?>" class="btn btn-sm btn-danger onclick-del">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Modal Edit -->
                        <div class="modal fade" id="modalEdit<?php echo $row['id_user']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit User</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="id_user" value="<?php echo $row['id_user']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Nama Lengkap</label>
                                                <input type="text" class="form-control" name="nama" value="<?php echo $row['nama_lengkap']; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Username</label>
                                                <input type="text" class="form-control" name="username" value="<?php echo $row['username']; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Password (Kosongkan jika tidak diubah)</label>
                                                <input type="password" class="form-control" name="password">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Level</label>
                                                <input type="text" class="form-control" name="level" value="admin" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status">
                                                    <option value="aktif" <?php echo ($row['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                                                    <option value="nonaktif" <?php echo ($row['status'] == 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="edit_user" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Level</label>
                        <input type="text" class="form-control" name="level" value="admin" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_user" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.onclick-del').click(function(e) {
            e.preventDefault();
            var link = $(this).attr('href');
            Swal.fire({
                title: 'Apakah anda yakin?',
                text: "Data user akan dihapus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = link;
                }
            })
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>