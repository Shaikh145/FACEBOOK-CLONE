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

// Process message
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conversation_id = $conn->real_escape_string($_POST['conversation_id']);
    $receiver_id = $conn->real_escape_string($_POST['receiver_id']);
    $content = $conn->real_escape_string($_POST['content']);
    
    // Validate inputs
    if (empty($content)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }
    
    // Check if conversation exists and belongs to user
    $check_sql = "SELECT id FROM conversations 
                 WHERE id = '$conversation_id' 
                 AND (user1_id = '$user_id' OR user2_id = '$user_id')";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows == 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
        exit;
    }
    
    // Insert message
    $sql = "INSERT INTO messages (conversation_id, sender_id, receiver_id, content, created_at) 
            VALUES ('$conversation_id', '$user_id', '$receiver_id', '$content', NOW())";
    
    if ($conn->query($sql)) {
        $message_id = $conn->insert_id;
        
        // Update conversation with last message
        $update_sql = "UPDATE conversations 
                      SET last_message_id = '$message_id', updated_at = NOW() 
                      WHERE id = '$conversation_id'";
        $conn->query($update_sql);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message_id' => $message_id]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $conn->error]);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
?>
