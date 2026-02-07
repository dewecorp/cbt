<?php
include '../../config/database.php';
$page_title = 'Pengaturan Sekolah';
include '../../includes/header.php';

// Cek Level Admin
if ($_SESSION['level'] != 'admin') {
    echo "<script>window.location='../../dashboard.php';</script>";
    exit;
}

// Get Data Setting
$query = mysqli_query($koneksi, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($query);

// Ensure column for admin welcome text exists
$col_check = mysqli_query($koneksi, "SHOW COLUMNS FROM setting LIKE 'admin_welcome_text'");
if (mysqli_num_rows($col_check) == 0) {
    mysqli_query($koneksi, "ALTER TABLE setting ADD COLUMN admin_welcome_text TEXT NULL");
}

// Ensure columns for submissions exist
$col_check_sub = mysqli_query($koneksi, "SHOW COLUMNS FROM submissions LIKE 'file_provider'");
if (mysqli_num_rows($col_check_sub) == 0) {
    mysqli_query($koneksi, "ALTER TABLE submissions ADD COLUMN file_provider VARCHAR(20) DEFAULT 'local'");
    mysqli_query($koneksi, "ALTER TABLE submissions ADD COLUMN file_id VARCHAR(255) NULL");
    mysqli_query($koneksi, "ALTER TABLE submissions ADD COLUMN file_link TEXT NULL");
}

// Handle Update
if (isset($_POST['simpan'])) {
    $nama_sekolah = mysqli_real_escape_string($koneksi, $_POST['nama_sekolah']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $tahun_ajaran = mysqli_real_escape_string($koneksi, $_POST['tahun_ajaran']);
    $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
    $kepala_madrasah = mysqli_real_escape_string($koneksi, $_POST['kepala_madrasah']);
    $nip_kepala = mysqli_real_escape_string($koneksi, $_POST['nip_kepala']);
    $panitia_ujian = mysqli_real_escape_string($koneksi, $_POST['panitia_ujian']);
    $admin_welcome_text = mysqli_real_escape_string($koneksi, $_POST['admin_welcome_text']);
    
    $logo_sql = "";
    
    // Handle Logo Upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['logo']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $new_filename = 'logo_' . time() . '.' . $ext;
            $destination = '../../assets/img/' . $new_filename;
            
            // Ensure directory exists
            if (!file_exists('../../assets/img/')) {
                mkdir('../../assets/img/', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                // Delete old logo if exists
                if (!empty($setting['logo']) && file_exists('../../assets/img/' . $setting['logo'])) {
                    unlink('../../assets/img/' . $setting['logo']);
                }
                $logo_sql = ", logo='$new_filename'";
            }
        } else {
            echo "<script>Swal.fire('Error', 'Format file tidak diizinkan. Gunakan JPG, JPEG, atau PNG', 'error');</script>";
        }
    }
    
    if ($setting) {
        $q_update = "UPDATE setting SET 
            nama_sekolah='$nama_sekolah', 
            alamat='$alamat',
            tahun_ajaran='$tahun_ajaran',
            semester='$semester',
            kepala_madrasah='$kepala_madrasah',
            nip_kepala='$nip_kepala',
            panitia_ujian='$panitia_ujian',
            admin_welcome_text='$admin_welcome_text'
            $logo_sql
            WHERE id='".$setting['id']."'";
    } else {
        // If no setting exists, insert new
        $logo_val = isset($new_filename) ? $new_filename : '';
        $q_update = "INSERT INTO setting (nama_sekolah, alamat, tahun_ajaran, semester, kepala_madrasah, nip_kepala, panitia_ujian, logo, admin_welcome_text) 
            VALUES ('$nama_sekolah', '$alamat', '$tahun_ajaran', '$semester', '$kepala_madrasah', '$nip_kepala', '$panitia_ujian', '$logo_val', '$admin_welcome_text')";
    }
    
    if (mysqli_query($koneksi, $q_update)) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Pengaturan berhasil disimpan',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'index.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
    }
}
// Handle Year Reset
elseif (isset($_POST['reset_tahun_ajaran'])) {
    // Only admin
    if ($_SESSION['level'] != 'admin') {
        echo "<script>window.location='../../dashboard.php';</script>";
        exit;
    }
    // Delete submissions files
    $upload_dir = '../../assets/uploads/submissions/';
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = $upload_dir . $f;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
    // Delete forum files
    $forum_dir = '../../assets/uploads/forum/';
    if (is_dir($forum_dir)) {
        $files = scandir($forum_dir);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = $forum_dir . $f;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
    // Clear database records
    mysqli_query($koneksi, "DELETE FROM submissions");
    mysqli_query($koneksi, "DELETE FROM assignments");
    mysqli_query($koneksi, "DELETE FROM absensi");
    mysqli_query($koneksi, "DELETE FROM forum_topics");
    mysqli_query($koneksi, "DELETE FROM forum_replies");
    mysqli_query($koneksi, "DELETE FROM forum_likes");
    mysqli_query($koneksi, "DELETE FROM forum_reply_likes");
    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Reset Berhasil',
            text: 'Semua data (tugas, absensi, forum) telah dihapus untuk memulai tahun ajaran baru.',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            window.location.href = 'index.php';
        });
    </script>";
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Pengaturan Sistem</h1>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Identitas Sekolah</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Sekolah / Madrasah</label>
                                <input type="text" class="form-control" name="nama_sekolah" value="<?php echo isset($setting['nama_sekolah']) ? $setting['nama_sekolah'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Logo Sekolah</label>
                                <input type="file" class="form-control" name="logo">
                                <?php if(isset($setting['logo']) && !empty($setting['logo'])): ?>
                                    <div class="mt-2">
                                        <img src="../../assets/img/<?php echo $setting['logo']; ?>" alt="Logo" style="max-height: 50px;">
                                        <small class="text-muted d-block">Logo saat ini</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Sekolah</label>
                            <textarea class="form-control" name="alamat" rows="2"><?php echo isset($setting['alamat']) ? $setting['alamat'] : ''; ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tahun Ajaran</label>
                                <input type="text" class="form-control" name="tahun_ajaran" placeholder="Contoh: 2023/2024" value="<?php echo isset($setting['tahun_ajaran']) ? $setting['tahun_ajaran'] : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Semester</label>
                                <select class="form-select" name="semester">
                                    <option value="1" <?php echo (isset($setting['semester']) && $setting['semester'] == '1') ? 'selected' : ''; ?>>Semester 1 (Ganjil)</option>
                                    <option value="2" <?php echo (isset($setting['semester']) && $setting['semester'] == '2') ? 'selected' : ''; ?>>Semester 2 (Genap)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kepala Madrasah</label>
                                <input type="text" class="form-control" name="kepala_madrasah" value="<?php echo isset($setting['kepala_madrasah']) ? $setting['kepala_madrasah'] : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">NIP Kepala Madrasah</label>
                                <input type="text" class="form-control" name="nip_kepala" value="<?php echo isset($setting['nip_kepala']) ? $setting['nip_kepala'] : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Panitia Ujian</label>
                            <input type="text" class="form-control" name="panitia_ujian" value="<?php echo isset($setting['panitia_ujian']) ? $setting['panitia_ujian'] : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Teks Selamat Datang Dashboard Admin</label>
                            <textarea class="form-control" id="admin_welcome_text" name="admin_welcome_text" rows="6"><?php echo isset($setting['admin_welcome_text']) ? $setting['admin_welcome_text'] : 'Aplikasi Computer Based Test (CBT) ini dirancang untuk memudahkan pelaksanaan ujian di MI Sultan Fattah Sukosono. Silahkan gunakan menu di samping untuk mengelola data dan ujian.'; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <button type="submit" name="simpan" class="btn btn-success"><i class="fas fa-save"></i> Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card Reset Tahun Ajaran -->
            <div class="card shadow mb-4 border-start border-danger border-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger"><i class="fas fa-trash-alt"></i> Reset Tahun Ajaran</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i> Fitur ini akan menghapus semua <b>tugas, pengumpulan tugas, data absensi, dan data forum</b> untuk memulai tahun ajaran baru.
                    </div>
                    <form method="POST" action="">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirm_reset" required>
                            <label class="form-check-label text-danger fw-bold" for="confirm_reset">Saya memahami konsekuensinya dan ingin melanjutkan reset.</label>
                        </div>
                        <button type="submit" name="reset_tahun_ajaran" class="btn btn-danger" onclick="return confirm('Hapus semua data (tugas, absensi, forum)? Tindakan ini tidak dapat dibatalkan.');">
                            <i class="fas fa-exclamation-triangle"></i> Reset Tahun Ajaran (Hapus Semua Data)
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
<style>
/* Sembunyikan notifikasi upgrade CKEditor */
.cke_notifications_area, .cke_notification { display: none !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var el = document.getElementById('admin_welcome_text');
    if (el && window.CKEDITOR && CKEDITOR.replace) {
        CKEDITOR.replace('admin_welcome_text', {
            removePlugins: 'notification,notificationaggregator'
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
