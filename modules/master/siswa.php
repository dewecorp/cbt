<?php
include '../../config/database.php';
$page_title = 'Data Siswa';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Include SimpleXLSX library
if (file_exists('../../vendor/shuchkin/simplexlsx/src/SimpleXLSX.php')) {
    include_once '../../vendor/shuchkin/simplexlsx/src/SimpleXLSX.php';
}
use Shuchkin\SimpleXLSX;

function generateRandomPassword($length = 6) {
    return substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

// Handle Add/Edit/Delete/Import
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $nisn = mysqli_real_escape_string($koneksi, $_POST['nisn']);
        $nama_siswa = mysqli_real_escape_string($koneksi, $_POST['nama_siswa']);
        $tempat_lahir = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir']);
        $tanggal_lahir = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
        $jk = mysqli_real_escape_string($koneksi, $_POST['jk']);
        $id_kelas = $_POST['id_kelas'];
        
        // Password logic
        if (!empty($_POST['password'])) {
            $password = mysqli_real_escape_string($koneksi, $_POST['password']);
        } else {
            $password = generateRandomPassword(); // Default Random
        }
        
        $check = mysqli_query($koneksi, "SELECT * FROM siswa WHERE nisn='$nisn'");
        if(mysqli_num_rows($check) > 0) {
             echo "<script>Swal.fire('Gagal', 'NISN sudah digunakan!', 'error');</script>";
        } else {
            $sql = "INSERT INTO siswa (nisn, nama_siswa, tempat_lahir, tanggal_lahir, jk, password, id_kelas) VALUES ('$nisn', '$nama_siswa', '$tempat_lahir', '$tanggal_lahir', '$jk', '$password', '$id_kelas')";
            if (mysqli_query($koneksi, $sql)) {
                log_activity('create', 'siswa', 'tambah siswa ' . $nisn);
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Data siswa berhasil ditambahkan',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'siswa.php?kelas=$id_kelas';
                    });
                </script>";
            } else {
                 echo "<script>Swal.fire('Gagal', 'Gagal menambah data: " . mysqli_error($koneksi) . "', 'error');</script>";
            }
        }
    } elseif (isset($_POST['edit'])) {
        $id_siswa = $_POST['id_siswa'];
        $nisn = mysqli_real_escape_string($koneksi, $_POST['nisn']);
        $nama_siswa = mysqli_real_escape_string($koneksi, $_POST['nama_siswa']);
        $tempat_lahir = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir']);
        $tanggal_lahir = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
        $jk = mysqli_real_escape_string($koneksi, $_POST['jk']);
        $id_kelas = $_POST['id_kelas'];
        
        $query_str = "UPDATE siswa SET nisn='$nisn', nama_siswa='$nama_siswa', tempat_lahir='$tempat_lahir', tanggal_lahir='$tanggal_lahir', jk='$jk', id_kelas='$id_kelas' WHERE id_siswa='$id_siswa'";
        
        if(!empty($_POST['password'])) {
            $password = mysqli_real_escape_string($koneksi, $_POST['password']);
            $query_str = "UPDATE siswa SET nisn='$nisn', nama_siswa='$nama_siswa', tempat_lahir='$tempat_lahir', tanggal_lahir='$tanggal_lahir', jk='$jk', id_kelas='$id_kelas', password='$password' WHERE id_siswa='$id_siswa'";
        }
        
        if (mysqli_query($koneksi, $query_str)) {
            log_activity('update', 'siswa', 'edit siswa ' . $nisn);
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data siswa berhasil diperbarui',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'siswa.php?kelas=$id_kelas';
                });
            </script>";
        } else {
            echo "<script>Swal.fire('Gagal', 'Gagal update data: " . mysqli_error($koneksi) . "', 'error');</script>";
        }
    } elseif (isset($_POST['import'])) {
        if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
            if (class_exists(SimpleXLSX::class)) {
                if ($xlsx = SimpleXLSX::parse($_FILES['file_excel']['tmp_name'])) {
                    $rows = $xlsx->rows();
                    $count = 0;
                    $err = 0;
                    // Assume Row 1 is header
                    foreach ($rows as $k => $r) {
                        if ($k === 0) continue; // Skip header
                        // Expected Format: No | NISN | Nama | Tempat Lahir | Tanggal Lahir (YYYY-MM-DD) | JK (L/P) | ID Kelas
                        
                        $nisn = isset($r[1]) ? mysqli_real_escape_string($koneksi, $r[1]) : '';
                        $nama = isset($r[2]) ? mysqli_real_escape_string($koneksi, $r[2]) : '';
                        $tpl = isset($r[3]) ? mysqli_real_escape_string($koneksi, $r[3]) : '';
                        // Fix Date Format (Convert d/m/Y or d-m-Y to Y-m-d)
                        $raw_tgl = isset($r[4]) ? $r[4] : '';
                        $tgl = '';
                        if (!empty($raw_tgl)) {
                            if (is_numeric($raw_tgl)) {
                                // Excel Serial Date
                                $unix_date = ($raw_tgl - 25569) * 86400;
                                $tgl = gmdate("Y-m-d", $unix_date);
                            } else {
                                // Try d/m/Y
                                $d = DateTime::createFromFormat('d/m/Y', $raw_tgl);
                                if ($d) {
                                    $tgl = $d->format('Y-m-d');
                                } else {
                                    // Try d-m-Y
                                    $d = DateTime::createFromFormat('d-m-Y', $raw_tgl);
                                    if ($d) {
                                        $tgl = $d->format('Y-m-d');
                                    } else {
                                        // Try Y-m-d
                                        $d = DateTime::createFromFormat('Y-m-d', $raw_tgl);
                                        if ($d) {
                                            $tgl = $d->format('Y-m-d');
                                        } else {
                                            // Fallback
                                            $ts = strtotime($raw_tgl);
                                            if ($ts) $tgl = date('Y-m-d', $ts);
                                        }
                                    }
                                }
                            }
                        }
                        $tgl = mysqli_real_escape_string($koneksi, $tgl); 
                        $jk = isset($r[5]) ? mysqli_real_escape_string($koneksi, $r[5]) : '';
                        $idk = isset($r[6]) ? mysqli_real_escape_string($koneksi, $r[6]) : '';
                        
                        // Basic validation
                        if(empty($nisn) || empty($nama) || empty($idk)) {
                            continue;
                        }

                        // Check duplicate
                        $check = mysqli_query($koneksi, "SELECT id_siswa FROM siswa WHERE nisn='$nisn'");
                        if(mysqli_num_rows($check) == 0) {
                            $pass = generateRandomPassword();
                            $sql = "INSERT INTO siswa (nisn, nama_siswa, tempat_lahir, tanggal_lahir, jk, password, id_kelas) VALUES ('$nisn', '$nama', '$tpl', '$tgl', '$jk', '$pass', '$idk')";
                            if(mysqli_query($koneksi, $sql)) $count++;
                            else $err++;
                        } else {
                            $err++; // Duplicate
                        }
                    }
                    $redirect_url = 'siswa.php';
                    if (isset($_GET['kelas'])) {
                        $redirect_url .= '?kelas=' . urlencode($_GET['kelas']);
                    }

                    log_activity('import', 'siswa', 'import siswa berhasil ' . $count . ', gagal ' . $err);
                    echo "<script>
                        Swal.fire({
                            title: 'Selesai',
                            text: 'Berhasil import $count data. Gagal/Duplikat/Skip: $err',
                            icon: 'info',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '$redirect_url';
                        });
                    </script>";
                } else {
                    echo "<script>Swal.fire('Error', 'Gagal parsing file Excel: " . SimpleXLSX::parseError() . "', 'error');</script>";
                }
            } else {
                echo "<script>Swal.fire('Error', 'Library SimpleXLSX tidak ditemukan.', 'error');</script>";
            }
        }
    }
    // Handle Reset All Password via POST
    if (isset($_POST['reset_all_password'])) {
        $id_kelas = $_POST['kelas'];
        
        // Get all students in class
        $q = mysqli_query($koneksi, "SELECT id_siswa FROM siswa WHERE id_kelas='$id_kelas'");
        $count = 0;
        while($row = mysqli_fetch_assoc($q)) {
            $new_pass = generateRandomPassword();
            $id = $row['id_siswa'];
            mysqli_query($koneksi, "UPDATE siswa SET password='$new_pass' WHERE id_siswa='$id'");
            $count++;
        }
        
        log_activity('update', 'siswa', 'reset semua password kelas ' . $id_kelas . ' total ' . $count);
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Password $count siswa berhasil direset',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'siswa.php?kelas=$id_kelas';
            });
        </script>";
    }
}

