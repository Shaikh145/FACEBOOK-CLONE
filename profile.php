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

$logged_user_id = $_SESSION['user_id'];
$logged_user_name = $_SESSION['user_name'];

// Determine which profile to show
$profile_id = isset($_GET['id']) ? $_GET['id'] : $logged_user_id;

// Get user info
$sql = "SELECT * FROM users WHERE id = '$profile_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<script>window.location.href = 'home.php';</script>";
    exit;
}

$user = $result->fetch_assoc();
$user_name = $user['name'];

// Get user posts
$sql = "SELECT * FROM posts WHERE user_id = '$profile_id' ORDER BY created_at DESC";
$posts_result = $conn->query($sql);

// Check friendship status
$is_friend = false;
$pending_request = false;
$request_sent_by_me = false;

if($profile_id != $logged_user_id) {
    // Check if they are friends
    $sql = "SELECT * FROM friends 
            WHERE (user_id = '$logged_user_id' AND friend_id = '$profile_id')
            OR (user_id = '$profile_id' AND friend_id = '$logged_user_id')";
    $friend_result = $conn->query($sql);
    
    if($friend_result->num_rows > 0) {
        $friendship = $friend_result->fetch_assoc();
        if($friendship['status'] == 'accepted') {
            $is_friend = true;
        } else {
            $pending_request = true;
            $request_sent_by_me = $friendship['user_id'] == $logged_user_id;
        }
    }
}

// Get friend count
$sql = "SELECT COUNT(*) as count FROM friends 
        WHERE ((user_id = '$profile_id' OR friend_id = '$profile_id') 
        AND status = 'accepted')";
$friend_count_result = $conn->query($sql);
$friend_count = $friend_count_result->fetch_assoc()['count'];

