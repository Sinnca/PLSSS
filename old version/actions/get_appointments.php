<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Get appointments
    $sql = "SELECT a.*, u.name as consultant_name, c.specialty, av.slot_date, av.slot_time, av.duration 
            FROM appointments a 
            JOIN consultants c ON a.consultant_id = c.id 
            JOIN users u ON c.user_id = u.id
            JOIN availability av ON a.availability_id = av.id 
            WHERE a.user_id = ? 
            AND TIMESTAMP(av.slot_date, av.slot_time) > NOW()
            ORDER BY av.slot_date ASC, av.slot_time ASC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new Exception("Get result failed: " . mysqli_error($conn));
    }

    $appointments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = [
            'id' => $row['id'],
            'consultant_name' => $row['consultant_name'],
            'specialty' => $row['specialty'],
            'appointment_date' => date('F j, Y', strtotime($row['slot_date'])),
            'appointment_time' => date('g:i A', strtotime($row['slot_time'])),
            'duration' => $row['duration'],
            'status' => $row['status']
        ];
    }

    // Return appointments as JSON
    header('Content-Type: application/json');
    echo json_encode($appointments);

} catch (Exception $e) {
    // Log the error
    error_log("Error in get_appointments.php: " . $e->getMessage());
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to load appointments: ' . $e->getMessage()]);
} 