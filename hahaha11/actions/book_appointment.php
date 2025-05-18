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
        // Get the availability details
        $sql = "SELECT * FROM availability WHERE id = $availability_id AND consultant_id = $consultant_id";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $availability = mysqli_fetch_assoc($result);
            
            // Check if slot is already booked
            if ($availability['is_booked'] == 1) {
                $errors[] = "This time slot is no longer available. Please select another time.";
            } else {
                // Begin transaction
                mysqli_begin_transaction($conn);
                
                try {
                    // Create appointment
                    $user_id = $_SESSION['user_id'];
                    $appointment_date = $availability['slot_date'];
                    $appointment_time = $availability['slot_time'];
                    
                    $sql = "INSERT INTO appointments (user_id, consultant_id, appointment_date, appointment_time, purpose, status, created_at) 
                            VALUES ($user_id, $consultant_id, '$appointment_date', '$appointment_time', '$purpose', 'pending', NOW())";
                    
                    if (!mysqli_query($conn, $sql)) {
                        throw new Exception("Failed to create appointment.");
                    }
                    
                    $appointment_id = mysqli_insert_id($conn);
                    
                    // Mark availability as booked
                    $sql = "UPDATE availability SET is_booked = 1, appointment_id = $appointment_id WHERE id = $availability_id";
                    
                    if (!mysqli_query($conn, $sql)) {
                        throw new Exception("Failed to update availability.");
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
        } else {
            $errors[] = "Invalid time slot selected.";
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