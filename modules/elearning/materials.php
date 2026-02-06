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
                                                        <th>Aksi</th>
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
                                                            <td>
                                                                <button class="btn btn-info btn-sm btn-preview" 
                                                                    data-id="<?php echo $mt['id_material']; ?>"
                                                                    data-tipe="<?php echo $mt['tipe']; ?>"
                                                                    data-path="<?php echo ($mt['tipe']=='link') ? $mt['path'] : '../../'.$mt['path']; ?>"
                                                                    data-judul="<?php echo htmlspecialchars($mt['judul']); ?>">
                                                                    <i class="fas fa-eye"></i> Lihat
                                                                </button>
                                                                <?php if($mt['tipe'] == 'link'): ?>
                                                                    <a href="<?php echo $mt['path']; ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-external-link-alt"></i> Buka</a>
                                                                <?php else: ?>
                                                                    <a href="../../<?php echo $mt['path']; ?>" download class="btn btn-success btn-sm"><i class="fas fa-download"></i> Unduh</a>
                                                                <?php endif; ?>
                                                            </td>
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
                                            <button class="btn btn-info btn-sm btn-preview" 
                                                data-id="<?php echo $mt['id_material']; ?>"
                                                data-tipe="<?php echo $mt['tipe']; ?>"
                                                data-path="<?php echo ($mt['tipe']=='link') ? $mt['path'] : '../../'.$mt['path']; ?>"
                                                data-judul="<?php echo htmlspecialchars($mt['judul']); ?>">
                                                <i class="fas fa-eye"></i> Preview
                                            </button>
                                            
                                            <?php if($mt['tipe'] == 'link'): ?>
                                                <a href="<?php echo $mt['path']; ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-external-link-alt"></i> Buka</a>
                                            <?php else: ?>
                                                <a href="../../<?php echo $mt['path']; ?>" download class="btn btn-success btn-sm"><i class="fas fa-download"></i> Unduh</a>
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

<!-- Modal Preview (Global) -->
<div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="previewTitle">Preview Materi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="min-height: 500px; background-color: #525659;">
        <div id="previewContainer" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 500px;">
            <!-- Content will be loaded here -->
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" id="btnDownloadOriginal" target="_blank" class="btn btn-primary"><i class="fas fa-download"></i> Download / Buka Asli</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- PDF.js Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
// Set worker for PDF.js
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

document.addEventListener('DOMContentLoaded', function(){
    var modalPreview = new bootstrap.Modal(document.getElementById('modalPreview'));
    var previewContainer = document.getElementById('previewContainer');
    var previewTitle = document.getElementById('previewTitle');
    var btnDownload = document.getElementById('btnDownloadOriginal');

    document.querySelectorAll('.btn-preview').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            var id = this.getAttribute('data-id');
            var tipe = this.getAttribute('data-tipe');
            var path = this.getAttribute('data-path');
            var judul = this.getAttribute('data-judul');

            previewTitle.textContent = judul;
            btnDownload.href = path;
            
            // Reset container
            previewContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100 p-5"><div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            previewContainer.style.backgroundColor = (tipe === 'pdf') ? '#525659' : '#f8f9fa';
            
            modalPreview.show();

            setTimeout(function(){
                var content = '';
                if (tipe === 'pdf') {
                    // Use Proxy Script via fetch to bypass IDM
                    var proxyUrl = 'get_material_content.php?id=' + id;
                    renderPDF(proxyUrl, previewContainer);
                } else if (tipe === 'video') {
                    content = '<video controls width="100%" style="max-height:600px;"><source src="' + path + '" type="video/mp4">Browser anda tidak mendukung tag video.</video>';
                    previewContainer.innerHTML = content;
                } else if (tipe === 'link') {
                     var fullUrl = path;
                     if (!path.startsWith('http')) {
                         fullUrl = 'http://' + path;
                     }
                     
                     // Detect YouTube and convert to embed
                     var youtubeRegExp = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
                     var ytMatch = fullUrl.match(youtubeRegExp);
                     
                     if (ytMatch && ytMatch[1]) {
                         fullUrl = 'https://www.youtube.com/embed/' + ytMatch[1];
                         content = '<iframe src="' + fullUrl + '" width="100%" height="600px" style="border:none;" allowfullscreen></iframe>';
                     } else {
                         // Use Proxy URL to bypass X-Frame-Options
                         var proxyUrl = 'proxy_url.php?url=' + encodeURIComponent(fullUrl);
                         content = '<div class="alert alert-info text-center m-2 p-2" style="font-size:0.9rem;">Menampilkan via Proxy Mode. <a href="' + fullUrl + '" target="_blank" class="fw-bold text-decoration-underline">Buka Link Asli</a> jika tampilan berantakan.</div>';
                         content += '<iframe src="' + proxyUrl + '" width="100%" height="600px" style="border:none;" sandbox="allow-forms allow-scripts allow-same-origin allow-popups"></iframe>';
                     }
                     
                     previewContainer.innerHTML = content;
                } else if (tipe === 'doc' || tipe === 'docx' || tipe === 'ppt' || tipe === 'pptx') {
                    var fullUrl = path;
                    if (!path.startsWith('http')) {
                        // Warning: Office Viewer needs public URL. Localhost won't work.
                        // We will try anyway, but likely fail on localhost.
                         var a = document.createElement('a');
                         a.href = path;
                         fullUrl = a.href;
                    }
                    content = '<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(fullUrl) + '" width="100%" height="600px" style="border:none;"></iframe>';
                    previewContainer.innerHTML = content;
                } else {
                    content = '<div class="text-center p-5">Format tidak didukung untuk preview. Silakan unduh file.</div>';
                    previewContainer.innerHTML = content;
                }
            }, 500);
        });
    });
    
    // Clear content when modal is hidden
    document.getElementById('modalPreview').addEventListener('hidden.bs.modal', function () {
        previewContainer.innerHTML = '';
    });

    function renderPDF(url, container) {
        container.innerHTML = ''; // Clear spinner
        
        // Use Base64 mode to completely bypass IDM interception
        fetch(url + '&mode=base64')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(base64 => {
            // Decode Base64 to Uint8Array
            var binary_string = window.atob(base64);
            var len = binary_string.length;
            var bytes = new Uint8Array(len);
            for (var i = 0; i < len; i++) {
                bytes[i] = binary_string.charCodeAt(i);
            }
            
            var loadingTask = pdfjsLib.getDocument({data: bytes});
            loadingTask.promise.then(function(pdf) {
                // Loop through all pages
                for (var pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                    pdf.getPage(pageNum).then(function(page) {
                        var scale = 1.5;
                        var viewport = page.getViewport({scale: scale});

                        var canvas = document.createElement('canvas');
                        canvas.className = 'mb-3 shadow-sm';
                        var context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        
                        // Center canvas
                        canvas.style.maxWidth = '100%';
                        canvas.style.height = 'auto';

                        container.appendChild(canvas);

                        var renderContext = {
                            canvasContext: context,
                            viewport: viewport
                        };
                        page.render(renderContext);
                    });
                }
            }, function (reason) {
                // PDF loading error
                console.error(reason);
                container.innerHTML = '<div class="text-center text-white p-5">Gagal memuat preview PDF (Corrupt atau Protected).<br><a href="'+url+'" target="_blank" class="btn btn-light mt-3">Download PDF</a></div>';
            });
        })
        .catch(error => {
            console.error('Fetch error:', error);
            container.innerHTML = '<div class="text-center text-white p-5">Gagal mengambil data PDF.<br><a href="#" class="btn btn-light mt-3">Coba Lagi</a></div>';
        });
    }
});
</script>
