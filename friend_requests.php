<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if(!isLoggedIn()) {
    redirect('index.php');
}

$user_id = getCurrentUserId();
$user_name = getCurrentUserName();

// Get user data
$sql = "SELECT profile_picture FROM users WHERE id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

// Get friend requests
$sql = "SELECT f.id, u.id as user_id, u.name, u.profile_picture 
        FROM friends f 
        JOIN users u ON f.user_id = u.id 
        WHERE f.friend_id = '$user_id' AND f.status = 'pending'
        ORDER BY f.created_at DESC";
$requests_result = $conn->query($sql);
$request_count = $requests_result->num_rows;

// Get sent requests
$sql = "SELECT f.id, u.id as user_id, u.name, u.profile_picture 
        FROM friends f 
        JOIN users u ON f.friend_id = u.id 
        WHERE f.user_id = '$user_id' AND f.status = 'pending'
        ORDER BY f.created_at DESC";
$sent_requests_result = $conn->query($sql);
$sent_request_count = $sent_requests_result->num_rows;

// Get friends
$sql = "SELECT 
            u.id, u.name, u.profile_picture,
            CASE 
                WHEN f.user_id = '$user_id' THEN f.friend_id
                ELSE f.user_id
            END as friend_id
        FROM friends f
        JOIN users u ON (
            CASE 
                WHEN f.user_id = '$user_id' THEN f.friend_id
                ELSE f.user_id
            END = u.id
        )
        WHERE (f.user_id = '$user_id' OR f.friend_id = '$user_id')
        AND f.status = 'accepted'
        ORDER BY u.name";
