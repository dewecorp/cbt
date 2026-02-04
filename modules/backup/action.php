<?php
// Matikan error reporting agar tidak merusak format JSON
error_reporting(0);
ini_set('display_errors', 0);

// Buffer output untuk menangkap whitespace/error yang tidak diinginkan
ob_start();

session_start();

try {
    include '../../config/database.php';

    // Cek Level Admin
    if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
        throw new Exception('Akses ditolak');
    }

    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // Bersihkan buffer sebelum mengirim header JSON
    ob_clean();
    header('Content-Type: application/json');

    if ($action == 'backup') {
        // Set Script Time Limit
        set_time_limit(3000); 
        ini_set('memory_limit', '512M'); // Increase memory limit just in case

        // Re-connect using object oriented style
        $mysqli = new mysqli($host, $user, $pass, $db); 
        
        if ($mysqli->connect_error) {
            throw new Exception("Koneksi gagal: " . $mysqli->connect_error);
        }

        $mysqli->select_db($db); 
        $mysqli->query("SET NAMES 'utf8'");

        $tables = array();
        $queryTables = $mysqli->query('SHOW TABLES'); 
        
        if (!$queryTables) {
            throw new Exception("Gagal mengambil daftar tabel: " . $mysqli->error);
        }

        while($row = $queryTables->fetch_row()) { 
            $tables[] = $row[0]; 
        }   

        // Setup file writing
        $filename = "backup_".$db."_".date('Y-m-d_H-i-s').".sql";
        $backupDir = "../../backups/";
        
        // Ensure directory exists
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $path = $backupDir . $filename;
        $handle = fopen($path, 'w');
        if (!$handle) {
            throw new Exception('Gagal membuat file backup (permission denied?)');
        }

        // Write header
        $head = "-- Backup Database CBT Sultan Fattah\n";
        $head .= "-- Tanggal: ".date('d-m-Y H:i:s')."\n";
        $head .= "-- \n\n";
        $head .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $head .= "START TRANSACTION;\n";
        $head .= "SET time_zone = \"+07:00\";\n\n";
        fwrite($handle, $head);

        foreach($tables as $table) {
            $result = $mysqli->query('SELECT * FROM '.$table);  
            if (!$result) continue;

            $fields_amount = $result->field_count;  
            $rows_num = $mysqli->affected_rows;     
            
            $res = $mysqli->query('SHOW CREATE TABLE '.$table); 
            $TableMLine = $res->fetch_row();
            fwrite($handle, "\n\n".$TableMLine[1].";\n\n");

            // Process rows in chunks to save memory
            for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter=0) {
                while($row = $result->fetch_row()) { 
                    if ($st_counter%100 == 0 || $st_counter == 0 ) {
                        fwrite($handle, "\nINSERT INTO ".$table." VALUES");
                    }
                    fwrite($handle, "\n(");
                    for($j=0; $j<$fields_amount; $j++) { 
                        $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) ); 
                        if (isset($row[$j])) {
                            fwrite($handle, '"'.$row[$j].'"'); 
                        } else { 
                            fwrite($handle, '""'); 
                        }     
                        if ($j<($fields_amount-1)) {
                            fwrite($handle, ',');
                        }      
                    }
                    fwrite($handle, ")");
                    if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) { 
                        fwrite($handle, ";");
                    } else {
                        fwrite($handle, ",");
                    } 
                    $st_counter=$st_counter+1;
                }
            } 
            fwrite($handle, "\n\n\n");
            $result->free(); // Free result set
        }
        fwrite($handle, "\nCOMMIT;");
        fclose($handle);

        echo json_encode(['status' => 'success', 'message' => 'Backup berhasil dibuat', 'file' => $filename]);

    } elseif ($action == 'restore') {
        if (isset($_FILES['file_restore']) && $_FILES['file_restore']['error'] == 0) {
            $ext = pathinfo($_FILES['file_restore']['name'], PATHINFO_EXTENSION);
            if ($ext == 'sql') {
                $filename = $_FILES['file_restore']['tmp_name'];
                
                $mysqli = new mysqli($host, $user, $pass, $db);
                if ($mysqli->connect_error) {
                    throw new Exception('Koneksi database gagal: ' . $mysqli->connect_error);
                }

                // Read the file
                $sql = file_get_contents($filename);
                
                // Execute multi query
                if ($mysqli->multi_query($sql)) {
                    do {
                        // Consume results to clear buffer
                        if ($res = $mysqli->store_result()) {
                            $res->free();
                        }
                    } while ($mysqli->more_results() && $mysqli->next_result());
                    
                    echo json_encode(['status' => 'success', 'message' => 'Restore database berhasil']);
                } else {
                    throw new Exception('Gagal restore: ' . $mysqli->error);
                }
            } else {
                 throw new Exception('Format file harus .sql');
            }
        } else {
            throw new Exception('Tidak ada file yang diupload');
        }

    } elseif ($action == 'delete') {
        $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
        if (empty($filename)) throw new Exception('Filename kosong');

        $path = "../../backups/" . basename($filename); // basename for security

        if (file_exists($path) && is_file($path)) {
            if (unlink($path)) {
                echo json_encode(['status' => 'success', 'message' => 'File backup berhasil dihapus']);
            } else {
                throw new Exception('Gagal menghapus file');
            }
        } else {
            throw new Exception('File tidak ditemukan');
        }
    } else {
        throw new Exception('Action tidak valid');
    }

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
ob_end_flush();
?>