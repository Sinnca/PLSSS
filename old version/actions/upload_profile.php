<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if consultant is logged in
checkConsultantLogin();

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $consultant_id = $_SESSION['consultant_id'];
    $upload_dir = "../uploads/profiles/";
    
    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] != 0) {
        $_SESSION['upload_error'] = "No file uploaded or upload error.";
        header("Location: /consultant/profile.php");
        exit;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['profile_photo']['type'], $allowed_types)) {
        $_SESSION['upload_error'] = "Only JPG, PNG and GIF images are allowed.";
        header("Location: /consultant/profile.php");
        exit;
    }
    
   // Validate file size (max 2MB)
   if ($_FILES['profile_photo']['size'] > 2 * 1024 * 1024) {
    $_SESSION['upload_error'] = "File is too large. Maximum size is 2MB.";
    header("Location: /consultant/profile.php");
    exit;
}

// Generate unique filename
$file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
$new_filename = $consultant_id . '_' . time() . '.' . $file_extension;
$target_file = $upload_dir . $new_filename;

// Move uploaded file
if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_file)) {
    // Update consultant profile in database
    
    // First, get current profile photo if exists
    $sql = "SELECT profile_photo FROM consultants WHERE id = $consultant_id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $old_photo = $row['profile_photo'];
    
    // Update with new photo
    $photo_path = "uploads/profiles/" . $new_filename;
    $sql = "UPDATE consultants SET profile_photo = '$photo_path', updated_at = NOW() WHERE id = $consultant_id";
    
    if (mysqli_query($conn, $sql)) {
        // Delete old photo if exists
        if (!empty($old_photo) && file_exists("../" . $old_photo)) {
            unlink("../" . $old_photo);
        }
        
        $_SESSION['success_message'] = "Profile photo was successfully updated.";
    } else {
        $_SESSION['upload_error'] = "Failed to update profile photo in database.";
        // Delete uploaded file if database update fails
        if (file_exists($target_file)) {
            unlink($target_file);
        }
    }
} else {
    $_SESSION['upload_error'] = "Failed to upload file. Please try again.";
}

// Redirect back to profile page
header("Location: /hahaha11/consultant/profile.php");
exit;
} else {
// If not a POST request, redirect to profile page
header("Location: /consultant/profile.php");
exit;
}
?>