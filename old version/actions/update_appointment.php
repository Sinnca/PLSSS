<?php
// This file should be placed in the 'actions' directory - e.g., ../actions/update_appointment.php

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if consultant is logged in
checkConsultantLogin();

// Set header to JSON
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Check if required data is provided
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data'
    ]);
    exit;
}

// Get data from POST request
$appointment_id = intval($_POST['id']);
$status = mysqli_real_escape_string($conn, $_POST['status']);
$consultant_id = $_SESSION['consultant_id'];

// Validate status
$valid_statuses = ['approved', 'rejected', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status'
    ]);
    exit;
}

// Check if appointment exists and belongs to this consultant
$sql = "SELECT a.*, av.id as availability_id 
        FROM appointments a
        LEFT JOIN availability av ON a.appointment_date = av.slot_date AND a.appointment_time = av.slot_time AND av.consultant_id = a.consultant_id
        WHERE a.id = $appointment_id AND a.consultant_id = $consultant_id";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Appointment not found or unauthorized access'
    ]);
    exit;
}

$appointment = mysqli_fetch_assoc($result);
$availability_id = $appointment['availability_id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update appointment status
    $update_sql = "UPDATE appointments SET status = '$status', updated_at = NOW() WHERE id = $appointment_id";
    if (!mysqli_query($conn, $update_sql)) {
        throw new Exception("Failed to update appointment status");
    }
    
    // If status is approved, update availability
    if ($status === 'approved' && $availability_id) {
        $availability_sql = "UPDATE availability SET is_booked = 1, appointment_id = $appointment_id WHERE id = $availability_id";
        if (!mysqli_query($conn, $availability_sql)) {
            throw new Exception("Failed to update availability");
        }
    } else if ($status === 'approved') {
        // If no availability record exists, create one
        $availability_sql = "INSERT INTO availability (consultant_id, slot_date, slot_time, is_booked, appointment_id) 
                            VALUES ($consultant_id, '{$appointment['appointment_date']}', '{$appointment['appointment_time']}', 1, $appointment_id)";
        if (!mysqli_query($conn, $availability_sql)) {
            throw new Exception("Failed to create availability record");
        }
    }
    
    // If status is rejected or cancelled, free up the availability
    if (($status === 'rejected' || $status === 'cancelled') && $availability_id) {
        $availability_sql = "UPDATE availability SET is_booked = 0, appointment_id = NULL WHERE id = $availability_id";
        if (!mysqli_query($conn, $availability_sql)) {
            throw new Exception("Failed to update availability");
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Appointment ' . $status . ' successfully'
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>