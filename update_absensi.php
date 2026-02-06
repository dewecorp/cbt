<?php
require 'config/database.php';
$q = mysqli_query($koneksi, "ALTER TABLE absensi ADD COLUMN id_course INT(11) NULL AFTER id_kelas");
if ($q) {
    echo "Column added successfully";
} else {
    echo "Error: " . mysqli_error($koneksi);
}
?>