// Get mutual friends count
if($profile_id != $logged_user_id) {
    $sql = "SELECT COUNT(*) as count FROM friends f1
            JOIN friends f2 ON 
            (f1.friend_id = f2.friend_id OR f1.friend_id = f2.user_id OR f1.user_id = f2.friend_id)
            WHERE 
            ((f1.user_id = '$logged_user_id' AND f1.status = 'accepted') AND
            (f2.user_id = '$profile_id' AND f2.status = 'accepted') AND
            f1.friend_id != '$profile_id' AND f2.friend_id != '$logged_user_id')";
    $mutual_result = $conn->query($sql);
    $mutual_count = $mutual_result->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $user_name; ?> | Facebook</title>
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
        
        .profile-header {
            background-color: white;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            margin-bottom: 16px;
        }
        
        .cover-photo {
            height: 350px;
            background-color: #f0f2f5;
            border-radius: 0 0 8px 8px;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #65676b;
            font-size: 1.2rem;
        }
        
        .profile-photo {
            width: 168px;
            height: 168px;
            border-radius: 50%;
            background-color: white;
            border: 4px solid white;
            position: absolute;
            bottom: -22px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 4rem;
            color: #1877f2;
            font-weight: bold;
        }
        
        .profile-info {
            padding: 32px 16px 16px;
            text-align: center;
        }
        
        .profile-name {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .profile-meta {
            color: #65676b;
            margin-bottom: 15px;
        }
        
        .profile-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #e4e6eb;
        }
        
        .profile-action-btn {
            display: flex;
            align-items: center;
            background-color: #1877f2;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .profile-action-btn.secondary {
            background-color: #e4e6eb;
            color: #050505;
        }
        
        .profile-action-btn i {
            margin-right: 8px;
        }
        
        .main-container {
            max-width: 940px;
            margin: 0 auto;
            padding: 0 16px;
            display: flex;
            gap: 16px;
        }
        
        .left-column {
            flex: 1;
        }
        
        .intro-box {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 16px;
            margin-bottom: 16px;
        }
        
        .box-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .intro-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #65676b;
        }
        
        .intro-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .photos-box {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 16px;
            margin-bottom: 16px;
        }
        
        .photos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5px;
            margin-top: 10px;
        }
        
        .photo-item {
            aspect-ratio: 1;
            background-color: #f0f2f5;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #65676b;
        }
        
        .friends-box {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 16px;
            margin-bottom: 16px;
        }
        
        .friends-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .friends-title {
            font-size: 1.25rem;
            font-weight: bold;
        }
        
        .see-all-friends {
            color: #1877f2;
            cursor: pointer;
        }
        
        .friends-count {
            color: #65676b;
            margin-bottom: 10px;
        }
        
        .friends-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        
        .friend-item {
            text-decoration: none;
            color: inherit;
        }
        
        .friend-photo {
            aspect-ratio: 1;
            background-color: #e4e6eb;
            border-radius: 8px;
            margin-bottom: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2rem;
            color: #1877f2;
            font-weight: bold;
        }
        
        .friend-name {
            font-size: 0.9rem;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .right-column {
            flex: 2;
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
        
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .profile-photo {
                width: 132px;
                height: 132px;
                font-size: 3rem;
            }
            
            .cover-photo {
                height: 200px;
            }
            
            .friends-grid {
                grid-template-columns: repeat(2, 1fr);
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
            <div class="nav-icon">
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
                // Check if user has profile picture
                $sql = "SELECT profile_picture FROM users WHERE id = '$logged_user_id'";
                $result = $conn->query($sql);
                $user_pic = $result->fetch_assoc();
                
                if ($user_pic && $user_pic['profile_picture']) {
                    echo '<img src="' . $user_pic['profile_picture'] . '" alt="Profile Picture">';
                } else {
                    echo substr($logged_user_name, 0, 1);
                }
                ?>
            </a>
            <div class="icon-btn">
                <i class="fas fa-plus"></i>
            </div>
            <div class="icon-btn">
                <i class="fab fa-facebook-messenger"></i>
            </div>
            <div class="icon-btn">
                <i class="fas fa-bell"></i>
            </div>
            <div class="icon-btn">
                <i class="fas fa-caret-down"></i>
            </div>
        </div>
    </div>
    
    <div class="profile-header">
        <div class="cover-photo">
            <span>Add Cover Photo</span>
            <div class="profile-photo">
                <?php
                if ($user['profile_picture']) {
                    echo '<img src="' . $user['profile_picture'] . '" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover;">';
                } else {
                    echo substr($user_name, 0, 1);
                }
                ?>
            </div>
        </div>
        
        <div class="profile-info">
            <h1 class="profile-name"><?php echo $user_name; ?></h1>
            <div class="profile-meta">
                <?php echo $friend_count; ?> Friends
                <?php if($profile_id != $logged_user_id && isset($mutual_count)): ?>
                    · <?php echo $mutual_count; ?> mutual friends
                <?php endif; ?>
            </div>
            
            <div class="profile-actions">
                <?php if($profile_id == $logged_user_id): ?>
                    <button class="profile-action-btn">
                        <i class="fas fa-plus"></i> Add to Story
                    </button>
                    <button class="profile-action-btn secondary">
                        <i class="fas fa-pen"></i> Edit Profile
                    </button>
                <?php elseif($is_friend): ?>
                    <button class="profile-action-btn">
                        <i class="fas fa-user-check"></i> Friends
                    </button>
                    <button class="profile-action-btn secondary">
                        <i class="fab fa-facebook-messenger"></i> Message
                    </button>
                <?php elseif($pending_request): ?>
                    <?php if($request_sent_by_me): ?>
                        <button class="profile-action-btn secondary">
                            <i class="fas fa-user-clock"></i> Request Sent
                        </button>
                    <?php else: ?>
                        <button class="profile-action-btn" onclick="acceptFriendFromProfile(<?php echo $profile_id; ?>)">
                            <i class="fas fa-user-plus"></i> Confirm Request
                        </button>
                        <button class="profile-action-btn secondary" onclick="declineFriendFromProfile(<?php echo $profile_id; ?>)">
                            <i class="fas fa-user-times"></i> Delete Request
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="profile-action-btn" onclick="addFriendFromProfile(<?php echo $profile_id; ?>)">
                        <i class="fas fa-user-plus"></i> Add Friend
                    </button>
                    <button class="profile-action-btn secondary">
                        <i class="fab fa-facebook-messenger"></i> Message
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="main-container">
        <div class="left-column">
            <div class="intro-box">
                <h2 class="box-title">Intro</h2>
                <div class="intro-item">
                    <i class="fas fa-briefcase"></i>
                    <span>Works at Facebook</span>
                </div>
                <div class="intro-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Studied at Harvard University</span>
                </div>
                <div class="intro-item">
                    <i class="fas fa-home"></i>
                    <span>Lives in New York, New York</span>
                </div>
                <div class="intro-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>From California</span>
                </div>
                <div class="intro-item">
                    <i class="fas fa-heart"></i>
                    <span>Single</span>
                </div>
                <div class="intro-item">
                    <i class="fas fa-clock"></i>
                    <span>Joined on <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="photos-box">
                <div class="box-title">Photos</div>
                <div class="photos-grid">
                    <div class="photo-item">Photo</div>
                    <div class="photo-item">Photo</div>
                    <div class="photo-item">Photo</div>
                    <div class="photo-item">Photo</div>
                    <div class="photo-item">Photo</div>
                    <div class="photo-item">Photo</div>
                    <div class="photo-item">Photo</div>
                    <div class="photo-item">Photo</div>
                    <div class="photo-item">Photo</div>
                </div>
            </div>
            
            <div class="friends-box">
                <div class="friends-header">
                    <div class="friends-title">Friends</div>
                    <div class="see-all-friends">See all friends</div>
                </div>
                <div class="friends-count"><?php echo $friend_count; ?> friends</div>
                <div class="friends-grid">
                    <a href="#" class="friend-item">
                        <div class="friend-photo">J</div>
                        <div class="friend-name">John Smith</div>
                    </a>
                    <a href="#" class="friend-item">
                        <div class="friend-photo">S</div>
                        <div class="friend-name">Sarah Johnson</div>
                    </a>
                    <a href="#" class="friend-item">
                        <div class="friend-photo">M</div>
                        <div class="friend-name">Michael Brown</div>
                    </a>
                    <a href="#" class="friend-item">
                        <div class="friend-photo">E</div>
                        <div class="friend-name">Emily Davis</div>
                    </a>
                    <a href="#" class="friend-item">
                        <div class="friend-photo">D</div>
                        <div class="friend-name">David Wilson</div>
                    </a>
                    <a href="#" class="friend-item">
                        <div class="friend-photo">L</div>
                        <div class="friend-name">Lisa Martinez</div>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="right-column">
            <?php if($profile_id == $logged_user_id): ?>
                <div class="create-post">
                    <div class="create-post-top">
                        <div class="profile-icon">
                            <?php echo substr($user_name, 0, 1); ?>
                        </div>
                        <div class="create-post-input" onclick="openPostModal()">
                            What's on your mind, <?php echo explode(' ', $user_name)[0]; ?>?
                        </div>
                    </div>
                    <div class="create-post-actions">
                        <div class="post-action">
                            <i class="fas fa-video" style="color: #f02849;"></i>
                            <span>Live Video</span>
                        </div>
                        <div class="post-action">
                            <i class="fas fa-images" style="color: #45bd62;"></i>
                            <span>Photo/Video</span>
                        </div>
                        <div class="post-action">
                            <i class="fas fa-smile" style="color: #f7b928;"></i>
                            <span>Feeling/Activity</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if($posts_result->num_rows > 0): ?>
                <?php while($post = $posts_result->fetch_assoc()): ?>
                    <div class="post">
                        <div class="post-header">
                            <div class="profile-icon">
                                <?php echo substr($user_name, 0, 1); ?>
                            </div>
                            <div class="post-info">
                                <a href="profile.php?id=<?php echo $profile_id; ?>" class="post-author"><?php echo $user_name; ?></a>
                                <div class="post-time"><?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="post-content">
                            <?php echo nl2br($post['content']); ?>
                            
                            <?php
                            // Check if post has media
                            $post_id = $post['id'];
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
                                <i class="fas fa-thumbs-up" style="color: #1877f2;"></i>
                                <span>0</span>
                            </div>
                            <div>
                                <span>0 comments</span>
                            </div>
                        </div>
                        <div class="post-actions">
                            <div class="post-action-btn">
                                <i class="far fa-thumbs-up"></i>
                                <span>Like</span>
                            </div>
                            <div class="post-action-btn">
                                <i class="far fa-comment"></i>
                                <span>Comment</span>
                            </div>
                            <div class="post-action-btn">
                                <i class="fas fa-share"></i>
                                <span>Share</span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="post">
                    <div class="post-content" style="text-align: center;">
                        No posts to show.
                        <?php if($profile_id == $logged_user_id): ?>
                            Create your first post by clicking "What's on your mind?" above.
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="modal" id="postModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Create Post</div>
                <div class="close-modal" onclick="closePostModal()">&times;</div>
            </div>
            <form class="post-form" method="post" action="home.php">
                <textarea class="post-textarea" name="post_content" placeholder="What's on your mind, <?php echo explode(' ', $user_name)[0]; ?>?" oninput="checkPostContent()"></textarea>
                <button type="submit" class="post-submit" id="postSubmit" disabled>Post</button>
            </form>
        </div>
    </div>
    
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
        
        function addFriendFromProfile(userId) {
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
        
        function acceptFriendFromProfile(userId) {
            fetch('friend_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=accept_from_profile&user_id=' + userId
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
        
        function declineFriendFromProfile(userId) {
            fetch('friend_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=decline_from_profile&user_id=' + userId
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
    </script>
</body>
</html>
