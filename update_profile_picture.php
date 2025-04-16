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

// Handle profile picture removal
if(isset($_GET['remove']) && $_GET['remove'] == 1) {
    // Get current profile picture
    $sql = "SELECT profile_picture FROM users WHERE id = '$user_id'";
    $result = $conn->query($sql);
    $user = $result->fetch_assoc();
    
    if($user['profile_picture'] && file_exists($user['profile_picture'])) {
        // Delete the file
        unlink($user['profile_picture']);
    }
    
    // Update database
    $update_sql = "UPDATE users SET profile_picture = NULL WHERE id = '$user_id'";
    if($conn->query($update_sql)) {
        $success = "Profile picture removed successfully!";
    } else {
        $error = "Error removing profile picture: " . $conn->error;
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if file was uploaded
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['profile_picture']['type'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_name = $_FILES['profile_picture']['name'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        
        // Check file type
        if(!in_array($file_type, $allowed_types)) {
            $error = "Invalid file type. Only JPEG, PNG, GIF, and WEBP images are allowed.";
        } 
        // Check file size (5MB limit)
        else if($file_size > 5242880) {
            $error = "File size exceeds the 5MB limit.";
        } 
        else {
            // Create upload directory if it doesn't exist
            $upload_dir = 'uploads/profile_pictures/';
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $new_file_name = $user_id . '_' . uniqid() . '_' . $file_name;
            $file_path = $upload_dir . $new_file_name;
            
            // Get current profile picture
            $sql = "SELECT profile_picture FROM users WHERE id = '$user_id'";
            $result = $conn->query($sql);
            $user = $result->fetch_assoc();
            
            // Delete old profile picture if exists
            if($user['profile_picture'] && file_exists($user['profile_picture'])) {
                unlink($user['profile_picture']);
            }
            
            // Move uploaded file
            if(move_uploaded_file($file_tmp, $file_path)) {
                // Update database
                $file_path = sanitize($conn, $file_path);
                $update_sql = "UPDATE users SET profile_picture = '$file_path' WHERE id = '$user_id'";
                
                if($conn->query($update_sql)) {
                    $success = "Profile picture updated successfully!";
                } else {
                    $error = "Error updating profile picture: " . $conn->error;
                }
            } else {
                $error = "Error uploading file. Please try again.";
            }
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

// Get user data
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile Picture | Facebook</title>
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
        
        .profile-picture-card {
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
        
        .current-picture-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .current-picture {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background-color: #e4e6eb;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 4rem;
            color: #1877f2;
            font-weight: bold;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        .current-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .current-picture-label {
            color: #65676b;
            font-size: 0.9rem;
        }
        
        .upload-form {
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
        
        .file-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            border: 2px dashed #dddfe2;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-upload:hover {
            border-color: #1877f2;
            background-color: #f0f2f5;
        }
        
        .file-upload i {
            font-size: 3rem;
            color: #1877f2;
            margin-bottom: 10px;
        }
        
        .file-upload-text {
            font-size: 1.1rem;
            color: #65676b;
            margin-bottom: 5px;
        }
        
        .file-upload-info {
            font-size: 0.9rem;
            color: #65676b;
        }
        
        .file-input {
            display: none;
        }
        
        .selected-file {
            margin-top: 10px;
            padding: 10px;
            background-color: #f0f2f5;
            border-radius: 6px;
            display: none;
        }
        
        .selected-file-name {
            font-weight: 500;
            color: #050505;
            word-break: break-all;
        }
        
        .selected-file-preview {
            margin-top: 10px;
            max-width: 100%;
            max-height: 300px;
            border-radius: 6px;
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
        
        .submit-btn:disabled {
            background-color: #e4e6eb;
            color: #bcc0c4;
            cursor: not-allowed;
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
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #1877f2;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .navbar-center {
                display: none;
            }
            
            .search-bar {
                display: none;
            }
            
            .current-picture {
                width: 150px;
                height: 150px;
                font-size: 3rem;
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
        <div class="profile-picture-card">
            <div class="card-header">
                <div class="card-title">Update Profile Picture</div>
                <div class="close-btn" onclick="window.location.href='edit_profile.php'">&times;</div>
            </div>
            
            <?php if($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="current-picture-container">
                <div class="current-picture">
                    <?php
                    if ($user['profile_picture']) {
                        echo '<img src="' . $user['profile_picture'] . '" alt="Profile Picture">';
                    } else {
                        echo substr($user['name'], 0, 1);
                    }
                    ?>
                </div>
                <div class="current-picture-label">Current Profile Picture</div>
            </div>
            
            <form class="upload-form" method="post" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label" for="profile_picture">Upload New Profile Picture</label>
                    <div class="file-upload" id="file-upload-area">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div class="file-upload-text">Click to upload or drag and drop</div>
                        <div class="file-upload-info">Supported formats: JPEG, PNG, GIF, WEBP (Max: 5MB)</div>
                        <input type="file" class="file-input" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp">
                    </div>
                    
                    <div class="selected-file" id="selected-file-container">
                        <div class="selected-file-name" id="selected-file-name"></div>
                        <img class="selected-file-preview" id="selected-file-preview" src="/placeholder.svg" alt="Preview">
                    </div>
                </div>
                
                <button type="submit" class="submit-btn" id="submit-btn" disabled>Update Profile Picture</button>
            </form>
            
            <a href="edit_profile.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Edit Profile
            </a>
        </div>
    </div>
    
    <script>
        // File upload preview
        const fileInput = document.getElementById('profile_picture');
        const fileUploadArea = document.getElementById('file-upload-area');
        const selectedFileContainer = document.getElementById('selected-file-container');
        const selectedFileName = document.getElementById('selected-file-name');
        const selectedFilePreview = document.getElementById('selected-file-preview');
        const submitBtn = document.getElementById('submit-btn');
        
        // Click on file upload area to trigger file input
        fileUploadArea.addEventListener('click', () => {
            fileInput.click();
        });
        
        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            fileUploadArea.classList.add('highlight');
        }
        
        function unhighlight() {
            fileUploadArea.classList.remove('highlight');
        }
        
        fileUploadArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length) {
                fileInput.files = files;
                handleFileSelect();
            }
        }
        
        // Handle file selection
        fileInput.addEventListener('change', handleFileSelect);
        
        function handleFileSelect() {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const fileSize = file.size / (1024 * 1024); // Convert to MB
                
                // Check file size
                if (fileSize > 5) {
                    alert('File size exceeds the 5MB limit.');
                    fileInput.value = '';
                    return;
                }
                
                // Check file type
                const fileType = file.type;
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                
                if (!validTypes.includes(fileType)) {
                    alert('Invalid file type. Only JPEG, PNG, GIF, and WEBP images are allowed.');
                    fileInput.value = '';
                    return;
                }
                
                // Display file name
                selectedFileName.textContent = file.name;
                
                // Create preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    selectedFilePreview.src = e.target.result;
                    selectedFileContainer.style.display = 'block';
                    submitBtn.disabled = false;
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>
