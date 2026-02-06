<?php
session_start();
include '../../config/database.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'siswa') {
    header("Location: ../../index.php");
    exit;
}

$page_title = 'Nilai Tugas';
$id_siswa = $_SESSION['user_id'];
$id_kelas = $_SESSION['id_kelas'];

include '../../includes/header.php';

// Fetch Assignments & Grades
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
    WHERE c.id_kelas = '$id_kelas' AND s.submitted_at IS NOT NULL
    ORDER BY s.submitted_at DESC
";
$grades = mysqli_query($koneksi, $query);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h3 mb-0 text-gray-800">Nilai Tugas</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Rekap Nilai Tugas Saya</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-datatable" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Mata Pelajaran</th>
                            <th>Judul Tugas</th>
                            <th>Dikirim Pada</th>
                            <th>Nilai</th>
                            <th>Catatan Guru</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php while($row = mysqli_fetch_assoc($grades)): ?>
                            <tr>
                                <td class="text-center" width="5%"><?php echo $no++; ?></td>
                                <td>
                                    <span class="fw-bold"><?php echo htmlspecialchars($row['nama_mapel']); ?></span><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['nama_course']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['submitted_at'])); ?></td>
                                <td class="text-center">
                                    <?php if(isset($row['nilai']) && $row['nilai'] !== null && $row['nilai'] != ''): ?>
                                        <span class="badge bg-success fs-6"><?php echo floatval($row['nilai']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Belum Dinilai</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!empty($row['catatan'])): ?>
                                        <i class="fas fa-comment-alt text-warning me-1"></i> <?php echo htmlspecialchars($row['catatan']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="../../<?php echo $row['submitted_file']; ?>" target="_blank" class="btn btn-sm btn-info text-white" title="Lihat File Saya">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
