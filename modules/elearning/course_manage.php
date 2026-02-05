<?php
include '../../config/database.php';
$page_title = 'Kelola Kelas Online';
if (!isset($_SESSION['level'])) { $_SESSION['level'] = 'admin'; }
$level = $_SESSION['level'];
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Ensure table exists
$exists = mysqli_query($koneksi, "SHOW TABLES LIKE 'course_students'");
if (mysqli_num_rows($exists) == 0) {
    mysqli_query($koneksi, "CREATE TABLE `course_students` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `course_id` int(11) NOT NULL,
        `siswa_id` int(11) NOT NULL,
        `added_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    )");
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$course = null;

if ($course_id > 0) {
    $qc = mysqli_query($koneksi, "
        SELECT c.*, k.nama_kelas, m.nama_mapel, u.nama_lengkap AS nama_guru 
        FROM courses c 
        JOIN kelas k ON c.id_kelas=k.id_kelas 
        JOIN mapel m ON c.id_mapel=m.id_mapel 
        JOIN users u ON c.pengampu=u.id_user 
        WHERE c.id_course=".$course_id
    );
    if ($qc && mysqli_num_rows($qc) > 0) {
        $course = mysqli_fetch_assoc($qc);
        if ($level === 'guru' && (int)$course['pengampu'] !== (int)$uid) {
            $course = null;
        }
    }
}

if (!$course) {
    include '../../includes/header.php';
    echo '<div class="container-fluid"><div class="alert alert-danger">Kelas Online tidak ditemukan atau Anda tidak memiliki akses.</div></div>';
    include '../../includes/footer.php';
    exit;
}

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $sid = (int)$_POST['siswa_id'];
    if ($sid > 0) {
        $existsRow = mysqli_query($koneksi, "SELECT id FROM course_students WHERE course_id=".$course_id." AND siswa_id=".$sid);
        if ($existsRow && mysqli_num_rows($existsRow) == 0) {
            mysqli_query($koneksi, "INSERT INTO course_students(course_id,siswa_id) VALUES(".$course_id.",".$sid.")");
        }
    }
    // Redirect to prevent resubmission
    header("Location: course_manage.php?course_id=".$course_id."&tab=siswa");
    exit;
}

// Handle Add All
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_all'])) {
    $sql = "
        INSERT INTO course_students(course_id, siswa_id)
        SELECT ".$course_id.", s.id_siswa
        FROM siswa s
        WHERE s.id_kelas=".$course['id_kelas']." AND s.status='aktif'
        AND NOT EXISTS (
            SELECT 1 FROM course_students cs 
            WHERE cs.course_id=".$course_id." AND cs.siswa_id=s.id_siswa
        )
    ";
    mysqli_query($koneksi, $sql);
    header("Location: course_manage.php?course_id=".$course_id."&tab=siswa");
    exit;
}

// Handle Remove Student
if (isset($_GET['remove_id'])) {
    $rid = (int)$_GET['remove_id'];
    if ($rid > 0) {
        mysqli_query($koneksi, "DELETE FROM course_students WHERE id=".$rid." AND course_id=".$course_id);
        header("Location: course_manage.php?course_id=".$course_id."&tab=siswa");
        exit;
    }
}

include '../../includes/header.php';

// Fetch Data
$enrolled = mysqli_query($koneksi, "SELECT cs.id, s.id_siswa, s.nisn, s.nama_siswa FROM course_students cs JOIN siswa s ON cs.siswa_id=s.id_siswa WHERE cs.course_id=".$course_id." ORDER BY s.nama_siswa ASC");
$student_count = mysqli_num_rows($enrolled);

$available = mysqli_query($koneksi, "SELECT s.id_siswa, s.nisn, s.nama_siswa FROM siswa s WHERE s.id_kelas=".$course['id_kelas']." AND s.status='aktif' AND s.id_siswa NOT IN (SELECT siswa_id FROM course_students WHERE course_id=".$course_id.") ORDER BY s.nama_siswa ASC");

$assignments = mysqli_query($koneksi, "SELECT * FROM assignments WHERE course_id=".$course_id." ORDER BY created_at DESC");
$materials = mysqli_query($koneksi, "SELECT * FROM materials WHERE course_id=".$course_id." ORDER BY created_at DESC");

// Active Tab Logic
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'info';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h5">Kelas Online: <?php echo htmlspecialchars($course['nama_course']); ?></h1>
        <div>
            <a href="courses.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <!-- Nav Tabs -->
    <ul class="nav nav-tabs mb-4" id="courseTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $active_tab == 'info' ? 'active' : ''; ?>" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="<?php echo $active_tab == 'info' ? 'true' : 'false'; ?>">
                <i class="fas fa-info-circle me-1"></i> Info Kelas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $active_tab == 'tugas' ? 'active' : ''; ?>" id="tugas-tab" data-bs-toggle="tab" data-bs-target="#tugas" type="button" role="tab" aria-controls="tugas" aria-selected="<?php echo $active_tab == 'tugas' ? 'true' : 'false'; ?>">
                <i class="fas fa-tasks me-1"></i> Tugas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $active_tab == 'materi' ? 'active' : ''; ?>" id="materi-tab" data-bs-toggle="tab" data-bs-target="#materi" type="button" role="tab" aria-controls="materi" aria-selected="<?php echo $active_tab == 'materi' ? 'true' : 'false'; ?>">
                <i class="fas fa-book me-1"></i> Materi
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $active_tab == 'siswa' ? 'active' : ''; ?>" id="siswa-tab" data-bs-toggle="tab" data-bs-target="#siswa" type="button" role="tab" aria-controls="siswa" aria-selected="<?php echo $active_tab == 'siswa' ? 'true' : 'false'; ?>">
                <i class="fas fa-users me-1"></i> Daftar Siswa
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="courseTabsContent">
        
        <!-- INFO KELAS TAB -->
        <div class="tab-pane fade <?php echo $active_tab == 'info' ? 'show active' : ''; ?>" id="info" role="tabpanel" aria-labelledby="info-tab">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Identitas Kelas</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 150px;">Nama Kelas Online</th>
                                    <td>: <?php echo htmlspecialchars($course['nama_course']); ?></td>
                                </tr>
                                <tr>
                                    <th>Kelas</th>
                                    <td>: <?php echo htmlspecialchars($course['nama_kelas']); ?></td>
                                </tr>
                                <tr>
                                    <th>Mata Pelajaran</th>
                                    <td>: <?php echo htmlspecialchars($course['nama_mapel']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 150px;">Guru Pengampu</th>
                                    <td>: <?php echo htmlspecialchars($course['nama_guru']); ?></td>
                                </tr>
                                <tr>
                                    <th>Tanggal Dibuat</th>
                                    <td>: <?php echo isset($course['created_at']) ? date('d F Y', strtotime($course['created_at'])) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Jumlah Siswa</th>
                                    <td>: <span class="badge bg-primary"><?php echo $student_count; ?> Siswa</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TUGAS TAB -->
        <div class="tab-pane fade <?php echo $active_tab == 'tugas' ? 'show active' : ''; ?>" id="tugas" role="tabpanel" aria-labelledby="tugas-tab">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-tasks me-2"></i>Daftar Tugas</h5>
                <a href="assignments.php" class="btn btn-primary btn-sm shadow-sm"><i class="fas fa-cog me-1"></i> Kelola Tugas</a>
            </div>
            
            <?php if(mysqli_num_rows($assignments) > 0): ?>
                <div class="row">
                    <?php while($a = mysqli_fetch_assoc($assignments)): 
                        $is_expired = strtotime($a['deadline']) < time();
                        $status_class = $is_expired ? 'danger' : 'success';
                        $status_text = $is_expired ? 'Berakhir' : 'Aktif';
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm border-0 border-start border-4 border-<?php echo $status_class; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info rounded-pill px-3"><?php echo htmlspecialchars($a['jenis_tugas']); ?></span>
                                    <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </div>
                                <h5 class="card-title font-weight-bold text-dark mb-2"><?php echo htmlspecialchars($a['judul']); ?></h5>
                                <div class="text-muted small mb-3">
                                    <i class="far fa-calendar-alt me-1"></i> Tenggat: <?php echo date('d M Y, H:i', strtotime($a['deadline'])); ?>
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTugas<?php echo $a['id_assignment']; ?>">Lihat Detail</button>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Detail Tugas -->
                        <div class="modal fade" id="modalTugas<?php echo $a['id_assignment']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="fas fa-tasks me-2"></i>Detail Tugas</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-start">
                                        <div class="mb-3">
                                            <label class="fw-bold text-muted small text-uppercase">Judul Tugas</label>
                                            <div class="fs-5 text-dark"><?php echo htmlspecialchars($a['judul']); ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="fw-bold text-muted small text-uppercase">Jenis Tugas</label>
                                                <div><span class="badge bg-info bg-opacity-10 text-info border border-info px-3"><?php echo htmlspecialchars($a['jenis_tugas']); ?></span></div>
                                            </div>
                                            <div class="col-md-6">
                                                 <label class="fw-bold text-muted small text-uppercase">Tenggat Waktu</label>
                                                 <div class="<?php echo $is_expired ? 'text-danger fw-bold' : 'text-success'; ?>">
                                                    <i class="far fa-calendar-alt me-1"></i> <?php echo date('d F Y, H:i', strtotime($a['deadline'])); ?>
                                                 </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="fw-bold text-muted small text-uppercase">Deskripsi / Instruksi</label>
                                            <div class="p-3 bg-light rounded border border-light">
                                                <?php echo nl2br(htmlspecialchars($a['deskripsi'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-clipboard-check fa-4x mb-3 text-gray-300"></i>
                    <p>Belum ada tugas di kelas ini.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- MATERI TAB -->
        <div class="tab-pane fade <?php echo $active_tab == 'materi' ? 'show active' : ''; ?>" id="materi" role="tabpanel" aria-labelledby="materi-tab">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-book-open me-2"></i>Daftar Materi</h5>
                <a href="materials.php" class="btn btn-primary btn-sm shadow-sm"><i class="fas fa-cog me-1"></i> Kelola Materi</a>
            </div>

            <?php if(mysqli_num_rows($materials) > 0): ?>
                <div class="row">
                    <?php while($m = mysqli_fetch_assoc($materials)): 
                        $icon = 'fa-file-alt';
                        $color = 'secondary';
                        $type = strtoupper($m['tipe']);
                        if(strpos($type, 'PDF') !== false) { $icon = 'fa-file-pdf'; $color = 'danger'; }
                        elseif(strpos($type, 'DOC') !== false) { $icon = 'fa-file-word'; $color = 'primary'; }
                        elseif(strpos($type, 'PPT') !== false) { $icon = 'fa-file-powerpoint'; $color = 'warning'; }
                        elseif(strpos($type, 'XLS') !== false) { $icon = 'fa-file-excel'; $color = 'success'; }
                        elseif(strpos($type, 'VIDEO') !== false || strpos($type, 'MP4') !== false) { $icon = 'fa-video'; $color = 'danger'; }
                        elseif($type == 'LINK') { $icon = 'fa-link'; $color = 'info'; }
                    ?>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card h-100 shadow-sm border-0 hover-shadow transition-300">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <span class="fa-stack fa-2x">
                                        <i class="fas fa-circle fa-stack-2x text-light"></i>
                                        <i class="fas <?php echo $icon; ?> fa-stack-1x text-<?php echo $color; ?>"></i>
                                    </span>
                                </div>
                                <h6 class="card-title font-weight-bold text-dark mb-1 text-truncate" title="<?php echo htmlspecialchars($m['judul']); ?>"><?php echo htmlspecialchars($m['judul']); ?></h6>
                                <p class="card-text text-muted small mb-3">
                                    <?php echo $type; ?> 
                                    <?php echo $m['size_bytes'] ? ' • '.round($m['size_bytes']/1024,1).' KB' : ''; ?>
                                </p>
                                <?php if($m['tipe']==='link'): ?>
                                    <a href="<?php echo $m['path']; ?>" target="_blank" class="btn btn-outline-info btn-sm w-100 rounded-pill">
                                        <i class="fas fa-external-link-alt me-1"></i> Buka Link
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo '../../'.$m['path']; ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100 rounded-pill">
                                        <i class="fas fa-download me-1"></i> Unduh
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-folder-open fa-4x mb-3 text-gray-300"></i>
                    <p>Belum ada materi di kelas ini.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- DAFTAR SISWA TAB -->
        <div class="tab-pane fade <?php echo $active_tab == 'siswa' ? 'show active' : ''; ?>" id="siswa" role="tabpanel" aria-labelledby="siswa-tab">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Siswa Terdaftar (<?php echo $student_count; ?>)</h6>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddStudent">
                        <i class="fas fa-user-plus"></i> Tambah Siswa
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>NISN</th>
                                    <th>Nama Siswa</th>
                                    <th width="150">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                mysqli_data_seek($enrolled, 0); // Reset pointer
                                while($e = mysqli_fetch_assoc($enrolled)): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($e['nisn']); ?></td>
                                    <td><?php echo htmlspecialchars($e['nama_siswa']); ?></td>
                                    <td>
                                        <button onclick="confirmAction('course_manage.php?course_id=<?php echo $course_id; ?>&remove_id=<?php echo $e['id']; ?>', 'Keluarkan siswa ini dari kelas?', 'Ya, keluarkan!')" class="btn btn-danger btn-sm">
                                            <i class="fas fa-user-minus"></i> Keluarkan
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

    </div>
</div>

<!-- Modal Tambah Siswa -->
<div class="modal fade" id="modalAddStudent" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Siswa ke Kelas Online</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Pilih Siswa dari Kelas <?php echo htmlspecialchars($course['nama_kelas']); ?></label>
            <select name="siswa_id" class="form-select" required>
                <option value="">-- Pilih Siswa --</option>
                <?php while($s = mysqli_fetch_assoc($available)): ?>
                    <option value="<?php echo $s['id_siswa']; ?>"><?php echo htmlspecialchars($s['nisn'].' - '.$s['nama_siswa']); ?></option>
                <?php endwhile; ?>
            </select>
            <div class="form-text">Hanya menampilkan siswa aktif yang belum masuk ke kelas ini.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_all" value="1" class="btn btn-secondary me-auto"><i class="fas fa-users"></i> Tambah Semua Siswa</button>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="add_student" value="1" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</button>
      </div>
    </form>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
