<?php
require '../../vendor/autoload.php';
include '../../config/database.php';
$page_title = 'Data Guru';
include '../../includes/header.php';

use Shuchkin\SimpleXLSX;

// Fetch Kelas
$q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
$kelas_opts = [];
while($k = mysqli_fetch_assoc($q_kelas)) {
    $kelas_opts[] = $k;
}

// Fetch Mapel
$q_mapel = mysqli_query($koneksi, "SELECT * FROM mapel ORDER BY nama_mapel ASC");
$mapel_opts = [];
while($m = mysqli_fetch_assoc($q_mapel)) {
    $mapel_opts[] = $m;
}

// Handle Add
if (isset($_POST['add'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $password_plain = $_POST['password'];
    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
    
    // Handle Multiselect
    $mengajar_kelas = isset($_POST['mengajar_kelas']) ? implode(',', $_POST['mengajar_kelas']) : '';
    $mengajar_mapel = isset($_POST['mengajar_mapel']) ? implode(',', $_POST['mengajar_mapel']) : '';
    
    // Upload Foto
    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "../../assets/img/guru/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $foto_name = $username . '_' . time() . '.' . $file_ext;
        $target_file = $target_dir . $foto_name;
        
        if (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                $foto = $foto_name;
            }
        }
    }
    
    $check = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
    if(mysqli_num_rows($check) > 0) {
         echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'NUPTK sudah digunakan!',
            });
        </script>";
    } else {
        $query = "INSERT INTO users (username, password, password_plain, nama_lengkap, foto, level, mengajar_kelas, mengajar_mapel) VALUES ('$username', '$password_hash', '$password_plain', '$nama_lengkap', '$foto', 'guru', '$mengajar_kelas', '$mengajar_mapel')";
        if(mysqli_query($koneksi, $query)) {
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data guru berhasil ditambahkan',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'guru.php';
                });
            </script>";
        } else {
            echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
        }
    }
}

// Handle Edit
if (isset($_POST['edit'])) {
    $id_user = $_POST['id_user'];
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $password_plain = $_POST['password'];
    
    // Handle Multiselect
    $mengajar_kelas = isset($_POST['mengajar_kelas']) ? implode(',', $_POST['mengajar_kelas']) : '';
    $mengajar_mapel = isset($_POST['mengajar_mapel']) ? implode(',', $_POST['mengajar_mapel']) : '';
    
    // Get old data
    $q_old = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user'");
    $d_old = mysqli_fetch_assoc($q_old);
    
    // Upload Foto
    $foto = $d_old['foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "../../assets/img/guru/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $foto_name = $username . '_' . time() . '.' . $file_ext;
        $target_file = $target_dir . $foto_name;
        
        if (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                // Delete old photo
                if ($d_old['foto'] && file_exists($target_dir . $d_old['foto'])) {
                    unlink($target_dir . $d_old['foto']);
                }
                $foto = $foto_name;
            }
        }
    }
    
    $query_str = "UPDATE users SET username='$username', nama_lengkap='$nama_lengkap', foto='$foto', mengajar_kelas='$mengajar_kelas', mengajar_mapel='$mengajar_mapel'";
    
    if(!empty($password_plain)) {
        $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
        $query_str .= ", password='$password_hash', password_plain='$password_plain'";
    }
    
    $query_str .= " WHERE id_user='$id_user'";
    
    if(mysqli_query($koneksi, $query_str)) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Data guru berhasil diperbarui',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'guru.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
    }
}

// Handle Import
if (isset($_POST['import'])) {
    if (isset($_FILES['file_import']) && $_FILES['file_import']['error'] == 0) {
        $ext = pathinfo($_FILES['file_import']['name'], PATHINFO_EXTENSION);
        if ($ext == 'xlsx') {
            if ($xlsx = SimpleXLSX::parse($_FILES['file_import']['tmp_name'])) {
                $success = 0;
                $failed = 0;
                
                // Skip header (first row)
                $rows = $xlsx->rows();
                if (is_array($rows)) {
                    for ($i = 1; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        // Format Excel: NUPTK, Nama Lengkap, Password
                        if (count($row) >= 3) {
                            $username = mysqli_real_escape_string($koneksi, $row[0]);
                            $nama = mysqli_real_escape_string($koneksi, $row[1]);
                            $pass = mysqli_real_escape_string($koneksi, $row[2]);
                            
                            // Skip empty rows
                            if(empty($username) || empty($nama)) continue;

                            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
                            
                            // Cek duplicate
                            $check = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
                            if (mysqli_num_rows($check) == 0) {
                                $q = "INSERT INTO users (username, password, password_plain, nama_lengkap, level) VALUES ('$username', '$pass_hash', '$pass', '$nama', 'guru')";
                                if (mysqli_query($koneksi, $q)) {
                                    $success++;
                                } else {
                                    $failed++;
                                }
                            } else {
                                $failed++; // Duplicate
                            }
                        }
                    }
                }
                
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Import Selesai',
                        text: 'Berhasil: $success, Gagal/Duplikat: $failed',
                    }).then(() => {
                        window.location.href = 'guru.php';
                    });
                </script>";
            } else {
                echo "<script>Swal.fire('Error', 'Gagal parsing file Excel', 'error');</script>";
            }
        } else {
            echo "<script>Swal.fire('Error', 'Format file harus XLSX', 'error');</script>";
        }
    }
}

