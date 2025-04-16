<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if(!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = getCurrentUserId();

// Process comment action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_id']) && isset($_POST['content'])) {
    $post_id = $conn->real_escape_string($_POST['post_id']);
    $content = $conn->real_escape_string($_POST['content']);
    
    if (empty($content)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit;
    }
    
    $sql = "INSERT INTO comments (user_id, post_id, content, created_at) 
            VALUES ('$user_id', '$post_id', '$content', NOW())";
    
    if ($conn->query($sql) === TRUE) {
        $comment_id = $conn->insert_id;
        
        // Get comment details
        $comment_sql = "SELECT c.id, c.content, c.created_at, u.name, u.id as user_id, u.profile_picture 
                       FROM comments c 
                       JOIN users u ON c.user_id = u.id 
                       WHERE c.id = '$comment_id'";
        $comment_result = $conn->query($comment_sql);
        $comment = $comment_result->fetch_assoc();
        
        // Get updated comment count
        $count_sql = "SELECT COUNT(*) as count FROM comments WHERE post_id = '$post_id'";
        $count_result = $conn->query($count_sql);
        $count = $count_result->fetch_assoc()['count'];
        
        // Format the comment HTML
        $profile_pic = $comment['profile_picture'] ? 
            '<img src="'.$comment['profile_picture'].'" alt="Profile Picture">' : 
            substr($comment['name'], 0, 1);
        
        $comment_html = '
            <div class="comment" id="comment-'.$comment['id'].'">
                <div class="comment-avatar">
                    <div class="profile-icon small-icon">'.$profile_pic.'</div>
                </div>
                <div class="comment-content">
                    <div class="comment-bubble">
                        <a href="profile.php?id='.$comment['user_id'].'" class="comment-author">'.$comment['name'].'</a>
                        <span class="comment-text">'.nl2br($comment['content']).'</span>
                    </div>
                    <div class="comment-actions">
                        <span class="comment-action">Like</span>
                        <span class="comment-action">Reply</span>
                        <span class="comment-time">Just now</span>
                    </div>
                </div>
            </div>
        ';
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'comment' => $comment_html, 
            'count' => $count
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $conn->error]);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Helper function to format time ago
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    } elseif ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    } elseif ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'Just now';
    }
}
?>
