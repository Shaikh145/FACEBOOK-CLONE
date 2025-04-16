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

// Process media upload
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = sanitize($conn, $_POST['description'] ?? '');
    $post_type = isset($_POST['post_type']) ? $_POST['post_type'] : 'text';
    
    // Create post in database first
    $sql = "INSERT INTO posts (user_id, content, post_type, created_at) 
            VALUES ('$user_id', '$description', '$post_type', NOW())";
    
    if ($conn->query($sql)) {
        $post_id = $conn->insert_id;
        
        // Handle file upload if present
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
            $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $allowed_video_types = ['video/mp4', 'video/webm', 'video/ogg'];
            
            $file_type = $_FILES['media_file']['type'];
            $file_size = $_FILES['media_file']['size'];
            $file_name = $_FILES['media_file']['name'];
            $file_tmp = $_FILES['media_file']['tmp_name'];
            
            // Generate unique filename
            $new_file_name = uniqid() . '_' . $file_name;
            
            // Check file type
            if (in_array($file_type, $allowed_image_types)) {
                $upload_dir = 'uploads/images/';
                $media_type = 'image';
            } elseif (in_array($file_type, $allowed_video_types)) {
                $upload_dir = 'uploads/videos/';
                $media_type = 'video';
                
                // Check video size (100MB limit)
                if ($file_size > 104857600) { // 100MB in bytes
                    $error = "Video size exceeds the 100MB limit.";
                    // Delete the post since media upload failed
                    $conn->query("DELETE FROM posts WHERE id = '$post_id'");
                    goto end_processing;
                }
            } else {
                $error = "Invalid file type. Only images (JPEG, PNG, GIF, WEBP) and videos (MP4, WEBM, OGG) are allowed.";
                // Delete the post since media upload failed
                $conn->query("DELETE FROM posts WHERE id = '$post_id'");
                goto end_processing;
            }
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_path = $upload_dir . $new_file_name;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $file_path)) {
                // Update post with media information
                $file_path = sanitize($conn, $file_path);
                $sql = "INSERT INTO post_media (post_id, media_type, media_path) 
                        VALUES ('$post_id', '$media_type', '$file_path')";
                
                if ($conn->query($sql)) {
                    $success = "Your post has been published successfully!";
                    redirect('home.php');
                } else {
                    $error = "Error saving media information: " . $conn->error;
                    // Delete the post since media info save failed
                    $conn->query("DELETE FROM posts WHERE id = '$post_id'");
                    // Delete the uploaded file
                    unlink($file_path);
                }
            } else {
                $error = "Error uploading file. Please try again.";
                // Delete the post since file upload failed
                $conn->query("DELETE FROM posts WHERE id = '$post_id'");
            }
        } else {
            $success = "Your post has been published successfully!";
            redirect('home.php');
        }
    } else {
        $error = "Error creating post: " . $conn->error;
    }
}

