<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if admin is logged in
checkAdminLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
        header("Location: ../admin/manage_users.php");
        exit;
    }
    
    // Check if email already exists for other users
    $check_email = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
    $result = mysqli_query($conn, $check_email);
    
    if (mysqli_num_rows($result) > 0) {
        $_SESSION['error_message'] = "Email already exists.";
        header("Location: ../admin/manage_users.php");
        exit;
    }
    
    // Update user
    $update_sql = "UPDATE users SET name = '$name', email = '$email', role = '$role' WHERE id = $user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $_SESSION['success_message'] = "User updated successfully.";
    } else {
        $_SESSION['error_message'] = "Error updating user: " . mysqli_error($conn);
    }
    
    header("Location: ../admin/manage_users.php");
    exit;
} else {
    header("Location: ../admin/manage_users.php");
    exit;
}
?>