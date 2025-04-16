<?php
// This file creates necessary directories for uploads
// Run this file once before using the application

// Create uploads directory and subdirectories
$directories = [
    'uploads',
    'uploads/images',
    'uploads/videos',
    'uploads/profile_pictures'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "Created directory: $dir<br>";
        } else {
            echo "Failed to create directory: $dir<br>";
        }
    } else {
        echo "Directory already exists: $dir<br>";
    }
    
    // Set permissions
    chmod($dir, 0777);
    echo "Set permissions for: $dir<br>";
}

echo "<br>Setup completed. You can now use the application.";
?>