// Handle Reset Password via POST
if (isset($_POST['reset_password'])) {
    $id_siswa = $_POST['id_siswa'];
    $redirect_kelas = isset($_POST['kelas']) ? $_POST['kelas'] : '';
    
    $new_password = generateRandomPassword();
    $sql = "UPDATE siswa SET password='$new_password' WHERE id_siswa='$id_siswa'";
    
    if (mysqli_query($koneksi, $sql)) {
        log_activity('update', 'siswa', 'reset password siswa ' . $id_siswa);
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Password Direset',
                text: 'Password baru: $new_password',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'siswa.php?kelas=$redirect_kelas';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Error', 'Gagal reset password', 'error');</script>";
    }
}

// Handle Delete via GET
if (isset($_GET['delete'])) {
    $id_siswa = $_GET['delete'];
    $redirect_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
    mysqli_query($koneksi, "DELETE FROM siswa WHERE id_siswa='$id_siswa'");
    log_activity('delete', 'siswa', 'hapus siswa ' . $id_siswa);
    echo "<script>window.location.href = 'siswa.php?kelas=$redirect_kelas';</script>";
}

// Get Kelas Data for Dropdown
$kelas_query = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
$kelas_options = "";
while($k = mysqli_fetch_assoc($kelas_query)) {
    $selected = (isset($_GET['kelas']) && $_GET['kelas'] == $k['id_kelas']) ? 'selected' : '';
    $kelas_options .= "<option value='".$k['id_kelas']."' $selected>".$k['nama_kelas']."</option>";
}

