<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user or consultant is logged in
if (!(isset($_SESSION['user_id']) || isset($_SESSION['consultant_id']))) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Not authorized.'
    ]);
    exit;
}

// Get appointment ID from request
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$appointment_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid appointment ID'
    ]);
    exit;
}

// Get appointment details
$sql = "SELECT a.*, 
        u.name as client_name, 
        u.email as client_email,
        c.specialty,
        av.slot_date,
        av.slot_time,
        DATE_ADD(av.slot_time, INTERVAL 1 HOUR) as end_time,
        cu.name as consultant_name,
        cu.email as consultant_email
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN consultants c ON a.consultant_id = c.id
        JOIN users cu ON c.user_id = cu.id
        JOIN availability av ON a.availability_id = av.id
        WHERE a.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointment = mysqli_fetch_assoc($result);

if ($appointment) {
    // Format the appointment data
    $appointment['formatted_date'] = date('F j, Y', strtotime($appointment['slot_date']));
    $appointment['formatted_start_time'] = date('g:i A', strtotime($appointment['slot_time']));
    $appointment['formatted_end_time'] = date('g:i A', strtotime($appointment['end_time']));
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'appointment' => $appointment
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Appointment not found'
    ]);
}

mysqli_stmt_close($stmt);
?>