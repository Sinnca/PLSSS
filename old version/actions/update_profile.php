<?php
session_start();
require_once '../config/db.php';

// Check if consultant is logged in
if (!isset($_SESSION['consultant_id'])) {
    header('Location: ../login.php');
    exit;
}

$consultant_id = $_SESSION['consultant_id'];
$user_id = $_SESSION['user_id'];

// Get form data
$name = mysqli_real_escape_string($conn, $_POST['name']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$title = mysqli_real_escape_string($conn, $_POST['title']);
$phone = mysqli_real_escape_string($conn, $_POST['phone']);
$bio = mysqli_real_escape_string($conn, $_POST['bio']);
$specialty = mysqli_real_escape_string($conn, $_POST['specialty']);
$hourly_rate = mysqli_real_escape_string($conn, $_POST['hourly_rate']);

// Handle profile photo upload
$profile_photo_update = "";
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
    // File upload logic here
} else {
    // No file uploaded, continue with profile update
}

// Check and add missing columns to consultants table
$required_columns = [
    "title" => "ALTER TABLE consultants ADD COLUMN title VARCHAR(255) NULL AFTER user_id",
    "phone" => "ALTER TABLE consultants ADD COLUMN phone VARCHAR(50) NULL AFTER title",
    "profile_photo" => "ALTER TABLE consultants ADD COLUMN profile_photo VARCHAR(255) NULL AFTER phone",
    "cv_file" => "ALTER TABLE consultants ADD COLUMN cv_file VARCHAR(255) NULL AFTER profile_photo",
    "currency" => "ALTER TABLE consultants ADD COLUMN currency VARCHAR(10) DEFAULT 'USD' AFTER hourly_rate",
    "rate_description" => "ALTER TABLE consultants ADD COLUMN rate_description TEXT NULL AFTER currency",
    "status" => "ALTER TABLE consultants ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER is_featured",
    "experience" => "ALTER TABLE consultants ADD COLUMN experience VARCHAR(50) NULL AFTER status",
    "location" => "ALTER TABLE consultants ADD COLUMN location VARCHAR(255) NULL AFTER experience",
    "qualification" => "ALTER TABLE consultants ADD COLUMN qualification VARCHAR(255) NULL AFTER location",
    "description" => "ALTER TABLE consultants ADD COLUMN description TEXT NULL AFTER qualification"
];

foreach ($required_columns as $column => $alter_sql) {
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM consultants LIKE '$column'");
    if (mysqli_num_rows($check_column) == 0) {
        mysqli_query($conn, $alter_sql);
    }
}

// Update user table
$sql = "UPDATE users SET name = '$name', email = '$email' WHERE id = $user_id";
if (mysqli_query($conn, $sql)) {
    // Update consultant table
    $sql = "UPDATE consultants SET 
            title = '$title', 
            phone = '$phone', 
            bio = '$bio',
            specialty = '$specialty',
            hourly_rate = '$hourly_rate',
            experience = '" . mysqli_real_escape_string($conn, $_POST['experience']) . "',
            location = '" . mysqli_real_escape_string($conn, $_POST['location']) . "',
            qualification = '" . mysqli_real_escape_string($conn, $_POST['qualification']) . "',
            description = '" . mysqli_real_escape_string($conn, $_POST['description']) . "'
            $profile_photo_update 
            WHERE id = $consultant_id";
    
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success_message'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating profile: " . mysqli_error($conn);
    }
} else {
    $_SESSION['error_message'] = "Error updating user information: " . mysqli_error($conn);
}

// Redirect back to profile page
header('Location: ../consultant/profile.php');
exit;
?>