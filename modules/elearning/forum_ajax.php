<?php
session_start();
include '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$uid = (int)$_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'like_toggle') {
    $topic_id = (int)$_POST['topic_id'];
    
    // Check if already liked
    $check = mysqli_query($koneksi, "SELECT id FROM forum_likes WHERE topic_id=$topic_id AND user_id=$uid");
    if (mysqli_num_rows($check) > 0) {
        // Unlike
        mysqli_query($koneksi, "DELETE FROM forum_likes WHERE topic_id=$topic_id AND user_id=$uid");
        $liked = false;
    } else {
        // Like
        mysqli_query($koneksi, "INSERT INTO forum_likes (topic_id, user_id) VALUES ($topic_id, $uid)");
        $liked = true;
    }
    
    // Get new count
    $countQ = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM forum_likes WHERE topic_id=$topic_id");
    $count = mysqli_fetch_assoc($countQ)['cnt'];
    
    echo json_encode(['status' => 'success', 'liked' => $liked, 'count' => $count]);
    exit;
}

if ($action === 'post_comment') {
    $topic_id = (int)$_POST['topic_id'];
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $content = mysqli_real_escape_string($koneksi, $_POST['content']);
    
    if ($topic_id > 0 && !empty($content)) {
        $insert = mysqli_query($koneksi, "INSERT INTO forum_replies (topic_id, user_id, content, parent_reply_id) VALUES ($topic_id, $uid, '$content', $parent_id)");
        if ($insert) {
            $last_id = mysqli_insert_id($koneksi);
            // Fetch user info for display
            $uq = mysqli_query($koneksi, "SELECT nama_lengkap FROM users WHERE id_user=$uid");
            $u = mysqli_fetch_assoc($uq);
            
            $html = '
            <div class="d-flex mb-2" id="comment-'.$last_id.'">
                <div class="flex-shrink-0">
                    <div class="avatar-circle" style="width: 32px; height: 32px; font-size: 14px;">
                        '.strtoupper(substr($u['nama_lengkap'], 0, 1)).'
                    </div>
                </div>
                <div class="flex-grow-1 ms-2">
                    <div class="bg-light p-2 rounded d-inline-block">
                        <div class="fw-bold" style="font-size: 0.9rem;">'.htmlspecialchars($u['nama_lengkap']).'</div>
                        <div style="font-size: 0.9rem;">'.nl2br(htmlspecialchars($content)).'</div>
                    </div>
                    <div class="d-flex align-items-center mt-1">
                        <small class="text-muted me-3" style="font-size: 0.75rem;">Baru saja</small>
                        <a href="javascript:void(0)" onclick="showReplyForm('.$last_id.')" class="text-decoration-none fw-bold text-muted" style="font-size: 0.75rem;">Balas</a>
                    </div>
                    <!-- Container for nested replies -->
                    <div id="replies-'.$last_id.'" class="mt-2 ps-3 border-start"></div>
                    <!-- Reply Form (Hidden by default) -->
                    <div id="reply-form-'.$last_id.'" class="mt-2" style="display:none;">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-2" style="width: 24px; height: 24px; font-size: 10px;">
                                '.strtoupper(substr($u['nama_lengkap'], 0, 1)).'
                            </div>
                            <input type="text" class="form-control form-control-sm rounded-pill bg-light border-0" placeholder="Tulis balasan..." id="input-reply-'.$last_id.'" onkeypress="handleReply(event, '.$topic_id.', '.$last_id.')">
                        </div>
                    </div>
                </div>
            </div>';
            
            echo json_encode(['status' => 'success', 'html' => $html, 'parent_id' => $parent_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Empty content']);
    }
    exit;
}
?>