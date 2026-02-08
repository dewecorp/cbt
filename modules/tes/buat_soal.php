<?php
include '../../config/database.php';
$page_title = 'Buat Soal';
include '../../includes/header.php';
include '../../includes/sidebar.php';
require '../../vendor/autoload.php';
require_once 'import_word_helper.php';
use Shuchkin\SimpleXLSX;

$id_bank = $_GET['id'];
$bank = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT b.*, m.nama_mapel FROM bank_soal b JOIN mapel m ON b.id_mapel = m.id_mapel WHERE id_bank_soal='$id_bank'"));

if (!$bank) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Bank soal tidak ditemukan!',
            confirmButtonText: 'Kembali'
        }).then(() => {
            window.location='bank_soal.php';
        });
    </script>";
    exit;
}

// Handle Import Soal
if (isset($_POST['import_soal'])) {
    if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
        if ($xlsx = SimpleXLSX::parse($_FILES['file_excel']['tmp_name'])) {
            $count = 0;
            foreach ($xlsx->rows() as $k => $r) {
                if ($k === 0) continue; // Skip header
                
                $jenis = strtolower(trim($r[0]));
                // Normalize jenis if user types differently
                if(strpos($jenis, 'ganda') !== false && strpos($jenis, 'kompleks') === false) $jenis = 'pilihan_ganda';
                if(strpos($jenis, 'kompleks') !== false) $jenis = 'pilihan_ganda_kompleks';
                if(strpos($jenis, 'jodoh') !== false) $jenis = 'menjodohkan'; // Excel support for menjodohkan is tricky, might need specific format. Skipping complex parsing for now, assuming user follows template.
                if(strpos($jenis, 'isian') !== false) $jenis = 'isian_singkat';
                if(strpos($jenis, 'essay') !== false || strpos($jenis, 'uraian') !== false) $jenis = 'essay';

                $pertanyaan = mysqli_real_escape_string($koneksi, $r[1]);
                $opsi_a = isset($r[2]) ? mysqli_real_escape_string($koneksi, $r[2]) : '';
                $opsi_b = isset($r[3]) ? mysqli_real_escape_string($koneksi, $r[3]) : '';
                $opsi_c = isset($r[4]) ? mysqli_real_escape_string($koneksi, $r[4]) : '';
                $opsi_d = isset($r[5]) ? mysqli_real_escape_string($koneksi, $r[5]) : '';
                $opsi_e = isset($r[6]) ? mysqli_real_escape_string($koneksi, $r[6]) : '';
                $kunci = isset($r[7]) ? mysqli_real_escape_string($koneksi, $r[7]) : '';

                if (empty($jenis) || empty($pertanyaan)) continue;

                $query = "INSERT INTO soal (id_bank_soal, jenis, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban) 
                          VALUES ('$id_bank', '$jenis', '$pertanyaan', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$kunci')";
                if(mysqli_query($koneksi, $query)) {
                    $count++;
                }
            }
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Import Berhasil',
                    text: '$count soal berhasil diimpor!',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'buat_soal.php?id=$id_bank';
                });
            </script>";
        } else {
            echo "<script>Swal.fire('Error', 'Gagal membaca file Excel. Pastikan format benar.', 'error');</script>";
        }
    } else {
        echo "<script>Swal.fire('Error', 'Silahkan pilih file terlebih dahulu', 'error');</script>";
    }
}

