<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if consultant is logged in
checkConsultantLogin();

// Get appointment ID from URL
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointment_id <= 0) {
    header("Location: /consultant/dashboard.php");
    exit;
}

// Check if appointment belongs to the consultant
$consultant_id = $_SESSION['consultant_id'];
$sql = "SELECT * FROM appointments WHERE id = $appointment_id AND consultant_id = $consultant_id AND status = 'pending'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error_message'] = "Appointment not found or already processed.";
    header("Location: /consultant/dashboard.php");
    exit;
}

// Update appointment status
$sql = "UPDATE appointments SET status = 'approved', updated_at = NOW() WHERE id = $appointment_id";

if (mysqli_query($conn, $sql)) {
    $_SESSION['success_message'] = "Appointment was successfully approved.";
} else {
    $_SESSION['error_message'] = "Failed to approve appointment.";
}

// Redirect back to dashboard
header("Location: /consultant/dashboard.php");
exit;
?>