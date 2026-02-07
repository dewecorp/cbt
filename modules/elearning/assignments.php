<?php
session_start();
include '../../config/database.php';
$page_title = 'Tugas';
if (!isset($_SESSION['level'])) { $_SESSION['level'] = 'admin'; }
$level = $_SESSION['level'];
$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$existsA = mysqli_query($koneksi, "SHOW TABLES LIKE 'assignments'");
if (mysqli_num_rows($existsA) == 0) {
    mysqli_query($koneksi, "CREATE TABLE `assignments` (
        `id_assignment` int(11) NOT NULL AUTO_INCREMENT,
        `course_id` int(11) NOT NULL,
        `jenis_tugas` varchar(50) DEFAULT 'Lain-lain',
        `judul` varchar(200) NOT NULL,
        `deskripsi` text,
        `deadline` datetime NOT NULL,
        `created_by` int(11) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_assignment`)
    )");
}
$existsS = mysqli_query($koneksi, "SHOW TABLES LIKE 'submissions'");
if (mysqli_num_rows($existsS) == 0) {
    mysqli_query($koneksi, "CREATE TABLE `submissions` (
        `id_submission` int(11) NOT NULL AUTO_INCREMENT,
        `assignment_id` int(11) NOT NULL,
        `siswa_id` int(11) NOT NULL,
        `file_path` varchar(255) DEFAULT NULL,
        `nilai` decimal(5,2) DEFAULT 0,
        `catatan` text,
        `submitted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_submission`)
    )");
}
if (!is_dir(dirname(__DIR__,2).'/assets/uploads/assignments')) {
    @mkdir(dirname(__DIR__,2).'/assets/uploads/assignments', 0777, true);
}
// Delete Assignment
if (isset($_GET['delete']) && ($level === 'admin' || $level === 'guru')) {
    $id_del = (int)$_GET['delete'];
    // Check ownership if guru
    $check = mysqli_query($koneksi, "SELECT created_by FROM assignments WHERE id_assignment=$id_del");
    if ($row = mysqli_fetch_assoc($check)) {
        if ($level === 'admin' || $row['created_by'] == $uid) {
            mysqli_query($koneksi, "DELETE FROM assignments WHERE id_assignment=$id_del");
            // Also delete submissions
            mysqli_query($koneksi, "DELETE FROM submissions WHERE assignment_id=$id_del");
            header("Location: assignments.php?status=deleted");
            exit;
        }
    }
}

// Create Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $course_id = (int)$_POST['course_id'];
    $jenis_tugas = mysqli_real_escape_string($koneksi, $_POST['jenis_tugas']);
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $deadline = mysqli_real_escape_string($koneksi, $_POST['deadline']);
    if ($course_id>0 && !empty($judul) && !empty($deadline)) {
        mysqli_query($koneksi, "INSERT INTO assignments(course_id,jenis_tugas,judul,deskripsi,deadline,created_by) VALUES($course_id,'$jenis_tugas','$judul','$deskripsi','$deadline',$uid)");
        header("Location: assignments.php?status=created");
        exit;
    }
}

// Edit Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_assignment'])) {
    $id_assignment = (int)$_POST['id_assignment'];
    $course_id = (int)$_POST['course_id'];
    $jenis_tugas = mysqli_real_escape_string($koneksi, $_POST['jenis_tugas']);
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $deadline = mysqli_real_escape_string($koneksi, $_POST['deadline']);
    
    // Check ownership if guru
    $check = mysqli_query($koneksi, "SELECT created_by FROM assignments WHERE id_assignment=$id_assignment");
    if ($row = mysqli_fetch_assoc($check)) {
        if ($level === 'admin' || $row['created_by'] == $uid) {
             if ($course_id>0 && !empty($judul) && !empty($deadline)) {
                mysqli_query($koneksi, "UPDATE assignments SET course_id=$course_id, jenis_tugas='$jenis_tugas', judul='$judul', deskripsi='$deskripsi', deadline='$deadline' WHERE id_assignment=$id_assignment");
                header("Location: assignments.php?status=updated");
                exit;
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    $assignment_id = (int)$_POST['assignment_id'];
    $siswa_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $path = '';
    if (isset($_FILES['file']['name']) && $_FILES['file']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','ppt','pptx','zip'];
        if (in_array($ext, $allowed)) {
            $fname = time().'_'.preg_replace('/[^a-zA-Z0-9\.\-_]/','', $_FILES['file']['name']);
            $dest = dirname(__DIR__,2).'/assets/uploads/assignments/'.$fname;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                $path = 'assets/uploads/assignments/'.$fname;
            }
        }
    }
    if ($assignment_id>0 && $siswa_id>0 && !empty($path)) {
        mysqli_query($koneksi, "INSERT INTO submissions(assignment_id,siswa_id,file_path) VALUES($assignment_id,$siswa_id,'$path')");
    }
}
include '../../includes/header.php';
$filterCourse = "";
if ($level === 'guru') { $filterCourse = " WHERE c.pengampu=".$uid; }
$courses = mysqli_query($koneksi, "SELECT c.id_course, c.nama_course, k.nama_kelas, m.nama_mapel FROM courses c JOIN kelas k ON c.id_kelas=k.id_kelas JOIN mapel m ON c.id_mapel=m.id_mapel $filterCourse ORDER BY c.nama_course ASC");
$assign_filter = "";
if ($level === 'siswa') {
    $id_kelas = isset($_SESSION['id_kelas']) ? $_SESSION['id_kelas'] : 0;
    $assign_filter = " WHERE c.id_kelas=".$id_kelas;
}
$assignments = mysqli_query($koneksi, "SELECT a.*, c.nama_course, k.nama_kelas, k.id_kelas, u.nama_lengkap FROM assignments a JOIN courses c ON a.course_id=c.id_course JOIN kelas k ON c.id_kelas=k.id_kelas JOIN users u ON a.created_by=u.id_user $assign_filter ORDER BY a.created_at DESC");

// Admin specific: Fetch classes and group assignments
$kelas_arr = [];
$admin_assignments = [];
if ($level === 'admin') {
    $q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
    if ($q_kelas) {
        while($k = mysqli_fetch_assoc($q_kelas)) {
            $kelas_arr[] = $k;
        }
    }
    
    // Group assignments by class
    while($a = mysqli_fetch_assoc($assignments)) {
        $admin_assignments[$a['id_kelas']][] = $a;
    }
    // Reset pointer for other views
    mysqli_data_seek($assignments, 0);
}

function getAssignmentBadgeClass($type) {
    switch ($type) {
        case 'CBT': return 'bg-primary';
        case 'Merangkum': return 'bg-info text-dark';
        case 'Observasi': return 'bg-warning text-dark';
        case 'Praktik': return 'bg-danger';
        case 'Proyek': return 'bg-secondary';
        case 'Latihan Soal': return 'bg-dark';
        default: return 'bg-success';
    }
}
?>
<style>
    /* Custom Green Pills */
    #pills-tab .nav-link.active, #pills-tab .show > .nav-link {
        color: #fff;
        background-color: #198754;
    }
    #pills-tab .nav-link {
        color: #198754;
    }
    #pills-tab .nav-link:hover {
        color: #146c43;
    }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">Tugas Pembelajaran</h6>
                    <div>
                        <?php if($level === 'guru'): ?>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalAssignment"><i class="fas fa-plus-circle"></i> Buat Tugas</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($level === 'admin'): ?>
                        <?php if (!empty($kelas_arr)): ?>
                            <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                                <?php foreach($kelas_arr as $index => $k): 
                                    $count = isset($admin_assignments[$k['id_kelas']]) ? count($admin_assignments[$k['id_kelas']]) : 0;
                                ?>
                                    <li class="nav-item me-2" role="presentation">
                                        <button class="nav-link <?php echo ($index === 0) ? 'active' : ''; ?> d-flex align-items-center gap-2" 
                                            id="pills-<?php echo $k['id_kelas']; ?>-tab" 
                                            data-bs-toggle="pill" 
                                            data-bs-target="#pills-<?php echo $k['id_kelas']; ?>" 
                                            type="button" 
                                            role="tab" 
                                            aria-controls="pills-<?php echo $k['id_kelas']; ?>" 
                                            aria-selected="<?php echo ($index === 0) ? 'true' : 'false'; ?>">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                            <?php echo htmlspecialchars($k['nama_kelas']); ?>
                                            <span class="badge bg-white text-success rounded-pill ms-2"><?php echo $count; ?></span>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="tab-content" id="pills-tabContent">
                                <?php foreach($kelas_arr as $index => $k): ?>
                                    <div class="tab-pane fade <?php echo ($index === 0) ? 'show active' : ''; ?>" id="pills-<?php echo $k['id_kelas']; ?>" role="tabpanel" aria-labelledby="pills-<?php echo $k['id_kelas']; ?>-tab">
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered" width="100%" cellspacing="0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Kelas</th>
                                                        <th>Kelas Online</th>
                                                        <th>Jenis</th>
                                                        <th>Judul</th>
                                                        <th>Tenggat</th>
                                                        <th>Guru</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if(isset($admin_assignments[$k['id_kelas']]) && count($admin_assignments[$k['id_kelas']]) > 0): ?>
                                                        <?php $no=1; foreach($admin_assignments[$k['id_kelas']] as $a): ?>
                                                        <tr>
                                                            <td><?php echo $no++; ?></td>
                                                            <td><?php echo htmlspecialchars($a['nama_kelas']); ?></td>
                                                            <td><?php echo htmlspecialchars($a['nama_course']); ?></td>
                                                            <td><span class="badge <?php echo getAssignmentBadgeClass($a['jenis_tugas']); ?>"><?php echo htmlspecialchars($a['jenis_tugas']); ?></span></td>   
                                                            <td><?php echo htmlspecialchars($a['judul']); ?></td>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($a['deadline'])); ?></td>
                                                            <td><?php echo htmlspecialchars($a['nama_lengkap']); ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-info-circle me-1"></i> Tidak ada tugas untuk kelas ini.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-folder-open fa-3x mb-3"></i>
                                <p>Belum ada data kelas.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-datatable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kelas</th>
                                    <th>Kelas Online</th>
                                    <th>Jenis</th>
                                    <th>Judul</th>
                                    <th>Tenggat</th>
                                    <th>Guru</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; while($a = mysqli_fetch_assoc($assignments)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($a['nama_kelas']); ?></td>
                                    <td><?php echo htmlspecialchars($a['nama_course']); ?></td>
                                    <td><span class="badge <?php echo getAssignmentBadgeClass($a['jenis_tugas']); ?>"><?php echo htmlspecialchars($a['jenis_tugas']); ?></span></td>
                                    <td><?php echo htmlspecialchars($a['judul']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($a['deadline'])); ?></td>
                                    <td><?php echo htmlspecialchars($a['nama_lengkap']); ?></td>
                                    <td>
                                        <?php if($level === 'siswa'): ?>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSubmit<?php echo $a['id_assignment']; ?>"><i class="fas fa-upload"></i> Unggah</button>
                                        <?php endif; ?>
                                        <?php if($level === 'guru' && $a['created_by'] == $uid): ?>
                                        <button class="btn btn-warning btn-sm" onclick="editAssignment(<?php echo htmlspecialchars(json_encode($a)); ?>)"><i class="fas fa-edit"></i></button>
                                        <a href="#" class="btn btn-danger btn-sm" onclick="confirmDeleteAssignment('assignments.php?delete=<?php echo $a['id_assignment']; ?>'); return false;"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if($level === 'siswa'): 
                        mysqli_data_seek($assignments, 0);
                        while($a = mysqli_fetch_assoc($assignments)): ?>
                    <div class="modal fade" id="modalSubmit<?php echo $a['id_assignment']; ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog">
                        <form method="post" enctype="multipart/form-data" class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Unggah Jawaban: <?php echo htmlspecialchars($a['judul']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <input type="hidden" name="assignment_id" value="<?php echo $a['id_assignment']; ?>">
                            <div class="mb-2">
                                <label class="form-label">File</label>
                                <input type="file" name="file" class="form-control" required>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="submit_assignment" value="1" class="btn btn-primary">Kirim</button>
                          </div>
                        </form>
                      </div>
                    </div>
                    <?php endwhile; endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if($level === 'guru'): ?>
<div class="modal fade" id="modalAssignment" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Buat Tugas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
            <label class="form-label">Kelas Online</label>
            <select name="course_id" class="form-select" required>
                <option value="">Pilih</option>
                <?php while($c = mysqli_fetch_assoc($courses)): ?>
                    <option value="<?php echo $c['id_course']; ?>"><?php echo $c['nama_kelas'].' - '.$c['nama_mapel'].' - '.$c['nama_course']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Jenis Tugas</label>
            <select name="jenis_tugas" class="form-select" required>
                <option value="Lain-lain">Lain-lain</option>
                <option value="CBT">CBT</option>
                <option value="Merangkum">Merangkum</option>
                <option value="Observasi">Observasi</option>
                <option value="Praktik">Praktik</option>
                <option value="Proyek">Proyek</option>
                <option value="Latihan Soal">Latihan Soal</option>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Judul</label>
            <input type="text" name="judul" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="4"></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label">Tenggat</label>
            <input type="datetime-local" name="deadline" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="create_assignment" value="1" class="btn btn-warning">Simpan</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalEditAssignment" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Tugas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id_assignment" id="edit_id_assignment">
        <div class="mb-2">
            <label class="form-label">Kelas Online</label>
            <select name="course_id" id="edit_course_id" class="form-select" required>
                <option value="">Pilih</option>
                <?php 
                mysqli_data_seek($courses, 0);
                while($c = mysqli_fetch_assoc($courses)): ?>
                    <option value="<?php echo $c['id_course']; ?>"><?php echo $c['nama_kelas'].' - '.$c['nama_mapel'].' - '.$c['nama_course']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Jenis Tugas</label>
            <select name="jenis_tugas" id="edit_jenis_tugas" class="form-select" required>
                <option value="Lain-lain">Lain-lain</option>
                <option value="CBT">CBT</option>
                <option value="Merangkum">Merangkum</option>
                <option value="Observasi">Observasi</option>
                <option value="Praktik">Praktik</option>
                <option value="Proyek">Proyek</option>
                <option value="Latihan Soal">Latihan Soal</option>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Judul</label>
            <input type="text" name="judul" id="edit_judul" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="4"></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label">Tenggat</label>
            <input type="datetime-local" name="deadline" id="edit_deadline" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="edit_assignment" value="1" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
function editAssignment(data) {
    document.getElementById('edit_id_assignment').value = data.id_assignment;
    document.getElementById('edit_course_id').value = data.course_id;
    document.getElementById('edit_jenis_tugas').value = data.jenis_tugas;
    document.getElementById('edit_judul').value = data.judul;
    document.getElementById('edit_deskripsi').value = data.deskripsi;
    document.getElementById('edit_deadline').value = data.deadline.replace(' ', 'T');
    
    var myModal = new bootstrap.Modal(document.getElementById('modalEditAssignment'));
    myModal.show();
}

function confirmDeleteAssignment(url) {
    Swal.fire({
        title: 'Hapus Tugas?',
        text: "Tugas yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    
    if (status === 'deleted') {
        Swal.fire({
            title: 'Berhasil!',
            text: 'Tugas berhasil dihapus.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        window.history.replaceState(null, null, window.location.pathname);
    } else if (status === 'created') {
        Swal.fire({
            title: 'Berhasil!',
            text: 'Tugas berhasil dibuat.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        window.history.replaceState(null, null, window.location.pathname);
    } else if (status === 'updated') {
        Swal.fire({
            title: 'Berhasil!',
            text: 'Tugas berhasil diperbarui.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        window.history.replaceState(null, null, window.location.pathname);
    }
});
</script>
<?php endif; ?>
<?php include '../../includes/footer.php'; ?>
