<?php
include '../../config/database.php';
$page_title = 'Kelola Kelas Online';
if (!isset($_SESSION['level'])) { $_SESSION['level'] = 'admin'; }
$level = $_SESSION['level'];
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
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
if ($course_id>0) {
    $qc = mysqli_query($koneksi, "SELECT c.*, k.nama_kelas, m.nama_mapel FROM courses c JOIN kelas k ON c.id_kelas=k.id_kelas JOIN mapel m ON c.id_mapel=m.id_mapel WHERE c.id_course=".$course_id);
    if ($qc && mysqli_num_rows($qc)>0) {
        $course = mysqli_fetch_assoc($qc);
        if ($level === 'guru' && (int)$course['pengampu'] !== $uid) {
            $course = null;
        }
    }
}
if (!$course) {
    include '../../includes/header.php';
    echo '<div class="container-fluid"><div class="alert alert-danger">Kelas Online tidak ditemukan.</div></div>';
    include '../../includes/footer.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $sid = (int)$_POST['siswa_id'];
    if ($sid>0) {
        $existsRow = mysqli_query($koneksi, "SELECT id FROM course_students WHERE course_id=".$course_id." AND siswa_id=".$sid);
        if ($existsRow && mysqli_num_rows($existsRow)==0) {
            mysqli_query($koneksi, "INSERT INTO course_students(course_id,siswa_id) VALUES(".$course_id.",".$sid.")");
        }
    }
}
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
}
if (isset($_GET['remove_id'])) {
    $rid = (int)$_GET['remove_id'];
    if ($rid>0) {
        mysqli_query($koneksi, "DELETE FROM course_students WHERE id=".$rid." AND course_id=".$course_id);
        header("Location: course_manage.php?course_id=".$course_id);
        exit;
    }
}
include '../../includes/header.php';
$enrolled = mysqli_query($koneksi, "SELECT cs.id, s.id_siswa, s.nisn, s.nama_siswa FROM course_students cs JOIN siswa s ON cs.siswa_id=s.id_siswa WHERE cs.course_id=".$course_id." ORDER BY s.nama_siswa ASC");
$available = mysqli_query($koneksi, "SELECT s.id_siswa, s.nisn, s.nama_siswa FROM siswa s WHERE s.id_kelas=".$course['id_kelas']." AND s.status='aktif' AND s.id_siswa NOT IN (SELECT siswa_id FROM course_students WHERE course_id=".$course_id.") ORDER BY s.nama_siswa ASC");
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h5">Kelas Online: <?php echo htmlspecialchars($course['nama_course']); ?> • <?php echo htmlspecialchars($course['nama_kelas']); ?> • <?php echo htmlspecialchars($course['nama_mapel']); ?></h1>
        <div>
            <a href="courses.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Siswa Terdaftar</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>NISN</th>
                                    <th>Nama</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($e = mysqli_fetch_assoc($enrolled)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($e['nisn']); ?></td>
                                    <td><?php echo htmlspecialchars($e['nama_siswa']); ?></td>
                                    <td>
                                        <a href="#" onclick="confirmAction('course_manage.php?course_id=<?php echo $course_id; ?>&remove_id=<?php echo $e['id']; ?>', 'Keluarkan siswa ini dari kelas?', 'Ya, keluarkan!'); return false;" class="btn btn-danger btn-sm"><i class="fas fa-user-minus"></i> Keluarkan</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">Tambah Siswa</h6>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-2">
                        <div class="col-12">
                            <select name="siswa_id" class="form-select" required>
                                <option value="">Pilih Siswa (<?php echo htmlspecialchars($course['nama_kelas']); ?>)</option>
                                <?php while($s = mysqli_fetch_assoc($available)): ?>
                                    <option value="<?php echo $s['id_siswa']; ?>"><?php echo htmlspecialchars($s['nisn'].' - '.$s['nama_siswa']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="add_student" value="1" class="btn btn-success btn-sm"><i class="fas fa-user-plus"></i> Tambah</button>
                            <button type="submit" name="add_all" value="1" class="btn btn-success btn-sm"><i class="fas fa-users"></i> Tambah Semua</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
