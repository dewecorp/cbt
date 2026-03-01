<?php
include '../../config/database.php';

// Use centralized session init to ensure correct timeouts (24h gc, 2h idle)
include '../../includes/init_session.php';

// Fallback if init_session didn't start (unlikely)
if (session_status() == PHP_SESSION_NONE) {
    session_name('CBT_SISWA');
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'siswa') {
    header("Location: ../../index.php");
    exit;
}

$id_ujian = $_GET['id'];
$id_siswa = $_SESSION['user_id'];

// Ambil Data Ujian Siswa
$us_query = mysqli_query($koneksi, "SELECT * FROM ujian_siswa WHERE id_ujian='$id_ujian' AND id_siswa='$id_siswa'");
if (mysqli_num_rows($us_query) == 0) {
    header("Location: konfirmasi.php?id=$id_ujian");
    exit;
}
$us = mysqli_fetch_assoc($us_query);

if ($us['status'] == 'selesai') {
    header("Location: ../../dashboard.php");
    exit;
}

// Ambil Data Ujian & Soal
$ujian = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM ujian WHERE id_ujian='$id_ujian'"));
$id_bank = $ujian['id_bank_soal'];

// Generate Soal jika belum ada di tabel jawaban_siswa (untuk randomisasi dan navigasi)
$cek_jawaban = mysqli_query($koneksi, "SELECT * FROM jawaban_siswa WHERE id_ujian_siswa='".$us['id_ujian_siswa']."'");
if (mysqli_num_rows($cek_jawaban) == 0) {
    // Ambil soal dari bank soal (bisa diacak disini)
    $q_soal = mysqli_query($koneksi, "SELECT id_soal FROM soal WHERE id_bank_soal='$id_bank' ORDER BY RAND()");
    while($s = mysqli_fetch_assoc($q_soal)) {
        mysqli_query($koneksi, "INSERT INTO jawaban_siswa (id_ujian_siswa, id_soal, jawaban, ragu) VALUES ('".$us['id_ujian_siswa']."', '".$s['id_soal']."', '', 0)");
    }
}

// Hitung Sisa Waktu
$waktu_mulai = strtotime($us['waktu_mulai']);
$tambah_waktu = isset($us['tambah_waktu']) ? $us['tambah_waktu'] : 0;
$waktu_selesai = $waktu_mulai + (($ujian['waktu'] + $tambah_waktu) * 60);
$sisa_detik = $waktu_selesai - time();

if ($sisa_detik <= 0) {
    // Auto submit jika waktu habis
    mysqli_query($koneksi, "UPDATE ujian_siswa SET status='selesai', waktu_selesai=NOW() WHERE id_ujian_siswa='".$us['id_ujian_siswa']."'");
    header("Location: ../../dashboard.php");
    exit;
}

// Navigasi Soal
$no_soal = isset($_GET['no']) ? (int)$_GET['no'] : 1;
$offset = $no_soal - 1;

