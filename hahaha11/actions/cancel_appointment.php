<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if consultant is logged in
checkConsultantLogin();

header('Content-Type: application/json');

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
    exit;
}

$appointment_id = (int)$_POST['id'];
$consultant_id = $_SESSION['consultant_id'];

// First verify the appointment belongs to this consultant
$check_sql = "SELECT id FROM appointments WHERE id = $appointment_id AND consultant_id = $consultant_id";
$check_result = mysqli_query($conn, $check_sql);

if (!$check_result || mysqli_num_rows($check_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found or unauthorized']);
    exit;
}

// Update appointment status to cancelled
$update_sql = "UPDATE appointments SET status = 'cancelled' WHERE id = $appointment_id";
$update_result = mysqli_query($conn, $update_sql);

if ($update_result) {
    echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment']);
} 