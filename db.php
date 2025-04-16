<?php
// Database configuration
$db_host = "localhost";
$db_user = "uklz9ew3hrop3";
$db_pass = "zyrbspyjlzjb";
$db_name = "dbvf88fgdguzl1";

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8mb4");

// Function to sanitize input data
function sanitize($conn, $data) {
    return $conn->real_escape_string(trim($data));
}

// Function to redirect with JavaScript
function redirect($url) {
    echo "<script>window.location.href = '$url';</script>";
    exit;
}

// Function to display error message
function showError($message) {
    return "<div class='error-message'>$message</div>";
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current user name
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? null;
}
?>