$selected_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Data Siswa</h1>
    </div>

    <!-- Filter Kelas -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Filter Kelas</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <select class="form-select" name="kelas" onchange="this.form.submit()">
                            <option value="">-- Pilih Kelas --</option>
                            <?php echo $kelas_options; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selected_kelas): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-success">Daftar Siswa</h6>
                <div class="mt-2 mt-md-0 d-flex flex-wrap justify-content-md-end">
                    <button type="button" class="btn btn-danger btn-sm me-2 mb-2" onclick="confirmResetAllPassword('<?php echo $selected_kelas; ?>')">
                        <i class="fas fa-key"></i> Reset Semua Password
                    </button>
                    <a href="export_siswa_excel.php?kelas=<?php echo $selected_kelas; ?>" class="btn btn-success btn-sm me-2 mb-2">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a href="export_siswa_pdf.php?kelas=<?php echo $selected_kelas; ?>" target="_blank" class="btn btn-secondary btn-sm me-2 mb-2">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <button type="button" class="btn btn-primary btn-sm me-2 mb-2" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-file-excel"></i> Import Excel
                    </button>
                    <button type="button" class="btn btn-primary btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Tambah Siswa
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-datatable" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="5%">Foto</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>L/P</th>
                            <th>Tempat, Tanggal Lahir</th>
                            <th>Kelas</th>
                            <th>Password</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.id_kelas = '$selected_kelas' ORDER BY s.nama_siswa ASC");
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($query)) :
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <img src="<?php echo !empty($row['foto']) && file_exists('../../assets/img/siswa/'.$row['foto']) ? '../../assets/img/siswa/'.$row['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($row['nama_siswa']).'&size=40&background=random'; ?>" 
                                         class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                </td>
                                <td><?php echo $row['nisn']; ?></td>
                                <td><?php echo $row['nama_siswa']; ?></td>
                                <td><?php echo $row['jk']; ?></td>
                                <td><?php echo $row['tempat_lahir'] . ', ' . date('d-m-Y', strtotime($row['tanggal_lahir'])); ?></td>
                                <td><?php echo $row['nama_kelas']; ?></td>
                                <td>
                                    <?php 
                                    if (strlen($row['password']) == 60 && substr($row['password'], 0, 4) === '$2y$') {
                                        echo '<span class="badge bg-secondary" title="Password ter-enkripsi, silakan reset">Ter-enkripsi</span>';
                                    } else {
                                        echo $row['password'];
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                        data-id="<?php echo $row['id_siswa']; ?>" 
                                        data-nisn="<?php echo $row['nisn']; ?>"
                                        data-nama="<?php echo $row['nama_siswa']; ?>"
                                        data-tempat="<?php echo $row['tempat_lahir']; ?>"
                                        data-tanggal="<?php echo $row['tanggal_lahir']; ?>"
                                        data-jk="<?php echo $row['jk']; ?>"
                                        data-kelas="<?php echo $row['id_kelas']; ?>"
                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                        title="Edit Siswa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm text-white" onclick="confirmResetPassword('<?php echo $row['id_siswa']; ?>', '<?php echo $selected_kelas; ?>')" title="Reset Password (Acak)">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('siswa.php?delete=<?php echo $row['id_siswa']; ?>&kelas=<?php echo $selected_kelas; ?>')" title="Hapus Siswa">
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
    <?php else: ?>
        <div class="card bg-success bg-opacity-10 border border-success text-success p-3 rounded text-center">
            <i class="fas fa-info-circle me-1"></i> Silakan pilih kelas terlebih dahulu untuk menampilkan data siswa.
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModalLabel">Tambah Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">NISN</label>
                        <input type="text" class="form-control" name="nisn" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Siswa</label>
                        <input type="text" class="form-control" name="nama_siswa" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" class="form-control" name="tempat_lahir">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="tanggal_lahir">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select class="form-select" name="jk" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <select class="form-select" name="id_kelas" required>
                            <option value="">Pilih Kelas</option>
                            <?php 
                            // Reset pointer
                            mysqli_data_seek($kelas_query, 0);
                            while($k = mysqli_fetch_assoc($kelas_query)) {
                                echo "<option value='".$k['id_kelas']."'>".$k['nama_kelas']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Kosongkan = default (NISN)">
                        <small class="text-muted">Default password adalah NISN jika dikosongkan.</small>
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
                <h5 class="modal-title" id="editModalLabel">Edit Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_siswa" id="edit_id_siswa">
                    <div class="mb-3">
                        <label class="form-label">NISN</label>
                        <input type="text" class="form-control" name="nisn" id="edit_nisn" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Siswa</label>
                        <input type="text" class="form-control" name="nama_siswa" id="edit_nama_siswa" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" class="form-control" name="tempat_lahir" id="edit_tempat_lahir">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="tanggal_lahir" id="edit_tanggal_lahir">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select class="form-select" name="jk" id="edit_jk" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <select class="form-select" name="id_kelas" id="edit_id_kelas" required>
                            <option value="">Pilih Kelas</option>
                            <?php 
                            mysqli_data_seek($kelas_query, 0);
                            while($k = mysqli_fetch_assoc($kelas_query)) {
                                echo "<option value='".$k['id_kelas']."'>".$k['nama_kelas']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (Kosongkan jika tidak diubah)</label>
                        <input type="password" class="form-control" name="password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit" class="btn btn-success">Update</button>
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
                <h5 class="modal-title" id="importModalLabel">Import Data Siswa (Excel)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih File Excel (.xlsx)</label>
                        <input type="file" class="form-control" name="file_excel" accept=".xlsx" required>
                    </div>
                    <div class="card bg-warning bg-opacity-10 border border-warning text-warning p-3 rounded mb-3 small">
                            <i class="fas fa-exclamation-triangle me-1"></i> <strong>Format Kolom Excel (Baris 1 Header):</strong><br>
                            No | NISN | Nama Siswa | Tempat Lahir | Tanggal Lahir (YYYY-MM-DD) | JK (L/P) | ID Kelas
                        </div>
                    <div class="mb-3">
                         <a href="download_template.php<?php echo $selected_kelas ? '?kelas='.$selected_kelas : ''; ?>" class="btn btn-outline-success btn-sm w-100">
                            <i class="fas fa-download"></i> Download Template Excel <?php echo $selected_kelas ? '(Sesuai Kelas)' : ''; ?>
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="import" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
    // Handle Edit Button Click
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        var nisn = $(this).data('nisn');
        var nama = $(this).data('nama');
        var tempat = $(this).data('tempat');
        var tanggal = $(this).data('tanggal');
        var jk = $(this).data('jk');
        var kelas = $(this).data('kelas');
        
        $('#edit_id_siswa').val(id);
        $('#edit_nisn').val(nisn);
        $('#edit_nama_siswa').val(nama);
        $('#edit_tempat_lahir').val(tempat);
        $('#edit_tanggal_lahir').val(tanggal);
        $('#edit_jk').val(jk);
        $('#edit_id_kelas').val(kelas);
    });

    // Handle Reset Password Confirmation
    function confirmResetPassword(id, kelas) {
        Swal.fire({
            title: 'Reset Password?',
            text: "Password akan diubah menjadi kode acak baru!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                var inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'id_siswa';
                inputId.value = id;
                form.appendChild(inputId);

                var inputKelas = document.createElement('input');
                inputKelas.type = 'hidden';
                inputKelas.name = 'kelas';
                inputKelas.value = kelas;
                form.appendChild(inputKelas);

                var inputReset = document.createElement('input');
                inputReset.type = 'hidden';
                inputReset.name = 'reset_password';
                inputReset.value = '1';
                form.appendChild(inputReset);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Handle Reset ALL Password Confirmation
    function confirmResetAllPassword(kelas) {
        Swal.fire({
            title: 'Reset SEMUA Password?',
            text: "Semua siswa di kelas ini akan mendapatkan password baru!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#198754',
            confirmButtonText: 'Ya, Reset Semua!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                var inputKelas = document.createElement('input');
                inputKelas.type = 'hidden';
                inputKelas.name = 'kelas';
                inputKelas.value = kelas;
                form.appendChild(inputKelas);

                var inputReset = document.createElement('input');
                inputReset.type = 'hidden';
                inputReset.name = 'reset_all_password';
                inputReset.value = '1';
                form.appendChild(inputReset);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
