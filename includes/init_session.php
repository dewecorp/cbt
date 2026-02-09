<?php
// Prevent multiple inclusions
if (defined('INIT_SESSION_INCLUDED')) {
    return;
}
define('INIT_SESSION_INCLUDED', true);

// Handle Multi-Tab Sessions
if (session_status() == PHP_SESSION_NONE) {
    
    $role = null;
    
    // 1. Priority: Explicit Role in URL or POST
    if (isset($_GET['role'])) {
        $role = $_GET['role'];
    } elseif (isset($_POST['role'])) {
        $role = $_POST['role'];
    }
    
    // 2. Context Awareness: Check Script Path
    if (!$role) {
        $path = $_SERVER['PHP_SELF']; // e.g., /cbt/modules/master/guru.php
        
        // Admin Contexts (Modules only accessible by Admin)
        if (strpos($path, '/modules/master/') !== false || 
            strpos($path, '/modules/pengaturan/') !== false ||
            strpos($path, '/modules/users/') !== false ||
            strpos($path, '/modules/laporan/') !== false ||
            strpos($path, '/modules/backup/') !== false ||
            strpos($path, '/modules/dashboard/admin.php') !== false ||
            strpos($path, '/admin.php') !== false) {
            $role = 'admin';
        }
        // Guru Contexts
        elseif (strpos($path, '/teacher.php') !== false || 
                strpos($path, '/modules/dashboard/guru.php') !== false) {
            $role = 'guru';
        }
        // Siswa Contexts
        elseif (strpos($path, '/student.php') !== false || 
                strpos($path, '/modules/dashboard/siswa.php') !== false) {
            $role = 'siswa';
        }
        // Shared Contexts (Modules used by multiple roles) - Try to detect from path keywords if needed
        // but mostly we rely on cookies/fallback if role param is missing.
        elseif (strpos($path, '/modules/tes/') !== false || 
                strpos($path, '/modules/elearning/') !== false ||
                strpos($path, '/modules/cetak/') !== false) {
             // These paths are ambiguous. We don't set $role here to allow Fallback logic to pick the active session.
        }
    }

    // 3. Referer Check (Smart Context Preservation)
    if (!$role && isset($_SERVER['HTTP_REFERER'])) {
        $query = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            if (isset($params['role']) && in_array($params['role'], ['admin', 'guru', 'siswa'])) {
                $role = $params['role'];
            }
        }
    }

    // 4. Set Session Name based on Role
    if ($role === 'siswa') {
        session_name('CBT_SISWA');
    } elseif ($role === 'guru') {
        session_name('CBT_GURU');
    } elseif ($role === 'admin') {
        session_name('CBT_ADMIN');
    } else {
        // 5. Ambiguity Handling
        $has_siswa = isset($_COOKIE['CBT_SISWA']);
        $has_guru = isset($_COOKIE['CBT_GURU']);
        $has_admin = isset($_COOKIE['CBT_ADMIN']);
        $count = ($has_siswa ? 1 : 0) + ($has_guru ? 1 : 0) + ($has_admin ? 1 : 0);

        if ($count > 1) {
            // Multiple sessions exist.
            // STABILITY FIX: Instead of redirecting to account selector (which confuses users), 
            // we default to the most privileged active session.
            // This ensures the page loads ("Normal" behavior) even if role param is missing.
            
            if ($has_admin) {
                session_name('CBT_ADMIN');
            } elseif ($has_guru) {
                session_name('CBT_GURU');
            } elseif ($has_siswa) {
                session_name('CBT_SISWA');
            }
        } elseif ($count === 1) {
             // Only one choice, safe to use.
             if ($has_siswa) session_name('CBT_SISWA');
             elseif ($has_guru) session_name('CBT_GURU');
             elseif ($has_admin) session_name('CBT_ADMIN');
        }
        // If count is 0, let session_start() use default PHPSESSID or create new (Login page)
    }
    
    session_start();
}
?>