<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if(!isLoggedIn()) {
    redirect('index.php');
}

$user_id = getCurrentUserId();
$error = "";
$success = "";

// Get user data
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($conn, $_POST['name']);
    $email = sanitize($conn, $_POST['email']);
    $bio = sanitize($conn, $_POST['bio'] ?? '');
    $location = sanitize($conn, $_POST['location'] ?? '');
    $workplace = sanitize($conn, $_POST['workplace'] ?? '');
    $education = sanitize($conn, $_POST['education'] ?? '');
    $relationship_status = sanitize($conn, $_POST['relationship_status'] ?? '');
    
    // Check if email is already taken by another user
    $check_email = "SELECT id FROM users WHERE email = '$email' AND id != '$user_id'";
    $email_result = $conn->query($check_email);
    
    if ($email_result->num_rows > 0) {
        $error = "Email is already taken by another user.";
    } else {
        // Update user information
        $update_sql = "UPDATE users SET 
                      name = '$name', 
                      email = '$email', 
                      bio = '$bio', 
                      location = '$location', 
                      workplace = '$workplace', 
                      education = '$education', 
                      relationship_status = '$relationship_status',
                      updated_at = NOW() 
                      WHERE id = '$user_id'";
        
        if ($conn->query($update_sql)) {
            // Update session variables
            $_SESSION['user_name'] = $name;
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $result = $conn->query($sql);
            $user = $result->fetch_assoc();
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | Facebook</title>
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
            max-width: 700px;
            margin: 20px auto;
            padding: 0 16px;
        }
        
        .edit-profile-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e4e6eb;
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .close-btn {
            color: #65676b;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .edit-form {
            display: flex;
            flex-direction: column;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #050505;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1877f2;
            box-shadow: 0 0 0 2px #e7f3ff;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .submit-btn {
            background-color: #1877f2;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: bold;
            padding: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: #166fe5;
        }
        
        .error-message {
            color: #ff0000;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffebe8;
            border: 1px solid #ffebe8;
            border-radius: 6px;
        }
        
        .success-message {
            color: #1e7b34;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e3f1e4;
            border: 1px solid #e3f1e4;
            border-radius: 6px;
        }
        
        .profile-picture-section {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e4e6eb;
        }
        
        .current-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #e4e6eb;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2.5rem;
            color: #1877f2;
            font-weight: bold;
            margin-right: 20px;
            overflow: hidden;
        }
        
        .current-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .picture-actions {
            display: flex;
            flex-direction: column;
        }
        
        .picture-action-btn {
            background-color: #e4e6eb;
            color: #050505;
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 10px;
            display: inline-flex;
            align-items: center;
        }
        
        .picture-action-btn i {
            margin-right: 8px;
        }
        
        .picture-action-btn.primary {
            background-color: #1877f2;
            color: white;
        }
        
        @media (max-width: 768px) {
            .navbar-center {
                display: none;
            }
            
            .search-bar {
                display: none;
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
                if ($user['profile_picture']) {
                    echo '<img src="' . $user['profile_picture'] . '" alt="Profile Picture">';
                } else {
                    echo substr($user['name'], 0, 1);
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
    
    <div class="main-container">
        <div class="edit-profile-card">
            <div class="card-header">
                <div class="card-title">Edit Profile</div>
                <div class="close-btn" onclick="window.location.href='profile.php'">&times;</div>
            </div>
            
            <?php if($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="profile-picture-section">
                <div class="current-picture">
                    <?php
                    if ($user['profile_picture']) {
                        echo '<img src="' . $user['profile_picture'] . '" alt="Profile Picture">';
                    } else {
                        echo substr($user['name'], 0, 1);
                    }
                    ?>
                </div>
                <div class="picture-actions">
                    <a href="update_profile_picture.php" class="picture-action-btn primary">
                        <i class="fas fa-camera"></i> Update Profile Picture
                    </a>
                    <?php if($user['profile_picture']): ?>
                        <a href="update_profile_picture.php?remove=1" class="picture-action-btn">
                            <i class="fas fa-trash"></i> Remove Picture
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <form class="edit-form" method="post" action="">
                <div class="form-group">
                    <label class="form-label" for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="bio">Bio</label>
                    <textarea class="form-control" id="bio" name="bio"><?php echo $user['bio'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="location">Location</label>
                    <input type="text" class="form-control" id="location" name="location" value="<?php echo $user['location'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="workplace">Workplace</label>
                    <input type="text" class="form-control" id="workplace" name="workplace" value="<?php echo $user['workplace'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="education">Education</label>
                    <input type="text" class="form-control" id="education" name="education" value="<?php echo $user['education'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="relationship_status">Relationship Status</label>
                    <select class="form-control" id="relationship_status" name="relationship_status">
                        <option value="" <?php echo empty($user['relationship_status']) ? 'selected' : ''; ?>>Select status</option>
                        <option value="Single" <?php echo ($user['relationship_status'] ?? '') == 'Single' ? 'selected' : ''; ?>>Single</option>
                        <option value="In a relationship" <?php echo ($user['relationship_status'] ?? '') == 'In a relationship' ? 'selected' : ''; ?>>In a relationship</option>
                        <option value="Engaged" <?php echo ($user['relationship_status'] ?? '') == 'Engaged' ? 'selected' : ''; ?>>Engaged</option>
                        <option value="Married" <?php echo ($user['relationship_status'] ?? '') == 'Married' ? 'selected' : ''; ?>>Married</option>
                        <option value="It's complicated" <?php echo ($user['relationship_status'] ?? '') == "It's complicated" ? 'selected' : ''; ?>>It's complicated</option>
                    </select>
                </div>
                
                <button type="submit" class="submit-btn">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>
