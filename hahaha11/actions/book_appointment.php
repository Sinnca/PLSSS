<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
checkUserLogin();

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $consultant_id = isset($_POST['consultant_id']) ? intval($_POST['consultant_id']) : 0;
    $availability_id = isset($_POST['availability_id']) ? intval($_POST['availability_id']) : 0;
    $purpose = mysqli_real_escape_string($conn, trim($_POST['purpose']));
    
    // Validate input
    $errors = [];
    
    if ($consultant_id <= 0) {
        $errors[] = "Invalid consultant selected.";
    }
    
    if ($availability_id <= 0) {
        $errors[] = "Please select an appointment time.";
    }
    
    if (empty($purpose)) {
        $errors[] = "Please enter the purpose of your appointment.";
    }
    
    // If no errors, proceed with booking
    if (empty($errors)) {
        // Start transaction with proper isolation level
        mysqli_begin_transaction($conn);
        
        try {
            // First, verify and lock the availability slot
            $sql = "SELECT * FROM availability 
                   WHERE id = ? AND consultant_id = ? AND is_booked = 0
                   FOR UPDATE"; // This locks the row for update
                   
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $availability_id, $consultant_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) != 1) {
                throw new Exception("This time slot is no longer available. Please select another time.");
            }
            
            $availability = mysqli_fetch_assoc($result);
            
            // Double-check if the slot is still available (in case of race condition)
            if ($availability['is_booked'] == 1) {
                throw new Exception("This time slot has just been booked by someone else. Please select another time.");
            }
            
            // Create appointment
            $user_id = $_SESSION['user_id'];
            $appointment_date = $availability['slot_date'];
            $appointment_time = $availability['slot_time'];
            
            // First, check if an appointment already exists for this slot
            $check_sql = "SELECT id FROM appointments 
                         WHERE consultant_id = ? 
                         AND appointment_date = ? 
                         AND appointment_time = ?
                         LIMIT 1";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "iss", $consultant_id, $appointment_date, $appointment_time);
            mysqli_stmt_execute($check_stmt);
            $existing_appointment = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($existing_appointment) > 0) {
                throw new Exception("This time slot has already been booked. Please select another time.");
            }
            
            // Insert the new appointment
            $sql = "INSERT INTO appointments 
                   (user_id, consultant_id, appointment_date, appointment_time, purpose, status, created_at) 
                   VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iisss", $user_id, $consultant_id, $appointment_date, $appointment_time, $purpose);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to create appointment. Please try again.");
            }
            
            $appointment_id = mysqli_insert_id($conn);
            
            // Mark availability as booked
            $update_sql = "UPDATE availability 
                          SET is_booked = 1, 
                              appointment_id = ? 
                          WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ii", $appointment_id, $availability_id);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Failed to update availability. Please try again.");
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Store appointment ID in session for confirmation page
            $_SESSION['pending_appointment_id'] = $appointment_id;
            
            // Redirect to confirmation page
            header("Location: /user/confirm.php");
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
    
    // If there were errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['booking_errors'] = $errors;
        $_SESSION['booking_data'] = [
            'consultant_id' => $consultant_id,
            'purpose' => $purpose
        ];
        header("Location: /user/appointment.php?id=$consultant_id");
        exit;
    }
} else {
    // If not a POST request, redirect to consultants page
    header("Location: /user/consultants.php");
    exit;
}
?>