<?php
session_start();
include '../../config/database.php';
$page_title = 'Pengumuman';
if (!isset($_SESSION['level'])) { $_SESSION['level'] = 'admin'; }
$level = $_SESSION['level'];
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$swal_script = "";

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

// Create Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_announcement'])) {
    $course_id = $_POST['course_id']==='' ? 'NULL' : (int)$_POST['course_id'];
    $title = mysqli_real_escape_string($koneksi, $_POST['title']);
    $body = mysqli_real_escape_string($koneksi, $_POST['body']);
    
    if (!empty($title) && !empty($body)) {
        if(mysqli_query($koneksi, "INSERT INTO announcements(course_id,title,body,created_by) VALUES($course_id,'$title','$body',$uid)")){
            $swal_script = "Swal.fire({title: 'Berhasil!', text: 'Pengumuman berhasil dibuat.', icon: 'success', timer: 2000, showConfirmButton: false});";
        }
    }
}

// Update Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_announcement'])) {
    $id = (int)$_POST['id_announcement'];
    $course_id = $_POST['course_id']==='' ? 'NULL' : (int)$_POST['course_id'];
    $title = mysqli_real_escape_string($koneksi, $_POST['title']);
    $body = mysqli_real_escape_string($koneksi, $_POST['body']);
    
    // Check permission
    $check = mysqli_query($koneksi, "SELECT created_by FROM announcements WHERE id_announcement=$id");
    $data = mysqli_fetch_assoc($check);
    
    if ($level === 'admin' || ($level === 'guru' && $data['created_by'] == $uid)) {
        if (!empty($title) && !empty($body)) {
            if(mysqli_query($koneksi, "UPDATE announcements SET course_id=$course_id, title='$title', body='$body' WHERE id_announcement=$id")){
                $swal_script = "Swal.fire({title: 'Berhasil!', text: 'Pengumuman berhasil diperbarui.', icon: 'success', timer: 2000, showConfirmButton: false});";
            }
        }
    }
}

// Delete Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    $id = (int)$_POST['id_announcement'];
    
    // Check permission
    $check = mysqli_query($koneksi, "SELECT created_by FROM announcements WHERE id_announcement=$id");
    $data = mysqli_fetch_assoc($check);
    
    if ($level === 'admin' || ($level === 'guru' && $data['created_by'] == $uid)) {
        if(mysqli_query($koneksi, "DELETE FROM announcements WHERE id_announcement=$id")){
            $swal_script = "Swal.fire({title: 'Berhasil!', text: 'Pengumuman berhasil dihapus.', icon: 'success', timer: 2000, showConfirmButton: false});";
        }
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
                    <h6 class="m-0 font-weight-bold text-success">Pengumuman</h6>
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
                                    <th>No</th>
                                    <th>Judul</th>
                                    <th>Kelas</th>
                                    <th>Kelas Online</th>
                                    <th>Tanggal</th>
                                    <?php if($level === 'admin' || $level === 'guru'): ?>
                                    <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; while($a = mysqli_fetch_assoc($ann)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($a['title']); ?></td>
                                    <td><?php echo $a['course_id'] ? htmlspecialchars($a['nama_kelas']) : '-'; ?></td>
                                    <td><?php echo $a['course_id'] ? htmlspecialchars($a['nama_course']) : 'Global'; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($a['created_at'])); ?></td>
                                    <?php if($level === 'admin' || $level === 'guru'): ?>
                                    <td>
                                        <?php if($level === 'admin' || ($level === 'guru' && $a['created_by'] == $uid)): ?>
                                        <button class="btn btn-warning btn-sm" 
                                            data-id="<?php echo $a['id_announcement']; ?>"
                                            data-title="<?php echo htmlspecialchars($a['title']); ?>"
                                            data-course="<?php echo $a['course_id']; ?>"
                                            data-body="<?php echo htmlspecialchars($a['body']); ?>"
                                            onclick="editAnnouncement(this)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="post" style="display:inline;" onsubmit="return confirmDeleteAnnouncement(event, this);">
                                            <input type="hidden" name="id_announcement" value="<?php echo $a['id_announcement']; ?>">
                                            <button type="submit" name="delete_announcement" value="1" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
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

<div class="modal fade" id="modalEditAnnouncement" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="id_announcement" id="edit_id_announcement">
      <div class="modal-header">
        <h5 class="modal-title">Edit Pengumuman</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
            <label class="form-label">Kelas Online (Opsional)</label>
            <select name="course_id" id="edit_course_id" class="form-select">
                <option value="">Global</option>
                <?php 
                if(isset($courses)) {
                    mysqli_data_seek($courses, 0);
                    while($c = mysqli_fetch_assoc($courses)): 
                ?>
                    <option value="<?php echo $c['id_course']; ?>"><?php echo $c['nama_kelas'].' - '.$c['nama_mapel'].' - '.$c['nama_course']; ?></option>
                <?php 
                    endwhile; 
                }
                ?>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Judul</label>
            <input type="text" name="title" id="edit_title" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Isi</label>
            <textarea name="body" id="edit_body" class="form-control" rows="4" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="update_announcement" value="1" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function editAnnouncement(btn) {
    var id = btn.getAttribute('data-id');
    var title = btn.getAttribute('data-title');
    var course = btn.getAttribute('data-course');
    var body = btn.getAttribute('data-body');
    
    document.getElementById('edit_id_announcement').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_body').value = body;
    document.getElementById('edit_course_id').value = course ? course : "";
    
    var myModal = new bootstrap.Modal(document.getElementById('modalEditAnnouncement'));
    myModal.show();
}

function confirmDeleteAnnouncement(e, form) {
    e.preventDefault();
    Swal.fire({
        title: 'Hapus Pengumuman?',
        text: "Pengumuman yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#198754',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
    return false;
}

<?php if(!empty($swal_script)): ?>
document.addEventListener('DOMContentLoaded', function() {
    <?php echo $swal_script; ?>
});
<?php endif; ?>
</script>
<?php include '../../includes/footer.php'; ?>
