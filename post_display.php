<?php
// This file contains the HTML structure for displaying posts with like and comment functionality
// Include this in your home.php and profile.php files where you want to display posts

function displayPost($post, $conn, $user_id, $user_name, $user) {
    // Get like count
    $post_id = $post['id'];
    $like_sql = "SELECT COUNT(*) as count FROM likes WHERE post_id = '$post_id'";
    $like_result = $conn->query($like_sql);
    $like_count = $like_result->fetch_assoc()['count'];
    
    // Check if user liked this post
    $user_liked_sql = "SELECT id FROM likes WHERE user_id = '$user_id' AND post_id = '$post_id'";
    $user_liked_result = $conn->query($user_liked_sql);
    $user_liked = $user_liked_result->num_rows > 0;
    
    // Get comment count
    $comment_sql = "SELECT COUNT(*) as count FROM comments WHERE post_id = '$post_id'";
    $comment_result = $conn->query($comment_sql);
    $comment_count = $comment_result->fetch_assoc()['count'];
    
    // Get profile picture
    $profile_user_id = $post['user_id'];
    $pic_sql = "SELECT profile_picture FROM users WHERE id = '$profile_user_id'";
    $pic_result = $conn->query($pic_sql);
    $pic_user = $pic_result->fetch_assoc();
    
    $profile_pic = ($pic_user && $pic_user['profile_picture']) ? 
        '<img src="' . $pic_user['profile_picture'] . '" alt="Profile Picture">' : 
        substr($post['name'], 0, 1);
    
    // Format time ago
    $post_time = timeAgo($post['created_at']);
    
    // Start post HTML
    ?>
    <div class="post" id="post-<?php echo $post_id; ?>">
        <div class="post-header">
            <div class="profile-icon">
                <?php echo $profile_pic; ?>
            </div>
            <div class="post-info">
                <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="post-author"><?php echo $post['name']; ?></a>
                <div class="post-time"><?php echo $post_time; ?></div>
            </div>
        </div>
        <div class="post-content">
            <?php echo nl2br($post['content']); ?>
            
            <?php
            // Check if post has media
            $media_sql = "SELECT * FROM post_media WHERE post_id = '$post_id'";
            $media_result = $conn->query($media_sql);
            
            if ($media_result && $media_result->num_rows > 0) {
                $media = $media_result->fetch_assoc();
                if ($media['media_type'] == 'image') {
                    echo '<div class="post-media"><img src="' . $media['media_path'] . '" alt="Post Image" class="post-image"></div>';
                } else if ($media['media_type'] == 'video') {
                    echo '<div class="post-media"><video controls class="post-video"><source src="' . $media['media_path'] . '" type="video/mp4"></video></div>';
                }
            }
            ?>
        </div>
        <div class="post-stats">
            <div>
                <?php if($like_count > 0): ?>
                    <i class="fas fa-thumbs-up" style="color: #1877f2;"></i>
                    <span id="like-count-<?php echo $post_id; ?>"><?php echo $like_count; ?></span>
                <?php endif; ?>
            </div>
            <div>
                <?php if($comment_count > 0): ?>
                    <span id="comment-count-<?php echo $post_id; ?>"><?php echo $comment_count; ?> comments</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="post-actions">
            <div class="post-action-btn <?php echo $user_liked ? 'active' : ''; ?>" onclick="likePost(<?php echo $post_id; ?>)">
                <i class="<?php echo $user_liked ? 'fas' : 'far'; ?> fa-thumbs-up"></i>
                <span>Like</span>
            </div>
            <div class="post-action-btn" onclick="toggleComments(<?php echo $post_id; ?>)">
                <i class="far fa-comment"></i>
                <span>Comment</span>
            </div>
            <div class="post-action-btn" onclick="openShareModal(<?php echo $post_id; ?>, '<?php echo addslashes($post['name']); ?>', '<?php echo addslashes($post['content']); ?>')">
                <i class="fas fa-share"></i>
                <span>Share</span>
            </div>
        </div>
        
        <!-- Comments Section -->
        <div class="comments-section" id="comments-section-<?php echo $post_id; ?>">
            <div class="comments-list" id="comments-list-<?php echo $post_id; ?>">
                <?php
                // Get comments
                $comments_sql = "SELECT c.*, u.name, u.profile_picture 
                               FROM comments c 
                               JOIN users u ON c.user_id = u.id 
                               WHERE c.post_id = '$post_id' 
                               ORDER BY c.created_at ASC";
                $comments_result = $conn->query($comments_sql);
                
                if ($comments_result && $comments_result->num_rows > 0) {
                    while($comment = $comments_result->fetch_assoc()) {
                        $comment_profile_pic = $comment['profile_picture'] ? 
                            '<img src="'.$comment['profile_picture'].'" alt="Profile Picture">' : 
                            substr($comment['name'], 0, 1);
                        ?>
                        <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                            <div class="comment-avatar">
                                <div class="profile-icon small-icon"><?php echo $comment_profile_pic; ?></div>
                            </div>
                            <div class="comment-content">
                                <div class="comment-bubble">
                                    <a href="profile.php?id=<?php echo $comment['user_id']; ?>" class="comment-author"><?php echo $comment['name']; ?></a>
                                    <span class="comment-text"><?php echo nl2br($comment['content']); ?></span>
                                </div>
                                <div class="comment-actions">
                                    <span class="comment-action">Like</span>
                                    <span class="comment-action">Reply</span>
                                    <span class="comment-time"><?php echo timeAgo($comment['created_at']); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            
            <div class="comment-form">
                <div class="comment-avatar">
                    <div class="profile-icon small-icon">
                        <?php
                        if ($user && $user['profile_picture']) {
                            echo '<img src="' . $user['profile_picture'] . '" alt="Profile Picture">';
                        } else {
                            echo substr($user_name, 0, 1);
                        }
                        ?>
                    </div>
                </div>
                <div class="comment-input-wrapper">
                    <input type="text" class="comment-input" id="comment-input-<?php echo $post_id; ?>" placeholder="Write a comment..." onkeypress="handleCommentKeyPress(event, <?php echo $post_id; ?>)">
                    <i class="far fa-smile comment-emoji"></i>
                </div>
            </div>
        </div>
    </div>
    <?php
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
