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
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Kelas Online</h6>
                    <div>
                        <?php if($level === 'admin' || $level === 'guru'): ?>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCourse"><i class="fas fa-plus"></i> Buat Kelas Online</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-datatable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Kelas Online</th>
                                    <th>Kelas</th>
                                    <th>Mapel</th>
                                    <th>Jumlah Materi</th>
                                    <th>Jumlah Tugas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($c = mysqli_fetch_assoc($courses)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($c['kode_course']); ?></td>
                                    <td><?php echo htmlspecialchars($c['nama_course']); ?></td>
                                    <td><?php echo htmlspecialchars($c['nama_kelas']); ?></td>
                                    <td><?php echo htmlspecialchars($c['nama_mapel']); ?></td>
                                    <td><?php echo (int)$c['jml_materi']; ?></td>
                                    <td><?php echo (int)$c['jml_tugas']; ?></td>
                                    <td>
                                        <?php if($level === 'admin' || (int)$c['pengampu'] === $uid): ?>
                                        <a href="courses.php?edit_id=<?php echo $c['id_course']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="course_manage.php?course_id=<?php echo $c['id_course']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-door-open"></i> Masuk</a>
                                        <a href="#" onclick="confirmDelete('courses.php?delete_id=<?php echo $c['id_course']; ?>'); return false;" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</a>
                                        <?php else: ?>
                                        <a href="course_manage.php?course_id=<?php echo $c['id_course']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-door-open"></i> Masuk</a>
                                        <!-- Debug: Level=<?php echo $level; ?>, Pengampu=<?php echo $c['pengampu']; ?>, UID=<?php echo $uid; ?> -->
                                        <?php endif; ?>
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
                <?php while($k = mysqli_fetch_assoc($kelas)): ?>
                    <option value="<?php echo $k['id_kelas']; ?>" <?php echo ($edit_course && (int)$edit_course['id_kelas']===(int)$k['id_kelas']) ? 'selected' : ''; ?>><?php echo $k['nama_kelas']; ?></option>
                <?php endwhile; ?>
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
