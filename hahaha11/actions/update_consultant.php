<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if admin is logged in
checkAdminLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consultant_id = intval($_POST['consultant_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
        header("Location: ../admin/manage_consultants.php");
        exit;
    }
    
    // Get the user_id for this consultant
    $get_user_id = "SELECT user_id FROM consultants WHERE id = $consultant_id";
    $result = mysqli_query($conn, $get_user_id);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $user_id = $row['user_id'];
        
        // Check if email already exists for other users
        $check_email = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
        $result = mysqli_query($conn, $check_email);
        
        if (mysqli_num_rows($result) > 0) {
            $_SESSION['error_message'] = "Email already exists.";
            header("Location: ../admin/manage_consultants.php");
            exit;
        }
        
        // Update user record
        $update_user = "UPDATE users SET name = '$name', email = '$email' WHERE id = $user_id";
        mysqli_query($conn, $update_user);
        
        // Update consultant record
        $update_consultant = "UPDATE consultants SET specialization = '$specialization' WHERE id = $consultant_id";
        
        if (mysqli_query($conn, $update_consultant)) {
            $_SESSION['success_message'] = "Consultant updated successfully.";
        } else {
            $_SESSION['error_message'] = "Error updating consultant: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error_message'] = "Consultant not found.";
    }
    
    header("Location: ../admin/manage_consultants.php");
    exit;
} else {
    header("Location: ../admin/manage_consultants.php");
    exit;
}
?>