// Handle Delete via GET
if (isset($_GET['delete'])) {
    $id_user = $_GET['delete'];
    
    // Get photo to delete
    $q_del = mysqli_query($koneksi, "SELECT foto FROM users WHERE id_user='$id_user'");
    $d_del = mysqli_fetch_assoc($q_del);
    if ($d_del['foto'] && file_exists("../../assets/img/guru/" . $d_del['foto'])) {
        unlink("../../assets/img/guru/" . $d_del['foto']);
    }
    
    if(mysqli_query($koneksi, "DELETE FROM users WHERE id_user='$id_user'")) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Data guru berhasil dihapus',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'guru.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
    }
}
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    /* Custom Select2 Styling for Multiselect */
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered .select2-selection__choice {
        background-color: #4e73df; /* Primary Color */
        border-color: #4e73df;
        color: #fff;
        border-radius: 0.35rem;
        padding: 2px 8px;
        font-size: 0.85rem;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered .select2-selection__choice .select2-selection__choice__remove {
        color: #fff;
        margin-right: 5px;
        border-right: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered .select2-selection__choice .select2-selection__choice__remove:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    /* Fix Focus Border */
    .select2-container--bootstrap-5.select2-container--focus .select2-selection, 
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Guru</h1>
    <div>
        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-file-excel"></i> Import Excel
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Tambah Guru
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
                        <th width="10%">Foto</th>
                        <th>NUPTK</th>
                        <th>Nama Lengkap</th>
                        <th>Password</th>
                        <th>Mengajar Kelas</th>
                        <th>Mengajar Mapel</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE level='guru' ORDER BY nama_lengkap ASC");
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($query)) :
                        // Process Kelas
                        $kelas_ids = explode(',', $row['mengajar_kelas'] ?? '');
                        $kelas_names = [];
                        foreach($kelas_ids as $kid) {
                            foreach($kelas_opts as $ko) {
                                if($ko['id_kelas'] == $kid) $kelas_names[] = $ko['nama_kelas'];
                            }
                        }
                        
                        // Process Mapel
                        $mapel_ids = explode(',', $row['mengajar_mapel'] ?? '');
                        $mapel_names = [];
                        foreach($mapel_ids as $mid) {
                            foreach($mapel_opts as $mo) {
                                if($mo['id_mapel'] == $mid) $mapel_names[] = $mo['nama_mapel'];
                            }
                        }
                    ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td class="text-center">
                                <?php if($row['foto']): ?>
                                    <img src="../../assets/img/guru/<?php echo $row['foto']; ?>" alt="Foto" class="img-thumbnail" style="height: 50px;">
                                <?php else: ?>
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['nama_lengkap']); ?>&background=random" alt="Foto" class="img-thumbnail" style="height: 50px;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo $row['nama_lengkap']; ?></td>
                            <td><?php echo $row['password_plain']; ?></td>
                            <td>
                                <?php 
                                if(!empty($kelas_names)) {
                                    foreach($kelas_names as $kn) {
                                        echo '<span class="badge bg-info me-1">'.$kn.'</span>';
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if(!empty($mapel_names)) {
                                    foreach($mapel_names as $mn) {
                                        echo '<span class="badge bg-secondary me-1">'.$mn.'</span>';
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                    data-id="<?php echo $row['id_user']; ?>" 
                                    data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                    data-nama="<?php echo htmlspecialchars($row['nama_lengkap']); ?>"
                                    data-password="<?php echo htmlspecialchars($row['password_plain']); ?>"
                                    data-kelas="<?php echo htmlspecialchars($row['mengajar_kelas'] ?? ''); ?>"
                                    data-mapel="<?php echo htmlspecialchars($row['mengajar_mapel'] ?? ''); ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('guru.php?delete=<?php echo $row['id_user']; ?>')">
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


<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModalLabel">Tambah Guru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">NUPTK</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mengajar Kelas</label>
                        <select class="form-select select2-add" name="mengajar_kelas[]" multiple="multiple" style="width: 100%">
                            <?php foreach($kelas_opts as $k): ?>
                                <option value="<?php echo $k['id_kelas']; ?>"><?php echo $k['nama_kelas']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mengajar Mapel</label>
                        <select class="form-select select2-add" name="mengajar_mapel[]" multiple="multiple" style="width: 100%">
                            <?php foreach($mapel_opts as $m): ?>
                                <option value="<?php echo $m['id_mapel']; ?>"><?php echo $m['nama_mapel']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" class="form-control" name="password" required>
                        <div class="form-text text-muted">Password akan tersimpan dan terlihat.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto</label>
                        <input type="file" class="form-control" name="foto" accept="image/*">
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
                <h5 class="modal-title" id="editModalLabel">Edit Guru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id_user" id="edit_id_user">
                    <div class="mb-3">
                        <label class="form-label">NUPTK</label>
                        <input type="text" class="form-control" name="username" id="edit_username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" id="edit_nama_lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mengajar Kelas</label>
                        <select class="form-select select2-edit" name="mengajar_kelas[]" id="edit_mengajar_kelas" multiple="multiple" style="width: 100%">
                            <?php foreach($kelas_opts as $k): ?>
                                <option value="<?php echo $k['id_kelas']; ?>"><?php echo $k['nama_kelas']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mengajar Mapel</label>
                        <select class="form-select select2-edit" name="mengajar_mapel[]" id="edit_mengajar_mapel" multiple="multiple" style="width: 100%">
                            <?php foreach($mapel_opts as $m): ?>
                                <option value="<?php echo $m['id_mapel']; ?>"><?php echo $m['nama_mapel']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" class="form-control" name="password" id="edit_password" placeholder="Kosongkan jika tidak diubah">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ganti Foto</label>
                        <input type="file" class="form-control" name="foto" accept="image/*">
                        <div class="form-text">Biarkan kosong jika tidak ingin mengganti foto.</div>
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

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Data Guru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="importForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Gunakan format file Excel (.xlsx) dengan urutan kolom:
                        <strong>NUPTK, Nama Lengkap, Password</strong>.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File Excel</label>
                        <input type="file" class="form-control" name="file_import" accept=".xlsx" required>
                    </div>
                    <div class="mb-3">
                        <a href="../../assets/template_guru.xlsx" class="btn btn-outline-success btn-sm" download>
                            <i class="fas fa-download"></i> Download Template
                        </a>
                    </div>
                    <!-- Progress Bar (Hidden by default) -->
                    <div class="progress d-none" id="importProgress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">Sedang memproses...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="import" class="btn btn-success" onclick="document.getElementById('importProgress').classList.remove('d-none');">Import</button>
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
        // Init Select2 for Add Modal
        $('.select2-add').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#addModal')
        });

        // Init Select2 for Edit Modal
        $('.select2-edit').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#editModal')
        });

        // Handle Edit Button Click
        $(document).on('click', '.btn-edit', function() {
            var id = $(this).attr('data-id');
            var username = $(this).attr('data-username');
            var nama = $(this).attr('data-nama');
            var password = $(this).attr('data-password');
            var kelas = $(this).attr('data-kelas');
            var mapel = $(this).attr('data-mapel');
            
            $('#edit_id_user').val(id);
            $('#edit_username').val(username);
            $('#edit_nama_lengkap').val(nama);
            $('#edit_password').val(password);
            
            // Set Select2 values
            if(kelas) {
                var kelasArr = kelas.split(',');
                $('#edit_mengajar_kelas').val(kelasArr).trigger('change');
            } else {
                $('#edit_mengajar_kelas').val(null).trigger('change');
            }

            if(mapel) {
                var mapelArr = mapel.split(',');
                $('#edit_mengajar_mapel').val(mapelArr).trigger('change');
            } else {
                $('#edit_mengajar_mapel').val(null).trigger('change');
            }
        });
        
        // Reset form when modal is closed
        $('#addModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            $('.select2-add').val(null).trigger('change');
        });
    });
</script>
