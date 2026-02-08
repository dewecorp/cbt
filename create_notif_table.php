<?php
require 'config/database.php';

// Check if table exists
$check = mysqli_query($koneksi, "SHOW TABLES LIKE 'notifications'");
if (mysqli_num_rows($check) == 0) {
    $sql = "CREATE TABLE `notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `sender_id` int(11) DEFAULT NULL,
        `sender_role` varchar(20) DEFAULT 'siswa',
        `type` varchar(50) NOT NULL,
        `item_id` int(11) NOT NULL,
        `message` text NOT NULL,
        `link` varchar(255) DEFAULT NULL,
        `is_read` tinyint(1) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `is_read` (`is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (mysqli_query($koneksi, $sql)) {
        echo "Table notifications created successfully.\n";
    } else {
        echo "Error creating table: " . mysqli_error($koneksi) . "\n";
    }
} else {
    echo "Table notifications already exists.\n";
}
?>