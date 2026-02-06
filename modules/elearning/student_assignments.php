<?php
session_start();
include '../../config/database.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'siswa') {
    header("Location: ../../index.php");
    exit;
}

$page_title = 'Kirim Tugas';
$id_siswa = $_SESSION['user_id'];
$id_kelas = $_SESSION['id_kelas'];

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    $assignment_id = (int)$_POST['assignment_id'];
    
    // Check if assignment belongs to student's class
    $check = mysqli_query($koneksi, "
        SELECT a.id_assignment 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id_course 
        WHERE a.id_assignment = '$assignment_id' AND c.id_kelas = '$id_kelas'
    ");
    
    if (mysqli_num_rows($check) > 0) {
        // File Upload
        if (isset($_FILES['file_tugas']) && $_FILES['file_tugas']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['file_tugas']['tmp_name'];
            $file_name = $_FILES['file_tugas']['name'];
            $file_size = $_FILES['file_tugas']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'mp4', 'mkv', 'avi'];
            
            if (in_array($file_ext, $allowed)) {
                if ($file_size <= 50 * 1024 * 1024) { // 50MB limit
                    $upload_dir = '../../assets/uploads/submissions/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    $new_name = 'sub_' . $assignment_id . '_' . $id_siswa . '_' . time() . '.' . $file_ext;
                    $dest = $upload_dir . $new_name;
                    
                    if (move_uploaded_file($file_tmp, $dest)) {
                        $db_path = 'assets/uploads/submissions/' . $new_name;
                        
                        // Check if already submitted (update or insert)
                        $check_sub = mysqli_query($koneksi, "SELECT id_submission FROM submissions WHERE assignment_id='$assignment_id' AND siswa_id='$id_siswa'");
                        
                        if (mysqli_num_rows($check_sub) > 0) {
                            $row = mysqli_fetch_assoc($check_sub);
                            $sid = $row['id_submission'];
                            // Update
                            mysqli_query($koneksi, "UPDATE submissions SET file_path='$db_path', submitted_at=NOW() WHERE id_submission='$sid'");
                        } else {
                            // Insert
                            mysqli_query($koneksi, "INSERT INTO submissions (assignment_id, siswa_id, file_path) VALUES ('$assignment_id', '$id_siswa', '$db_path')");
                        }
                        
                        $_SESSION['success'] = "Tugas berhasil dikirim!";
                    } else {
                        $_SESSION['error'] = "Gagal mengunggah file.";
                    }
                } else {
                    $_SESSION['error'] = "Ukuran file terlalu besar (Maks 50MB).";
                }
            } else {
                $_SESSION['error'] = "Format file tidak diizinkan. Gunakan JPG, PNG, PDF, DOC, TXT, atau Video.";
            }
        } else {
            $_SESSION['error'] = "Pilih file terlebih dahulu.";
        }
    } else {
        $_SESSION['error'] = "Tugas tidak ditemukan atau tidak akses.";
    }
    
    header("Location: student_assignments.php");
    exit;
}

include '../../includes/header.php';

// Fetch Assignments
$query = "
    SELECT a.*, c.nama_course, m.nama_mapel, u.nama_lengkap as nama_guru,
           s.file_path as submitted_file,
           s.submitted_at,
           s.nilai,
           s.catatan
    FROM assignments a
    JOIN courses c ON a.course_id = c.id_course
    JOIN mapel m ON c.id_mapel = m.id_mapel
    JOIN users u ON a.created_by = u.id_user
    LEFT JOIN submissions s ON a.id_assignment = s.assignment_id AND s.siswa_id = '$id_siswa'
    WHERE c.id_kelas = '$id_kelas'
    ORDER BY a.deadline DESC