$friends_result = $conn->query($sql);
$friend_count = $friends_result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friend Requests | Facebook</title>
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
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 16px;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .tabs {
            display: flex;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            color: #65676b;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            color: #1877f2;
            border-bottom-color: #1877f2;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 16px;
            margin-bottom: 16px;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #050505;
        }
        
        .request-item {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e4e6eb;
        }
        
        .request-item:last-child {
            border-bottom: none;
        }
        
        .request-avatar {
            margin-right: 12px;
        }
        
        .request-info {
            flex: 1;
        }
        
        .request-name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .request-name a {
            color: #050505;
            text-decoration: none;
        }
        
        .request-name a:hover {
            text-decoration: underline;
        }
        
        .request-meta {
            color: #65676b;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .request-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background-color: #1877f2;
            color: white;
        }
        
        .btn-secondary {
            background-color: #e4e6eb;
            color: #050505;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: #65676b;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #1877f2;
        }
        
        .empty-state-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #050505;
        }
        
        .friend-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .friend-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .friend-cover {
            height: 80px;
            background-color: #f0f2f5;
        }
        
        .friend-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: white;
            border: 4px solid white;
            margin: -50px auto 0;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2.5rem;
            color: #1877f2;
            font-weight: bold;
            overflow: hidden;
        }
        
        .friend-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .friend-info {
            padding: 10px 16px 16px;
            text-align: center;
        }
        
        .friend-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .friend-name a {
            color: #050505;
            text-decoration: none;
        }
        
        .friend-name a:hover {
            text-decoration: underline;
        }
        
        .friend-meta {
            color: #65676b;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .friend-actions {
            display: flex;
            justify-content: center;
        }
        
        @media (max-width: 768px) {
            .navbar-center {
                display: none;
            }
            
            .search-bar {
                display: none;
            }
            
            .friend-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
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
            <a href="home.php" class="nav-icon">
                <i class="fas fa-home"></i>
            </a>
            <div class="nav-icon">
                <i class="fas fa-tv"></i>
            </div>
            <div class="nav-icon">
                <i class="fas fa-store"></i>
            </div>
            <div class="nav-icon active">
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
        <h1 class="page-title">Friends</h1>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('requests')">Friend Requests <?php if($request_count > 0): ?><span>(<?php echo $request_count; ?>)</span><?php endif; ?></div>
            <div class="tab" onclick="showTab('sent')">Sent Requests <?php if($sent_request_count > 0): ?><span>(<?php echo $sent_request_count; ?>)</span><?php endif; ?></div>
            <div class="tab" onclick="showTab('friends')">All Friends <?php if($friend_count > 0): ?><span>(<?php echo $friend_count; ?>)</span><?php endif; ?></div>
        </div>
        
        <div id="requests-tab" class="tab-content active">
            <div class="card">
                <h2 class="card-title">Friend Requests</h2>
                
                <?php if($request_count > 0): ?>
                    <?php while($request = $requests_result->fetch_assoc()): ?>
                        <div class="request-item">
                            <div class="request-avatar">
                                <div class="profile-icon">
                                    <?php
                                    if ($request['profile_picture']) {
                                        echo '<img src="' . $request['profile_picture'] . '" alt="Profile Picture">';
                                    } else {
                                        echo substr($request['name'], 0, 1);
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="request-info">
                                <div class="request-name">
                                    <a href="profile.php?id=<?php echo $request['user_id']; ?>"><?php echo $request['name']; ?></a>
                                </div>
                                <div class="request-meta">
                                    <!-- You can add mutual friends info here -->
                                </div>
                                <div class="request-actions">
                                    <button class="btn btn-primary" onclick="acceptFriend(<?php echo $request['id']; ?>)">Confirm</button>
                                    <button class="btn btn-secondary" onclick="declineFriend(<?php echo $request['id']; ?>)">Delete</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-friends"></i>
                        <div class="empty-state-title">No Friend Requests</div>
                        <p>When you have friend requests, you'll see them here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="sent-tab" class="tab-content">
            <div class="card">
                <h2 class="card-title">Sent Requests</h2>
                
                <?php if($sent_request_count > 0): ?>
                    <?php while($sent_request = $sent_requests_result->fetch_assoc()): ?>
                        <div class="request-item">
                            <div class="request-avatar">
                                <div class="profile-icon">
                                    <?php
                                    if ($sent_request['profile_picture']) {
                                        echo '<img src="' . $sent_request['profile_picture'] . '" alt="Profile Picture">';
                                    } else {
                                        echo substr($sent_request['name'], 0, 1);
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="request-info">
                                <div class="request-name">
                                    <a href="profile.php?id=<?php echo $sent_request['user_id']; ?>"><?php echo $sent_request['name']; ?></a>
                                </div>
                                <div class="request-meta">
                                    Request sent
                                </div>
                                <div class="request-actions">
                                    <button class="btn btn-secondary" onclick="cancelFriendRequest(<?php echo $sent_request['user_id']; ?>)">Cancel Request</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-paper-plane"></i>
                        <div class="empty-state-title">No Sent Requests</div>
                        <p>You haven't sent any friend requests.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="friends-tab" class="tab-content">
            <div class="card">
                <h2 class="card-title">All Friends</h2>
                
                <?php if($friend_count > 0): ?>
                    <div class="friend-grid">
                        <?php while($friend = $friends_result->fetch_assoc()): ?>
                            <div class="friend-card">
                                <div class="friend-cover"></div>
                                <div class="friend-avatar">
                                    <?php
                                    if ($friend['profile_picture']) {
                                        echo '<img src="' . $friend['profile_picture'] . '" alt="Profile Picture">';
                                    } else {
                                        echo substr($friend['name'], 0, 1);
                                    }
                                    ?>
                                </div>
                                <div class="friend-info">
                                    <div class="friend-name">
                                        <a href="profile.php?id=<?php echo $friend['id']; ?>"><?php echo $friend['name']; ?></a>
                                    </div>
                                    <div class="friend-actions">
                                        <button class="btn btn-secondary" onclick="unfriend(<?php echo $friend['id']; ?>)">Unfriend</button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-friends"></i>
                        <div class="empty-state-title">No Friends Yet</div>
                        <p>When you become friends with people, they'll appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="friend_actions.js"></script>
    <script>
        function showTab(tabId) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Deactivate all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activate selected tab and content
            document.getElementById(tabId + '-tab').classList.add('active');
            document.querySelector(`.tab[onclick="showTab('${tabId}')"]`).classList.add('active');
        }
    </script>
</body>
</html>
