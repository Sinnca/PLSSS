<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if consultant is logged in
checkConsultantLogin();

// Check if cv_file column exists, if not create it
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM consultants LIKE 'cv_file'");
if (mysqli_num_rows($check_column) == 0) {
    $alter_sql = "ALTER TABLE consultants ADD COLUMN cv_file VARCHAR(255) NULL AFTER profile_photo";
    if (!mysqli_query($conn, $alter_sql)) {
        $_SESSION['upload_error'] = '<div class="alert alert-danger" style="background: #2d3a5a; color: #ff6b6b; border: 1px solid #ff6b6b;">Error creating cv_file column: ' . mysqli_error($conn) . '</div>';
        header("Location: ../consultant/profile.php");
        exit;
    }
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $consultant_id = $_SESSION['consultant_id'];
    $upload_dir = "../uploads/cvs/";
    
    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['cv_file']) || $_FILES['cv_file']['error'] != 0) {
        $_SESSION['upload_error'] = '<div class="alert alert-danger" style="background: #2d3a5a; color: #ff6b6b; border: 1px solid #ff6b6b;">No file uploaded or upload error.</div>';
        header("Location: ../consultant/profile.php");
        exit;
    }
    
    // Validate file type
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($_FILES['cv_file']['type'], $allowed_types)) {
        $_SESSION['upload_error'] = '<div class="alert alert-danger" style="background: #2d3a5a; color: #ff6b6b; border: 1px solid #ff6b6b;">Only PDF, DOC and DOCX files are allowed.</div>';
        header("Location: ../consultant/profile.php");
        exit;
    }
    
    // Validate file size (max 5MB)
    if ($_FILES['cv_file']['size'] > 5 * 1024 * 1024) {
        $_SESSION['upload_error'] = '<div class="alert alert-danger" style="background: #2d3a5a; color: #ff6b6b; border: 1px solid #ff6b6b;">File is too large. Maximum size is 5MB.</div>';
        header("Location: ../consultant/profile.php");
        exit;
    }
    
    // Generate unique filename
    $file_extension = pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION);
    $new_filename = 'cv_' . $consultant_id . '_' . time() . '.' . $file_extension;
    $target_file = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $target_file)) {
        // Update consultant profile in database
        $cv_path = 'uploads/cvs/' . $new_filename;
        
        // First, get current CV if exists
        $sql = "SELECT cv_file FROM consultants WHERE id = $consultant_id";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $old_cv = $row['cv_file'];
        
        // Update with new CV
        $sql = "UPDATE consultants SET cv_file = '$cv_path' WHERE id = $consultant_id";
        
        if (mysqli_query($conn, $sql)) {
            // Delete old CV if exists
            if (!empty($old_cv) && file_exists("../" . $old_cv)) {
                unlink("../" . $old_cv);
            }
            
            $_SESSION['success_message'] = '<div class="alert" style="background: #2d3a5a; color: #4f8cff; border: 1px solid #4f8cff;">CV was successfully uploaded.</div>';
        } else {
            $_SESSION['upload_error'] = '<div class="alert alert-danger" style="background: #2d3a5a; color: #ff6b6b; border: 1px solid #ff6b6b;">Failed to update CV in database.</div>';
            // Delete uploaded file if database update fails
            if (file_exists($target_file)) {
                unlink($target_file);
            }
        }
    } else {
        $_SESSION['upload_error'] = '<div class="alert alert-danger" style="background: #2d3a5a; color: #ff6b6b; border: 1px solid #ff6b6b;">Failed to upload file. Please try again.</div>';
    }
    
    // Redirect back to profile page
    header("Location: ../consultant/profile.php");
    exit;
} else {
    // If not a POST request, redirect to profile page
    header("Location: ../consultant/profile.php");
    exit;
}
?>
