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

// Process like action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_id'])) {
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
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
?>
