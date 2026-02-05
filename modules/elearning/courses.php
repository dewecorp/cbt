<?php
include '../../config/database.php';
$page_title = 'Kelas Online';
if (!isset($_SESSION['level'])) { $_SESSION['level'] = 'admin'; }
$level = $_SESSION['level'];
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
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
include '../../includes/header.php';
$kelas = mysqli_query($koneksi, "SELECT id_kelas,nama_kelas FROM kelas ORDER BY nama_kelas ASC");
$mapel = mysqli_query($koneksi, "SELECT id_mapel,nama_mapel FROM mapel ORDER BY nama_mapel ASC");
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
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Kelas Online</h6>
                    <div>
                        <?php if($level === 'admin' || $level === 'guru'): ?>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCourse"><i class="fas fa-plus"></i> Buat Kursus</button>
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
        <h5 class="modal-title">Buat Kelas Online</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
            <label class="form-label">Kode Kelas Online</label>
            <input type="text" name="kode_course" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Nama Kelas Online</label>
            <input type="text" name="nama_course" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Kelas</label>
            <select name="id_kelas" class="form-select" required>
                <option value="">Pilih Kelas</option>
                <?php while($k = mysqli_fetch_assoc($kelas)): ?>
                    <option value="<?php echo $k['id_kelas']; ?>"><?php echo $k['nama_kelas']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Mata Pelajaran</label>
            <select name="id_mapel" class="form-select" required>
                <option value="">Pilih Mapel</option>
                <?php while($m = mysqli_fetch_assoc($mapel)): ?>
                    <option value="<?php echo $m['id_mapel']; ?>"><?php echo $m['nama_mapel']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="create_course" value="1" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php include '../../includes/footer.php'; ?>