// Handle Import Soal Word
if (isset($_POST['import_soal_word'])) {
    if (isset($_FILES['file_word']) && $_FILES['file_word']['error'] == 0) {
        $questions = parseQuestionsFromDocx($_FILES['file_word']['tmp_name']);
        
        if ($questions !== false && !empty($questions)) {
            $count = 0;
            foreach ($questions as $q) {
                $jenis = $q['jenis'];
                $pertanyaan = mysqli_real_escape_string($koneksi, $q['pertanyaan']);
                $opsi_a = mysqli_real_escape_string($koneksi, $q['opsi_a']);
                $opsi_b = mysqli_real_escape_string($koneksi, $q['opsi_b']);
                $opsi_c = mysqli_real_escape_string($koneksi, $q['opsi_c']);
                $opsi_d = mysqli_real_escape_string($koneksi, $q['opsi_d']);
                $opsi_e = mysqli_real_escape_string($koneksi, $q['opsi_e']);
                $kunci = mysqli_real_escape_string($koneksi, $q['kunci']);
                
                // Handle Menjodohkan specific format
                if ($jenis == 'menjodohkan') {
                    $kiri = isset($q['kiri']) ? $q['kiri'] : [];
                    $kanan = isset($q['kanan']) ? $q['kanan'] : [];
                    $opsi_a = mysqli_real_escape_string($koneksi, json_encode($kiri));
                    $opsi_b = mysqli_real_escape_string($koneksi, json_encode($kanan));
                    
                    // Auto-generate key 0:0, 1:1, etc.
                    $pairs = [];
                    for($i=0; $i<count($kiri); $i++) {
                        $pairs[] = "$i:$i"; 
                    }
                    $kunci = implode(",", $pairs);
                }
                
                if (empty($pertanyaan)) continue;
                
                $query = "INSERT INTO soal (id_bank_soal, jenis, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban) 
                          VALUES ('$id_bank', '$jenis', '$pertanyaan', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$kunci')";
                
                if(mysqli_query($koneksi, $query)) {
                    $count++;
                }
            }
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Import Word Berhasil',
                    text: '$count soal berhasil diimpor dari Word!',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'buat_soal.php?id=$id_bank';
                });
            </script>";
        } else {
             echo "<script>Swal.fire('Error', 'Gagal membaca file Word atau format tidak sesuai. Pastikan menggunakan format yang benar.', 'error');</script>";
        }
    } else {
        echo "<script>Swal.fire('Error', 'Silahkan pilih file Word (.docx) terlebih dahulu', 'error');</script>";
    }
}

// Handle Add Soal
if (isset($_POST['simpan_soal'])) {
    $jenis = $_POST['jenis'];
    $pertanyaan = mysqli_real_escape_string($koneksi, $_POST['pertanyaan']);
    $bobot = isset($_POST['bobot']) && $_POST['bobot'] !== '' ? $_POST['bobot'] : 0;
    
    $opsi_a = $opsi_b = $opsi_c = $opsi_d = $opsi_e = "";
    $kunci = "";
    $bobot = isset($_POST['bobot']) && $_POST['bobot'] !== '' ? $_POST['bobot'] : 0;

    if ($jenis == 'pilihan_ganda') {
        $opsi_a = mysqli_real_escape_string($koneksi, $_POST['pg_a']);
        $opsi_b = mysqli_real_escape_string($koneksi, $_POST['pg_b']);
        $opsi_c = mysqli_real_escape_string($koneksi, $_POST['pg_c']);
        $opsi_d = mysqli_real_escape_string($koneksi, $_POST['pg_d']);
        $opsi_e = mysqli_real_escape_string($koneksi, $_POST['pg_e']);
        $kunci = $_POST['pg_kunci'];
    } elseif ($jenis == 'pilihan_ganda_kompleks') {
        $opsi_a = mysqli_real_escape_string($koneksi, $_POST['pgk_a']);
        $opsi_b = mysqli_real_escape_string($koneksi, $_POST['pgk_b']);
        $opsi_c = mysqli_real_escape_string($koneksi, $_POST['pgk_c']);
        $opsi_d = mysqli_real_escape_string($koneksi, $_POST['pgk_d']);
        $opsi_e = mysqli_real_escape_string($koneksi, $_POST['pgk_e']);
        
        // Gabungkan jawaban yang dipilih (array) menjadi string
        if(isset($_POST['pgk_kunci'])) {
            $kunci = implode(",", $_POST['pgk_kunci']);
        }
    } elseif ($jenis == 'menjodohkan') {
        // Simpan data menjodohkan dalam format JSON di kolom opsi_a (kiri) dan opsi_b (kanan)
        // Kunci jawaban simpan pasangan indeksnya
        $kiri = [];
        $kanan = [];
        if(isset($_POST['match_left']) && isset($_POST['match_right'])){
            foreach($_POST['match_left'] as $idx => $val){
                $l = trim($val);
                $r = trim($_POST['match_right'][$idx] ?? '');
                if($l !== '' && $r !== ''){
                    $kiri[] = $l;
                    $kanan[] = $r;
                }
            }
        }
        
        $opsi_a = mysqli_real_escape_string($koneksi, json_encode($kiri));
        $opsi_b = mysqli_real_escape_string($koneksi, json_encode($kanan));
        
        // Kita asumsikan inputnya sudah berpasangan urut (Baris 1 Kiri jodohnya Baris 1 Kanan)
        // Format kunci: indexKiri:indexKanan (0:0, 1:1, dst)
        $pairs = [];
        for($i=0; $i<count($kiri); $i++) {
            $pairs[] = "$i:$i"; 
        }
        $kunci = implode(",", $pairs);
        
    } elseif ($jenis == 'isian_singkat') {
        $kunci = mysqli_real_escape_string($koneksi, $_POST['isian_kunci']);
    } elseif ($jenis == 'essay') {
        $kunci = mysqli_real_escape_string($koneksi, $_POST['essay_kunci']);
    }

    $query = "INSERT INTO soal (id_bank_soal, jenis, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban, bobot) 
              VALUES ('$id_bank', '$jenis', '$pertanyaan', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$kunci', '$bobot')";
    
    if(mysqli_query($koneksi, $query)) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Soal berhasil ditambahkan',
                timer: 1000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'buat_soal.php?id=$id_bank';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
    }
}

