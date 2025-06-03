<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
require_once '../config/db.php';

// Get appointment ID from URL
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointment_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid appointment ID']);
    exit;
}

// Fetch appointment details
$sql = "SELECT a.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
        cu.name as consultant_name, cu.email as consultant_email, cu.phone as consultant_phone,
        av.slot_date, av.slot_time, av.is_booked, a.created_at
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

if (!$result || mysqli_num_rows($result) == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Appointment not found']);
    exit;
}

$appointment = mysqli_fetch_assoc($result);

// Return appointment details as JSON
header('Content-Type: application/json');
echo json_encode($appointment); 