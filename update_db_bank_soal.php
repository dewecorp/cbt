<?php
include 'config/database.php';

// Check if column exists
$check = mysqli_query($koneksi, "SHOW COLUMNS FROM bank_soal LIKE 'created_at'");
if (mysqli_num_rows($check) == 0) {
    $sql = "ALTER TABLE bank_soal ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    if (mysqli_query($koneksi, $sql)) {
        echo "Column created_at added successfully.";
    } else {
        echo "Error adding column: " . mysqli_error($koneksi);
    }
} else {
    echo "Column created_at already exists.";
}
?>