";
$assignments = mysqli_query($koneksi, $query);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h3 mb-0 text-gray-800">Kirim Tugas</h1>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Berhasil!',
                    text: '<?php echo $_SESSION['success']; ?>',
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Gagal!',
                    text: '<?php echo $_SESSION['error']; ?>',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row">
        <?php if(mysqli_num_rows($assignments) > 0): ?>
            <?php while($a = mysqli_fetch_assoc($assignments)): 
                $is_expired = strtotime($a['deadline']) < time();
                $is_submitted = !empty($a['submitted_file']);
                $status_class = $is_submitted ? 'success' : ($is_expired ? 'danger' : 'warning');
                $status_text = $is_submitted ? 'Sudah Dikirim' : ($is_expired ? 'Terlewat' : 'Belum Dikirim');
            ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow h-100 border-start border-4 border-<?php echo $status_class; ?>">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($a['nama_mapel']); ?> - <?php echo htmlspecialchars($a['nama_course']); ?></h6>
                        <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </div>
                    <div class="card-body">
                        <h5 class="font-weight-bold"><?php echo htmlspecialchars($a['judul']); ?></h5>
                        <div class="text-muted small mb-3">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($a['nama_guru']); ?> &bull; 
                            <i class="far fa-calendar-alt me-1"></i> Tenggat: <?php echo date('d M Y, H:i', strtotime($a['deadline'])); ?>
                        </div>
                        
                        <div class="p-3 bg-light rounded mb-3 border">
                            <?php echo nl2br(htmlspecialchars($a['deskripsi'])); ?>
                        </div>

                        <?php if($is_submitted): ?>
                            <div class="card bg-success bg-opacity-10 border border-success p-3 mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                    <div class="w-100">
                                        <div class="text-success fw-bold">
                                            Tugas telah dikirim pada <?php echo date('d M Y H:i', strtotime($a['submitted_at'])); ?>
                                        </div>
                                        <div class="mt-2">
                                            <a href="../../<?php echo $a['submitted_file']; ?>" target="_blank" class="btn btn-sm btn-light border text-primary"><i class="fas fa-paperclip"></i> Lihat File Saya</a>
                                        </div>
                                
                                        <?php if(isset($a['nilai']) && $a['nilai'] !== null && $a['nilai'] != ''): ?>
                                            <div class="mt-3 p-3 bg-white border rounded shadow-sm">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="h2 fw-bold text-primary me-3"><?php echo floatval($a['nilai']); ?></div>
                                                    <div>
                                                        <div class="small text-uppercase text-muted fw-bold">Nilai Tugas</div>
                                                        <div class="small text-muted">Dinilai oleh <?php echo htmlspecialchars($a['nama_guru']); ?></div>
                                                    </div>
                                                </div>
                                                <?php if(!empty($a['catatan'])): ?>
                                                    <div class="border-top pt-2 mt-2">
                                                        <div class="small fw-bold text-dark mb-1"><i class="fas fa-comment-alt me-1 text-secondary"></i> Catatan Guru:</div>
                                                        <div class="small text-muted fst-italic bg-light p-2 rounded">
                                                            "<?php echo nl2br(htmlspecialchars($a['catatan'])); ?>"
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <?php if($a['jenis_tugas'] === 'CBT'): ?>
                                <a href="../../index.php" class="btn btn-primary">
                                    <i class="fas fa-laptop-code me-1"></i> Buka Dashboard CBT
                                </a>
                            <?php else: ?>
                                <?php if(!$is_expired || $is_submitted): ?>
                                    <button class="btn btn-<?php echo $is_submitted ? 'warning' : 'primary'; ?>" data-bs-toggle="modal" data-bs-target="#modalSubmit<?php echo $a['id_assignment']; ?>">
                                        <i class="fas fa-paper-plane me-1"></i> <?php echo $is_submitted ? 'Kirim Ulang / Edit' : 'Kirim Tugas'; ?>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>Waktu Habis</button>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <a href="course_manage.php?course_id=<?php echo $a['course_id']; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Kembali ke Kelas
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Modal Submit -->
                <div class="modal fade" id="modalSubmit<?php echo $a['id_assignment']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="post" enctype="multipart/form-data" class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Kirim Tugas: <?php echo htmlspecialchars($a['judul']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="assignment_id" value="<?php echo $a['id_assignment']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Upload File Tugas</label>
                                    <input type="file" name="file_tugas" class="form-control" required>
                                    <div class="form-text">Format: Gambar, PDF, Word, Teks, Video. Maks 50MB.</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" name="submit_assignment" class="btn btn-primary">Kirim</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-tasks fa-4x text-gray-300 mb-3"></i>
                <p class="text-muted">Belum ada tugas untuk kelas Anda.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
