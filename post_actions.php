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

// Process actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Like/Unlike post
    if ($action == 'like' && isset($_POST['post_id'])) {
        $post_id = $conn->real_escape_string($_POST['post_id']);
        
        // Check if already liked
        $check_sql = "SELECT id FROM likes WHERE user_id = '$user_id' AND post_id = '$post_id'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            // Unlike
            $sql = "DELETE FROM likes WHERE user_id = '$user_id' AND post_id = '$post_id'";
            $liked = false;
        } else {
            // Like
            $sql = "INSERT INTO likes (user_id, post_id, created_at) VALUES ('$user_id', '$post_id', NOW())";
            $liked = true;
        }
        
        if ($conn->query($sql) === TRUE) {
            // Get updated like count
            $count_sql = "SELECT COUNT(*) as count FROM likes WHERE post_id = '$post_id'";
            $count_result = $conn->query($count_sql);
            $count = $count_result->fetch_assoc()['count'];
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $conn->error]);
            exit;
        }
    }
    
    // Add comment
    else if ($action == 'comment' && isset($_POST['post_id']) && isset($_POST['content'])) {
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
                            <span class="comment-time">'.timeAgo($comment['created_at']).'</span>
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
    }
    
    // Share post
    else if ($action == 'share' && isset($_POST['post_id'])) {
        $original_post_id = $conn->real_escape_string($_POST['post_id']);
        $share_content = isset($_POST['content']) ? $conn->real_escape_string($_POST['content']) : '';
        
        // Get original post
        $post_sql = "SELECT p.*, u.name as author_name 
                    FROM posts p 
                    JOIN users u ON p.user_id = u.id 
                    WHERE p.id = '$original_post_id'";
        $post_result = $conn->query($post_sql);
        
        if ($post_result->num_rows == 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            exit;
        }
        
        $original_post = $post_result->fetch_assoc();
        
        // Create shared post
        $sql = "INSERT INTO posts (user_id, content, shared_post_id, created_at) 
                VALUES ('$user_id', '$share_content', '$original_post_id', NOW())";
        
        if ($conn->query($sql) === TRUE) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Post shared successfully']);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $conn->error]);
            exit;
        }
    }
    
    else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
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
