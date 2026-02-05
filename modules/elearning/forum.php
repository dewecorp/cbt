<?php
include '../../config/database.php';
$page_title = 'Forum';
if (!isset($_SESSION['level'])) { $_SESSION['level'] = 'admin'; }
$level = $_SESSION['level'];
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$existsT = mysqli_query($koneksi, "SHOW TABLES LIKE 'forum_topics'");
if (mysqli_num_rows($existsT) == 0) {
    mysqli_query($koneksi, "CREATE TABLE `forum_topics` (
        `id_topic` int(11) NOT NULL AUTO_INCREMENT,
        `course_id` int(11) NOT NULL,
        `title` varchar(200) NOT NULL,
        `created_by` int(11) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_topic`)
    )");
}
$existsR = mysqli_query($koneksi, "SHOW TABLES LIKE 'forum_replies'");
if (mysqli_num_rows($existsR) == 0) {
    mysqli_query($koneksi, "CREATE TABLE `forum_replies` (
        `id_reply` int(11) NOT NULL AUTO_INCREMENT,
        `topic_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `content` text NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_reply`)
    )");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_topic'])) {
    $course_id = (int)$_POST['course_id'];
    $title = mysqli_real_escape_string($koneksi, $_POST['title']);
    if ($course_id>0 && !empty($title)) {
        mysqli_query($koneksi, "INSERT INTO forum_topics(course_id,title,created_by) VALUES($course_id,'$title',$uid)");
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_topic'])) {
    $topic_id = (int)$_POST['topic_id'];
    $content = mysqli_real_escape_string($koneksi, $_POST['content']);
    if ($topic_id>0 && !empty($content)) {
        mysqli_query($koneksi, "INSERT INTO forum_replies(topic_id,user_id,content) VALUES($topic_id,$uid,'$content')");
    }
}
include '../../includes/header.php';
$filterCourse = "";
if ($level === 'guru') { $filterCourse = " WHERE c.pengampu=".$uid; }
$courses = mysqli_query($koneksi, "SELECT c.id_course, c.nama_course, k.nama_kelas, m.nama_mapel FROM courses c JOIN kelas k ON c.id_kelas=k.id_kelas JOIN mapel m ON c.id_mapel=m.id_mapel $filterCourse ORDER BY c.nama_course ASC");
$topic_filter = "";
if ($level === 'siswa') {
    $id_kelas = isset($_SESSION['id_kelas']) ? $_SESSION['id_kelas'] : 0;
    $topic_filter = " WHERE c.id_kelas=".$id_kelas;
}
$topics = mysqli_query($koneksi, "SELECT t.*, c.nama_course, k.nama_kelas, u.nama_lengkap, (SELECT COUNT(*) FROM forum_replies r WHERE r.topic_id=t.id_topic) AS jml_reply FROM forum_topics t JOIN courses c ON t.course_id=c.id_course JOIN kelas k ON c.id_kelas=k.id_kelas JOIN users u ON t.created_by=u.id_user $topic_filter ORDER BY t.updated_at DESC");
$topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
$replies = null;
if ($topic_id>0) {
    $replies = mysqli_query($koneksi, "SELECT r.*, u.nama_lengkap FROM forum_replies r JOIN users u ON r.user_id=u.id_user WHERE r.topic_id=$topic_id ORDER BY r.created_at ASC");
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Forum Diskusi</h6>
                    <div>
                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalTopic"><i class="fas fa-comment-medical"></i> Buat Topik</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-datatable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Kelas</th>
                                    <th>Kelas Online</th>
                                    <th>Judul Topik</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Balasan</th>
                                    <th>Terakhir Aktif</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($t = mysqli_fetch_assoc($topics)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($t['nama_kelas']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nama_course']); ?></td>
                                    <td><?php echo htmlspecialchars($t['title']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nama_lengkap']); ?></td>
                                    <td><?php echo (int)$t['jml_reply']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($t['updated_at'])); ?></td>
                                    <td><a href="?topic_id=<?php echo $t['id_topic']; ?>" class="btn btn-primary btn-sm">Lihat</a></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if($topic_id>0 && $replies): ?>
                    <div class="card mt-3">
                        <div class="card-header">Balasan</div>
                        <div class="card-body">
                            <?php while($r = mysqli_fetch_assoc($replies)): ?>
                                <div class="mb-2"><strong><?php echo htmlspecialchars($r['nama_lengkap']); ?></strong> • <?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?><div><?php echo nl2br(htmlspecialchars($r['content'])); ?></div></div>
                            <?php endwhile; ?>
                            <form method="post" class="mt-3">
                                <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                                <div class="mb-2">
                                    <textarea name="content" class="form-control" rows="3" required></textarea>
                                </div>
                                <button type="submit" name="reply_topic" value="1" class="btn btn-primary btn-sm">Kirim Balasan</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalTopic" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Buat Topik</h5>
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
            <label class="form-label">Judul Topik</label>
            <input type="text" name="title" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="create_topic" value="1" class="btn btn-info">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php include '../../includes/footer.php'; ?>
