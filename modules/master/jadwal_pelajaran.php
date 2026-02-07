<?php
include '../../config/database.php';
$page_title = 'Jadwal Pelajaran';
include '../../includes/header.php';

// Check/Create Table
$check_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'jadwal_pelajaran'");
if (mysqli_num_rows($check_table) == 0) {
    $create_sql = "CREATE TABLE jadwal_pelajaran (
        id_jadwal INT(11) AUTO_INCREMENT PRIMARY KEY,
        hari VARCHAR(20) NOT NULL,
        id_kelas INT(11) NOT NULL,
        id_guru INT(11) NOT NULL,
        mapel_ids TEXT NOT NULL
    )";
    mysqli_query($koneksi, $create_sql);
} else {
    // Check if id_kelas column exists (migration for existing table)
    $check_col = mysqli_query($koneksi, "SHOW COLUMNS FROM jadwal_pelajaran LIKE 'id_kelas'");
    if (mysqli_num_rows($check_col) == 0) {
        mysqli_query($koneksi, "ALTER TABLE jadwal_pelajaran ADD COLUMN id_kelas INT(11) NOT NULL AFTER hari");
    }
}

// Fetch Classes
$kelas_data = [];
$q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
while ($k = mysqli_fetch_assoc($q_kelas)) {
    $kelas_data[] = $k;
}

// Handle Add
if (isset($_POST['add'])) {
    $hari = mysqli_real_escape_string($koneksi, $_POST['hari']);
    $id_kelas = (int)$_POST['id_kelas'];
    $id_guru = (int)$_POST['id_guru'];
    $mapel_ids = isset($_POST['mapel_ids']) ? implode(',', $_POST['mapel_ids']) : '';

    if (empty($mapel_ids)) {
         echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Mata pelajaran harus dipilih!',
            });
        </script>";
    } else {
        $query = "INSERT INTO jadwal_pelajaran (hari, id_kelas, id_guru, mapel_ids) VALUES ('$hari', '$id_kelas', '$id_guru', '$mapel_ids')";
        if (mysqli_query($koneksi, $query)) {
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Jadwal pelajaran berhasil ditambahkan',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'jadwal_pelajaran.php?kelas=' + $id_kelas;
                });
            </script>";
        } else {
             echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
        }
    }
}

// Handle Edit
if (isset($_POST['edit'])) {
    $id_jadwal = $_POST['id_jadwal'];
    $hari = mysqli_real_escape_string($koneksi, $_POST['hari']);
    $id_kelas = (int)$_POST['id_kelas']; // Preserve class
    $id_guru = (int)$_POST['id_guru'];
    $mapel_ids = isset($_POST['mapel_ids']) ? implode(',', $_POST['mapel_ids']) : '';

    if (empty($mapel_ids)) {
         echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Mata pelajaran harus dipilih!',
            });
        </script>";
    } else {
        $query = "UPDATE jadwal_pelajaran SET hari='$hari', id_guru='$id_guru', mapel_ids='$mapel_ids' WHERE id_jadwal='$id_jadwal'";
        if (mysqli_query($koneksi, $query)) {
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Jadwal pelajaran berhasil diperbarui',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'jadwal_pelajaran.php?kelas=' + $id_kelas;
                });
            </script>";
        } else {
             echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id_jadwal = $_GET['delete'];
    
    mysqli_query($koneksi, "DELETE FROM jadwal_pelajaran WHERE id_jadwal='$id_jadwal'");
    echo "<script>
        window.location.href = 'jadwal_pelajaran.php';
    </script>";
}

// Fetch Data for Dropdowns
$guru_data = [];
$q_guru = mysqli_query($koneksi, "SELECT id_user, nama_lengkap FROM users WHERE level='guru' ORDER BY nama_lengkap ASC");
while ($g = mysqli_fetch_assoc($q_guru)) {
    $guru_data[] = $g;
}

$mapel_data = [];
$q_mapel = mysqli_query($koneksi, "SELECT id_mapel, nama_mapel FROM mapel ORDER BY nama_mapel ASC");
while ($m = mysqli_fetch_assoc($q_mapel)) {
    $mapel_data[] = $m;
}

