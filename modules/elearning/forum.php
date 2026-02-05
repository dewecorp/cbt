<?php
session_start();
include '../../config/database.php';
$page_title = 'Forum Diskusi Guru & Admin';

// Batasi akses: Siswa tidak boleh akses halaman ini
if (!isset($_SESSION['level']) || $_SESSION['level'] === 'siswa') {
    header("Location: ../../index.php");
    exit;
}

$level = $_SESSION['level'];
$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Handle Post Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    // Forum Umum: class_id = 0, course_id = 0
    $class_id = 0;
    $role = ($level === 'admin') ? 'admin' : 'guru';
    
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
        // Title is optional now, set default or empty
        $title = substr($content, 0, 50) . '...'; 
        mysqli_query($koneksi, "INSERT INTO forum_topics(class_id, course_id, title, content, image, file, created_by, author_role) VALUES(0, 0, '$title', '$content', '$image_path', '$file_path', $uid, '$role')");
        header("Location: forum.php");
        exit;
    }
}

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
            echo '          <div style="font-size: 0.9rem;">'.nl2br(htmlspecialchars($r['content'])).'</div>';
        }
        echo '      </div>';
        echo '      <div class="d-flex align-items-center mt-1">';
        echo '          <small class="text-muted me-3" style="font-size: 0.75rem;">'.date('d/m H:i', strtotime($r['created_at'])).'</small>';
        echo '          <a href="javascript:void(0)" onclick="toggleReplyLike('.$r_id.')" class="text-decoration-none fw-bold text-muted me-2" style="font-size: 0.75rem;">Suka</a>';
        echo '          <small id="reply-like-count-'.$r_id.'" class="text-muted me-3" style="font-size: 0.75rem;"></small>';
        echo '          <a href="javascript:void(0)" onclick="showReplyForm('.$r_id.')" class="text-decoration-none fw-bold text-muted" style="font-size: 0.75rem;">Balas</a>';
        echo '      </div>';
        
        // Nested replies container
        echo '      <div id="replies-'.$r_id.'" class="mt-2 ps-3 border-start">';
        if (isset($children[$r_id])) {
            render_comments([], $children, $r_id);
        }
        echo '      </div>';
        
        // Reply Form
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

include '../../includes/header.php';

// Fetch Posts (Topics) - Only General Topics (class_id=0)
$topic_filter = " WHERE t.class_id=0 AND t.course_id=0 ";