// Handle Update Soal
if (isset($_POST['update_soal'])) {
    $id_soal = $_POST['id_soal'];
    $jenis = $_POST['jenis'];
    $pertanyaan = mysqli_real_escape_string($koneksi, $_POST['pertanyaan']);
    $bobot = isset($_POST['bobot']) && $_POST['bobot'] !== '' ? $_POST['bobot'] : 0;
    
    $opsi_a = $opsi_b = $opsi_c = $opsi_d = $opsi_e = "";
    $kunci = "";

    if ($jenis == 'pilihan_ganda') {
        $opsi_a = mysqli_real_escape_string($koneksi, $_POST['pg_a']);
        $opsi_b = mysqli_real_escape_string($koneksi, $_POST['pg_b']);
        $opsi_c = mysqli_real_escape_string($koneksi, $_POST['pg_c']);
        $opsi_d = mysqli_real_escape_string($koneksi, $_POST['pg_d']);
        $opsi_e = mysqli_real_escape_string($koneksi, $_POST['pg_e']);
        $kunci = $_POST['pg_kunci'];
    } elseif ($jenis == 'pilihan_ganda_kompleks') {
        $opsi_a = mysqli_real_escape_string($koneksi, $_POST['pgk_a']);
        $opsi_b = mysqli_real_escape_string($koneksi, $_POST['pgk_b']);
        $opsi_c = mysqli_real_escape_string($koneksi, $_POST['pgk_c']);
        $opsi_d = mysqli_real_escape_string($koneksi, $_POST['pgk_d']);
        $opsi_e = mysqli_real_escape_string($koneksi, $_POST['pgk_e']);
        
        if(isset($_POST['pgk_kunci'])) {
            $kunci = implode(",", $_POST['pgk_kunci']);
        }
    } elseif ($jenis == 'menjodohkan') {
        $kiri = [];
        $kanan = [];
        if(isset($_POST['match_left']) && isset($_POST['match_right'])){
            foreach($_POST['match_left'] as $idx => $val){
                $l = trim($val);
                $r = trim($_POST['match_right'][$idx] ?? '');
                if($l !== '' && $r !== ''){
                    $kiri[] = $l;
                    $kanan[] = $r;
                }
            }
        }
        
        $opsi_a = mysqli_real_escape_string($koneksi, json_encode($kiri));
        $opsi_b = mysqli_real_escape_string($koneksi, json_encode($kanan));
        
        $pairs = [];
        for($i=0; $i<count($kiri); $i++) {
            $pairs[] = "$i:$i"; 
        }
        $kunci = implode(",", $pairs);
        
    } elseif ($jenis == 'isian_singkat') {
        $kunci = mysqli_real_escape_string($koneksi, $_POST['isian_kunci']);
    } elseif ($jenis == 'essay') {
        $kunci = mysqli_real_escape_string($koneksi, $_POST['essay_kunci']);
    }

    $query = "UPDATE soal SET 
              jenis='$jenis', 
              pertanyaan='$pertanyaan', 
              opsi_a='$opsi_a', 
              opsi_b='$opsi_b', 
              opsi_c='$opsi_c', 
              opsi_d='$opsi_d', 
              opsi_e='$opsi_e', 
              kunci_jawaban='$kunci',
              bobot='$bobot' 
              WHERE id_soal='$id_soal' AND id_bank_soal='$id_bank'";
    
    if(mysqli_query($koneksi, $query)) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Soal berhasil diperbarui',
                timer: 1000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'buat_soal.php?id=$id_bank';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Error', '".mysqli_error($koneksi)."', 'error');</script>";
    }
}

