<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Database connection
$conn = new mysqli("localhost", "uklz9ew3hrop3", "zyrbspyjlzjb", "dbvf88fgdguzl1");

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Process actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Add friend
    if ($action == 'add' && isset($_POST['user_id'])) {
        $friend_id = $conn->real_escape_string($_POST['user_id']);
        
        // Check if request already exists
        $check_sql = "SELECT id FROM friends 
                     WHERE (user_id = '$user_id' AND friend_id = '$friend_id')
                     OR (user_id = '$friend_id' AND friend_id = '$user_id')";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Friend request already exists']);
            exit;
        }
        
        // Insert friend request
        $sql = "INSERT INTO friends (user_id, friend_id, status, created_at) 
                VALUES ('$user_id', '$friend_id', 'pending', NOW())";
        
        if ($conn->query($sql) === TRUE) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $conn->error]);
            exit;
        }
    }
    
    // Accept friend request
    else if ($action == 'accept' && isset($_POST['request_id'])) {
        $request_id = $conn->real_escape_string($_POST['request_id']);
        
        // Check if request exists and belongs to the user
        $check_sql = "SELECT id FROM friends 
                     WHERE id = '$request_id' AND friend_id = '$user_id' AND status = 'pending'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows == 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid friend request']);
            exit;
        }
        
        // Update friend request status
        $sql = "UPDATE friends SET status = 'accepted', updated_at = NOW() WHERE id = '$request_id'";
        
        if ($conn->query($sql) === TRUE) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $conn->error]);
            exit;
        }
    }
    
    // Decline friend request
    else if ($action == 'decline' && isset($_POST['request_id'])) {
        $request_id = $conn->real_escape_string($_POST['request_id']);
        
        // Check if request exists and belongs to the user
        $check_sql = "SELECT id FROM friends 
                     WHERE id = '$request_id' AND friend_id = '$user_id' AND status = 'pending'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows == 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid friend request']);
            exit;
        }
        
        // Delete friend request
        $sql = "DELETE FROM friends WHERE id = '$request_id'";
        
        if ($conn->query($sql) === TRUE) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $conn->error]);
            exit;
        }
    }
    
    // Accept friend request from profile page
    else if ($action == 'accept_from_profile' && isset($_POST['user_id'])) {
        $friend_id = $conn->real_escape_string($_POST['user_id']);
        
        // Check if request exists
        $check_sql = "SELECT id FROM friends 
                     WHERE user_id = '$friend_id' AND friend_id = '$user_id' AND status = 'pending'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows == 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid friend request']);
            exit;
        }
        
        $request_id = $check_result->fetch_assoc()['id'];
        
        // Update friend request status
        $sql = "UPDATE friends SET status = 'accepted', updated_at = NOW() WHERE id = '$request_id'";
        
        if ($conn->query($sql) === TRUE) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $conn->error]);
            exit;
        }
    }
    
    // Decline friend request from profile page
    else if ($action == 'decline_from_profile' && isset($_POST['user_id'])) {
        $friend_id = $conn->real_escape_string($_POST['user_id']);
        
        // Check if request exists
        $check_sql = "SELECT id FROM friends 
                     WHERE user_id = '$friend_id' AND friend_id = '$user_id' AND status = 'pending'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows == 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid friend request']);
            exit;
        }
        
        $request_id = $check_result->fetch_assoc()['id'];
        
        // Delete friend request
        $sql = "DELETE FROM friends WHERE id = '$request_id'";
        
        if ($conn->query($sql) === TRUE) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
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
?>
