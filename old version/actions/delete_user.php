<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if admin is logged in
checkAdminLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    // First check if user exists
    $check_sql = "SELECT * FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($result) > 0) {
        // Delete user's appointments first
        $delete_appointments = "DELETE FROM appointments WHERE user_id = $user_id";
        mysqli_query($conn, $delete_appointments);
        
        // Delete user's availability if they are a consultant
        $delete_availability = "DELETE FROM availability WHERE consultant_id IN (SELECT id FROM consultants WHERE user_id = $user_id)";
        mysqli_query($conn, $delete_availability);
        
        // Delete consultant record if exists
        $delete_consultant = "DELETE FROM consultants WHERE user_id = $user_id";
        mysqli_query($conn, $delete_consultant);
        
        // Finally delete the user
        $delete_user = "DELETE FROM users WHERE id = $user_id";
        
        if (mysqli_query($conn, $delete_user)) {
            $_SESSION['success_message'] = "User deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting user: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error_message'] = "User not found.";
    }
    
    header("Location: ../admin/manage_users.php");
    exit;
} else {
    header("Location: ../admin/manage_users.php");
    exit;
}
?>