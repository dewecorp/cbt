<?php
session_start();
include '../../config/database.php';
$page_title = 'Rekap Kehadiran Siswa';

// Access Control
if (!isset($_SESSION['level']) || $_SESSION['level'] == 'siswa') {
    echo '<script>window.location.href="../../index.php";</script>';
    exit;
}

include '../../includes/header.php';

// Filter Variables
$bulan = isset($_GET['bulan']) ? sprintf('%02d', $_GET['bulan']) : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '';

// Month Names
$month_names = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Get Classes
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$level = isset($_SESSION['level']) ? $_SESSION['level'] : '';

if ($level === 'guru') {
    // Only classes taught by this teacher
    $q_kelas = mysqli_query($koneksi, "
        SELECT DISTINCT k.* 
        FROM kelas k
        JOIN courses c ON k.id_kelas = c.id_kelas
        WHERE c.pengampu = '$uid'
        ORDER BY k.nama_kelas ASC
    ");
} else {
    // Admin sees all
    $q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
}

// Auto-select if only one class
if (empty($id_kelas) && mysqli_num_rows($q_kelas) == 1) {
    $r_first = mysqli_fetch_assoc($q_kelas);
    $id_kelas = $r_first['id_kelas'];
    // Reset pointer
    mysqli_data_seek($q_kelas, 0);
}

// Data Processing
$students = [];
$attendance_data = [];
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$nama_kelas_selected = '';

if (!empty($id_kelas)) {
    // Get Class Name
    $q_k_name = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id_kelas='$id_kelas'");
    if ($r_k = mysqli_fetch_assoc($q_k_name)) {
        $nama_kelas_selected = $r_k['nama_kelas'];
    }

    // Get Students
    $q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_kelas='$id_kelas' AND status='aktif' ORDER BY nama_siswa ASC");
    while($r = mysqli_fetch_assoc($q_siswa)) {
        $students[] = $r;
    }

    // Get Attendance (General Attendance: id_course=0 or NULL)
    $start_date = "$tahun-$bulan-01";
    $end_date = "$tahun-$bulan-$days_in_month";
    
    $q_att = mysqli_query($koneksi, "
        SELECT a.id_siswa, DAY(a.tanggal) as day, a.status 
        FROM absensi a 
        JOIN siswa s ON a.id_siswa = s.id_siswa
        WHERE s.id_kelas = '$id_kelas' 
        AND (a.id_course = '0' OR a.id_course IS NULL)
        AND a.tanggal BETWEEN '$start_date' AND '$end_date'
    ");
    
    while($row = mysqli_fetch_assoc($q_att)) {
        // Map status to code or full status
        // H: Hadir, S: Sakit, I: Izin, A: Alpha
        $code = '-';
        if ($row['status'] == 'Hadir') $code = 'H';
        elseif ($row['status'] == 'Sakit') $code = 'S';
        elseif ($row['status'] == 'Izin') $code = 'I';
        elseif ($row['status'] == 'Alpha') $code = 'A';
        
        $attendance_data[$row['id_siswa']][$row['day']] = $code;
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
        <h1 class="h3 mb-0 text-gray-800">Rekap Kehadiran Siswa</h1>
    </div>

    <!-- Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Data</h6>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Pilih Kelas</label>
                    <select name="id_kelas" class="form-select" onchange="this.form.submit()" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php 
                        mysqli_data_seek($q_kelas, 0);
                        while($k = mysqli_fetch_assoc($q_kelas)): 
                        ?>
                            <option value="<?php echo $k['id_kelas']; ?>" <?php echo ($id_kelas == $k['id_kelas']) ? 'selected' : ''; ?>>
                                <?php echo $k['nama_kelas']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bulan</label>
                    <select name="bulan" class="form-select">
                        <?php foreach($month_names as $k => $v): ?>
                            <option value="<?php echo $k; ?>" <?php echo ($bulan == $k) ? 'selected' : ''; ?>><?php echo $v; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tahun</label>
                    <input type="number" name="tahun" class="form-control" value="<?php echo $tahun; ?>" min="2020" max="<?php echo date('Y')+1; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($id_kelas && !empty($students)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Rekap Absensi: <?php echo $month_names[$bulan] . ' ' . $tahun; ?>
            </h6>
            <div>
                <a href="export_absensi_excel.php?id_kelas=<?php echo $id_kelas; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" target="_blank" class="btn btn-sm btn-success me-1">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="export_absensi_pdf.php?id_kelas=<?php echo $id_kelas; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" target="_blank" class="btn btn-sm btn-danger me-2">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <span class="badge bg-success me-1">H: Hadir</span>
                <span class="badge bg-warning me-1">S: Sakit</span>
                <span class="badge bg-info me-1">I: Izin</span>
                <span class="badge bg-danger">A: Alpha</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm" width="100%" cellspacing="0" style="font-size: 0.85rem;">
                    <thead class="table-light text-center">
                        <tr>
                            <th rowspan="2" class="align-middle" style="width: 40px;">No</th>
                            <th rowspan="2" class="align-middle" style="min-width: 200px; text-align: left; padding-left: 10px;">Nama Siswa</th>
                            <th colspan="<?php echo $days_in_month; ?>"><?php echo $month_names[$bulan]; ?></th>
                            <th rowspan="2" class="align-middle" style="width: 60px;">Total</th>
                        </tr>
                        <tr>
                            <?php for($d=1; $d<=$days_in_month; $d++): ?>
                                <th style="width: 25px;"><?php echo $d; ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach($students as $s): 
                            $sid = $s['id_siswa'];
                            $total_hadir = 0;
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td style="padding-left: 10px;"><?php echo htmlspecialchars($s['nama_siswa']); ?></td>
                            <?php for($d=1; $d<=$days_in_month; $d++): 
                                $status = isset($attendance_data[$sid][$d]) ? $attendance_data[$sid][$d] : '';
                                $bg = '';
                                if ($status == 'H') { $bg = 'bg-success text-white'; $total_hadir++; }
                                elseif ($status == 'S') $bg = 'bg-warning text-dark';
                                elseif ($status == 'I') $bg = 'bg-info text-white';
                                elseif ($status == 'A') $bg = 'bg-danger text-white';
                            ?>
                                <td class="text-center <?php echo $bg; ?>" style="padding: 2px;"><?php echo $status; ?></td>
                            <?php endfor; ?>
                            <td class="text-center fw-bold"><?php echo $total_hadir; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (count($students) == 0): ?>
                <div class="text-center p-3 text-muted">Belum ada data siswa di kelas ini.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php elseif ($id_kelas): ?>
        <div class="alert alert-info text-center">
            Tidak ada siswa ditemukan di kelas ini.
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            Silakan pilih kelas terlebih dahulu untuk melihat rekap kehadiran.
        </div>
    <?php endif; ?>

</div>

<?php include '../../includes/footer.php'; ?>