// Ambil 1 Soal berdasarkan urutan di jawaban_siswa
$q_js = mysqli_query($koneksi, "
    SELECT js.*, s.* 
    FROM jawaban_siswa js 
    JOIN soal s ON js.id_soal = s.id_soal 
    WHERE js.id_ujian_siswa='".$us['id_ujian_siswa']."' 
    LIMIT 1 OFFSET $offset
");
$soal = mysqli_fetch_assoc($q_js);

// Total Soal
$total_soal = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM jawaban_siswa WHERE id_ujian_siswa='".$us['id_ujian_siswa']."'"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asesmen Berlangsung - <?php echo isset($ujian['nama_ujian']) ? $ujian['nama_ujian'] : 'CBT MI Sultan Fattah'; ?> - CBT MI Sultan Fattah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .soal-container { min-height: 400px; }
        .nav-soal-btn { 
            width: 40px; 
            height: 40px; 
            margin: 3px; 
            font-size: 14px; 
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }
        .nav-soal-btn.answered { background-color: #198754; color: white; }
        .nav-soal-btn.active { border: 2px solid #0d6efd; font-weight: bold; }
        .timer-box { font-size: 1.5rem; font-weight: bold; font-family: monospace; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
    <div class="container-fluid">
        <span class="navbar-brand">CBT MI Sultan Fattah</span>
        <div class="d-flex text-white align-items-center">
            <i class="fas fa-clock me-2"></i>
            <div id="timer" class="timer-box">00:00:00</div>
        </div>
    </div>
</nav>

<div class="bg-danger text-white text-center p-2 fw-bold shadow-sm">
    <i class="fas fa-exclamation-triangle me-2"></i> PERINGATAN: DILARANG MEMBUKA TAB LAIN ATAU MINIMIZE BROWSER! UJIAN AKAN OTOMATIS TERHENTI.
</div>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Area Soal -->
        <div class="col-md-9 mb-4">
            <div class="card shadow">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0">Soal No. <?php echo $no_soal; ?></h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="raguCheck" <?php echo ($soal['ragu']) ? 'checked' : ''; ?> onchange="setRagu(<?php echo $soal['id_jawaban']; ?>)">
                        <label class="form-check-label text-warning fw-bold" for="raguCheck">Ragu-ragu</label>
                    </div>
                </div>
                <div class="card-body soal-container">
                    <div class="mb-4 fs-5"><?php echo $soal['pertanyaan']; ?></div>
                    
                    <form id="formJawaban" onsubmit="event.preventDefault(); return false;">
                        <input type="hidden" name="id_jawaban" value="<?php echo $soal['id_jawaban']; ?>">
                        <input type="hidden" name="jenis" value="<?php echo $soal['jenis']; ?>">
                        
                        <?php if($soal['jenis'] == 'pilihan_ganda'): ?>
                            <div class="list-group">
                                <?php 
                                $opsi = ['A', 'B', 'C', 'D', 'E'];
                                foreach($opsi as $o): 
                                    $val_opsi = $soal['opsi_'.strtolower($o)];
                                    if(!empty($val_opsi)):
                                ?>
                                <label class="list-group-item list-group-item-action">
                                    <input class="form-check-input me-1" type="radio" name="jawaban" value="<?php echo $o; ?>" <?php echo ($soal['jawaban'] == $o) ? 'checked' : ''; ?> onchange="simpanJawaban()">
                                    <strong><?php echo $o; ?>.</strong> <?php echo $val_opsi; ?>
                                </label>
                                <?php endif; endforeach; ?>
                            </div>

                        <?php elseif($soal['jenis'] == 'pilihan_ganda_kompleks'): 
                             $jawaban_arr = explode(',', $soal['jawaban']);
                        ?>
                             <div class="list-group">
                                <?php 
                                $opsi = ['A', 'B', 'C', 'D', 'E'];
                                foreach($opsi as $o): 
                                    $val_opsi = $soal['opsi_'.strtolower($o)];
                                    if(!empty($val_opsi)):
                                ?>
                                <label class="list-group-item list-group-item-action">
                                    <input class="form-check-input me-1" type="checkbox" name="jawaban_pgk[]" value="<?php echo $o; ?>" <?php echo (in_array($o, $jawaban_arr)) ? 'checked' : ''; ?> onchange="simpanJawaban()">
                                    <strong><?php echo $o; ?>.</strong> <?php echo $val_opsi; ?>
                                </label>
                                <?php endif; endforeach; ?>
                            </div>

                        <?php elseif($soal['jenis'] == 'menjodohkan'): 
                            $kiri = json_decode($soal['opsi_a']);
                            $kanan = json_decode($soal['opsi_b']);
                            // Jawaban disimpan sebagai "0:1,1:0" (indexKiri:indexKanan)
                            $pairs = [];
                            if(!empty($soal['jawaban'])) {
                                $pairs_raw = explode(',', $soal['jawaban']);
                                foreach($pairs_raw as $p) {
                                    $ex = explode(':', $p);
                                    if(count($ex)==2) $pairs[$ex[0]] = $ex[1];
                                }
                            }
                        ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead><tr><th>Pernyataan</th><th>Pasangan Jawaban</th></tr></thead>
                                    <tbody>
                                        <?php foreach($kiri as $idx => $val): ?>
                                        <tr>
                                            <td><?php echo $val; ?></td>
                                            <td>
                                                <select class="form-select" name="match_pair[<?php echo $idx; ?>]" onchange="simpanJawaban()">
                                                    <option value="">-- Pilih --</option>
                                                    <?php foreach($kanan as $idx_k => $val_k): ?>
                                                        <option value="<?php echo $idx_k; ?>" <?php echo (isset($pairs[$idx]) && $pairs[$idx] == $idx_k) ? 'selected' : ''; ?>>
                                                            <?php echo $val_k; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php elseif($soal['jenis'] == 'isian_singkat'): ?>
                            <div class="mb-3">
                                <label class="form-label">Jawaban Singkat:</label>
                                <input type="text" class="form-control" name="jawaban_text" value="<?php echo htmlspecialchars($soal['jawaban']); ?>" onblur="simpanJawaban()">
                            </div>

                        <?php elseif($soal['jenis'] == 'essay'): ?>
                            <div class="mb-3">
                                <label class="form-label">Jawaban Uraian:</label>
                                <textarea class="form-control" name="jawaban_essay" rows="5" onblur="simpanJawaban()"><?php echo htmlspecialchars($soal['jawaban']); ?></textarea>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <?php if($no_soal > 1): ?>
                        <a href="javascript:void(0)" onclick="navigateTo('kerjakan.php?id=<?php echo $id_ujian; ?>&no=<?php echo $no_soal-1; ?>')" class="btn btn-secondary"><i class="fas fa-chevron-left"></i> Sebelumnya</a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>

                    <?php if($no_soal < $total_soal): ?>
                        <a href="javascript:void(0)" onclick="navigateTo('kerjakan.php?id=<?php echo $id_ujian; ?>&no=<?php echo $no_soal+1; ?>')" class="btn btn-primary">Selanjutnya <i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <button type="button" class="btn btn-success" onclick="selesaiUjian()">Selesai Asesmen <i class="fas fa-check"></i></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Navigasi Nomor -->
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-header bg-white fw-bold">Navigasi Soal</div>
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-center">
                        <?php
                        $q_nav = mysqli_query($koneksi, "SELECT * FROM jawaban_siswa WHERE id_ujian_siswa='".$us['id_ujian_siswa']."' ORDER BY id_jawaban ASC");
                        $n = 1;
                        while($nav = mysqli_fetch_assoc($q_nav)):
                            $status_class = '';
                            if(!empty($nav['jawaban'])) $status_class = 'answered';
                            if($nav['ragu']) $status_class = 'bg-warning text-dark';
                            if($n == $no_soal) $status_class .= ' active';
                        ?>
                            <a href="javascript:void(0)" onclick="navigateTo('kerjakan.php?id=<?php echo $id_ujian; ?>&no=<?php echo $n; ?>')" class="btn btn-outline-secondary nav-soal-btn <?php echo $status_class; ?>">
                                <?php echo $n++; ?>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="card-footer small">
                    <span class="badge bg-success me-1">&nbsp;</span> Dijawab
                    <span class="badge bg-warning text-dark me-1">&nbsp;</span> Ragu
                    <span class="badge border border-success text-dark">&nbsp;</span> Aktif
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Heartbeat Session (Setiap 5 menit)
    setInterval(function() {
        $.ajax({
            url: 'keep_alive.php',
            success: function(response) {
                console.log('Session active: ' + response.timestamp);
            }
        });
    }, 300000); // 5 menit

    // Timer Countdown
    var sisaDetik = <?php echo $sisa_detik; ?>;
    
    function updateTimer() {
        if (sisaDetik < 0) sisaDetik = 0;
        
        var hours = Math.floor(sisaDetik / 3600);
        var minutes = Math.floor((sisaDetik % 3600) / 60);
        var seconds = sisaDetik % 60;
        
        var timerDisplay = (hours < 10 ? "0" : "") + hours + ":" + 
                           (minutes < 10 ? "0" : "") + minutes + ":" + 
                           (seconds < 10 ? "0" : "") + seconds;
        
        var el = document.getElementById('timer');
        if (el) el.innerText = timerDisplay;
            
        if (sisaDetik <= 0) {
            clearInterval(timerInterval);
            Swal.fire({
                title: 'Waktu Habis!',
                text: 'Waktu pengerjaan ujian telah berakhir. Jawaban akan tersimpan otomatis.',
                icon: 'warning',
                confirmButtonText: 'OK',
                allowOutsideClick: false
            }).then((result) => {
                // Auto submit via form or redirect
                // Use selesai_ujian.php directly or submit form
                window.location.href = "selesai_ujian.php?id=<?php echo $id_ujian; ?>&auto=1";
            });
        }
        sisaDetik--;
    }
    
    var timerInterval = setInterval(updateTimer, 1000);
    updateTimer();

    // Simpan Jawaban
    function simpanJawaban(nextUrl = null) {
        var formData = $('#formJawaban').serialize();
        $.ajax({
            url: 'simpan_jawaban.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log("Jawaban tersimpan");
                if(nextUrl) {
                    window.location.href = nextUrl;
                }
            },
            error: function() {
                if(nextUrl) {
                    window.location.href = nextUrl;
                }
            }
        });
    }

    function navigateTo(url) {
        simpanJawaban(url);
    }

    // Set Ragu
    function setRagu(id_jawaban) {
        var ragu = $('#raguCheck').is(':checked') ? 1 : 0;
        $.ajax({
            url: 'set_ragu.php',
            type: 'POST',
            data: {id_jawaban: id_jawaban, ragu: ragu},
            success: function() {
                location.reload(); // Reload untuk update warna navigasi (simple way)
            }
        });
    }

    function selesaiUjian() {
        Swal.fire({
            title: 'Selesai Asesmen?',
            text: "Apakah anda yakin ingin mengakhiri ujian ini? Pastikan semua jawaban sudah terisi.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Selesai',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                simpanJawaban("selesai_ujian.php?id=<?php echo $id_ujian; ?>");
            }
        });
    }

    // Deteksi Pindah Tab / Minimize (Anti-Cheat)
    document.addEventListener("visibilitychange", function() {
        if (document.hidden) {
            handleViolation();
        }
    });

    var violationDetected = false;

    function handleViolation() {
        if(violationDetected) return;
        violationDetected = true;
        
        // Stop timer
        clearInterval(timerInterval);
        
        Swal.fire({
            title: 'PELANGGARAN TERDETEKSI!',
            text: 'Anda meninggalkan halaman asesmen (membuka tab lain/minimize). Asesmen Anda otomatis dihentikan!',
            icon: 'error',
            allowOutsideClick: false,
            confirmButtonText: 'Keluar',
            confirmButtonColor: '#d33'
        }).then((result) => {
            window.location.href = "selesai_ujian.php?id=<?php echo $id_ujian; ?>&violation=true";
        });
    }
</script>

</body>
</html>
