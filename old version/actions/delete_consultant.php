<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if admin is logged in
checkAdminLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consultant_id'])) {
    $consultant_id = intval($_POST['consultant_id']);
    
    // First check if consultant exists
    $check_sql = "SELECT * FROM consultants WHERE id = $consultant_id";
    $result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($result) > 0) {
        $consultant = mysqli_fetch_assoc($result);
        $user_id = $consultant['user_id'];
        
        // Delete consultant's appointments
        $delete_appointments = "DELETE FROM appointments WHERE consultant_id = $consultant_id";
        mysqli_query($conn, $delete_appointments);
        
        // Delete consultant's availability
        $delete_availability = "DELETE FROM availability WHERE consultant_id = $consultant_id";
        mysqli_query($conn, $delete_availability);
        
        // Delete consultant record
        $delete_consultant = "DELETE FROM consultants WHERE id = $consultant_id";
        mysqli_query($conn, $delete_consultant);
        
        // Delete user record
        $delete_user = "DELETE FROM users WHERE id = $user_id";
        
        if (mysqli_query($conn, $delete_user)) {
            $_SESSION['success_message'] = "Consultant deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting consultant: " . mysqli_error($conn);
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