$topicsQ = mysqli_query($koneksi, "
    SELECT t.*, 
    CASE 
        WHEN t.author_role = 'siswa' THEN s.nama_siswa
        ELSE u.nama_lengkap 
    END AS nama_lengkap,
    (SELECT COUNT(*) FROM forum_replies r WHERE r.topic_id=t.id_topic) AS comment_count,
    (SELECT COUNT(*) FROM forum_likes l WHERE l.topic_id=t.id_topic) AS like_count,
    (SELECT COUNT(*) FROM forum_likes l2 WHERE l2.topic_id=t.id_topic AND l2.user_id=$uid) AS is_liked
    FROM forum_topics t 
    LEFT JOIN users u ON t.created_by=u.id_user 
    LEFT JOIN siswa s ON t.created_by=s.id_siswa
    $topic_filter 
    ORDER BY t.created_at DESC
");
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
    .comments-section {
        margin-top: 10px;
    }
    .comment-item {
        display: flex;
        margin-bottom: 10px;
    }
    .comment-bubble {
        background-color: #f0f2f5;
        border-radius: 18px;
        padding: 8px 12px;
        margin-left: 8px;
        flex-grow: 1;
    }
    .comment-author {
        font-weight: bold;
        font-size: 0.9rem;
    }
    .comment-text {
        font-size: 0.9rem;
    }
    .comment-time {
        font-size: 0.75rem;
        color: #65676b;
        margin-left: 12px;
        margin-top: 2px;
    }
    .create-post-card {
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <h4 class="mb-0 text-gray-800"><i class="fas fa-comments text-primary"></i> Forum Guru & Admin</h4>
    </div>

    <!-- Create Post -->
    <div class="card create-post-card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="d-flex mb-3">
                    <div class="avatar-circle me-2">
                        <?php 
                        $my_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'U';
                        echo strtoupper(substr($my_name, 0, 1)); 
                        ?>
                    </div>
                    <div class="w-100">
                        <textarea name="content" class="form-control" rows="3" placeholder="Apa yang Anda pikirkan?" style="border: none; resize: none; font-size: 1.1rem;"></textarea>
                    </div>
                </div>
                <div class="border-top pt-2 d-flex justify-content-between align-items-center">
                    <div>
                        <label class="btn btn-light btn-sm text-success" title="Upload Gambar">
                            <i class="fas fa-image fa-lg"></i> <input type="file" name="image" accept="image/*" style="display: none;"> Foto
                        </label>
                        <label class="btn btn-light btn-sm text-danger" title="Upload File">
                            <i class="fas fa-paperclip fa-lg"></i> <input type="file" name="file" style="display: none;"> File
                        </label>
                    </div>
                    <button type="submit" name="create_post" value="1" class="btn btn-primary btn-sm px-4">Kirim</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Feed -->
    <?php while($t = mysqli_fetch_assoc($topicsQ)): ?>
    <div class="card post-card">
        <div class="card-body">
            <div class="post-header">
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($t['nama_lengkap'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="post-info">
                    <div class="post-author"><?php echo htmlspecialchars($t['nama_lengkap'] ?? 'Pengguna Tidak Dikenal'); ?></div>
                    <div class="post-time">
                        <i class="fas fa-globe-asia"></i> 
                        <?php echo date('d M Y H:i', strtotime($t['created_at'])); ?> • 
                        <span class="badge bg-light text-dark border">Umum</span>
                    </div>
                </div>
            </div>
            
            <?php if(!empty($t['content'])): ?>
                <div class="post-content"><?php echo nl2br(htmlspecialchars($t['content'])); ?></div>
            <?php endif; ?>

            <?php if(!empty($t['image'])): ?>
                <img src="../../assets/uploads/forum/<?php echo $t['image']; ?>" class="post-image" alt="Post Image">
            <?php endif; ?>

            <?php if(!empty($t['file'])): ?>
                <div class="post-file">
                    <i class="fas fa-file-alt fa-2x text-primary me-3"></i>
                    <div>
                        <div class="fw-bold"><?php echo $t['file']; ?></div>
                        <a href="../../assets/uploads/forum/<?php echo $t['file']; ?>" target="_blank" class="text-decoration-none small">Download File</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between text-muted small mt-3 px-2">
                <span id="like-count-<?php echo $t['id_topic']; ?>">
                    <?php if($t['like_count'] > 0) echo '<i class="fas fa-thumbs-up text-primary"></i> ' . $t['like_count']; ?>
                </span>
                <span><?php echo $t['comment_count']; ?> Komentar</span>
            </div>

            <div class="post-actions">
                <button class="action-btn <?php echo ($t['is_liked'] > 0) ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $t['id_topic']; ?>)" id="btn-like-<?php echo $t['id_topic']; ?>">
                    <i class="far fa-thumbs-up"></i> Suka
                </button>
                <button class="action-btn" onclick="toggleComments(<?php echo $t['id_topic']; ?>)">
                    <i class="far fa-comment-alt"></i> Komentar
                </button>
            </div>

            <!-- Comments -->
            <div class="comments-section" id="comments-<?php echo $t['id_topic']; ?>" style="display: <?php echo ($t['comment_count'] > 0) ? 'block' : 'none'; ?>;">
                <div id="comment-list-<?php echo $t['id_topic']; ?>">
                    <?php 
                    $repliesQ = mysqli_query($koneksi, "SELECT r.*, 
                        CASE 
                            WHEN r.user_role = 'siswa' THEN sw.nama_siswa 
                            ELSE u.nama_lengkap 
                        END AS display_name 
                        FROM forum_replies r 
                        LEFT JOIN users u ON r.user_id=u.id_user 
                        LEFT JOIN siswa sw ON r.user_id=sw.id_siswa 
                        WHERE r.topic_id=".$t['id_topic']." ORDER BY r.created_at ASC");
                    $comments = [];
                    $children = [];
                    while($r = mysqli_fetch_assoc($repliesQ)) {
                        if ($r['parent_reply_id'] == 0) {
                            $comments[] = $r;
                        } else {
                            $children[$r['parent_reply_id']][] = $r;
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

<script>
function toggleLike(topicId) {
    $.post('forum_ajax.php', { action: 'like_toggle', topic_id: topicId }, function(data) {
        const res = JSON.parse(data);
        if (res.status === 'success') {
            const btn = $('#btn-like-' + topicId);
            if (res.liked) {
                btn.addClass('liked');
                btn.html('<i class="fas fa-thumbs-up"></i> Suka');
            } else {
                btn.removeClass('liked');
                btn.html('<i class="far fa-thumbs-up"></i> Suka');
            }
            
            const countSpan = $('#like-count-' + topicId);
            if (res.count > 0) {
                countSpan.html('<i class="fas fa-thumbs-up text-primary"></i> ' + res.count);
            } else {
                countSpan.html('');
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
            
            // Update comment count if needed (optional)
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
    const start = input.prop('selectionStart') || input.val().length;
    const end = input.prop('selectionEnd') || input.val().length;
    const text = input.val();
    input.val(text.substring(0, start) + emoji + text.substring(end));
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
