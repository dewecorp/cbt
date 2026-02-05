<?php
session_start();
include '../../config/database.php';
$page_title = 'Materi';
if (!isset($_SESSION['level'])) { $_SESSION['level'] = 'admin'; }
$level = $_SESSION['level'];
$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$exists = mysqli_query($koneksi, "SHOW TABLES LIKE 'materials'");
if (mysqli_num_rows($exists) == 0) {
    mysqli_query($koneksi, "CREATE TABLE `materials` (
        `id_material` int(11) NOT NULL AUTO_INCREMENT,
        `course_id` int(11) NOT NULL,
        `judul` varchar(200) NOT NULL,
        `tipe` enum('pdf','ppt','doc','video','link') NOT NULL,
        `path` varchar(255) NOT NULL,
        `owner_id` int(11) NOT NULL,
        `size_bytes` int(11) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_material`)
    )");
}
if (!is_dir(dirname(__DIR__,2).'/assets/uploads/materials')) {
    @mkdir(dirname(__DIR__,2).'/assets/uploads/materials', 0777, true);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_material'])) {
    $course_id = (int)$_POST['course_id'];
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $tipe = $_POST['tipe'];
    $path = '';
    $size = 0;
    if ($tipe === 'link') {
        $path = mysqli_real_escape_string($koneksi, $_POST['link_url']);
    } else {
        if (isset($_FILES['file']['name']) && $_FILES['file']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf','ppt','pptx','doc','docx','mp4','mov'];
            if (in_array($ext, $allowed)) {
                $fname = time().'_'.preg_replace('/[^a-zA-Z0-9\.\-_]/','', $_FILES['file']['name']);
                $dest = dirname(__DIR__,2).'/assets/uploads/materials/'.$fname;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                    $path = 'assets/uploads/materials/'.$fname;
                    $size = filesize($dest);
                }
            }
        }
    }
    if ($course_id>0 && !empty($judul) && !empty($tipe) && !empty($path)) {
        mysqli_query($koneksi, "INSERT INTO materials(course_id,judul,tipe,path,owner_id,size_bytes) VALUES($course_id,'$judul','$tipe','$path',$uid,$size)");
    }
}

// Delete Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_material'])) {
    $id = (int)$_POST['id_material'];
    $check = mysqli_query($koneksi, "SELECT * FROM materials WHERE id_material=$id");
    if ($m = mysqli_fetch_assoc($check)) {
        if ($level === 'admin' || ($level === 'guru' && $m['owner_id'] == $uid)) {
            if ($m['tipe'] !== 'link' && file_exists(dirname(__DIR__,2).'/'.$m['path'])) {
                unlink(dirname(__DIR__,2).'/'.$m['path']);
            }
            mysqli_query($koneksi, "DELETE FROM materials WHERE id_material=$id");
        }
    }
}

// Edit Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_material'])) {
    $id = (int)$_POST['id_material'];
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $tipe = $_POST['tipe'];
    
    $check = mysqli_query($koneksi, "SELECT * FROM materials WHERE id_material=$id");
    if ($m = mysqli_fetch_assoc($check)) {
        if ($level === 'admin' || ($level === 'guru' && $m['owner_id'] == $uid)) {
            $path = $m['path'];
            $size = $m['size_bytes'];
            
            if ($tipe === 'link') {
                 $path = mysqli_real_escape_string($koneksi, $_POST['link_url']);
                 $size = 0;
            } else {
                if (isset($_FILES['file']['name']) && $_FILES['file']['error'] === 0) {
                    if ($m['tipe'] !== 'link' && file_exists(dirname(__DIR__,2).'/'.$m['path'])) {
                        unlink(dirname(__DIR__,2).'/'.$m['path']);
                    }
                    
                    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                    $allowed = ['pdf','ppt','pptx','doc','docx','mp4','mov'];
                    if (in_array($ext, $allowed)) {
                        $fname = time().'_'.preg_replace('/[^a-zA-Z0-9\.\-_]/','', $_FILES['file']['name']);
                        $dest = dirname(__DIR__,2).'/assets/uploads/materials/'.$fname;
                        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                            $path = 'assets/uploads/materials/'.$fname;
                            $size = filesize($dest);
                        }
                    }
                }
            }
            
            mysqli_query($koneksi, "UPDATE materials SET judul='$judul', tipe='$tipe', path='$path', size_bytes=$size WHERE id_material=$id");
        }
    }
}
include '../../includes/header.php';
$filterCourse = "";
if ($level === 'guru') { $filterCourse = " WHERE c.pengampu=".$uid; }
$courses = mysqli_query($koneksi, "SELECT c.id_course, c.nama_course, k.nama_kelas, m.nama_mapel FROM courses c JOIN kelas k ON c.id_kelas=k.id_kelas JOIN mapel m ON c.id_mapel=m.id_mapel $filterCourse ORDER BY c.nama_course ASC");
$mat_filter = "";
if ($level === 'siswa') {
    $id_kelas = isset($_SESSION['id_kelas']) ? $_SESSION['id_kelas'] : 0;
    $mat_filter = " WHERE c.id_kelas=".$id_kelas;
}
$materials = mysqli_query($koneksi, "SELECT mt.*, c.nama_course, k.nama_kelas, k.id_kelas, u.nama_lengkap FROM materials mt JOIN courses c ON mt.course_id=c.id_course JOIN kelas k ON c.id_kelas=k.id_kelas JOIN users u ON mt.owner_id=u.id_user $mat_filter ORDER BY mt.created_at DESC");

// Admin specific: Fetch classes and group materials
$kelas_arr = [];
$admin_materials = [];
if ($level === 'admin') {
    $q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
    if ($q_kelas) {
        while($k = mysqli_fetch_assoc($q_kelas)) {
            $kelas_arr[] = $k;
        }
    }
    
    // Group materials by class
    while($mt = mysqli_fetch_assoc($materials)) {
        $admin_materials[$mt['id_kelas']][] = $mt;
    }
    // Reset pointer for other views if needed (though we'll separate logic)
    mysqli_data_seek($materials, 0);
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Materi</h6>
                    <div>
                        <?php if($level === 'guru'): ?>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalMaterial"><i class="fas fa-upload"></i> Tambah Materi</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($level === 'admin'): ?>
                        <?php if (!empty($kelas_arr)): ?>
                            <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                                <?php foreach($kelas_arr as $index => $k): 
                                    $count = isset($admin_materials[$k['id_kelas']]) ? count($admin_materials[$k['id_kelas']]) : 0;
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
                                            <span class="badge bg-white text-primary rounded-pill ms-2"><?php echo $count; ?></span>
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
                                                        <th>Judul</th>
                                                        <th>Jenis</th>
                                                        <th>Ukuran</th>
                                                        <th>Pengunggah</th>
                                                        <th>Tanggal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if(isset($admin_materials[$k['id_kelas']]) && count($admin_materials[$k['id_kelas']]) > 0): ?>
                                                        <?php $no=1; foreach($admin_materials[$k['id_kelas']] as $mt): ?>
                                                        <tr>
                                                            <td><?php echo $no++; ?></td>
                                                            <td><?php echo htmlspecialchars($mt['nama_kelas']); ?></td>
                                                            <td><?php echo htmlspecialchars($mt['nama_course']); ?></td>
                                                            <td><?php echo htmlspecialchars($mt['judul']); ?></td>
                                                            <td><?php echo strtoupper($mt['tipe']); ?></td>
                                                            <td><?php echo $mt['size_bytes'] ? round($mt['size_bytes']/1024,1).' KB' : '-'; ?></td>
                                                            <td><?php echo htmlspecialchars($mt['nama_lengkap']); ?></td>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($mt['created_at'])); ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-info-circle me-1"></i> Tidak ada materi untuk kelas ini.</td></tr>
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
                        <!-- Guru/Siswa View -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-datatable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kelas</th>
                                        <th>Kelas Online</th>
                                        <th>Judul</th>
                                        <th>Jenis</th>
                                        <th>Ukuran</th>
                                        <th>Pengunggah</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no=1; while($mt = mysqli_fetch_assoc($materials)): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($mt['nama_kelas']); ?></td>
                                        <td><?php echo htmlspecialchars($mt['nama_course']); ?></td>
                                        <td><?php echo htmlspecialchars($mt['judul']); ?></td>
                                        <td><?php echo strtoupper($mt['tipe']); ?></td>
                                        <td><?php echo $mt['size_bytes'] ? round($mt['size_bytes']/1024,1).' KB' : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($mt['nama_lengkap']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($mt['created_at'])); ?></td>
                                        <td>
                                            <?php if($mt['tipe']==='link'): ?>
                                            <a href="<?php echo $mt['path']; ?>" target="_blank" class="btn btn-info btn-sm"><i class="fas fa-external-link-alt"></i> Buka</a>
                                            <?php else: ?>
                                            <a href="<?php echo '../../'.$mt['path']; ?>" target="_blank" class="btn btn-info btn-sm"><i class="fas fa-download"></i> Unduh</a>
                                            <?php endif; ?>
                                            
                                            <?php if($level === 'admin' || ($level === 'guru' && $mt['owner_id'] == $uid)): ?>
                                            <button class="btn btn-warning btn-sm btn-edit" 
                                                data-id="<?php echo $mt['id_material']; ?>"
                                                data-judul="<?php echo htmlspecialchars($mt['judul']); ?>"
                                                data-tipe="<?php echo $mt['tipe']; ?>"
                                                data-path="<?php echo htmlspecialchars($mt['path']); ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalEditMaterial">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus?');">
                                                <input type="hidden" name="id_material" value="<?php echo $mt['id_material']; ?>">
                                                <button type="submit" name="delete_material" value="1" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if($level === 'guru'): ?>
<div class="modal fade" id="modalMaterial" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Materi</h5>
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
            <label class="form-label">Jenis</label>
            <select name="tipe" class="form-select" id="tipeSelect" required>
                <option value="pdf">PDF</option>
                <option value="ppt">PPT</option>
                <option value="doc">DOC</option>
                <option value="video">Video</option>
                <option value="link">Tautan</option>
            </select>
        </div>
        <div class="mb-2" id="fileInputWrap">
            <label class="form-label">File</label>
            <input type="file" name="file" class="form-control">
        </div>
        <div class="mb-2 d-none" id="linkInputWrap">
            <label class="form-label">URL</label>
            <input type="url" name="link_url" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="create_material" value="1" class="btn btn-success">Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var sel = document.getElementById('tipeSelect');
    var fileWrap = document.getElementById('fileInputWrap');
    var linkWrap = document.getElementById('linkInputWrap');
    sel.addEventListener('change', function(){
        if (sel.value === 'link') {
            fileWrap.classList.add('d-none');
            linkWrap.classList.remove('d-none');
        } else {
            fileWrap.classList.remove('d-none');
            linkWrap.classList.add('d-none');
        }
    });
});
</script>

<div class="modal fade" id="modalEditMaterial" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <input type="hidden" name="id_material" id="edit_id_material">
      <div class="modal-header">
        <h5 class="modal-title">Edit Materi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
            <label class="form-label">Judul</label>
            <input type="text" name="judul" id="edit_judul" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Jenis</label>
            <select name="tipe" class="form-select" id="edit_tipe" required>
                <option value="pdf">PDF</option>
                <option value="ppt">PPT</option>
                <option value="doc">DOC</option>
                <option value="video">Video</option>
                <option value="link">Tautan</option>
            </select>
        </div>
        <div class="mb-2" id="edit_fileWrap">
            <label class="form-label">File (Biarkan kosong jika tidak diubah)</label>
            <input type="file" name="file" class="form-control">
            <small class="text-muted" id="current_file_info"></small>
        </div>
        <div class="mb-2 d-none" id="edit_linkWrap">
            <label class="form-label">URL</label>
            <input type="url" name="link_url" id="edit_link_url" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="edit_material" value="1" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
var editSel = document.getElementById('edit_tipe');
var editFileWrap = document.getElementById('edit_fileWrap');
var editLinkWrap = document.getElementById('edit_linkWrap');

if(editSel) {
    editSel.addEventListener('change', function(){
        if (editSel.value === 'link') {
            editFileWrap.classList.add('d-none');
            editLinkWrap.classList.remove('d-none');
        } else {
            editFileWrap.classList.remove('d-none');
            editLinkWrap.classList.add('d-none');
        }
    });
}

var editButtons = document.querySelectorAll('.btn-edit');
editButtons.forEach(function(btn){
    btn.addEventListener('click', function(){
        var id = this.getAttribute('data-id');
        var judul = this.getAttribute('data-judul');
        var tipe = this.getAttribute('data-tipe');
        var path = this.getAttribute('data-path');
        
        document.getElementById('edit_id_material').value = id;
        document.getElementById('edit_judul').value = judul;
        document.getElementById('edit_tipe').value = tipe;
        
        if (tipe === 'link') {
            document.getElementById('edit_link_url').value = path;
            editFileWrap.classList.add('d-none');
            editLinkWrap.classList.remove('d-none');
        } else {
            editFileWrap.classList.remove('d-none');
            editLinkWrap.classList.add('d-none');
        }
        
        // Trigger change event manually if needed, or rely on above logic
        // But setting .value doesn't trigger 'change' automatically
    });
});
</script>
<?php endif; ?>
<?php include '../../includes/footer.php'; ?>