// Map Mapel ID to Name
$mapel_map = [];
foreach ($mapel_data as $m) {
    $mapel_map[$m['id_mapel']] = $m['nama_mapel'];
}

// Fetch ALL Schedules and Group by Class
$schedules_by_class = [];
$query = "SELECT j.*, u.nama_lengkap 
          FROM jadwal_pelajaran j 
          LEFT JOIN users u ON j.id_guru = u.id_user 
          ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), u.nama_lengkap ASC";
$result = mysqli_query($koneksi, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $schedules_by_class[$row['id_kelas']][] = $row;
}
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-success">Jadwal Pelajaran</h6>
        </div>
        <div class="card-body">
            <?php if(!empty($kelas_data)): 
                // Determine active class
                $active_kelas_id = isset($_GET['kelas']) ? (int)$_GET['kelas'] : (isset($kelas_data[0]['id_kelas']) ? $kelas_data[0]['id_kelas'] : 0);
            ?>
                <!-- Class Tabs -->
                <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                    <?php foreach($kelas_data as $index => $k): 
                        $count = isset($schedules_by_class[$k['id_kelas']]) ? count($schedules_by_class[$k['id_kelas']]) : 0;
                        $is_active = ($k['id_kelas'] == $active_kelas_id);
                    ?>
                    <li class="nav-item me-2" role="presentation">
                        <button class="nav-link <?php echo $is_active ? 'active' : ''; ?> d-flex align-items-center gap-2" 
                            id="pills-<?php echo $k['id_kelas']; ?>-tab" 
                            data-bs-toggle="pill" 
                            data-bs-target="#pills-<?php echo $k['id_kelas']; ?>" 
                            type="button" 
                            role="tab" 
                            aria-controls="pills-<?php echo $k['id_kelas']; ?>" 
                            aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <?php echo $k['nama_kelas']; ?>
                            <span class="badge bg-white text-success rounded-pill ms-2"><?php echo $count; ?></span>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="pills-tabContent">
                    <?php foreach($kelas_data as $index => $k): 
                        $class_schedules = isset($schedules_by_class[$k['id_kelas']]) ? $schedules_by_class[$k['id_kelas']] : [];
                        $is_active = ($k['id_kelas'] == $active_kelas_id);
                    ?>
                    <div class="tab-pane fade <?php echo $is_active ? 'show active' : ''; ?>" 
                         id="pills-<?php echo $k['id_kelas']; ?>" 
                         role="tabpanel" 
                         aria-labelledby="pills-<?php echo $k['id_kelas']; ?>-tab">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="m-0 font-weight-bold text-gray-800">Jadwal <?php echo $k['nama_kelas']; ?></h6>
                            <button type="button" class="btn btn-primary btn-sm" onclick="openAddModal(<?php echo $k['id_kelas']; ?>, '<?php echo $k['nama_kelas']; ?>')">
                                <i class="fas fa-plus"></i> Tambah Jadwal
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped table-datatable" width="100%" cellspacing="0">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Hari</th>
                                        <th>Nama Guru</th>
                                        <th>Mata Pelajaran</th>
                                        <th width="15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    foreach ($class_schedules as $row) {
                                        $m_ids = explode(',', $row['mapel_ids']);
                                        $m_names = [];
                                        foreach ($m_ids as $mid) {
                                            if (isset($mapel_map[$mid])) {
                                                $m_names[] = $mapel_map[$mid];
                                            }
                                        }
                                        $mapel_list = implode(', ', $m_names);
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $row['hari']; ?></td>
                                        <td><?php echo $row['nama_lengkap']; ?></td>
                                        <td>
                                            <?php foreach($m_names as $mn): ?>
                                                <span class="badge bg-primary text-white me-1 mb-1"><?php echo $mn; ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning text-white me-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal<?php echo $row['id_jadwal']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="javascript:void(0);" onclick="confirmDelete('jadwal_pelajaran.php?delete=<?php echo $row['id_jadwal']; ?>')" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </a>

                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?php echo $row['id_jadwal']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-warning text-white">
                                                            <h5 class="modal-title">Edit Jadwal</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id_jadwal" value="<?php echo $row['id_jadwal']; ?>">
                                                                <input type="hidden" name="id_kelas" value="<?php echo $k['id_kelas']; ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Hari</label>
                                                                    <select class="form-select" name="hari" required>
                                                                        <?php
                                                                        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                                                                        foreach ($days as $day) {
                                                                            $selected = ($row['hari'] == $day) ? 'selected' : '';
                                                                            echo "<option value='$day' $selected>$day</option>";
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nama Guru</label>
                                                                    <select class="form-select select2-guru-edit" name="id_guru" required style="width: 100%;">
                                                                        <option value="">Pilih Guru</option>
                                                                        <?php foreach ($guru_data as $g): ?>
                                                                            <option value="<?php echo $g['id_user']; ?>" <?php echo ($row['id_guru'] == $g['id_user']) ? 'selected' : ''; ?>>
                                                                                <?php echo $g['nama_lengkap']; ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Mata Pelajaran</label>
                                                                    <select class="form-select select2-mapel-edit" name="mapel_ids[]" multiple required style="width: 100%;">
                                                                        <?php 
                                                                        $current_mapels = explode(',', $row['mapel_ids']);
                                                                        foreach ($mapel_data as $m): 
                                                                            $selected = in_array($m['id_mapel'], $current_mapels) ? 'selected' : '';
                                                                        ?>
                                                                            <option value="<?php echo $m['id_mapel']; ?>" <?php echo $selected; ?>>
                                                                                <?php echo $m['nama_mapel']; ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" name="edit" class="btn btn-warning text-white">Simpan Perubahan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card bg-success bg-opacity-10 border border-success text-success p-3 rounded text-center">
                    <i class="fas fa-info-circle me-1"></i> Belum ada data kelas. Silakan tambahkan data kelas di menu Master Data.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Modal (Generic) -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addModalTitle">Tambah Jadwal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_kelas" id="addModalIdKelas">
                    <div class="mb-3">
                        <label class="form-label">Hari</label>
                        <select class="form-select" name="hari" required>
                            <option value="">Pilih Hari</option>
                            <?php
                            $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                            foreach ($days as $day) {
                                echo "<option value='$day'>$day</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Guru</label>
                        <select class="form-select" id="select2-guru-add" name="id_guru" required style="width: 100%;">
                            <option value="">Pilih Guru</option>
                            <?php foreach ($guru_data as $g): ?>
                                <option value="<?php echo $g['id_user']; ?>">
                                    <?php echo $g['nama_lengkap']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mata Pelajaran</label>
                        <select class="form-select" id="select2-mapel-add" name="mapel_ids[]" multiple required style="width: 100%;">
                            <?php foreach ($mapel_data as $m): ?>
                                <option value="<?php echo $m['id_mapel']; ?>">
                                    <?php echo $m['nama_mapel']; ?>
                                </option>
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

<?php include '../../includes/footer.php'; ?>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
function openAddModal(idKelas, namaKelas) {
    $('#addModalIdKelas').val(idKelas);
    $('#addModalTitle').text('Tambah Jadwal - ' + namaKelas);
    var modal = new bootstrap.Modal(document.getElementById('addModal'));
    modal.show();
}

$(document).ready(function() {
    // Initialize Select2 for Add Modal
    $('#select2-guru-add').select2({
        theme: "bootstrap-5",
        dropdownParent: $('#addModal')
    });
    $('#select2-mapel-add').select2({
        theme: "bootstrap-5",
        dropdownParent: $('#addModal'),
        placeholder: "Pilih Mata Pelajaran"
    });

    // Initialize Select2 for Edit Modals
    // Re-initialize when modal is shown to ensure correct width
    $('.modal').on('shown.bs.modal', function () {
        $(this).find('.select2-guru-edit').select2({
            theme: "bootstrap-5",
            dropdownParent: $(this)
        });
        $(this).find('.select2-mapel-edit').select2({
            theme: "bootstrap-5",
            dropdownParent: $(this),
            placeholder: "Pilih Mata Pelajaran"
        });
    });
});
</script>
