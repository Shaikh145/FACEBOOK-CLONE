<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

// Database connection
$conn = new mysqli("localhost", "uklz9ew3hrop3", "zyrbspyjlzjb", "dbvf88fgdguzl1");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Process new post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_content'])) {
    $content = $conn->real_escape_string($_POST['post_content']);
    
    $sql = "INSERT INTO posts (user_id, content, created_at) VALUES ('$user_id', '$content', NOW())";
    
    if ($conn->query($sql) !== TRUE) {
        $post_error = "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Get posts from user and friends
$sql = "SELECT p.id, p.content, p.created_at, u.name, u.id as user_id, u.profile_picture 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = '$user_id' 
        OR p.user_id IN (SELECT friend_id FROM friends WHERE user_id = '$user_id' AND status = 'accepted')
        OR p.user_id IN (SELECT user_id FROM friends WHERE friend_id = '$user_id' AND status = 'accepted')
        ORDER BY p.created_at DESC";
$posts_result = $conn->query($sql);

// Get friend requests
$sql = "SELECT f.id, u.name, u.id as user_id 
        FROM friends f 
        JOIN users u ON f.user_id = u.id 
        WHERE f.friend_id = '$user_id' AND f.status = 'pending'";
$requests_result = $conn->query($sql);
$request_count = $requests_result->num_rows;

// Get friend suggestions (users who are not friends)
$sql = "SELECT id, name FROM users 
        WHERE id != '$user_id' 
        AND id NOT IN (
            SELECT friend_id FROM friends WHERE user_id = '$user_id'
            UNION
            SELECT user_id FROM friends WHERE friend_id = '$user_id'
        )
        LIMIT 5";
$suggestions_result = $conn->query($sql);

// Get user data for profile picture
$sql = "SELECT profile_picture FROM users WHERE id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

// Include post display helper
require_once 'post_display.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 0 16px;
            height: 56px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-left {
            display: flex;
            align-items: center;
        }
        
        .logo {
            color: #1877f2;
            font-size: 2rem;
            font-weight: bold;
            text-decoration: none;
        }
        
        .search-bar {
            background-color: #f0f2f5;
            border-radius: 50px;
            padding: 8px 16px;
            margin-left: 10px;
            display: flex;
            align-items: center;
        }
        
        .search-bar i {
            color: #65676b;
            margin-right: 8px;
        }
        
        .search-bar input {
            border: none;
            background-color: transparent;
            outline: none;
            font-size: 0.9rem;
            width: 240px;
        }
        
        .navbar-center {
            display: flex;
        }
        
        .nav-icon {
            color: #65676b;
            font-size: 1.5rem;
            padding: 10px 40px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .nav-icon.active {
            color: #1877f2;
            border-bottom: 3px solid #1877f2;
        }
        
        .nav-icon:hover {
            background-color: #f0f2f5;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
        }
        
        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e4e6eb;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 10px;
            cursor: pointer;
            text-decoration: none;
            color: #050505;
            font-weight: bold;
            overflow: hidden;
        }

        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e4e6eb;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-left: 8px;
            cursor: pointer;
        }
        
        .icon-btn i {
            color: #050505;
            font-size: 1.2rem;
        }
        
        .main-container {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .left-sidebar {
            width: 25%;
            position: sticky;
            top: 76px;
            height: calc(100vh - 76px);
            overflow-y: auto;
        }
        
        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: #050505;
            margin-bottom: 5px;
        }
        
        .sidebar-item:hover {
            background-color: #e4e6eb;
        }
        
        .sidebar-item i {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e4e6eb;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 10px;
            font-size: 1.2rem;
            color: #1877f2;
        }
        
        .sidebar-item span {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .feed {
            width: 50%;
        }
        
        .create-post {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 12px 16px;
            margin-bottom: 16px;
        }
        
        .create-post-top {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .create-post-input {
            margin-left: 10px;
            background-color: #f0f2f5;
            border-radius: 20px;
            padding: 8px 12px;
            flex-grow: 1;
            cursor: pointer;
            color: #65676b;
        }
        
        .create-post-actions {
            display: flex;
            justify-content: space-around;
            border-top: 1px solid #e4e6eb;
            padding-top: 10px;
        }
        
        .post-action {
            display: flex;
            align-items: center;
            color: #65676b;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            text-decoration: none;
        }
        
        .post-action:hover {
            background-color: #f0f2f5;
        }
        
        .post-action i {
            margin-right: 5px;
            font-size: 1.2rem;
        }
        
        .post {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 12px 16px;
            margin-bottom: 16px;
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
            font-weight: 500;
            color: #050505;
            text-decoration: none;
        }
        
        .post-time {
            font-size: 0.8rem;
            color: #65676b;
        }
        
        .post-content {
            margin-bottom: 10px;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .post-stats {
            display: flex;
            justify-content: space-between;
            color: #65676b;
            font-size: 0.9rem;
            padding: 10px 0;
            border-bottom: 1px solid #e4e6eb;
        }
        
        .post-actions {
            display: flex;
            justify-content: space-around;
            padding: 5px 0;
        }
        
        .post-action-btn {
            display: flex;
            align-items: center;
            color: #65676b;
            cursor: pointer;
            padding: 5px 0;
            flex: 1;
            justify-content: center;
            border-radius: 5px;
        }
        
        .post-action-btn:hover {
            background-color: #f0f2f5;
        }
        
        .post-action-btn i {
            margin-right: 5px;
        }
        
        .post-action-btn.active {
            color: #1877f2;
        }
        
        .right-sidebar {
            width: 25%;
            position: sticky;
            top: 76px;
            height: calc(100vh - 76px);
            overflow-y: auto;
        }
        
        .sidebar-title {
            color: #65676b;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            padding: 0 8px;
        }
        
        .friend-request {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 12px;
            margin-bottom: 10px;
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .request-title {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .see-all {
            color: #1877f2;
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .request-item {
            display: flex;
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 8px;
        }
        
        .request-info {
            margin-left: 10px;
            flex-grow: 1;
        }
        
        .request-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .request-actions {
            display: flex;
            gap: 5px;
        }
        
        .accept-btn, .decline-btn {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
        }
        
        .accept-btn {
            background-color: #1877f2;
            color: white;
        }
        
        .decline-btn {
            background-color: #e4e6eb;
            color: #050505;
        }
        
        .contacts {
            margin-top: 20px;
        }
        
        .contact-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding: 0 8px;
        }
        
        .contact-title {
            color: #65676b;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .contact-actions {
            display: flex;
            gap: 10px;
        }
        
        .contact-action {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #e4e6eb;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }
        
        .contact-action i {
            color: #65676b;
            font-size: 1rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .contact-item:hover {
            background-color: #e4e6eb;
        }
        
        .contact-name {
            margin-left: 10px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
            padding: 20px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e4e6eb;
        }
        
        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .close-modal {
            cursor: pointer;
            font-size: 1.5rem;
            color: #65676b;
        }
        
        .post-form {
            width: 100%;
        }
        
        .post-textarea {
            width: 100%;
            min-height: 100px;
            border: none;
            resize: none;
            font-size: 1rem;
            margin-bottom: 15px;
            outline: none;
        }
        
        .post-submit {
            width: 100%;
            padding: 8px;
            background-color: #1877f2;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .post-submit:disabled {
            background-color: #e4e6eb;
            color: #bcc0c4;
            cursor: not-allowed;
        }
        
        @media (max-width: 992px) {
            .main-container {
                flex-direction: column;
            }
            
            .left-sidebar, .feed, .right-sidebar {
                width: 100%;
                margin-bottom: 20px;
            }
            
            .left-sidebar, .right-sidebar {
                position: static;
                height: auto;
            }
        }

        .post-media {
            margin-top: 10px;
            border-radius: 8px;
            overflow: hidden;
        }

        .post-image {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
        }

        .post-video {
            width: 100%;
            max-height: 500px;
        }
        
        /* Comment styles */
        .comments-section {
            margin-top: 10px;
            border-top: 1px solid #e4e6eb;
            padding-top: 10px;
            display: none;
        }
        
        .comments-list {
            margin-bottom: 10px;
        }
        
        .comment {
            display: flex;
            margin-bottom: 8px;
        }
        
        .comment-avatar {
            margin-right: 8px;
        }
        
        .small-icon {
            width: 32px;
            height: 32px;
            font-size: 0.8rem;
        }
        
        .comment-content {
            flex: 1;
        }
        
        .comment-bubble {
            background-color: #f0f2f5;
            border-radius: 18px;
            padding: 8px 12px;
            display: inline-block;
            max-width: 100%;
        }
        
        .comment-author {
            font-weight: 600;
            color: #050505;
            text-decoration: none;
            margin-right: 5px;
        }
        
        .comment-text {
            font-size: 0.9rem;
        }
        
        .comment-actions {
            display: flex;
            margin-top: 2px;
            padding-left: 12px;
        }
        
        .comment-action {
            font-size: 0.75rem;
            font-weight: 600;
            color: #65676b;
            margin-right: 8px;
            cursor: pointer;
        }
        
        .comment-time {
            font-size: 0.75rem;
            color: #65676b;
        }
        
        .comment-form {
            display: flex;
            align-items: center;
        }
        
        .comment-input-wrapper {
            flex: 1;
            background-color: #f0f2f5;
            border-radius: 20px;
            padding: 8px 12px;
            position: relative;
        }
        
        .comment-input {
            width: 100%;
            border: none;
            background-color: transparent;
            outline: none;
            font-size: 0.9rem;
        }
        
        .comment-emoji {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #65676b;
            cursor: pointer;
        }
        
        /* Share modal */
        .share-modal .modal-content {
            width: 500px;
        }
        
        .share-post-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .share-post-info {
            margin-left: 10px;
        }
        
        .share-post-content {
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #e4e6eb;
            border-radius: 8px;
            background-color: #f0f2f5;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-left">
            <a href="home.php" class="logo">f</a>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search Facebook">
            </div>
        </div>
        
        <div class="navbar-center">
            <div class="nav-icon active">
                <i class="fas fa-home"></i>
            </div>
            <div class="nav-icon">
                <i class="fas fa-tv"></i>
            </div>
            <div class="nav-icon">
                <i class="fas fa-store"></i>
            </div>
            <div class="nav-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="nav-icon">
                <i class="fas fa-gamepad"></i>
            </div>
        </div>
        
        <div class="navbar-right">
            <a href="profile.php" class="profile-icon">
                <?php
                if ($user && $user['profile_picture']) {
                    echo '<img src="' . $user['profile_picture'] . '" alt="Profile Picture">';
                } else {
                    echo substr($user_name, 0, 1);
                }
                ?>
            </a>
            <div class="icon-btn">
                <i class="fas fa-plus"></i>
            </div>
            <a href="messages.php" class="icon-btn">
                <i class="fab fa-facebook-messenger"></i>
            </a>
            <div class="icon-btn">
                <i class="fas fa-bell"></i>
            </div>
            <div class="icon-btn">
                <i class="fas fa-caret-down"></i>
            </div>
        </div>
    </div>
    
    <div class="main-container">
        <div class="left-sidebar">
            <a href="profile.php" class="sidebar-item">
                <i class="fas fa-user"></i>
                <span><?php echo $user_name; ?></span>
            </a>
            <div class="sidebar-item">
                <i class="fas fa-user-friends"></i>
                <span>Friends</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-users"></i>
                <span>Groups</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-store"></i>
                <span>Marketplace</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-tv"></i>
                <span>Watch</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-history"></i>
                <span>Memories</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-bookmark"></i>
                <span>Saved</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-flag"></i>
                <span>Pages</span>
            </div>
            <div class="sidebar-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Events</span>
            </div>
            <a href="logout.php" class="sidebar-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
        
        <div class="feed">
            <div class="create-post">
                <div class="create-post-top">
                    <div class="profile-icon">
                        <?php
                        if ($user && $user['profile_picture']) {
                            echo '<img src="' . $user['profile_picture'] . '" alt="Profile Picture">';
                        } else {
                            echo substr($user_name, 0, 1);
                        }
                        ?>
                    </div>
                    <div class="create-post-input" onclick="openPostModal()">
                        What's on your mind, <?php echo explode(' ', $user_name)[0]; ?>?
                    </div>
                </div>
                <div class="create-post-actions">
                    <a href="upload_media.php" class="post-action">
                        <i class="fas fa-video" style="color: #f02849;"></i>
                        <span>Live Video</span>
                    </a>
                    <a href="upload_media.php" class="post-action">
                        <i class="fas fa-images" style="color: #45bd62;"></i>
                        <span>Photo/Video</span>
                    </a>
                    <div class="post-action">
                        <i class="fas fa-smile" style="color: #f7b928;"></i>
                        <span>Feeling/Activity</span>
                    </div>
                </div>
            </div>
            
            <?php if($posts_result->num_rows > 0): ?>
                <?php while($post = $posts_result->fetch_assoc()): ?>
                    <?php displayPost($post, $conn, $user_id, $user_name, $user); ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="post">
                    <div class="post-content" style="text-align: center;">
                        No posts to show. Create your first post or add friends to see their posts.
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="right-sidebar">
            <?php if($request_count > 0): ?>
                <div class="friend-request">
                    <div class="request-header">
                        <div class="request-title">Friend Requests</div>
                        <div class="see-all">See All</div>
                    </div>
                    
                    <?php while($request = $requests_result->fetch_assoc()): ?>
                        <div class="request-item">
                            <div class="profile-icon">
                                <?php echo substr($request['name'], 0, 1); ?>
                            </div>
                            <div class="request-info">
                                <div class="request-name"><?php echo $request['name']; ?></div>
                                <div class="request-actions">
                                    <button class="accept-btn" onclick="acceptFriend(<?php echo $request['id']; ?>)">Confirm</button>
                                    <button class="decline-btn" onclick="declineFriend(<?php echo $request['id']; ?>)">Delete</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
            
            <div class="contacts">
                <div class="contact-header">
                    <div class="contact-title">People You May Know</div>
                    <div class="contact-actions">
                        <div class="contact-action">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="contact-action">
                            <i class="fas fa-ellipsis-h"></i>
                        </div>
                    </div>
                </div>
                
                <?php if($suggestions_result->num_rows > 0): ?>
                    <?php while($suggestion = $suggestions_result->fetch_assoc()): ?>
                        <div class="contact-item">
                            <div class="profile-icon">
                                <?php echo substr($suggestion['name'], 0, 1); ?>
                            </div>
                            <div class="contact-name"><?php echo $suggestion['name']; ?></div>
                            <button class="accept-btn" style="margin-left: auto;" onclick="addFriend(<?php echo $suggestion['id']; ?>)">Add</button>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding: 8px; color: #65676b;">No suggestions available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Create Post Modal -->
    <div class="modal" id="postModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Create Post</div>
                <div class="close-modal" onclick="closePostModal()">&times;</div>
            </div>
            <form class="post-form" method="post" action="">
                <textarea class="post-textarea" name="post_content" placeholder="What's on your mind, <?php echo explode(' ', $user_name)[0]; ?>?" oninput="checkPostContent()"></textarea>
                <button type="submit" class="post-submit" id="postSubmit" disabled>Post</button>
            </form>
        </div>
    </div>
    
    <!-- Share Post Modal -->
    <div class="modal share-modal" id="shareModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Share Post</div>
                <div class="close-modal" onclick="closeShareModal()">&times;</div>
            </div>
            <div class="share-post-header">
                <div class="profile-icon">
                    <?php
                    if ($user && $user['profile_picture']) {
                        echo '<img src="' . $user['profile_picture'] . '" alt="Profile Picture">';
                    } else {
                        echo substr($user_name, 0, 1);
                    }
                    ?>
                </div>
                <div class="share-post-info">
                    <div class="post-author"><?php echo $user_name; ?></div>
                </div>
            </div>
            <form class="post-form" id="shareForm">
                <textarea class="post-textarea" id="shareContent" placeholder="Write something..." oninput="checkShareContent()"></textarea>
                <div class="share-post-content" id="originalPostContent"></div>
                <input type="hidden" id="originalPostId" value="">
                <button type="button" class="post-submit" id="shareSubmit" onclick="sharePost()">Share</button>
            </form>
        </div>
    </div>
    
    <!-- Include post interactions JavaScript -->
    <?php include 'post_interactions.php'; ?>
    
    <script>
        function openPostModal() {
            document.getElementById('postModal').style.display = 'flex';
        }
        
        function closePostModal() {
            document.getElementById('postModal').style.display = 'none';
        }
        
        function checkPostContent() {
            const textarea = document.querySelector('.post-textarea');
            const submitBtn = document.getElementById('postSubmit');
            
            if(textarea.value.trim().length > 0) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
        
        function addFriend(userId) {
            // Using fetch API to send friend request
            fetch('friend_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add&user_id=' + userId
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Friend request sent!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        function acceptFriend(requestId) {
            fetch('friend_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=accept&request_id=' + requestId
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Friend request accepted!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        function declineFriend(requestId) {
            fetch('friend_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=decline&request_id=' + requestId
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Friend request declined!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        function checkShareContent() {
            const textarea = document.getElementById('shareContent');
            const submitBtn = document.getElementById('shareSubmit');
            
            submitBtn.disabled = textarea.value.trim().length === 0;
        }
    </script>
</body>
</html>
