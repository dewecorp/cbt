<?php
include 'config/database.php';
$q = mysqli_query($koneksi, "DESCRIBE users");
echo "USERS Table:\n";
while ($r = mysqli_fetch_assoc($q)) {
    echo $r['Field'] . " - " . $r['Type'] . "\n";
}

$q2 = mysqli_query($koneksi, "DESCRIBE mapel");
echo "\nMAPEL Table:\n";
while ($r = mysqli_fetch_assoc($q2)) {
    echo $r['Field'] . " - " . $r['Type'] . "\n";
}