end_processing:
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Media | Facebook</title>
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
        
        .upload-card {
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
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            font-size: 1rem;
            resize: none;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1877f2;
            box-shadow: 0 0 0 2px #e7f3ff;
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
        
        .post-type-selector {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #e4e6eb;
        }
        
        .post-type-option {
            flex: 1;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            color: #65676b;
            font-weight: 600;
            border-bottom: 3px solid transparent;
        }
        
        .post-type-option.active {
            color: #1877f2;
            border-bottom-color: #1877f2;
        }
        
        .post-type-option i {
            margin-right: 5px;
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
                // Check if user has profile picture
                $user_id = getCurrentUserId();
                $sql = "SELECT profile_picture FROM users WHERE id = '$user_id'";
                $result = $conn->query($sql);
                $user = $result->fetch_assoc();
                
                if ($user['profile_picture']) {
                    echo '<img src="' . $user['profile_picture'] . '" alt="Profile Picture">';
                } else {
                    echo substr(getCurrentUserName(), 0, 1);
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
        <div class="upload-card">
            <div class="card-header">
                <div class="card-title">Create Post</div>
                <div class="close-btn" onclick="window.location.href='home.php'">&times;</div>
            </div>
            
            <?php if($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="post-type-selector">
                <div class="post-type-option active" data-type="text">
                    <i class="fas fa-font"></i> Text
                </div>
                <div class="post-type-option" data-type="photo">
                    <i class="fas fa-image"></i> Photo
                </div>
                <div class="post-type-option" data-type="video">
                    <i class="fas fa-video"></i> Video
                </div>
            </div>
            
            <form class="upload-form" method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="post_type" id="post_type" value="text">
                
                <div class="form-group">
                    <label class="form-label" for="description">What's on your mind?</label>
                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Write something..."></textarea>
                </div>
                
                <div class="form-group" id="media-upload-container" style="display: none;">
                    <label class="form-label" for="media_file">Upload Media</label>
                    <div class="file-upload" id="file-upload-area">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div class="file-upload-text">Click to upload or drag and drop</div>
                        <div class="file-upload-info">Supported formats: JPEG, PNG, GIF, WEBP, MP4, WEBM, OGG (Max: 100MB)</div>
                        <input type="file" class="file-input" id="media_file" name="media_file" accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/ogg">
                    </div>
                    
                    <div class="selected-file" id="selected-file-container">
                        <div class="selected-file-name" id="selected-file-name"></div>
                        <div id="selected-file-preview-container">
                            <!-- Preview will be inserted here -->
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn" id="submit-btn">Post</button>
            </form>
        </div>
    </div>
    
    <script>
        // File upload preview
        const fileInput = document.getElementById('media_file');
        const fileUploadArea = document.getElementById('file-upload-area');
        const selectedFileContainer = document.getElementById('selected-file-container');
        const selectedFileName = document.getElementById('selected-file-name');
        const selectedFilePreviewContainer = document.getElementById('selected-file-preview-container');
        const submitBtn = document.getElementById('submit-btn');
        const postTypeOptions = document.querySelectorAll('.post-type-option');
        const postTypeInput = document.getElementById('post_type');
        const mediaUploadContainer = document.getElementById('media-upload-container');
        const descriptionInput = document.getElementById('description');
        
        // Post type selector
        postTypeOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove active class from all options
                postTypeOptions.forEach(opt => opt.classList.remove('active'));
                
                // Add active class to clicked option
                this.classList.add('active');
                
                // Set post type value
                const postType = this.getAttribute('data-type');
                postTypeInput.value = postType;
                
                // Show/hide media upload based on post type
                if (postType === 'photo' || postType === 'video') {
                    mediaUploadContainer.style.display = 'block';
                    
                    // Set accept attribute based on post type
                    if (postType === 'photo') {
                        fileInput.setAttribute('accept', 'image/jpeg,image/png,image/gif,image/webp');
                    } else {
                        fileInput.setAttribute('accept', 'video/mp4,video/webm,video/ogg');
                    }
                } else {
                    mediaUploadContainer.style.display = 'none';
                }
                
                validateForm();
            });
        });
        
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
                if (fileSize > 100) {
                    alert('File size exceeds the 100MB limit.');
                    fileInput.value = '';
                    return;
                }
                
                // Display file name
                selectedFileName.textContent = file.name;
                
                // Clear previous preview
                selectedFilePreviewContainer.innerHTML = '';
                
                // Create preview based on file type
                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.classList.add('selected-file-preview');
                    img.file = file;
                    selectedFilePreviewContainer.appendChild(img);
                    
                    const reader = new FileReader();
                    reader.onload = (function(aImg) {
                        return function(e) {
                            aImg.src = e.target.result;
                        };
                    })(img);
                    reader.readAsDataURL(file);
                } else if (file.type.startsWith('video/')) {
                    const video = document.createElement('video');
                    video.classList.add('selected-file-preview');
                    video.controls = true;
                    video.file = file;
                    selectedFilePreviewContainer.appendChild(video);
                    
                    const reader = new FileReader();
                    reader.onload = (function(aVideo) {
                        return function(e) {
                            aVideo.src = e.target.result;
                        };
                    })(video);
                    reader.readAsDataURL(file);
                }
                
                // Show selected file container
                selectedFileContainer.style.display = 'block';
                
                validateForm();
            }
        }
        
        // Form validation
        function validateForm() {
            const postType = postTypeInput.value;
            const description = descriptionInput.value.trim();
            
            if (postType === 'text') {
                // For text posts, description is required
                submitBtn.disabled = description === '';
            } else {
                // For photo/video posts, either description or file is required
                submitBtn.disabled = description === '' && (!fileInput.files.length);
            }
        }
        
        // Listen for description input changes
        descriptionInput.addEventListener('input', validateForm);
        
        // Initial validation
        validateForm();
    </script>
</body>
</html>
