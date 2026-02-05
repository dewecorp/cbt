<?php
session_start();
include '../../config/database.php';

if (!isset($_SESSION['level']) || ($_SESSION['level'] !== 'guru' && $_SESSION['level'] !== 'admin')) {
    header("Location: ../../login.php");
    exit;
}

$page_title = 'Pengumpulan Tugas';
$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$uid = $_SESSION['user_id'];
$level = $_SESSION['level'];

if ($assignment_id <= 0) {
    header("Location: assignments.php");
    exit;
}

// Verify assignment ownership
$q_assign = mysqli_query($koneksi, "
    SELECT a.*, c.nama_course, k.nama_kelas, m.nama_mapel 
    FROM assignments a 
    JOIN courses c ON a.course_id = c.id_course 
    JOIN kelas k ON c.id_kelas = k.id_kelas
    JOIN mapel m ON c.id_mapel = m.id_mapel
    WHERE a.id_assignment = '$assignment_id'
");
$assignment = mysqli_fetch_assoc($q_assign);

if (!$assignment) {
    echo "Tugas tidak ditemukan.";
    exit;
}

if ($level === 'guru' && $assignment['created_by'] != $uid) {
    echo "Anda tidak memiliki akses ke tugas ini.";
    exit;
}

// Handle Grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grade'])) {
    $sub_id = (int)$_POST['submission_id'];
    $nilai = (float)$_POST['nilai'];
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan']);
    
    mysqli_query($koneksi, "UPDATE submissions SET nilai='$nilai', catatan='$catatan' WHERE id_submission='$sub_id'");
    $_SESSION['success'] = "Nilai berhasil disimpan.";
    header("Location: submissions.php?assignment_id=$assignment_id");
    exit;
}

include '../../includes/header.php';

// Fetch Submissions
$q_subs = mysqli_query($koneksi, "
    SELECT s.*, sw.nama_siswa, sw.nisn 
    FROM submissions s 
    JOIN siswa sw ON s.siswa_id = sw.id_siswa 
    WHERE s.assignment_id = '$assignment_id' 
    ORDER BY s.submitted_at ASC
");

// Fetch All Students in Class (to see who hasn't submitted)
$q_students = mysqli_query($koneksi, "
    SELECT sw.id_siswa, sw.nama_siswa, sw.nisn 
    FROM siswa sw 
    WHERE sw.id_kelas = '".$assignment['id_kelas']."' AND sw.status='aktif'
    ORDER BY sw.nama_siswa ASC
");
$all_students = [];
while($r = mysqli_fetch_assoc($q_students)) {
    $all_students[$r['id_siswa']] = $r;
}

// Map submissions
$submitted_data = [];
while($sub = mysqli_fetch_assoc($q_subs)) {
    $submitted_data[$sub['siswa_id']] = $sub;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Pengumpulan Tugas</h1>
            <p class="mb-0 text-muted">
                <?php echo htmlspecialchars($assignment['judul']); ?> 
                (<?php echo htmlspecialchars($assignment['nama_kelas']); ?> - <?php echo htmlspecialchars($assignment['nama_mapel']); ?>)
            </p>
        </div>
        <div>
            <a href="assignments.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Pengumpulan Siswa</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th width="50">No</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Status</th>
                            <th>Waktu Kirim</th>
                            <th>File</th>
                            <th>Nilai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach($all_students as $sid => $student): 
                            $sub = isset($submitted_data[$sid]) ? $submitted_data[$sid] : null;
                            $is_late = $sub && strtotime($sub['submitted_at']) > strtotime($assignment['deadline']);
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($student['nisn']); ?></td>
                            <td><?php echo htmlspecialchars($student['nama_siswa']); ?></td>
                            <td>
                                <?php if($sub): ?>
                                    <span class="badge bg-success">Sudah Mengumpulkan</span>
                                    <?php if($is_late): ?>
                                        <span class="badge bg-warning text-dark">Terlambat</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-danger">Belum Mengumpulkan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $sub ? date('d/m/Y H:i', strtotime($sub['submitted_at'])) : '-'; ?>
                            </td>
                            <td>
                                <?php if($sub && $sub['file_path']): ?>
                                    <a href="../../<?php echo $sub['file_path']; ?>" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fas fa-download"></i> Unduh
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($sub): ?>
                                    <span class="fw-bold text-primary"><?php echo $sub['nilai'] > 0 ? $sub['nilai'] : '-'; ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($sub): ?>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGrade<?php echo $sub['id_submission']; ?>">
                                        <i class="fas fa-marker"></i> Nilai
                                    </button>

                                    <!-- Modal Grade -->
                                    <div class="modal fade" id="modalGrade<?php echo $sub['id_submission']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <form method="post" class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Beri Nilai: <?php echo htmlspecialchars($student['nama_siswa']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="submission_id" value="<?php echo $sub['id_submission']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nilai (0-100)</label>
                                                        <input type="number" name="nilai" class="form-control" min="0" max="100" step="0.01" value="<?php echo $sub['nilai']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Catatan Guru</label>
                                                        <textarea name="catatan" class="form-control" rows="3"><?php echo htmlspecialchars($sub['catatan']); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" name="update_grade" class="btn btn-primary">Simpan Nilai</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
