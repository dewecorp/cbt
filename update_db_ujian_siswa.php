<?php
include 'config/database.php';

// Check if column exists
$check = mysqli_query($koneksi, "SHOW COLUMNS FROM ujian_siswa LIKE 'tambah_waktu'");
if (mysqli_num_rows($check) == 0) {
    $sql = "ALTER TABLE ujian_siswa ADD COLUMN tambah_waktu INT(11) DEFAULT 0";
    if (mysqli_query($koneksi, $sql)) {
        echo "Column tambah_waktu added successfully.";
    } else {
        echo "Error adding column: " . mysqli_error($koneksi);
    }
} else {
    echo "Column tambah_waktu already exists.";
}
?>
