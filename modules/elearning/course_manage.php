<?php
include '../../config/database.php';
$page_title = 'Kelola Kelas Online';
if (!isset($_SESSION['level'])) { $_SESSION['level'] = 'admin'; }
$level = $_SESSION['level'];
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Ensure table exists
$exists = mysqli_query($koneksi, "SHOW TABLES LIKE 'course_students'");
if (mysqli_num_rows($exists) == 0) {
    mysqli_query($koneksi, "CREATE TABLE `course_students` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `course_id` int(11) NOT NULL,
        `siswa_id` int(11) NOT NULL,
        `added_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    )");
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$course = null;

if ($course_id > 0) {
    $qc = mysqli_query($koneksi, "
        SELECT c.*, k.nama_kelas, m.nama_mapel, u.nama_lengkap AS nama_guru 
        FROM courses c 
        JOIN kelas k ON c.id_kelas=k.id_kelas 
        JOIN mapel m ON c.id_mapel=m.id_mapel 
        JOIN users u ON c.pengampu=u.id_user 
        WHERE c.id_course=".$course_id
    );
    if ($qc && mysqli_num_rows($qc) > 0) {
        $course = mysqli_fetch_assoc($qc);
        if ($level === 'guru' && (int)$course['pengampu'] !== (int)$uid) {
            $course = null;
        }
        if ($level === 'siswa' && isset($_SESSION['id_kelas']) && (int)$course['id_kelas'] !== (int)$_SESSION['id_kelas']) {
            $course = null;
        }
    }
}

if (!$course) {
    include '../../includes/header.php';
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Akses Ditolak",
                text: "Kelas Online tidak ditemukan atau Anda tidak memiliki akses.",
                icon: "error",
                confirmButtonText: "Kembali",
                allowOutsideClick: false
            }).then((result) => {
                window.location.href = "../../index.php";
            });
        });
    </script>';
    include '../../includes/footer.php';
    exit;
}

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $sid = (int)$_POST['siswa_id'];
    if ($sid > 0) {
        $existsRow = mysqli_query($koneksi, "SELECT id FROM course_students WHERE course_id=".$course_id." AND siswa_id=".$sid);
        if ($existsRow && mysqli_num_rows($existsRow) == 0) {
            mysqli_query($koneksi, "INSERT INTO course_students(course_id,siswa_id) VALUES(".$course_id.",".$sid.")");
        }
    }
    // Redirect to prevent resubmission
    header("Location: course_manage.php?course_id=".$course_id."&tab=siswa&status=added");
    exit;
}

// Handle Add All
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_all'])) {
    $sql = "
        INSERT INTO course_students(course_id, siswa_id)
        SELECT ".$course_id.", s.id_siswa
        FROM siswa s
        WHERE s.id_kelas=".$course['id_kelas']." AND s.status='aktif'
        AND NOT EXISTS (
            SELECT 1 FROM course_students cs 
            WHERE cs.course_id=".$course_id." AND cs.siswa_id=s.id_siswa
        )
    ";
    mysqli_query($koneksi, $sql);
    header("Location: course_manage.php?course_id=".$course_id."&tab=siswa&status=added");
    exit;
}

// Handle Remove Student
if (isset($_GET['remove_id'])) {
    $rid = (int)$_GET['remove_id'];
    if ($rid > 0) {
        mysqli_query($koneksi, "DELETE FROM course_students WHERE id=".$rid." AND course_id=".$course_id);
        header("Location: course_manage.php?course_id=".$course_id."&tab=siswa&status=removed");
        exit;
    }
}

// Handle Forum Post Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $role = ($level === 'admin') ? 'admin' : (($level === 'guru') ? 'guru' : 'siswa');
    $content = mysqli_real_escape_string($koneksi, $_POST['content']);
    $image_path = null;
    $file_path = null;

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($ext), $allowed)) {
            $new_name = 'img_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], '../../assets/uploads/forum/' . $new_name)) {
                $image_path = $new_name;
            }
        }
    }

    // Handle File Upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'];
        if (in_array(strtolower($ext), $allowed)) {
            $new_name = 'doc_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['file']['tmp_name'], '../../assets/uploads/forum/' . $new_name)) {
                $file_path = $new_name;
            }
        }
    }

    if (!empty($content) || $image_path || $file_path) {
        $title = substr($content, 0, 50) . '...'; 
        $class_id = $course['id_kelas'];
        mysqli_query($koneksi, "INSERT INTO forum_topics(class_id, course_id, title, content, image, file, created_by, author_role) VALUES($class_id, $course_id, '$title', '$content', '$image_path', '$file_path', $uid, '$role')");
        header("Location: course_manage.php?course_id=".$course_id."&tab=info&status=posted");
        exit;
    }
}

// Handle Attendance Submission - REMOVED (Moved to Dashboard)

if (!function_exists('get_indo_day')) {
    function get_indo_day($day_en) {
        $days = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];
        return isset($days[$day_en]) ? $days[$day_en] : $day_en;
    }
}

// Forum Helper Function
if (!function_exists('render_comments')) {
    function render_comments($comments, $children, $parent_id = 0) {
        $list = ($parent_id == 0) ? $comments : (isset($children[$parent_id]) ? $children[$parent_id] : []);
        
        foreach ($list as $r) {
            $r_id = $r['id_reply'];
            $margin = ($parent_id == 0) ? 'mb-2' : 'mt-2';
            
            echo '<div class="d-flex '.$margin.'" id="comment-'.$r_id.'">';
            echo '  <div class="flex-shrink-0">';
            $dn = isset($r['display_name']) ? $r['display_name'] : ($r['nama_lengkap'] ?? '');
            echo '      <div class="avatar-circle" style="width: 32px; height: 32px; font-size: 14px;">'.strtoupper(substr($dn, 0, 1)).'</div>';
            echo '  </div>';
            echo '  <div class="flex-grow-1 ms-2">';
            echo '      <div class="bg-light p-2 rounded d-inline-block">';
            echo '          <div class="fw-bold" style="font-size: 0.9rem;">'.htmlspecialchars($dn).'</div>';
            if (!empty($r['sticker_code'])) {
                echo '          <div style="font-size: 2rem; line-height: 1.2;">'.htmlspecialchars($r['sticker_code']).'</div>';
            } else {
                echo '          <div style="font-size: 0.9rem;">'.nl2br(htmlspecialchars($r['content'] ?? '')).'</div>';
            }
            echo '      </div>';
            echo '      <div class="d-flex align-items-center mt-1">';
            echo '          <small class="text-muted me-3" style="font-size: 0.75rem;">'.date('d/m H:i', strtotime($r['created_at'])).'</small>';
            echo '          <a href="javascript:void(0)" onclick="toggleReplyLike('.$r_id.')" class="text-decoration-none fw-bold text-muted me-2" style="font-size: 0.75rem;">Suka</a>';
            echo '          <span id="reply-like-count-'.$r_id.'" class="text-muted me-3" style="font-size: 0.75rem;"></span>';
            echo '          <a href="javascript:void(0)" onclick="showReplyForm('.$r_id.')" class="text-decoration-none fw-bold text-muted" style="font-size: 0.75rem;">Balas</a>';
            echo '      </div>';
            echo '      <div id="replies-'.$r_id.'" class="mt-2 ps-3 border-start">';
            if (isset($children[$r_id])) {
                render_comments([], $children, $r_id);
            }
            echo '      </div>';
            echo '      <div id="reply-form-'.$r_id.'" class="mt-2" style="display:none;">';
            echo '          <div class="d-flex align-items-center">';
            echo '              <div class="avatar-circle me-2" style="width: 24px; height: 24px; font-size: 10px;">'.strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)).'</div>';
            echo '              <input type="text" class="form-control form-control-sm rounded-pill bg-light border-0" placeholder="Tulis balasan..." id="input-reply-'.$r_id.'" onkeypress="handleReply(event, '.$r['topic_id'].', '.$r_id.')">';
            echo '              <button class="btn btn-link text-muted p-0 ms-2" onclick="toggleEmojiPicker(\'input-reply-'.$r_id.'\', '.$r['topic_id'].', '.$r_id.')"><i class="far fa-smile"></i></button>';
            echo '              <button class="btn btn-link text-muted p-0 ms-2" onclick="toggleStickerPicker('.$r['topic_id'].', '.$r_id.')"><i class="far fa-sticky-note"></i></button>';
            echo '          </div>';
            echo '          <div id="emoji-picker-'.$r_id.'" class="mt-1" style="display:none;">';
            echo '              <span class="me-2" onclick="insertEmoji(\'input-reply-'.$r_id.'\', \'😀\')">😀</span>';
            echo '              <span class="me-2" onclick="insertEmoji(\'input-reply-'.$r_id.'\', \'😎\')">😎</span>';
            echo '              <span class="me-2" onclick="insertEmoji(\'input-reply-'.$r_id.'\', \'👍\')">👍</span>';
            echo '              <span class="me-2" onclick="insertEmoji(\'input-reply-'.$r_id.'\', \'❤️\')">❤️</span>';
            echo '              <span class="me-2" onclick="insertEmoji(\'input-reply-'.$r_id.'\', \'🎉\')">🎉</span>';
            echo '              <span class="me-2" onclick="insertEmoji(\'input-reply-'.$r_id.'\', \'🙏\')">🙏</span>';
            echo '          </div>';
            echo '          <div id="sticker-picker-'.$r_id.'" class="mt-1" style="display:none;">';
            echo '              <span class="me-2" onclick="sendSticker('.$r['topic_id'].', '.$r_id.', \'😀\')">😀</span>';
            echo '              <span class="me-2" onclick="sendSticker('.$r['topic_id'].', '.$r_id.', \'😎\')">😎</span>';
            echo '              <span class="me-2" onclick="sendSticker('.$r['topic_id'].', '.$r_id.', \'👍\')">👍</span>';
            echo '              <span class="me-2" onclick="sendSticker('.$r['topic_id'].', '.$r_id.', \'❤️\')">❤️</span>';
            echo '              <span class="me-2" onclick="sendSticker('.$r['topic_id'].', '.$r_id.', \'🎉\')">🎉</span>';
            echo '              <span class="me-2" onclick="sendSticker('.$r['topic_id'].', '.$r_id.', \'🙏\')">🙏</span>';
            echo '          </div>';
            echo '      </div>';
            echo '  </div>';
            echo '</div>';
        }
    }
}

include '../../includes/header.php';

// Fetch Data
$enrolled = mysqli_query($koneksi, "SELECT cs.id, s.id_siswa, s.nisn, s.nama_siswa FROM course_students cs JOIN siswa s ON cs.siswa_id=s.id_siswa WHERE cs.course_id=".$course_id." ORDER BY s.nama_siswa ASC");
$student_count = mysqli_num_rows($enrolled);

$available = mysqli_query($koneksi, "SELECT s.id_siswa, s.nisn, s.nama_siswa FROM siswa s WHERE s.id_kelas=".$course['id_kelas']." AND s.status='aktif' AND s.id_siswa NOT IN (SELECT siswa_id FROM course_students WHERE course_id=".$course_id.") ORDER BY s.nama_siswa ASC");

$assignments = mysqli_query($koneksi, "SELECT * FROM assignments WHERE course_id=".$course_id." ORDER BY created_at DESC");
$materials = mysqli_query($koneksi, "SELECT * FROM materials WHERE course_id=".$course_id." ORDER BY created_at DESC");

// Fetch Forum Topics for this course
$topicsQ = mysqli_query($koneksi, "
    SELECT t.*, k.nama_kelas, 
    CASE 
        WHEN t.author_role = 'siswa' THEN s.nama_siswa
        ELSE u.nama_lengkap 
    END AS nama_lengkap,
    (SELECT COUNT(*) FROM forum_replies r WHERE r.topic_id=t.id_topic) AS comment_count,
    (SELECT COUNT(*) FROM forum_likes l WHERE l.topic_id=t.id_topic) AS like_count,
    (SELECT COUNT(*) FROM forum_likes l2 WHERE l2.topic_id=t.id_topic AND l2.user_id=$uid) AS is_liked
    FROM forum_topics t 
    LEFT JOIN kelas k ON t.class_id=k.id_kelas 
    LEFT JOIN users u ON t.created_by=u.id_user 
    LEFT JOIN siswa s ON t.created_by=s.id_siswa
    WHERE t.course_id = $course_id
    ORDER BY t.created_at DESC
");

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'info';
?>

<style>
    .avatar-circle {
        width: 40px;
        height: 40px;
        background-color: #0d6efd;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
    }
    .post-card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .post-header {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    .post-info {
        margin-left: 10px;
    }
    .post-author {
        font-weight: bold;
        color: #333;
        text-decoration: none;
    }
    .post-time {
        font-size: 0.8rem;
        color: #777;
    }
    .post-content {
        font-size: 1rem;
        color: #111;
        margin-bottom: 10px;
        white-space: pre-wrap;
    }
    .post-image {
        width: 100%;
        border-radius: 8px;
        margin-top: 10px;
        border: 1px solid #eee;
    }
    .post-file {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #ddd;
        margin-top: 10px;
        display: flex;
        align-items: center;
    }
    .post-actions {
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
        padding: 5px 0;
        margin-top: 10px;
        display: flex;
        justify-content: space-around;
    }
    .action-btn {
        background: none;
        border: none;
        color: #65676b;
        font-weight: 600;
        padding: 5px 20px;
        border-radius: 5px;
        width: 45%;
        transition: 0.2s;
    }
    .action-btn:hover {
        background-color: #f0f2f5;
    }
    .action-btn.liked {
        color: #0d6efd;
    }
    .create-post-card {
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
        <h1 class="h3 mb-0 text-gray-800"><?php echo htmlspecialchars($course['nama_mapel']); ?> - <?php echo htmlspecialchars($course['nama_kelas']); ?></h1>
        <a href="courses.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'info' ? 'active' : ''; ?>" href="?course_id=<?php echo $course_id; ?>&tab=info">Info Kelas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'siswa' ? 'active' : ''; ?>" href="?course_id=<?php echo $course_id; ?>&tab=siswa">Siswa (<?php echo $student_count; ?>)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'tugas' ? 'active' : ''; ?>" href="?course_id=<?php echo $course_id; ?>&tab=tugas">Tugas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'materi' ? 'active' : ''; ?>" href="?course_id=<?php echo $course_id; ?>&tab=materi">Materi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'kehadiran' ? 'active' : ''; ?>" href="?course_id=<?php echo $course_id; ?>&tab=kehadiran">Kehadiran</a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        
        <!-- INFO TAB -->
        <div class="tab-pane fade <?php echo $active_tab == 'info' ? 'show active' : ''; ?>">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Kelas</h6>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <table class="table table-borderless table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-secondary" style="width: 180px;">Kode Kelas Online</td>
                                        <td style="width: 20px;">:</td>
                                        <td class="text-dark fw-bold"><?php echo htmlspecialchars($course['kode_course']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-secondary">Mata Pelajaran</td>
                                        <td>:</td>
                                        <td class="text-dark"><?php echo htmlspecialchars($course['nama_mapel']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-secondary">Kelas</td>
                                        <td>:</td>
                                        <td class="text-dark"><?php echo htmlspecialchars($course['nama_kelas']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-secondary">Guru Pengampu</td>
                                        <td>:</td>
                                        <td class="text-dark"><?php echo htmlspecialchars($course['nama_guru']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-secondary">Jumlah Siswa</td>
                                        <td>:</td>
                                        <td class="text-dark"><span class="badge bg-info rounded-pill"><?php echo $student_count; ?> Siswa</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-secondary">Tanggal Dibuat</td>
                                        <td>:</td>
                                        <td class="text-dark"><?php echo date('d F Y', strtotime($course['created_at'])); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-4 text-center d-none d-md-block">
                             <i class="fas fa-chalkboard-teacher text-gray-200" style="font-size: 10rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORUM SECTION -->
            <hr class="my-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 text-gray-800"><i class="fas fa-comments text-primary"></i> Forum Diskusi Kelas</h4>
            </div>
            
            <!-- Create Post -->
            <div class="card create-post-card">
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="create_post" value="1">
                        <div class="d-flex mb-3">
                            <div class="avatar-circle me-2">
                                <?php 
                                $my_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'U';
                                echo strtoupper(substr($my_name, 0, 1)); 
                                ?>
                            </div>
                            <textarea name="content" class="form-control bg-light border-0 rounded-3" rows="2" placeholder="Apa yang ingin Anda diskusikan di kelas ini?"></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <div class="d-flex">
                                <label class="btn btn-light btn-sm rounded-pill me-2 text-primary">
                                    <i class="fas fa-image"></i> Foto
                                    <input type="file" name="image" class="d-none" accept="image/*">
                                </label>
                                <label class="btn btn-light btn-sm rounded-pill text-danger">
                                    <i class="fas fa-paperclip"></i> File
                                    <input type="file" name="file" class="d-none" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar">
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">Kirim</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Topics List -->
            <div id="topics-container">
            <?php while($t = mysqli_fetch_assoc($topicsQ)): ?>
                <div class="card post-card">
                    <div class="card-body">
                        <div class="post-header">
                            <div class="avatar-circle">
                                <?php echo strtoupper(substr($t['nama_lengkap'] ?? '', 0, 1)); ?>
                            </div>
                            <div class="post-info">
                                <a href="#" class="post-author"><?php echo htmlspecialchars($t['nama_lengkap'] ?? ''); ?></a>
                                <div class="post-time"><?php echo date('d M Y H:i', strtotime($t['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="post-content"><?php echo htmlspecialchars($t['content'] ?? ''); ?></div>
                        
                        <?php if(!empty($t['image'])): ?>
                            <img src="../../assets/uploads/forum/<?php echo $t['image']; ?>" class="post-image" alt="Post Image">
                        <?php endif; ?>
                        
                        <?php if(!empty($t['file'])): ?>
                            <a href="../../assets/uploads/forum/<?php echo $t['file']; ?>" class="text-decoration-none" target="_blank">
                                <div class="post-file">
                                    <i class="fas fa-file-alt fa-2x text-primary me-3"></i>
                                    <div>
                                        <div class="fw-bold text-dark">Lampiran File</div>
                                        <small class="text-muted">Klik untuk mengunduh</small>
                                    </div>
                                </div>
                            </a>
                        <?php endif; ?>
                        
                        <div class="post-actions">
                            <button class="action-btn <?php echo ($t['is_liked'] > 0) ? 'liked' : ''; ?>" id="btn-like-<?php echo $t['id_topic']; ?>" onclick="toggleLike(<?php echo $t['id_topic']; ?>)">
                                <i class="<?php echo ($t['is_liked'] > 0) ? 'fas' : 'far'; ?> fa-thumbs-up"></i> Suka <span id="like-count-<?php echo $t['id_topic']; ?>"><?php echo ($t['like_count'] > 0) ? $t['like_count'] : ''; ?></span>
                            </button>
                            <button class="action-btn" onclick="$('#input-comment-<?php echo $t['id_topic']; ?>').focus()">
                                <i class="far fa-comment-alt"></i> Komentar (<?php echo $t['comment_count']; ?>)
                            </button>
                        </div>
                        
                        <!-- Comments Section -->
                        <div class="comments-section" id="comments-<?php echo $t['id_topic']; ?>">
                            <div id="comment-list-<?php echo $t['id_topic']; ?>">
                                <?php
                                $commentsQ = mysqli_query($koneksi, "
                                    SELECT r.*, 
                                    CASE 
                                        WHEN r.user_role = 'siswa' THEN s.nama_siswa
                                        ELSE u.nama_lengkap 
                                    END AS display_name
                                    FROM forum_replies r 
                                    LEFT JOIN users u ON r.user_id=u.id_user 
                                    LEFT JOIN siswa s ON r.user_id=s.id_siswa
                                    WHERE r.topic_id=".$t['id_topic']." 
                                    ORDER BY r.created_at ASC
                                ");
                                $comments = [];
                                $children = [];
                                while($cm = mysqli_fetch_assoc($commentsQ)) {
                                    if ($cm['parent_reply_id'] == 0) {
                                        $comments[] = $cm;
                                    } else {
                                        $children[$cm['parent_reply_id']][] = $cm;
                                    }
                                }
                                render_comments($comments, $children);
                                ?>
                            </div>
                            
                            <div class="d-flex mt-2 align-items-center">
                                <div class="avatar-circle me-2" style="width: 32px; height: 32px; font-size: 14px;">
                                    <?php echo strtoupper(substr($my_name, 0, 1)); ?>
                                </div>
                                <input type="text" class="form-control rounded-pill bg-light border-0" placeholder="Tulis komentar..." id="input-comment-<?php echo $t['id_topic']; ?>" onkeypress="handleComment(event, <?php echo $t['id_topic']; ?>)">
                                <button class="btn btn-link text-primary p-0 ms-2" onclick="postComment(<?php echo $t['id_topic']; ?>)"><i class="fas fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>
            
            <?php if(mysqli_num_rows($topicsQ) == 0): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-comments fa-3x mb-3"></i>
                    <p>Belum ada diskusi di kelas ini. Jadilah yang pertama memulai!</p>
                </div>
            <?php endif; ?>

        </div>

        <!-- SISWA TAB -->
        <div class="tab-pane fade <?php echo $active_tab == 'siswa' ? 'show active' : ''; ?>">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa</h6>
                    <?php if($level === 'guru'): ?>
                        <div>
                            <form method="post" class="d-inline" onsubmit="return confirmAddAll(this);">
                                <input type="hidden" name="add_all" value="1">
                                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-users"></i> Tambah Semua</button>
                            </form>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-plus"></i> Tambah Siswa</button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NISN</th>
                                    <th>Nama Siswa</th>
                                    <?php if($level === 'guru'): ?>
                                    <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; while($row = mysqli_fetch_assoc($enrolled)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nisn']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_siswa']); ?></td>
                                    <?php if($level === 'guru'): ?>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmRemoveStudent('?course_id=<?php echo $course_id; ?>&tab=siswa&remove_id=<?php echo $row['id']; ?>')"><i class="fas fa-user-times"></i> Keluarkan</button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TUGAS TAB -->
         <div class="tab-pane fade <?php echo $active_tab == 'tugas' ? 'show active' : ''; ?>">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Tugas</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php while($asg = mysqli_fetch_assoc($assignments)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 shadow-sm border-left-primary">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="h5 font-weight-bold text-primary text-uppercase mb-1"><?php echo htmlspecialchars($asg['judul']); ?></div>
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                <i class="fas fa-clock"></i> <?php echo date('d M Y', strtotime($asg['deadline'])); ?>
                                            </div>
                                        </div>
                                        <p class="card-text mb-3"><?php echo htmlspecialchars(substr($asg['deskripsi'] ?? '', 0, 100)); ?>...</p>
                                        <div class="d-flex justify-content-between">
                                             <button class="btn btn-secondary btn-sm" onclick="showDetail(<?php echo htmlspecialchars(json_encode($asg), ENT_QUOTES, 'UTF-8'); ?>)">Detail</button>
                                             <div>
                                                <?php if($level === 'guru'): ?>
                                                    <?php if($asg['jenis_tugas'] == 'CBT'): ?>
                                                        <a href="../tes/hasil_ujian.php?id_kelas=<?php echo $course['id_kelas']; ?>" class="btn btn-info btn-sm">Lihat Hasil Asesmen</a>
                                                    <?php else: ?>
                                                        <a href="submissions.php?assignment_id=<?php echo $asg['id_assignment']; ?>" class="btn btn-info btn-sm">Lihat Pengumpulan</a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <a href="student_assignments.php?id=<?php echo $asg['id_assignment']; ?>" class="btn btn-success btn-sm">Kerjakan</a>
                                                <?php endif; ?>
                                             </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <?php if(mysqli_num_rows($assignments) == 0): ?>
                        <div class="text-center p-3">Belum ada tugas.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- MATERI TAB -->
         <div class="tab-pane fade <?php echo $active_tab == 'materi' ? 'show active' : ''; ?>">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Materi Pembelajaran</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                         <?php while($mat = mysqli_fetch_assoc($materials)): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 shadow-sm border-left-info">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <?php 
                                            $icon = 'fa-file-alt';
                                            if($mat['tipe'] == 'pdf') $icon = 'fa-file-pdf';
                                            elseif($mat['tipe'] == 'ppt') $icon = 'fa-file-powerpoint';
                                            elseif($mat['tipe'] == 'video') $icon = 'fa-video';
                                            elseif($mat['tipe'] == 'link') $icon = 'fa-link';
                                            ?>
                                            <i class="fas <?php echo $icon; ?> fa-3x text-primary"></i>
                                        </div>
                                        <h5 class="card-title font-weight-bold text-dark mb-1"><?php echo htmlspecialchars($mat['judul']); ?></h5>
                                        <small class="text-muted d-block mb-3"><?php echo date('d M Y', strtotime($mat['created_at'])); ?></small>
                                        
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-info btn-sm flex-fill btn-preview" 
                                                data-id="<?php echo $mat['id_material']; ?>"
                                                data-tipe="<?php echo $mat['tipe']; ?>"
                                                data-path="<?php echo ($mat['tipe']=='link') ? $mat['path'] : '../../'.$mat['path']; ?>"
                                                data-judul="<?php echo htmlspecialchars($mat['judul']); ?>">
                                                <i class="fas fa-eye"></i> Preview
                                            </button>
                                            <?php if($mat['tipe'] == 'link'): ?>
                                                <a href="<?php echo $mat['path']; ?>" target="_blank" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-external-link-alt"></i> Buka</a>
                                            <?php else: ?>
                                                <a href="../../<?php echo $mat['path']; ?>" download class="btn btn-success btn-sm flex-fill"><i class="fas fa-download"></i> Unduh</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <?php if(mysqli_num_rows($materials) == 0): ?>
                        <div class="text-center p-3">Belum ada materi.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- KEHADIRAN TAB -->
        <div class="tab-pane fade <?php echo $active_tab == 'kehadiran' ? 'show active' : ''; ?>" id="kehadiran" role="tabpanel">
            
            <!-- Riwayat Kehadiran -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Riwayat Kehadiran Kelas Ini</h6>
                </div>
                <div class="card-body">
                    <div class="card bg-info bg-opacity-10 border border-info text-info p-3 rounded mb-3">
                        <i class="fas fa-info-circle me-1"></i> Absensi dilakukan melalui halaman Dashboard Utama sesuai jadwal pelajaran.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Siswa</th>
                                    <th>Waktu</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($level == 'siswa') {
                                    // Show ONLY attendance for THIS course (linked via Schedule)
                                    $q_absen = mysqli_query($koneksi, "SELECT a.*, s.nama_siswa FROM absensi a JOIN siswa s ON a.id_siswa=s.id_siswa WHERE a.id_siswa='$uid' AND a.id_course='$course_id' ORDER BY a.tanggal DESC");
                                } else {
                                    // Guru sees all students in this class for THIS course
                                    $q_absen = mysqli_query($koneksi, "
                                        SELECT a.*, s.nama_siswa 
                                        FROM absensi a 
                                        JOIN course_students cs ON a.id_siswa = cs.siswa_id
                                        JOIN siswa s ON a.id_siswa=s.id_siswa 
                                        WHERE cs.course_id='$course_id' AND a.id_course='$course_id'
                                        ORDER BY a.tanggal DESC, a.jam_masuk ASC
                                    ");
                                }
                                
                                if (mysqli_num_rows($q_absen) > 0) {
                                    $no = 1;
                                    while($row = mysqli_fetch_assoc($q_absen)):
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_siswa']); ?></td>
                                        <td><?php echo date('H:i', strtotime($row['jam_masuk'])); ?></td>
                                        <td>
                                            <?php 
                                            $badge = 'secondary';
                                            if ($row['status'] == 'Hadir') $badge = 'success';
                                            if ($row['status'] == 'Sakit') $badge = 'warning';
                                            if ($row['status'] == 'Izin') $badge = 'info';
                                            if ($row['status'] == 'Alpha') $badge = 'danger';
                                            ?>
                                            <span class="badge bg-<?php echo $badge; ?>"><?php echo $row['status']; ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                    </tr>
                                    <?php endwhile; 
                                } else {
                                    echo '<tr><td colspan="6" class="text-center">Belum ada data kehadiran.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modals for Sakit/Izin -->
<?php if ($level == 'siswa' && isset($attendance_today) && !$attendance_today): ?>
<!-- Modals Removed as requested -->
<?php endif; ?>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
      <div class="modal-header">
        <h5 class="modal-title" id="addStudentModalLabel">Tambah Siswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="add_student" value="1">
        <div class="mb-3">
            <label class="form-label">Pilih Siswa</label>
            <select name="siswa_id" class="form-select" required>
                <option value="">-- Pilih Siswa --</option>
                <?php while($s = mysqli_fetch_assoc($available)): ?>
                    <option value="<?php echo $s['id_siswa']; ?>"><?php echo $s['nama_siswa']; ?> (<?php echo $s['nisn']; ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Detail Tugas -->
<div class="modal fade" id="modalDetailAssignment" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailJudul">Detail Tugas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h5 id="detailJudulText" class="mb-3"></h5>
        <p><strong>Jenis Tugas:</strong> <span id="detailJenis"></span></p>
        <p><strong>Tenggat:</strong> <span id="detailDeadline"></span></p>
        <p><strong>Deskripsi:</strong></p>
        <div id="detailDeskripsi" class="bg-light p-3 rounded" style="white-space: pre-wrap;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function showDetail(data) {
    document.getElementById('detailJudulText').innerText = data.judul;
    document.getElementById('detailJenis').innerText = data.jenis_tugas;
    document.getElementById('detailDeadline').innerText = new Date(data.deadline).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' });
    document.getElementById('detailDeskripsi').innerText = data.deskripsi;
    var myModal = new bootstrap.Modal(document.getElementById('modalDetailAssignment'));
    myModal.show();
}

function confirmRemoveStudent(url) {
    Swal.fire({
        title: 'Keluarkan Siswa?',
        text: "Siswa akan dikeluarkan dari kelas online ini dan akan kembali ke daftar siswa yang tersedia untuk ditambahkan.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Keluarkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    })
}

function confirmAddAll(form) {
    Swal.fire({
        title: 'Tambahkan Semua?',
        text: "Semua siswa yang tersedia di kelas ini akan dimasukkan ke kelas online.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Tambahkan Semua!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    })
    return false;
}

// Check for status parameter to show success alerts
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    
    if (status === 'removed') {
        Swal.fire({
            title: 'Dikeluarkan!',
            text: 'Siswa berhasil dikeluarkan dari kelas.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        // Clean URL
        window.history.replaceState(null, null, window.location.pathname + "?course_id=" + urlParams.get('course_id') + "&tab=siswa");
    } else if (status === 'added') {
        Swal.fire({
            title: 'Ditambahkan!',
            text: 'Siswa berhasil ditambahkan ke kelas.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        // Clean URL
        window.history.replaceState(null, null, window.location.pathname + "?course_id=" + urlParams.get('course_id') + "&tab=siswa");
    } else if (status === 'posted') {
        Swal.fire({
            title: 'Terposting!',
            text: 'Postingan berhasil dibuat.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        // Clean URL
        window.history.replaceState(null, null, window.location.pathname + "?course_id=" + urlParams.get('course_id') + "&tab=info");
    } else if (status === 'saved') {
        Swal.fire({
            title: 'Berhasil!',
            text: 'Absensi berhasil disimpan.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        // Clean URL
        window.history.replaceState(null, null, window.location.pathname + "?course_id=" + urlParams.get('course_id') + "&tab=kehadiran");
    }
});
</script>

<script>
function toggleLike(topicId) {
    $.post('forum_ajax.php', { action: 'like_toggle', topic_id: topicId }, function(data) {
        const res = JSON.parse(data);
        if (res.status === 'success') {
            const btn = $('#btn-like-' + topicId);
            if (res.liked) {
                btn.addClass('liked');
                btn.html('<i class="fas fa-thumbs-up"></i> Suka <span id="like-count-'+topicId+'">'+(res.count > 0 ? res.count : '')+'</span>');
            } else {
                btn.removeClass('liked');
                btn.html('<i class="far fa-thumbs-up"></i> Suka <span id="like-count-'+topicId+'">'+(res.count > 0 ? res.count : '')+'</span>');
            }
        }
    });
}

function toggleComments(topicId) {
    $('#comments-' + topicId).slideToggle();
    $('#input-comment-' + topicId).focus();
}

function showReplyForm(commentId) {
    $('#reply-form-' + commentId).slideToggle();
    $('#input-reply-' + commentId).focus();
}

function handleReply(e, topicId, parentId) {
    if (e.key === 'Enter') {
        postComment(topicId, parentId);
    }
}

function handleComment(e, topicId) {
    if (e.key === 'Enter') {
        postComment(topicId, 0);
    }
}

function postComment(topicId, parentId = 0) {
    let input;
    if (parentId === 0) {
        input = $('#input-comment-' + topicId);
    } else {
        input = $('#input-reply-' + parentId);
    }
    
    const content = input.val().trim();
    if (!content) return;

    $.post('forum_ajax.php', { action: 'post_comment', topic_id: topicId, parent_id: parentId, content: content }, function(data) {
        const res = JSON.parse(data);
        if (res.status === 'success') {
            if (res.parent_id == 0) {
                $('#comment-list-' + topicId).append(res.html);
            } else {
                $('#replies-' + res.parent_id).append(res.html);
                $('#reply-form-' + res.parent_id).hide();
            }
            input.val('');
        }
    });
}

function toggleReplyLike(replyId) {
    $.post('forum_ajax.php', { action: 'reply_like_toggle', reply_id: replyId }, function(data) {
        const res = JSON.parse(data);
        if (res.status === 'success') {
            const countSpan = $('#reply-like-count-' + replyId);
            if (res.count > 0) {
                countSpan.text(res.count + ' Suka');
            } else {
                countSpan.text('');
            }
        }
    });
}

function toggleEmojiPicker(inputId, topicId, parentId) {
    const pickerId = '#emoji-picker-' + (parentId || ('t' + topicId));
    $(pickerId).slideToggle();
}

function insertEmoji(inputId, emoji) {
    const input = $('#' + inputId);
    const text = input.val();
    input.val(text + emoji);
    input.focus();
}

function toggleStickerPicker(topicId, parentId) {
    const pickerId = '#sticker-picker-' + parentId;
    $(pickerId).slideToggle();
}

function sendSticker(topicId, parentId, sticker) {
    $.post('forum_ajax.php', { action: 'post_sticker', topic_id: topicId, parent_id: parentId, sticker: sticker }, function(data) {
        const res = JSON.parse(data);
        if (res.status === 'success') {
            $('#replies-' + res.parent_id).append(res.html);
            $('#sticker-picker-' + res.parent_id).hide();
            $('#reply-form-' + res.parent_id).hide();
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>

<!-- Modal Preview (Global) -->
<div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="previewTitle">Preview Materi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="min-height: 500px; background-color: #525659;">
        <div id="previewContainer" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 500px;">
            <!-- Content will be loaded here -->
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" id="btnDownloadOriginal" target="_blank" class="btn btn-primary"><i class="fas fa-download"></i> Download / Buka Asli</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- PDF.js Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
// Set worker for PDF.js
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

document.addEventListener('DOMContentLoaded', function(){
    var modalPreview = new bootstrap.Modal(document.getElementById('modalPreview'));
    var previewContainer = document.getElementById('previewContainer');
    var previewTitle = document.getElementById('previewTitle');
    var btnDownload = document.getElementById('btnDownloadOriginal');

    document.querySelectorAll('.btn-preview').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            var id = this.getAttribute('data-id');
            var tipe = this.getAttribute('data-tipe');
            var path = this.getAttribute('data-path');
            var judul = this.getAttribute('data-judul');

            previewTitle.textContent = judul;
            btnDownload.href = path;
            
            // Reset container
            previewContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center h-100 p-5"><div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            previewContainer.style.backgroundColor = (tipe === 'pdf') ? '#525659' : '#f8f9fa';
            
            modalPreview.show();

            setTimeout(function(){
                var content = '';
                if (tipe === 'pdf') {
                    // Use Proxy Script via fetch to bypass IDM
                    var proxyUrl = 'get_material_content.php?id=' + id;
                    renderPDF(proxyUrl, previewContainer);
                } else if (tipe === 'video') {
                    content = '<video controls width="100%" style="max-height:600px;"><source src="' + path + '" type="video/mp4">Browser anda tidak mendukung tag video.</video>';
                    previewContainer.innerHTML = content;
                } else if (tipe === 'link') {
                     var fullUrl = path;
                     if (!path.startsWith('http')) {
                         fullUrl = 'http://' + path;
                     }
                     
                     // Detect YouTube and convert to embed
                     var youtubeRegExp = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
                     var ytMatch = fullUrl.match(youtubeRegExp);
                     
                     if (ytMatch && ytMatch[1]) {
                         fullUrl = 'https://www.youtube.com/embed/' + ytMatch[1];
                         content = '<iframe src="' + fullUrl + '" width="100%" height="600px" style="border:none;" allowfullscreen></iframe>';
                     } else {
                         // Use Proxy URL to bypass X-Frame-Options
                         var proxyUrl = 'proxy_url.php?url=' + encodeURIComponent(fullUrl);
                         content = '<div class="alert alert-info alert-dismissible fade show m-2 p-2" role="alert" style="font-size:0.85rem;">';
                         content += '<i class="fas fa-info-circle me-1"></i> Menampilkan via Proxy Mode. <a href="' + fullUrl + '" target="_blank" class="fw-bold text-decoration-underline alert-link">Buka Link Asli</a> jika tampilan berantakan.';
                         content += '<button type="button" class="btn-close p-2" data-bs-dismiss="alert" aria-label="Close"></button>';
                         content += '</div>';
                         content += '<iframe src="' + proxyUrl + '" width="100%" height="600px" style="border:none;" sandbox="allow-forms allow-scripts allow-same-origin allow-popups"></iframe>';
                     }
                     
                     previewContainer.innerHTML = content;
                } else if (tipe === 'doc' || tipe === 'docx' || tipe === 'ppt' || tipe === 'pptx') {
                    var fullUrl = path;
                    if (!path.startsWith('http')) {
                        // Warning: Office Viewer needs public URL. Localhost won't work.
                        // We will try anyway, but likely fail on localhost.
                         var a = document.createElement('a');
                         a.href = path;
                         fullUrl = a.href;
                    }
                    content = '<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(fullUrl) + '" width="100%" height="600px" style="border:none;"></iframe>';
                    previewContainer.innerHTML = content;
                } else {
                    content = '<div class="text-center p-5">Format tidak didukung untuk preview. Silakan unduh file.</div>';
                    previewContainer.innerHTML = content;
                }
            }, 500);
        });
    });
    
    // Clear content when modal is hidden
    document.getElementById('modalPreview').addEventListener('hidden.bs.modal', function () {
        previewContainer.innerHTML = '';
    });

    function renderPDF(url, container) {
        container.innerHTML = ''; // Clear spinner
        
        // Use Base64 mode to completely bypass IDM interception
        fetch(url + '&mode=base64')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(base64 => {
            // Decode Base64 to Uint8Array
            var binary_string = window.atob(base64);
            var len = binary_string.length;
            var bytes = new Uint8Array(len);
            for (var i = 0; i < len; i++) {
                bytes[i] = binary_string.charCodeAt(i);
            }
            
            var loadingTask = pdfjsLib.getDocument({data: bytes});
            loadingTask.promise.then(function(pdf) {
                // Loop through all pages
                for (var pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                    pdf.getPage(pageNum).then(function(page) {
                        var scale = 1.5;
                        var viewport = page.getViewport({scale: scale});

                        var canvas = document.createElement('canvas');
                        canvas.className = 'mb-3 shadow-sm';
                        var context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        
                        // Center canvas
                        canvas.style.maxWidth = '100%';
                        canvas.style.height = 'auto';

                        container.appendChild(canvas);

                        var renderContext = {
                            canvasContext: context,
                            viewport: viewport
                        };
                        page.render(renderContext);
                    });
                }
            }, function (reason) {
                // PDF loading error
                console.error(reason);
                container.innerHTML = '<div class="text-center text-white p-5">Gagal memuat preview PDF (Corrupt atau Protected).<br><a href="'+url+'" target="_blank" class="btn btn-light mt-3">Download PDF</a></div>';
            });
        })
        .catch(error => {
            console.error('Fetch error:', error);
            container.innerHTML = '<div class="text-center text-white p-5">Gagal mengambil data PDF.<br><a href="#" class="btn btn-light mt-3">Coba Lagi</a></div>';
        });
    }
});
</script>