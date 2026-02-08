<?php
include __DIR__ . '/../../config/database.php';
$sql = "ALTER TABLE soal ADD COLUMN bobot DECIMAL(5,2) DEFAULT 1.00";
if(mysqli_query($koneksi, $sql)) {
    echo "Column 'bobot' added successfully.";
} else {
    echo "Error adding column: " . mysqli_error($koneksi);
}
?>
