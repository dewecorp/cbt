<?php
include '../../config/database.php';
$page_title = 'Pengumuman';
if (!isset($_SESSION['level'])) { $_SESSION['level'] = 'admin'; }
$level = $_SESSION['level'];
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$exists = mysqli_query($koneksi, "SHOW TABLES LIKE 'announcements'");
if (mysqli_num_rows($exists) == 0) {
    mysqli_query($koneksi, "CREATE TABLE `announcements` (
        `id_announcement` int(11) NOT NULL AUTO_INCREMENT,
        `course_id` int(11) DEFAULT NULL,
        `title` varchar(200) NOT NULL,
        `body` text NOT NULL,
        `created_by` int(11) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_announcement`)
    )");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_announcement'])) {
    $course_id = $_POST['course_id']==='' ? 'NULL' : (int)$_POST['course_id'];
    $title = mysqli_real_escape_string($koneksi, $_POST['title']);
    $body = mysqli_real_escape_string($koneksi, $_POST['body']);
    if (!empty($title) && !empty($body)) {
        mysqli_query($koneksi, "INSERT INTO announcements(course_id,title,body,created_by) VALUES($course_id,'$title','$body',$uid)");
    }
}
include '../../includes/header.php';
$filterCourse = "";
if ($level === 'guru') { $filterCourse = " WHERE c.pengampu=".$uid; }
$courses = mysqli_query($koneksi, "SELECT c.id_course, c.nama_course, k.nama_kelas, m.nama_mapel FROM courses c JOIN kelas k ON c.id_kelas=k.id_kelas JOIN mapel m ON c.id_mapel=m.id_mapel $filterCourse ORDER BY c.nama_course ASC");
$ann_filter = "";
if ($level === 'siswa') {
    $id_kelas = isset($_SESSION['id_kelas']) ? $_SESSION['id_kelas'] : 0;
    $ann_filter = " WHERE c.id_kelas=".$id_kelas." OR a.course_id IS NULL";
}
$ann = mysqli_query($koneksi, "SELECT a.*, c.nama_course, k.nama_kelas, u.nama_lengkap FROM announcements a LEFT JOIN courses c ON a.course_id=c.id_course LEFT JOIN kelas k ON c.id_kelas=k.id_kelas JOIN users u ON a.created_by=u.id_user $ann_filter ORDER BY a.created_at DESC");
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Pengumuman</h6>
                    <div>
                        <?php if($level === 'admin' || $level === 'guru'): ?>
                        <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAnnouncement"><i class="fas fa-bullhorn"></i> Buat Pengumuman</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-datatable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Kelas</th>
                                    <th>Kelas Online</th>
                                    <th>Diumumkan Oleh</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($a = mysqli_fetch_assoc($ann)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($a['title']); ?></td>
                                    <td><?php echo $a['course_id'] ? htmlspecialchars($a['nama_kelas']) : '-'; ?></td>
                                    <td><?php echo $a['course_id'] ? htmlspecialchars($a['nama_course']) : 'Global'; ?></td>
                                    <td><?php echo htmlspecialchars($a['nama_lengkap']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($a['created_at'])); ?></td>
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
<?php if($level === 'admin' || $level === 'guru'): ?>
<div class="modal fade" id="modalAnnouncement" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Buat Pengumuman</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
            <label class="form-label">Kelas Online (Opsional)</label>
            <select name="course_id" class="form-select">
                <option value="">Global</option>
                <?php while($c = mysqli_fetch_assoc($courses)): ?>
                    <option value="<?php echo $c['id_course']; ?>"><?php echo $c['nama_kelas'].' - '.$c['nama_mapel'].' - '.$c['nama_course']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Judul</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Isi</label>
            <textarea name="body" class="form-control" rows="4" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="create_announcement" value="1" class="btn btn-secondary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php include '../../includes/footer.php'; ?>
