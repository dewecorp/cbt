<?php
include 'config/database.php';
echo "Checking User with ID 1:\n";
$q = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user=1");
if(mysqli_num_rows($q) > 0) {
    $r = mysqli_fetch_assoc($q);
    print_r($r);
} else {
    echo "No user with ID 1.\n";
}
?>