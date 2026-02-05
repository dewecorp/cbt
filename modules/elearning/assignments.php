<?php
include '../../config/database.php';
$page_title = 'Tugas';
if (!isset($_SESSION['level'])) { $_SESSION['level'] = 'admin'; }
$level = $_SESSION['level'];
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$existsA = mysqli_query($koneksi, "SHOW TABLES LIKE 'assignments'");
if (mysqli_num_rows($existsA) == 0) {
    mysqli_query($koneksi, "CREATE TABLE `assignments` (
        `id_assignment` int(11) NOT NULL AUTO_INCREMENT,
        `course_id` int(11) NOT NULL,
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $course_id = (int)$_POST['course_id'];
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $deadline = mysqli_real_escape_string($koneksi, $_POST['deadline']);
    if ($course_id>0 && !empty($judul) && !empty($deadline)) {
        mysqli_query($koneksi, "INSERT INTO assignments(course_id,judul,deskripsi,deadline,created_by) VALUES($course_id,'$judul','$deskripsi','$deadline',$uid)");
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
$assignments = mysqli_query($koneksi, "SELECT a.*, c.nama_course, k.nama_kelas, u.nama_lengkap FROM assignments a JOIN courses c ON a.course_id=c.id_course JOIN kelas k ON c.id_kelas=k.id_kelas JOIN users u ON a.created_by=u.id_user $assign_filter ORDER BY a.created_at DESC");
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Tugas Pembelajaran</h6>
                    <div>
                        <?php if($level === 'admin' || $level === 'guru'): ?>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalAssignment"><i class="fas fa-plus-circle"></i> Buat Tugas</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-datatable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Kelas</th>
                                    <th>Kelas Online</th>
                                    <th>Judul</th>
                                    <th>Tenggat</th>
                                    <th>Guru</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($a = mysqli_fetch_assoc($assignments)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($a['nama_kelas']); ?></td>
                                    <td><?php echo htmlspecialchars($a['nama_course']); ?></td>
                                    <td><?php echo htmlspecialchars($a['judul']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($a['deadline'])); ?></td>
                                    <td><?php echo htmlspecialchars($a['nama_lengkap']); ?></td>
                                    <td>
                                        <?php if($level === 'siswa'): ?>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSubmit<?php echo $a['id_assignment']; ?>"><i class="fas fa-upload"></i> Unggah</button>
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
                </div>
            </div>
        </div>
    </div>
</div>
<?php if($level === 'admin' || $level === 'guru'): ?>
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
<?php endif; ?>
<?php include '../../includes/footer.php'; ?>
