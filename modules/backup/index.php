<?php
include '../../config/database.php';
$page_title = 'Backup Database';
include '../../includes/header.php';

// Cek Level Admin
if ($_SESSION['level'] != 'admin') {
    echo "<script>window.location='../../dashboard.php';</script>";
    exit;
}

// Helper function to format size
function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}

// Get list of backups
$backupDir = __DIR__ . "/../../backups/";
$backupFiles = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if ($file != "." && $file != ".." && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $backupFiles[] = [
                'name' => $file,
                'size' => filesize($backupDir . $file),
                'time' => filemtime($backupDir . $file)
            ];
        }
    }
    // Sort by time desc
    usort($backupFiles, function($a, $b) {
        return $b['time'] - $a['time'];
    });
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Backup & Restore Database <small class="text-muted" style="font-size: 0.5em;">v1.2</small></h1>
    </div>

    <div class="row">
        <!-- Backup Section -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Backup Database</h6>
                </div>
                <div class="card-body">
                    <p>Klik tombol di bawah ini untuk membuat backup database baru.</p>
                    <button id="btnBackup" class="btn btn-primary btn-lg btn-block" onclick="doBackup()">
                        <i class="fas fa-save"></i> Buat Backup Database
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Restore Section -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">Restore Database</h6>
                </div>
                <div class="card-body">
                    <p class="text-danger">Peringatan: Restore database akan menimpa data saat ini. Pastikan Anda sudah melakukan backup terlebih dahulu.</p>
                    
                    <form id="formRestore" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="file_restore" class="form-label">Pilih File Backup (.sql)</label>
                            <input class="form-control" type="file" id="file_restore" name="file_restore" accept=".sql" required>
                        </div>
                        <button type="submit" class="btn btn-danger btn-lg btn-block">
                            <i class="fas fa-upload"></i> Restore Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup History Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-secondary">Riwayat Backup</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm" width="100%" cellspacing="0">
                            <thead class="bg-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Nama File</th>
                                    <th>Ukuran</th>
                                    <th>Tanggal</th>
                                    <th width="20%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($backupFiles)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Belum ada file backup</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($backupFiles as $file): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $file['name']; ?></td>
                                        <td><?php echo formatSizeUnits($file['size']); ?></td>
                                        <td><?php echo date('d-m-Y H:i:s', $file['time']); ?></td>
                                        <td>
                                            <a href="javascript:void(0);" class="btn btn-success btn-sm" onclick="startDownload('<?php echo $file['name']; ?>')" title="Unduh">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="javascript:void(0);" class="btn btn-danger btn-sm" onclick="confirmBackupDelete('<?php echo $file['name']; ?>', event)" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function startDownload(filename) {
    Swal.fire({
        title: 'Menyiapkan Unduhan',
        html: 'Mohon tunggu, sedang memproses file...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    setTimeout(() => {
        // Trigger download
        window.location.href = 'download.php?file=' + filename;
        
        // Show success alert
        Swal.fire({
            icon: 'success',
            title: 'Download Dimulai',
            text: 'File backup sedang diunduh ke perangkat Anda.',
            timer: 2000,
            showConfirmButton: false
        });
    }, 2000); // 2 seconds delay
}

function doBackup() {
    Swal.fire({
        title: 'Memproses Backup',
        html: 'Mohon tunggu sebentar...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const minDelay = new Promise(resolve => setTimeout(resolve, 2000));
    const request = fetch('action.php?action=backup').then(response => response.json());

    Promise.all([minDelay, request])
    .then(([_, data]) => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Error', 'Terjadi kesalahan pada server: ' + error, 'error');
        console.error(error);
    });
}

function confirmBackupDelete(filename, event) {
    if(event) event.preventDefault();
    
    Swal.fire({
        title: 'Hapus Backup?',
        text: "File backup " + filename + " akan dihapus permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#198754',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            processDeleteBackup(filename);
        }
    });
}

function processDeleteBackup(filename) {
    Swal.fire({
        title: 'Menghapus Backup',
        html: 'Mohon tunggu sebentar...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let formData = new FormData();
    formData.append('filename', filename);

    const minDelay = new Promise(resolve => setTimeout(resolve, 2000));
    const request = fetch('action.php?action=delete', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Cek jika response bukan JSON valid
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json();
        } else {
            return response.text().then(text => {
                throw new Error("Server returned non-JSON response: " + text.substring(0, 100));
            });
        }
    });

    Promise.all([minDelay, request])
    .then(([_, data]) => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Terhapus',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Error', 'Terjadi kesalahan: ' + error.message, 'error');
        console.error(error);
    });
}

document.getElementById('formRestore').addEventListener('submit', function(e) {
    e.preventDefault();
    
    Swal.fire({
        title: 'Konfirmasi Restore',
        text: "Database saat ini akan ditimpa! Lanjutkan?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#198754',
        confirmButtonText: 'Ya, Restore!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            let formData = new FormData(this);
            
            Swal.fire({
                title: 'Memproses Restore',
                html: 'Mohon tunggu, proses ini mungkin memakan waktu...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('action.php?action=restore', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: data.message,
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                console.error(error);
            });
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>