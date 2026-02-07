<?php
session_start();
include '../../config/database.php';
$page_title = 'Kelas Online';
if (!isset($_SESSION['level'])) { $_SESSION['level'] = 'admin'; }
$level = $_SESSION['level'];
$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$exists = mysqli_query($koneksi, "SHOW TABLES LIKE 'courses'");
if (mysqli_num_rows($exists) == 0) {
    mysqli_query($koneksi, "CREATE TABLE `courses` (
        `id_course` int(11) NOT NULL AUTO_INCREMENT,
        `kode_course` varchar(30) NOT NULL,
        `nama_course` varchar(150) NOT NULL,
        `id_kelas` int(11) NOT NULL,
        `id_mapel` int(11) NOT NULL,
        `pengampu` int(11) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_course`)
    )");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_course'])) {
    $kode = mysqli_real_escape_string($koneksi, $_POST['kode_course']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_course']);
    $id_kelas = (int)$_POST['id_kelas'];
    $id_mapel = (int)$_POST['id_mapel'];
    $pengampu = $uid;
    if (!empty($kode) && !empty($nama) && $id_kelas > 0 && $id_mapel > 0) {
        mysqli_query($koneksi, "INSERT INTO courses(kode_course,nama_course,id_kelas,id_mapel,pengampu) VALUES('$kode','$nama',$id_kelas,$id_mapel,$pengampu)");
        header("Location: courses.php");
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_course'])) {
    $id_course = (int)$_POST['id_course'];
    $kode = mysqli_real_escape_string($koneksi, $_POST['kode_course']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_course']);
    $id_kelas = (int)$_POST['id_kelas'];
    $id_mapel = (int)$_POST['id_mapel'];
    $ownerCheck = mysqli_query($koneksi, "SELECT pengampu FROM courses WHERE id_course=$id_course");
    $ok = true;
    if ($level === 'guru') {
        if ($ownerCheck && mysqli_num_rows($ownerCheck)>0) {
            $ow = mysqli_fetch_assoc($ownerCheck);
            if ((int)$ow['pengampu'] !== $uid) $ok=false;
        }
    }
    if ($ok && $id_course>0 && !empty($kode) && !empty($nama) && $id_kelas>0 && $id_mapel>0) {
        mysqli_query($koneksi, "UPDATE courses SET kode_course='$kode', nama_course='$nama', id_kelas=$id_kelas, id_mapel=$id_mapel WHERE id_course=$id_course");
        header("Location: courses.php");
        exit;
    }
}
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $canDel = true;
    if ($level === 'guru') {
        $qown = mysqli_query($koneksi, "SELECT pengampu FROM courses WHERE id_course=$del_id");
        if ($qown && mysqli_num_rows($qown)>0) {
            $ow = mysqli_fetch_assoc($qown);
            if ((int)$ow['pengampu'] !== $uid) $canDel=false;
        }
    }
    if ($canDel && $del_id>0) {
        mysqli_query($koneksi, "DELETE FROM materials WHERE course_id=$del_id");
        mysqli_query($koneksi, "DELETE FROM assignments WHERE course_id=$del_id");
        mysqli_query($koneksi, "DELETE FROM forum_topics WHERE course_id=$del_id");
        mysqli_query($koneksi, "DELETE FROM announcements WHERE course_id=$del_id");
        $tcs = mysqli_query($koneksi, "SHOW TABLES LIKE 'course_students'");
        if ($tcs && mysqli_num_rows($tcs)>0) {
            mysqli_query($koneksi, "DELETE FROM course_students WHERE course_id=$del_id");
        }
        mysqli_query($koneksi, "DELETE FROM courses WHERE id_course=$del_id");
        header("Location: courses.php");
        exit;
    }
}
include '../../includes/header.php';
$kelas = null;
$mapel = null;
$kelas_arr = []; // Array to store classes for modal and admin tabs

if ($level === 'guru') {
    // Ambil data dari user (Logic disamakan dengan bank_soal.php)
    $qg = mysqli_query($koneksi, "SELECT mengajar_kelas, mengajar_mapel FROM users WHERE id_user='$uid'");
    $dg = ($qg && mysqli_num_rows($qg) > 0) ? mysqli_fetch_assoc($qg) : ['mengajar_kelas'=>'', 'mengajar_mapel'=>''];
    
    // Logic Mapel
    $where_mapel = "WHERE 1=0";
    if (!empty($dg['mengajar_mapel'])) {
        $mapel_ids = trim($dg['mengajar_mapel']);
        // Pastikan tidak ada koma di akhir yang bikin error SQL
        $mapel_ids = rtrim($mapel_ids, ',');
        if (!empty($mapel_ids)) {
            $where_mapel = "WHERE id_mapel IN ($mapel_ids)";
        }
    }
    
    $mapel = mysqli_query($koneksi, "SELECT * FROM mapel $where_mapel ORDER BY nama_mapel ASC");
    if (!$mapel) { 
        // Jika query error (misal syntax IN salah), return kosong
        $mapel = mysqli_query($koneksi, "SELECT * FROM mapel WHERE 1=0");
    }


    // Logic Kelas
    $where_kelas = "WHERE 1=0";
    if (!empty($dg['mengajar_kelas'])) {
        $kelas_ids = trim($dg['mengajar_kelas']);
        $kelas_ids = rtrim($kelas_ids, ',');
        if (!empty($kelas_ids)) {
            $where_kelas = "WHERE id_kelas IN ($kelas_ids)";
        }
    }
    $kelas = mysqli_query($koneksi, "SELECT * FROM kelas $where_kelas ORDER BY nama_kelas ASC");
    if (!$kelas) {
        $kelas = mysqli_query($koneksi, "SELECT * FROM kelas WHERE 1=0");
    }

} else {
    $kelas = mysqli_query($koneksi, "SELECT id_kelas,nama_kelas FROM kelas ORDER BY nama_kelas ASC");
    $mapel = mysqli_query($koneksi, "SELECT id_mapel,nama_mapel FROM mapel ORDER BY nama_mapel ASC");
}

// Convert classes to array for reuse
if ($kelas) {
    while($k = mysqli_fetch_assoc($kelas)) {
        $kelas_arr[] = $k;
    }
}

$filter = "";
if ($level === 'guru') { $filter = " WHERE c.pengampu=".$uid; }
$courses = mysqli_query($koneksi, "
    SELECT c.*, k.nama_kelas, m.nama_mapel,
    (SELECT COUNT(*) FROM materials mt WHERE mt.course_id=c.id_course) AS jml_materi,
    (SELECT COUNT(*) FROM assignments a WHERE a.course_id=c.id_course) AS jml_tugas
    FROM courses c 
    JOIN kelas k ON c.id_kelas=k.id_kelas
    JOIN mapel m ON c.id_mapel=m.id_mapel
    $filter
    ORDER BY c.created_at DESC
");

$admin_courses = [];
if ($level === 'admin') {
    while($c = mysqli_fetch_assoc($courses)) {
        $admin_courses[$c['id_kelas']][] = $c;
    }
}

$edit_course = null;
$open_edit = false;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $qedit = mysqli_query($koneksi, "SELECT * FROM courses WHERE id_course=$eid");
    if ($qedit && mysqli_num_rows($qedit)>0) {
        $row = mysqli_fetch_assoc($qedit);
        if ($level === 'admin' || (int)$row['pengampu'] === $uid) {
            $edit_course = $row;
            $open_edit = true;
        }
    }
}
?>
<style>
#pills-tab .nav-link.active, #pills-tab .show > .nav-link {
    background-color: #198754 !important;
    color: white !important;
}
#pills-tab .nav-link {
    color: #6c757d;
}
#pills-tab .nav-link:hover {
    color: #198754;
}
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">Kelas Online</h6>
                    <div>
                        <?php if($level === 'guru'): ?>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCourse"><i class="fas fa-plus"></i> Buat Kelas Online</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    
                    <?php if ($level === 'admin'): ?>
                        <!-- Admin View with Tabs -->
                         <?php if (!empty($kelas_arr)): ?>
                            <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                                <?php foreach($kelas_arr as $index => $k): 
                                    $count = isset($admin_courses[$k['id_kelas']]) ? count($admin_courses[$k['id_kelas']]) : 0;
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
                                        <div class="row">
                                            <?php if(isset($admin_courses[$k['id_kelas']]) && count($admin_courses[$k['id_kelas']]) > 0): ?>
                                                <?php foreach($admin_courses[$k['id_kelas']] as $c): ?>
                                                    <div class="col-xl-4 col-md-6 mb-4">
                                                        <div class="card shadow h-100 border-start border-4 border-success hover-shadow transition-300">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <span class="badge bg-success"><?php echo htmlspecialchars($c['nama_kelas']); ?></span>
                                                                    <small class="text-muted fw-bold"><?php echo htmlspecialchars($c['kode_course']); ?></small>
                                                                </div>
                                                                <h5 class="card-title font-weight-bold text-dark mb-2 text-truncate" title="<?php echo htmlspecialchars($c['nama_course']); ?>">
                                                                    <?php echo htmlspecialchars($c['nama_course']); ?>
                                                                </h5>
                                                                <p class="text-muted small mb-3">
                                                                    <i class="fas fa-book me-1"></i> <?php echo htmlspecialchars($c['nama_mapel']); ?>
                                                                </p>
                                                                
                                                                <div class="row text-center mb-3 g-2">
                                                                    <div class="col-6">
                                                                        <div class="bg-light p-2 rounded border">
                                                                            <div class="h5 mb-0 font-weight-bold text-success"><?php echo (int)$c['jml_materi']; ?></div>
                                                                            <div class="small text-muted" style="font-size: 0.75rem;">Materi</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="bg-light p-2 rounded border">
                                                                            <div class="h5 mb-0 font-weight-bold text-success"><?php echo (int)$c['jml_tugas']; ?></div>
                                                                            <div class="small text-muted" style="font-size: 0.75rem;">Tugas</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- No buttons for Admin as requested -->
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="col-12 text-center py-5 text-muted">
                                                    <i class="fas fa-layer-group fa-3x mb-3 text-gray-300"></i>
                                                    <p>Tidak ada kelas online untuk kelas ini.</p>
                                                </div>
                                            <?php endif; ?>
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
                        <!-- Guru View (Existing) -->
                        <?php if(mysqli_num_rows($courses) > 0): ?>
                        <div class="row">
                            <?php while($c = mysqli_fetch_assoc($courses)): ?>
                            <div class="col-xl-4 col-md-6 mb-4">
                                <div class="card shadow h-100 border-start border-4 border-success hover-shadow transition-300">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-success"><?php echo htmlspecialchars($c['nama_kelas']); ?></span>
                                            <small class="text-muted fw-bold"><?php echo htmlspecialchars($c['kode_course']); ?></small>
                                        </div>
                                        <h5 class="card-title font-weight-bold text-dark mb-2 text-truncate" title="<?php echo htmlspecialchars($c['nama_course']); ?>">
                                            <?php echo htmlspecialchars($c['nama_course']); ?>
                                        </h5>
                                        <p class="text-muted small mb-3">
                                            <i class="fas fa-book me-1"></i> <?php echo htmlspecialchars($c['nama_mapel']); ?>
                                        </p>
                                        
                                        <div class="row text-center mb-3 g-2">
                                            <div class="col-6">
                                                <div class="bg-light p-2 rounded border">
                                                    <div class="h5 mb-0 font-weight-bold text-success"><?php echo (int)$c['jml_materi']; ?></div>
                                                    <div class="small text-muted" style="font-size: 0.75rem;">Materi</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="bg-light p-2 rounded border">
                                                    <div class="h5 mb-0 font-weight-bold text-success"><?php echo (int)$c['jml_tugas']; ?></div>
                                                    <div class="small text-muted" style="font-size: 0.75rem;">Tugas</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <a href="course_manage.php?course_id=<?php echo $c['id_course']; ?>" class="btn btn-primary">
                                                <i class="fas fa-door-open me-1"></i> Masuk Kelas
                                            </a>
                                            <?php if($level === 'admin' || (int)$c['pengampu'] === $uid): ?>
                                            <div class="btn-group">
                                                <a href="courses.php?edit_id=<?php echo $c['id_course']; ?>" class="btn btn-outline-warning btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="#" onclick="confirmDelete('courses.php?delete_id=<?php echo $c['id_course']; ?>'); return false;" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-layer-group fa-4x mb-3 text-gray-300"></i>
                            <p>Belum ada kelas online.</p>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php if($level === 'admin' || $level === 'guru'): ?>
<div class="modal fade" id="modalCourse" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo $edit_course ? 'Edit Kelas Online' : 'Buat Kelas Online'; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php if($edit_course): ?>
            <input type="hidden" name="id_course" value="<?php echo $edit_course['id_course']; ?>">
        <?php endif; ?>
        <div class="mb-2">
            <label class="form-label">Kode Kelas Online</label>
            <input type="text" name="kode_course" class="form-control" value="<?php echo $edit_course ? htmlspecialchars($edit_course['kode_course']) : ''; ?>" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Nama Kelas Online</label>
            <input type="text" name="nama_course" class="form-control" value="<?php echo $edit_course ? htmlspecialchars($edit_course['nama_course']) : ''; ?>" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Kelas</label>
            <select name="id_kelas" class="form-select" required>
                <option value="">Pilih Kelas</option>
                <?php foreach($kelas_arr as $k): ?>
                    <option value="<?php echo $k['id_kelas']; ?>" <?php echo ($edit_course && (int)$edit_course['id_kelas']===(int)$k['id_kelas']) ? 'selected' : ''; ?>><?php echo $k['nama_kelas']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Mata Pelajaran</label>
            <select name="id_mapel" class="form-select" required>
                <option value="">Pilih Mapel</option>
                <?php while($m = mysqli_fetch_assoc($mapel)): ?>
                    <option value="<?php echo $m['id_mapel']; ?>" <?php echo ($edit_course && (int)$edit_course['id_mapel']===(int)$m['id_mapel']) ? 'selected' : ''; ?>><?php echo $m['nama_mapel']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <?php if($edit_course): ?>
        <button type="submit" name="update_course" value="1" class="btn btn-primary">Simpan</button>
        <?php else: ?>
        <button type="submit" name="create_course" value="1" class="btn btn-primary">Simpan</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
<?php if($open_edit): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var m = new bootstrap.Modal(document.getElementById('modalCourse'));
    m.show();
});
</script>
<?php endif; ?>
<?php endif; ?>
<?php include '../../includes/footer.php'; ?>