// Handle Delete Soal
if (isset($_GET['delete_soal'])) {
    $id_soal = $_GET['delete_soal'];
    mysqli_query($koneksi, "DELETE FROM soal WHERE id_soal='$id_soal'");
    echo "<script>window.location.href = 'buat_soal.php?id=$id_bank';</script>";
}
?>

<!-- Summernote CSS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">

<?php
$is_edit = false;
$e_id_soal = '';
$e_jenis = 'pilihan_ganda';
$e_pertanyaan = '';
$e_opsi_a = '';
$e_opsi_b = '';
$e_opsi_c = '';
$e_opsi_d = '';
$e_opsi_e = '';
$e_kunci = '';
$e_bobot = '';
$e_kiri = [];
$e_kanan = [];

if (isset($_GET['edit_soal'])) {
    $id_edit = $_GET['edit_soal'];
    $q_edit = mysqli_query($koneksi, "SELECT * FROM soal WHERE id_soal='$id_edit' AND id_bank_soal='$id_bank'");
    if(mysqli_num_rows($q_edit) > 0) {
        $d_edit = mysqli_fetch_assoc($q_edit);
        $is_edit = true;
        $e_id_soal = $d_edit['id_soal'];
        $e_jenis = $d_edit['jenis'];
        $e_pertanyaan = $d_edit['pertanyaan'];
        $e_opsi_a = $d_edit['opsi_a'];
        $e_opsi_b = $d_edit['opsi_b'];
        $e_opsi_c = $d_edit['opsi_c'];
        $e_opsi_d = $d_edit['opsi_d'];
        $e_opsi_e = $d_edit['opsi_e'];
        $e_kunci = $d_edit['kunci_jawaban'];
        $e_bobot = isset($d_edit['bobot']) ? $d_edit['bobot'] : '';
        
        if($e_jenis == 'menjodohkan') {
            $e_kiri = json_decode($e_opsi_a, true);
            $e_kanan = json_decode($e_opsi_b, true);
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">Kelola Soal</h1>
            <p class="mb-0 text-muted">Bank Soal: <strong><?php echo $bank['kode_bank']; ?></strong> | Mapel: <?php echo $bank['nama_mapel']; ?></p>
        </div>
        <div>
            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-file-excel"></i> Import Excel
            </button>
            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#importWordModal">
                <i class="fas fa-file-word"></i> Import Word
            </button>
            <a href="bank_soal.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <!-- Form Tambah Soal -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#collapseForm" style="cursor: pointer;">
            <h6 class="m-0 font-weight-bold text-success">Form Buat Soal</h6>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="collapse show" id="collapseForm">
            <div class="card-body">
                <form method="POST">
                    <?php if($is_edit): ?>
                        <input type="hidden" name="id_soal" value="<?php echo $e_id_soal; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Jenis Soal</label>
                        <select class="form-select" name="jenis" id="jenis_soal" onchange="changeJenis()">
                            <option value="pilihan_ganda" <?php echo ($e_jenis=='pilihan_ganda')?'selected':''; ?>>Pilihan Ganda</option>
                            <option value="pilihan_ganda_kompleks" <?php echo ($e_jenis=='pilihan_ganda_kompleks')?'selected':''; ?>>Pilihan Ganda Kompleks</option>
                            <option value="menjodohkan" <?php echo ($e_jenis=='menjodohkan')?'selected':''; ?>>Menjodohkan</option>
                            <option value="isian_singkat" <?php echo ($e_jenis=='isian_singkat')?'selected':''; ?>>Isian Singkat</option>
                            <option value="essay" <?php echo ($e_jenis=='essay')?'selected':''; ?>>Uraian / Essay</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Bobot Soal / Score</label>
                        <input type="number" step="0.01" name="bobot" class="form-control" value="<?php echo $e_bobot; ?>" placeholder="Kosongkan untuk otomatis (Default 1)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pertanyaan</label>
                        <textarea class="form-control summernote" name="pertanyaan" required><?php echo $e_pertanyaan; ?></textarea>
                    </div>

                    <!-- Area Pilihan Ganda -->
                    <div id="area_pg" class="soal-area <?php echo ($e_jenis!='pilihan_ganda')?'d-none':''; ?>">
                        <div class="row">
                            <div class="col-md-6 mb-2"><div class="input-group"><span class="input-group-text">A</span><input type="text" name="pg_a" class="form-control" value="<?php echo ($e_jenis=='pilihan_ganda')?$e_opsi_a:''; ?>"></div></div>
                            <div class="col-md-6 mb-2"><div class="input-group"><span class="input-group-text">B</span><input type="text" name="pg_b" class="form-control" value="<?php echo ($e_jenis=='pilihan_ganda')?$e_opsi_b:''; ?>"></div></div>
                            <div class="col-md-6 mb-2"><div class="input-group"><span class="input-group-text">C</span><input type="text" name="pg_c" class="form-control" value="<?php echo ($e_jenis=='pilihan_ganda')?$e_opsi_c:''; ?>"></div></div>
                            <div class="col-md-6 mb-2"><div class="input-group"><span class="input-group-text">D</span><input type="text" name="pg_d" class="form-control" value="<?php echo ($e_jenis=='pilihan_ganda')?$e_opsi_d:''; ?>"></div></div>
                            <div class="col-md-6 mb-2"><div class="input-group"><span class="input-group-text">E</span><input type="text" name="pg_e" class="form-control" value="<?php echo ($e_jenis=='pilihan_ganda')?$e_opsi_e:''; ?>"></div></div>
                        </div>
                        <div class="mt-2">
                            <label class="form-label fw-bold">Kunci Jawaban</label>
                            <select name="pg_kunci" class="form-select w-auto">
                                <option value="A" <?php echo ($e_kunci=='A')?'selected':''; ?>>A</option>
                                <option value="B" <?php echo ($e_kunci=='B')?'selected':''; ?>>B</option>
                                <option value="C" <?php echo ($e_kunci=='C')?'selected':''; ?>>C</option>
                                <option value="D" <?php echo ($e_kunci=='D')?'selected':''; ?>>D</option>
                                <option value="E" <?php echo ($e_kunci=='E')?'selected':''; ?>>E</option>
                            </select>
                        </div>
                    </div>

                    <!-- Area Pilihan Ganda Kompleks -->
                    <div id="area_pgk" class="soal-area <?php echo ($e_jenis!='pilihan_ganda_kompleks')?'d-none':''; ?>">
                        <?php $pgk_keys = explode(',', $e_kunci); ?>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <div class="input-group-text"><input class="form-check-input mt-0" type="checkbox" name="pgk_kunci[]" value="A" <?php echo (in_array('A', $pgk_keys))?'checked':''; ?>></div>
                                    <span class="input-group-text">A</span>
                                    <input type="text" name="pgk_a" class="form-control" value="<?php echo ($e_jenis=='pilihan_ganda_kompleks')?$e_opsi_a:''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <div class="input-group-text"><input class="form-check-input mt-0" type="checkbox" name="pgk_kunci[]" value="B" <?php echo (in_array('B', $pgk_keys))?'checked':''; ?>></div>
                                    <span class="input-group-text">B</span>
                                    <input type="text" name="pgk_b" class="form-control" value="<?php echo ($e_jenis=='pilihan_ganda_kompleks')?$e_opsi_b:''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <div class="input-group-text"><input class="form-check-input mt-0" type="checkbox" name="pgk_kunci[]" value="C" <?php echo (in_array('C', $pgk_keys))?'checked':''; ?>></div>
                                    <span class="input-group-text">C</span>
                                    <input type="text" name="pgk_c" class="form-control" value="<?php echo ($e_jenis=='pilihan_ganda_kompleks')?$e_opsi_c:''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <div class="input-group-text"><input class="form-check-input mt-0" type="checkbox" name="pgk_kunci[]" value="D" <?php echo (in_array('D', $pgk_keys))?'checked':''; ?>></div>
                                    <span class="input-group-text">D</span>
                                    <input type="text" name="pgk_d" class="form-control" value="<?php echo ($e_jenis=='pilihan_ganda_kompleks')?$e_opsi_d:''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <div class="input-group-text"><input class="form-check-input mt-0" type="checkbox" name="pgk_kunci[]" value="E" <?php echo (in_array('E', $pgk_keys))?'checked':''; ?>></div>
                                    <span class="input-group-text">E</span>
                                    <input type="text" name="pgk_e" class="form-control" value="<?php echo ($e_jenis=='pilihan_ganda_kompleks')?$e_opsi_e:''; ?>">
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">* Centang kotak di kiri untuk menandai jawaban benar (bisa lebih dari satu)</small>
                    </div>

                    <!-- Area Menjodohkan -->
                    <div id="area_match" class="soal-area <?php echo ($e_jenis!='menjodohkan')?'d-none':''; ?>">
                        <label class="form-label fw-bold">Pasangan Jawaban (Kiri - Kanan)</label>
                        <div id="match-container">
                            <?php if($is_edit && $e_jenis == 'menjodohkan' && count($e_kiri) > 0): ?>
                                <?php foreach($e_kiri as $idx => $val): ?>
                                <div class="row mb-2 match-row">
                                    <div class="col-5"><input type="text" name="match_left[]" class="form-control" placeholder="Pernyataan Kiri" value="<?php echo htmlspecialchars($val); ?>"></div>
                                    <div class="col-1 text-center align-self-center"><i class="fas fa-arrow-right"></i></div>
                                    <div class="col-5"><input type="text" name="match_right[]" class="form-control" placeholder="Jawaban Kanan" value="<?php echo htmlspecialchars($e_kanan[$idx] ?? ''); ?>"></div>
                                    <div class="col-1 text-center align-self-center">
                                        <button type="button" class="btn btn-danger btn-sm btn-remove-match" onclick="removeMatchRow(this)"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <div class="row mb-2 match-row">
                                <div class="col-5"><input type="text" name="match_left[]" class="form-control" placeholder="Pernyataan Kiri"></div>
                                <div class="col-1 text-center align-self-center"><i class="fas fa-arrow-right"></i></div>
                                <div class="col-5"><input type="text" name="match_right[]" class="form-control" placeholder="Jawaban Kanan"></div>
                                <div class="col-1 text-center align-self-center">
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-match" onclick="removeMatchRow(this)"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-info btn-sm mt-2 text-white" onclick="addMatchRow()"><i class="fas fa-plus"></i> Tambah Pasangan</button>
                        <small class="d-block mt-2 text-muted">* Isi pasangan yang benar sejajar.</small>
                    </div>

                    <!-- Area Isian Singkat -->
                    <div id="area_isian" class="soal-area <?php echo ($e_jenis!='isian_singkat')?'d-none':''; ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kunci Jawaban Singkat</label>
                            <input type="text" name="isian_kunci" class="form-control" placeholder="Jawaban yang benar" value="<?php echo ($e_jenis=='isian_singkat')?$e_kunci:''; ?>">
                        </div>
                    </div>

                    <!-- Area Essay -->
                    <div id="area_essay" class="soal-area <?php echo ($e_jenis!='essay')?'d-none':''; ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kata Kunci Jawaban (Untuk referensi koreksi)</label>
                            <textarea name="essay_kunci" class="form-control" rows="3"><?php echo ($e_jenis=='essay')?$e_kunci:''; ?></textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <?php if($is_edit): ?>
                            <button type="submit" name="update_soal" class="btn btn-warning text-white"><i class="fas fa-save"></i> Update Soal</button>
                            <a href="buat_soal.php?id=<?php echo $id_bank; ?>" class="btn btn-secondary ms-2"><i class="fas fa-times"></i> Batal</a>
                        <?php else: ?>
                            <button type="submit" name="simpan_soal" class="btn btn-success"><i class="fas fa-save"></i> Simpan Soal</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- List Soal -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Daftar Soal</h6>
        </div>
        <div class="card-body">
            <?php
            $q_soal = mysqli_query($koneksi, "SELECT * FROM soal WHERE id_bank_soal='$id_bank' ORDER BY id_soal ASC");
            if(mysqli_num_rows($q_soal) > 0):
                $no = 1;
                while($s = mysqli_fetch_assoc($q_soal)):
            ?>
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between">
                    <h5 class="fw-bold">
                        Soal No. <?php echo $no++; ?> 
                        <span class="badge bg-secondary small"><?php echo str_replace('_', ' ', strtoupper($s['jenis'])); ?></span>
                        <span class="badge bg-info text-dark small ms-2">Bobot: <?php echo isset($s['bobot']) ? $s['bobot'] : '1.00'; ?></span>
                    </h5>
                    <div>
                        <a href="buat_soal.php?id=<?php echo $id_bank; ?>&edit_soal=<?php echo $s['id_soal']; ?>" class="btn btn-warning btn-sm text-white me-1"><i class="fas fa-edit"></i></a>
                        <a href="buat_soal.php?id=<?php echo $id_bank; ?>&delete_soal=<?php echo $s['id_soal']; ?>" class="btn btn-danger btn-sm" onclick="confirmDeleteSoal(event, this.href); return false;"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <div class="mb-2"><?php echo $s['pertanyaan']; ?></div>
                
                <?php if($s['jenis'] == 'pilihan_ganda'): ?>
                    <ul class="list-unstyled ms-3">
                        <li class="<?php echo ($s['kunci_jawaban']=='A')?'text-success fw-bold':''; ?>">A. <?php echo $s['opsi_a']; ?></li>
                        <li class="<?php echo ($s['kunci_jawaban']=='B')?'text-success fw-bold':''; ?>">B. <?php echo $s['opsi_b']; ?></li>
                        <li class="<?php echo ($s['kunci_jawaban']=='C')?'text-success fw-bold':''; ?>">C. <?php echo $s['opsi_c']; ?></li>
                        <li class="<?php echo ($s['kunci_jawaban']=='D')?'text-success fw-bold':''; ?>">D. <?php echo $s['opsi_d']; ?></li>
                        <li class="<?php echo ($s['kunci_jawaban']=='E')?'text-success fw-bold':''; ?>">E. <?php echo $s['opsi_e']; ?></li>
                    </ul>
                    <div class="small text-muted">Kunci: <?php echo $s['kunci_jawaban']; ?></div>
                
                <?php elseif($s['jenis'] == 'pilihan_ganda_kompleks'): 
                    $keys = explode(',', $s['kunci_jawaban']);
                ?>
                    <ul class="list-unstyled ms-3">
                        <li class="<?php echo (in_array('A', $keys))?'text-success fw-bold':''; ?>">A. <?php echo $s['opsi_a']; ?></li>
                        <li class="<?php echo (in_array('B', $keys))?'text-success fw-bold':''; ?>">B. <?php echo $s['opsi_b']; ?></li>
                        <li class="<?php echo (in_array('C', $keys))?'text-success fw-bold':''; ?>">C. <?php echo $s['opsi_c']; ?></li>
                        <li class="<?php echo (in_array('D', $keys))?'text-success fw-bold':''; ?>">D. <?php echo $s['opsi_d']; ?></li>
                        <li class="<?php echo (in_array('E', $keys))?'text-success fw-bold':''; ?>">E. <?php echo $s['opsi_e']; ?></li>
                    </ul>
                    <div class="small text-muted">Kunci: <?php echo $s['kunci_jawaban']; ?></div>

                <?php elseif($s['jenis'] == 'menjodohkan'): 
                    $kiri = json_decode($s['opsi_a']);
                    $kanan = json_decode($s['opsi_b']);
                ?>
                    <div class="row ms-3">
                        <div class="col-6">
                            <strong>Pernyataan:</strong>
                            <ul><?php foreach($kiri as $k) echo "<li>$k</li>"; ?></ul>
                        </div>
                        <div class="col-6">
                            <strong>Jawaban:</strong>
                            <ul><?php foreach($kanan as $k) echo "<li>$k</li>"; ?></ul>
                        </div>
                    </div>

                <?php elseif($s['jenis'] == 'isian_singkat' || $s['jenis'] == 'essay'): ?>
                    <div class="ms-3 card bg-secondary bg-opacity-10 border border-secondary text-secondary p-2 rounded d-inline-block"><strong>Kunci:</strong> <?php echo $s['kunci_jawaban']; ?></div>
                <?php endif; ?>
            </div>
            <?php endwhile; else: ?>
            <div class="card bg-info bg-opacity-10 border border-info text-info p-3 rounded text-center"><i class="fas fa-info-circle me-1"></i> Belum ada soal.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Summernote JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Soal dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="card bg-info bg-opacity-10 border border-info text-info p-3 rounded mb-3">
                        <small>
                            <i class="fas fa-info-circle"></i> Gunakan template Excel yang telah disediakan agar format sesuai.
                            <br>
                            <a href="download_template_soal.php" class="fw-bold text-decoration-none text-info"><i class="fas fa-download"></i> Download Template</a>
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih File Excel (.xlsx)</label>
                        <input type="file" class="form-control" name="file_excel" accept=".xlsx" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="import_soal" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Word Modal -->
<div class="modal fade" id="importWordModal" tabindex="-1" aria-labelledby="importWordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importWordModalLabel">Import Soal dari Word</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="card bg-success bg-opacity-10 border border-success text-success p-3 rounded mb-3">
                        <small>
                            <i class="fas fa-info-circle"></i> <strong>Format Dokumen Word (.docx):</strong><br>
                            Gunakan template yang telah disediakan agar format sesuai. <br>
                            <a href="download_template_word.php" class="fw-bold text-decoration-none text-success"><i class="fas fa-download"></i> Download Template Word</a>
                            <br><br>
                            <em>Fitur deteksi otomatis:</em><br>
                            - Pilihan Ganda (Opsi A-E)<br>
                            - Pilihan Ganda Kompleks (Kunci: A, B)<br>
                            - Menjodohkan (Format: Kiri => Kanan)<br>
                            - Isian / Essay (Tanpa opsi)
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih File Word (.docx)</label>
                        <input type="file" class="form-control" name="file_word" accept=".docx" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="import_soal_word" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.summernote').summernote({
            placeholder: 'Tulis pertanyaan disini...',
            tabsize: 2,
            height: 150,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    });

    function changeJenis() {
        var jenis = document.getElementById('jenis_soal').value;
        
        // Hide all areas
        document.querySelectorAll('.soal-area').forEach(el => el.classList.add('d-none'));
        
        // Remove required from all inputs inside soal-area
        document.querySelectorAll('.soal-area input, .soal-area textarea').forEach(el => el.required = false);

        // Show selected area
        if (jenis == 'pilihan_ganda') {
            document.getElementById('area_pg').classList.remove('d-none');
        } else if (jenis == 'pilihan_ganda_kompleks') {
            document.getElementById('area_pgk').classList.remove('d-none');
        } else if (jenis == 'menjodohkan') {
            document.getElementById('area_match').classList.remove('d-none');
        } else if (jenis == 'isian_singkat') {
            document.getElementById('area_isian').classList.remove('d-none');
        } else if (jenis == 'essay') {
            document.getElementById('area_essay').classList.remove('d-none');
        }
    }

    function addMatchRow() {
        var container = document.getElementById('match-container');
        var row = document.createElement('div');
        row.className = 'row mb-2 match-row';
        row.innerHTML = `
            <div class="col-5"><input type="text" name="match_left[]" class="form-control" placeholder="Pernyataan Kiri"></div>
            <div class="col-1 text-center align-self-center"><i class="fas fa-arrow-right"></i></div>
            <div class="col-5"><input type="text" name="match_right[]" class="form-control" placeholder="Jawaban Kanan"></div>
            <div class="col-1 text-center align-self-center">
                <button type="button" class="btn btn-danger btn-sm btn-remove-match" onclick="removeMatchRow(this)"><i class="fas fa-times"></i></button>
            </div>
        `;
        container.appendChild(row);
    }

    function removeMatchRow(btn) {
        if(document.querySelectorAll('.match-row').length > 1) {
            btn.closest('.match-row').remove();
        } else {
            Swal.fire('Peringatan', 'Minimal harus ada satu pasangan!', 'warning');
        }
    }

    function confirmDeleteSoal(e, url) {
        e.preventDefault();
        Swal.fire({
            title: 'Hapus Soal?',
            text: "Soal yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>

<?php include '../../includes/footer.php'; ?>
