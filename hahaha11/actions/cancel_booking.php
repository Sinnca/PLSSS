<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /user/login.php");
    exit;
}

// Check user role
$is_user = ($_SESSION['user_role'] == 'user');
$is_consultant = ($_SESSION['user_role'] == 'consultant');

// Get appointment ID from URL
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointment_id <= 0) {
    if ($is_user) {
        header("Location: /user/dashboard.php");
    } else {
        header("Location: /consultant/dashboard.php");
    }
    exit;
}

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // Fetch appointment
    $user_id = $_SESSION['user_id'];
    
    if ($is_user) {
        $sql = "SELECT * FROM appointments WHERE id = $appointment_id AND user_id = $user_id";
    } else {
        $consultant_id = $_SESSION['consultant_id'];
        $sql = "SELECT * FROM appointments WHERE id = $appointment_id AND consultant_id = $consultant_id";
    }
    
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 0) {
        throw new Exception("Appointment not found.");
    }
    
    $appointment = mysqli_fetch_assoc($result);
    
    // Check if appointment can be cancelled
    $appointment_datetime = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];
    $current_datetime = date('Y-m-d H:i:s');
    $hours_difference = (strtotime($appointment_datetime) - strtotime($current_datetime)) / 3600;
    
    // Only allow cancellation if appointment is at least 24 hours away or if consultant is cancelling
    if ($hours_difference < 24 && $is_user) {
        throw new Exception("Appointments can only be cancelled at least 24 hours in advance.");
    }
    
    // Update appointment status
    $sql = "UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = $appointment_id";
    
    if (!mysqli_query($conn, $sql)) {
        throw new Exception("Failed to cancel appointment.");
    }
    
    // Free up availability
    $sql = "UPDATE availability SET is_booked = 0, appointment_id = NULL 
            WHERE appointment_id = $appointment_id";
    
    if (!mysqli_query($conn, $sql)) {
        throw new Exception("Failed to update availability.");
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Set success message
    $_SESSION['success_message'] = "Appointment was successfully cancelled.";
    
    // Redirect back to dashboard
    if ($is_user) {
        header("Location: /user/dashboard.php");
    } else {
        header("Location: /consultant/dashboard.php");
    }
    exit;
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    // Set error message
    $_SESSION['error_message'] = $e->getMessage();
    
    // Redirect back to dashboard
    if ($is_user) {
        header("Location: /user/dashboard.php");
    } else {
        header("Location: /consultant/dashboard.php");
    }
    exit;
}
?>