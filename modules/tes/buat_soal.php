<?php
include '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$id_bank = $_GET['id'];
$bank = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT b.*, m.nama_mapel FROM bank_soal b JOIN mapel m ON b.id_mapel = m.id_mapel WHERE id_bank_soal='$id_bank'"));

if (!$bank) {
    echo "<script>alert('Bank soal tidak ditemukan'); window.location='bank_soal.php';</script>";
    exit;
}

// Handle Add Soal
if (isset($_POST['simpan_soal'])) {
    $jenis = $_POST['jenis'];
    $pertanyaan = mysqli_real_escape_string($koneksi, $_POST['pertanyaan']);
    
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
        
        // Gabungkan jawaban yang dipilih (array) menjadi string
        if(isset($_POST['pgk_kunci'])) {
            $kunci = implode(",", $_POST['pgk_kunci']);
        }
    } elseif ($jenis == 'menjodohkan') {
        // Simpan data menjodohkan dalam format JSON di kolom opsi_a (kiri) dan opsi_b (kanan)
        // Kunci jawaban simpan pasangan indeksnya
        $kiri = array_values(array_filter($_POST['match_left']));
        $kanan = array_values(array_filter($_POST['match_right']));
        
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

    $query = "INSERT INTO soal (id_bank_soal, jenis, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban) 
              VALUES ('$id_bank', '$jenis', '$pertanyaan', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$kunci')";
    
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

// Handle Delete Soal
if (isset($_GET['delete_soal'])) {
    $id_soal = $_GET['delete_soal'];
    mysqli_query($koneksi, "DELETE FROM soal WHERE id_soal='$id_soal'");
    echo "<script>window.location.href = 'buat_soal.php?id=$id_bank';</script>";
}
?>

<!-- Summernote CSS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">Kelola Soal</h1>
            <p class="mb-0 text-muted">Bank Soal: <strong><?php echo $bank['kode_bank']; ?></strong> | Mapel: <?php echo $bank['nama_mapel']; ?></p>
        </div>
        <a href="bank_soal.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <!-- Form Tambah Soal -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#collapseForm" style="cursor: pointer;">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-plus-circle"></i> Tambah Soal Baru</h6>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="collapse show" id="collapseForm">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jenis Soal</label>
                        <select class="form-select" name="jenis" id="jenis_soal" onchange="changeJenis()">
                            <option value="pilihan_ganda">Pilihan Ganda</option>
                            <option value="pilihan_ganda_kompleks">Pilihan Ganda Kompleks</option>
                            <option value="menjodohkan">Menjodohkan</option>
                            <option value="isian_singkat">Isian Singkat</option>
                            <option value="essay">Uraian / Essay</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pertanyaan</label>
                        <textarea class="form-control summernote" name="pertanyaan" required></textarea>
                    </div>

                    <!-- Area Pilihan Ganda -->
                    <div id="area_pg" class="soal-area">
                        <div class="row">
                            <div class="col-md-6 mb-2"><div class="input-group"><span class="input-group-text">A</span><input type="text" name="pg_a" class="form-control"></div></div>
                            <div class="col-md-6 mb-2"><div class="input-group"><span class="input-group-text">B</span><input type="text" name="pg_b" class="form-control"></div></div>
                            <div class="col-md-6 mb-2"><div class="input-group"><span class="input-group-text">C</span><input type="text" name="pg_c" class="form-control"></div></div>
                            <div class="col-md-6 mb-2"><div class="input-group"><span class="input-group-text">D</span><input type="text" name="pg_d" class="form-control"></div></div>
                            <div class="col-md-6 mb-2"><div class="input-group"><span class="input-group-text">E</span><input type="text" name="pg_e" class="form-control"></div></div>
                        </div>
                        <div class="mt-2">
                            <label class="form-label fw-bold">Kunci Jawaban</label>
                            <select name="pg_kunci" class="form-select w-auto">
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                            </select>
                        </div>
                    </div>

                    <!-- Area Pilihan Ganda Kompleks -->
                    <div id="area_pgk" class="soal-area d-none">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <div class="input-group-text"><input class="form-check-input mt-0" type="checkbox" name="pgk_kunci[]" value="A"></div>
                                    <span class="input-group-text">A</span>
                                    <input type="text" name="pgk_a" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <div class="input-group-text"><input class="form-check-input mt-0" type="checkbox" name="pgk_kunci[]" value="B"></div>
                                    <span class="input-group-text">B</span>
                                    <input type="text" name="pgk_b" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <div class="input-group-text"><input class="form-check-input mt-0" type="checkbox" name="pgk_kunci[]" value="C"></div>
                                    <span class="input-group-text">C</span>
                                    <input type="text" name="pgk_c" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <div class="input-group-text"><input class="form-check-input mt-0" type="checkbox" name="pgk_kunci[]" value="D"></div>
                                    <span class="input-group-text">D</span>
                                    <input type="text" name="pgk_d" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <div class="input-group-text"><input class="form-check-input mt-0" type="checkbox" name="pgk_kunci[]" value="E"></div>
                                    <span class="input-group-text">E</span>
                                    <input type="text" name="pgk_e" class="form-control">
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">* Centang kotak di kiri untuk menandai jawaban benar (bisa lebih dari satu)</small>
                    </div>

                    <!-- Area Menjodohkan -->
                    <div id="area_match" class="soal-area d-none">
                        <label class="form-label fw-bold">Pasangan Jawaban (Kiri - Kanan)</label>
                        <div id="match-container">
                            <div class="row mb-2">
                                <div class="col-5"><input type="text" name="match_left[]" class="form-control" placeholder="Pernyataan Kiri 1"></div>
                                <div class="col-2 text-center align-self-center"><i class="fas fa-arrow-right"></i></div>
                                <div class="col-5"><input type="text" name="match_right[]" class="form-control" placeholder="Jawaban Kanan 1"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5"><input type="text" name="match_left[]" class="form-control" placeholder="Pernyataan Kiri 2"></div>
                                <div class="col-2 text-center align-self-center"><i class="fas fa-arrow-right"></i></div>
                                <div class="col-5"><input type="text" name="match_right[]" class="form-control" placeholder="Jawaban Kanan 2"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5"><input type="text" name="match_left[]" class="form-control" placeholder="Pernyataan Kiri 3"></div>
                                <div class="col-2 text-center align-self-center"><i class="fas fa-arrow-right"></i></div>
                                <div class="col-5"><input type="text" name="match_right[]" class="form-control" placeholder="Jawaban Kanan 3"></div>
                            </div>
                        </div>
                        <small class="text-muted">* Isi pasangan yang benar sejajar.</small>
                    </div>

                    <!-- Area Isian Singkat -->
                    <div id="area_isian" class="soal-area d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kunci Jawaban Singkat</label>
                            <input type="text" name="isian_kunci" class="form-control" placeholder="Jawaban yang benar">
                        </div>
                    </div>

                    <!-- Area Essay -->
                    <div id="area_essay" class="soal-area d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kata Kunci Jawaban (Untuk referensi koreksi)</label>
                            <textarea name="essay_kunci" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" name="simpan_soal" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Soal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- List Soal -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Soal</h6>
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
                    <h5 class="fw-bold">Soal No. <?php echo $no++; ?> <span class="badge bg-secondary small"><?php echo str_replace('_', ' ', strtoupper($s['jenis'])); ?></span></h5>
                    <div>
                        <a href="buat_soal.php?id=<?php echo $id_bank; ?>&delete_soal=<?php echo $s['id_soal']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus soal ini?')"><i class="fas fa-trash"></i></a>
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
                    <div class="ms-3 alert alert-secondary p-2">Kunci: <?php echo $s['kunci_jawaban']; ?></div>
                <?php endif; ?>
            </div>
            <?php endwhile; else: ?>
            <div class="alert alert-info">Belum ada soal.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Summernote JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

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
</script>

<?php include '../../includes/footer.php'; ?>
