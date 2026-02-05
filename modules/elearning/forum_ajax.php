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

if ($action === 'reply_like_toggle') {
    $reply_id = (int)$_POST['reply_id'];
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS forum_reply_likes (id INT AUTO_INCREMENT PRIMARY KEY, reply_id INT NOT NULL, user_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $check = mysqli_query($koneksi, "SELECT id FROM forum_reply_likes WHERE reply_id=$reply_id AND user_id=$uid");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($koneksi, "DELETE FROM forum_reply_likes WHERE reply_id=$reply_id AND user_id=$uid");
        $liked = false;
    } else {
        mysqli_query($koneksi, "INSERT INTO forum_reply_likes (reply_id, user_id) VALUES ($reply_id, $uid)");
        $liked = true;
    }
    $countQ = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM forum_reply_likes WHERE reply_id=$reply_id");
    $count = mysqli_fetch_assoc($countQ)['cnt'];
    echo json_encode(['status' => 'success', 'liked' => $liked, 'count' => $count]);
    exit;
}

if ($action === 'post_comment') {
    $topic_id = (int)$_POST['topic_id'];
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $raw_content = $_POST['content'];
    $content = mysqli_real_escape_string($koneksi, $raw_content);
    $level = isset($_SESSION['level']) ? $_SESSION['level'] : 'siswa';
    $role = ($level === 'admin') ? 'admin' : (($level === 'guru') ? 'guru' : 'siswa');
    
    if ($topic_id > 0 && !empty($content)) {
        $insert = mysqli_query($koneksi, "INSERT INTO forum_replies (topic_id, user_id, user_role, content, parent_reply_id) VALUES ($topic_id, $uid, '$role', '$content', $parent_id)");
        if ($insert) {
            $last_id = mysqli_insert_id($koneksi);
            
            if ($role === 'siswa') {
                $uq = mysqli_query($koneksi, "SELECT nama_siswa AS display_name FROM siswa WHERE id_siswa=$uid");
            } else {
                $uq = mysqli_query($koneksi, "SELECT nama_lengkap AS display_name FROM users WHERE id_user=$uid");
            }
            $u = mysqli_fetch_assoc($uq);
            $display_name = $u['display_name'] ?? 'Pengguna';
            
            $html = '
            <div class="d-flex mb-2" id="comment-'.$last_id.'">
                <div class="flex-shrink-0">
                    <div class="avatar-circle" style="width: 32px; height: 32px; font-size: 14px;">
                        '.strtoupper(substr($display_name, 0, 1)).'
                    </div>
                </div>
                <div class="flex-grow-1 ms-2">
                    <div class="bg-light p-2 rounded d-inline-block">
                        <div class="fw-bold" style="font-size: 0.9rem;">'.htmlspecialchars($display_name).'</div>
                        <div style="font-size: 0.9rem;">'.nl2br(htmlspecialchars($raw_content)).'</div>
                    </div>
                    <div class="d-flex align-items-center mt-1">
                        <small class="text-muted me-3" style="font-size: 0.75rem;">Baru saja</small>
                        <a href="javascript:void(0)" onclick="toggleReplyLike('.$last_id.')" class="text-decoration-none fw-bold text-muted me-3" style="font-size: 0.75rem;">Suka</a>
                        <span id="reply-like-count-'.$last_id.'" class="text-muted" style="font-size: 0.75rem;"></span>
                        <a href="javascript:void(0)" onclick="showReplyForm('.$last_id.')" class="text-decoration-none fw-bold text-muted" style="font-size: 0.75rem;">Balas</a>
                    </div>
                    <!-- Container for nested replies -->
                    <div id="replies-'.$last_id.'" class="mt-2 ps-3 border-start"></div>
                    <!-- Reply Form (Hidden by default) -->
                    <div id="reply-form-'.$last_id.'" class="mt-2" style="display:none;">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-2" style="width: 24px; height: 24px; font-size: 10px;">
                                '.strtoupper(substr($display_name, 0, 1)).'
                            </div>
                            <input type="text" class="form-control form-control-sm rounded-pill bg-light border-0" placeholder="Tulis balasan..." id="input-reply-'.$last_id.'" onkeypress="handleReply(event, '.$topic_id.', '.$last_id.')">
                            <button class="btn btn-link text-muted p-0 ms-2" onclick="toggleEmojiPicker(\'input-reply-'.$last_id.'\', '.$topic_id.', '.$last_id.')"><i class="far fa-smile"></i></button>
                            <button class="btn btn-link text-muted p-0 ms-2" onclick="toggleStickerPicker('.$topic_id.', '.$last_id.')"><i class="far fa-sticky-note"></i></button>
                        </div>
                        <div id="emoji-picker-'.$last_id.'" class="mt-1" style="display:none;">
                            <span class="me-2" onclick="insertEmoji(\'input-reply-'.$last_id.'\', \'😀\')">😀</span>
                            <span class="me-2" onclick="insertEmoji(\'input-reply-'.$last_id.'\', \'😎\')">😎</span>
                            <span class="me-2" onclick="insertEmoji(\'input-reply-'.$last_id.'\', \'👍\')">👍</span>
                            <span class="me-2" onclick="insertEmoji(\'input-reply-'.$last_id.'\', \'❤️\')">❤️</span>
                            <span class="me-2" onclick="insertEmoji(\'input-reply-'.$last_id.'\', \'🎉\')">🎉</span>
                            <span class="me-2" onclick="insertEmoji(\'input-reply-'.$last_id.'\', \'🙏\')">🙏</span>
                        </div>
                        <div id="sticker-picker-'.$last_id.'" class="mt-1" style="display:none;">
                            <span class="me-2" onclick="sendSticker('.$topic_id.', '.$last_id.', \'😀\')">😀</span>
                            <span class="me-2" onclick="sendSticker('.$topic_id.', '.$last_id.', \'😎\')">😎</span>
                            <span class="me-2" onclick="sendSticker('.$topic_id.', '.$last_id.', \'👍\')">👍</span>
                            <span class="me-2" onclick="sendSticker('.$topic_id.', '.$last_id.', \'❤️\')">❤️</span>
                            <span class="me-2" onclick="sendSticker('.$topic_id.', '.$last_id.', \'🎉\')">🎉</span>
                            <span class="me-2" onclick="sendSticker('.$topic_id.', '.$last_id.', \'🙏\')">🙏</span>
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

if ($action === 'post_sticker') {
    $topic_id = (int)$_POST['topic_id'];
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $sticker = isset($_POST['sticker']) ? $_POST['sticker'] : '';
    if ($sticker === '') {
        echo json_encode(['status' => 'error', 'message' => 'Sticker kosong']);
        exit;
    }
    mysqli_query($koneksi, "ALTER TABLE forum_replies ADD COLUMN IF NOT EXISTS sticker_code VARCHAR(32) NULL");
    $level = isset($_SESSION['level']) ? $_SESSION['level'] : 'siswa';
    $role = ($level === 'admin') ? 'admin' : (($level === 'guru') ? 'guru' : 'siswa');
    $insert = mysqli_query($koneksi, "INSERT INTO forum_replies (topic_id, user_id, user_role, content, sticker_code, parent_reply_id) VALUES ($topic_id, $uid, '$role', '', '".mysqli_real_escape_string($koneksi, $sticker)."', $parent_id)");
    if ($insert) {
        $last_id = mysqli_insert_id($koneksi);
        if ($role === 'siswa') {
            $uq = mysqli_query($koneksi, "SELECT nama_siswa AS display_name FROM siswa WHERE id_siswa=$uid");
        } else {
            $uq = mysqli_query($koneksi, "SELECT nama_lengkap AS display_name FROM users WHERE id_user=$uid");
        }
        $u = mysqli_fetch_assoc($uq);
        $display_name = $u['display_name'] ?? 'Pengguna';
        $html = '
        <div class="d-flex mb-2" id="comment-'.$last_id.'">
            <div class="flex-shrink-0">
                <div class="avatar-circle" style="width: 32px; height: 32px; font-size: 14px;">
                    '.strtoupper(substr($display_name, 0, 1)).'
                </div>
            </div>
            <div class="flex-grow-1 ms-2">
                <div class="bg-light p-2 rounded d-inline-block">
                    <div class="fw-bold" style="font-size: 0.9rem;">'.htmlspecialchars($display_name).'</div>
                    <div style="font-size: 2rem; line-height: 1.2;">'.htmlspecialchars($sticker).'</div>
                </div>
                <div class="d-flex align-items-center mt-1">
                    <small class="text-muted me-3" style="font-size: 0.75rem;">Baru saja</small>
                    <a href="javascript:void(0)" onclick="toggleReplyLike('.$last_id.')" class="text-decoration-none fw-bold text-muted me-3" style="font-size: 0.75rem;">Suka</a>
                    <span id="reply-like-count-'.$last_id.'" class="text-muted" style="font-size: 0.75rem;"></span>
                    <a href="javascript:void(0)" onclick="showReplyForm('.$last_id.')" class="text-decoration-none fw-bold text-muted" style="font-size: 0.75rem;">Balas</a>
                </div>
                <div id="replies-'.$last_id.'" class="mt-2 ps-3 border-start"></div>
                <div id="reply-form-'.$last_id.'" class="mt-2" style="display:none;">
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle me-2" style="width: 24px; height: 24px; font-size: 10px;">
                            '.strtoupper(substr($display_name, 0, 1)).'
                        </div>
                        <input type="text" class="form-control form-control-sm rounded-pill bg-light border-0" placeholder="Tulis balasan..." id="input-reply-'.$last_id.'" onkeypress="handleReply(event, '.$topic_id.', '.$last_id.')">
                        <button class="btn btn-link text-muted p-0 ms-2" onclick="toggleEmojiPicker(\'input-reply-'.$last_id.'\', '.$topic_id.', '.$last_id.')"><i class="far fa-smile"></i></button>
                        <button class="btn btn-link text-muted p-0 ms-2" onclick="toggleStickerPicker('.$topic_id.', '.$last_id.')"><i class="far fa-sticky-note"></i></button>
                    </div>
                    <div id="emoji-picker-'.$last_id.'" class="mt-1" style="display:none;">
                        <span class="me-2" onclick="insertEmoji(\'input-reply-'.$last_id.'\', \'😀\')">😀</span>
                        <span class="me-2" onclick="insertEmoji(\'input-reply-'.$last_id.'\', \'😎\')">😎</span>
                        <span class="me-2" onclick="insertEmoji(\'input-reply-'.$last_id.'\', \'👍\')">👍</span>
                        <span class="me-2" onclick="insertEmoji(\'input-reply-'.$last_id.'\', \'❤️\')">❤️</span>
                        <span class="me-2" onclick="insertEmoji(\'input-reply-'.$last_id.'\', \'🎉\')">🎉</span>
                        <span class="me-2" onclick="insertEmoji(\'input-reply-'.$last_id.'\', \'🙏\')">🙏</span>
                    </div>
                    <div id="sticker-picker-'.$last_id.'" class="mt-1" style="display:none;">
                        <span class="me-2" onclick="sendSticker('.$topic_id.', '.$last_id.', \'😀\')">😀</span>
                        <span class="me-2" onclick="sendSticker('.$topic_id.', '.$last_id.', \'😎\')">😎</span>
                        <span class="me-2" onclick="sendSticker('.$topic_id.', '.$last_id.', \'👍\')">👍</span>
                        <span class="me-2" onclick="sendSticker('.$topic_id.', '.$last_id.', \'❤️\')">❤️</span>
                        <span class="me-2" onclick="sendSticker('.$topic_id.', '.$last_id.', \'🎉\')">🎉</span>
                        <span class="me-2" onclick="sendSticker('.$topic_id.', '.$last_id.', \'🙏\')">🙏</span>
                    </div>
                </div>
            </div>
        </div>';
        echo json_encode(['status' => 'success', 'html' => $html, 'parent_id' => $parent_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}
?>
