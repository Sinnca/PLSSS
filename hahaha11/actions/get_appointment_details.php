<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!isset($_SESSION['consultant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}
$appointment_id = (int)$_GET['id'];
$consultant_id = $_SESSION['consultant_id'];

$sql = "SELECT a.*, u.name as client_name, u.email as client_email, av.slot_time, av.slot_date, av.duration
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN availability av ON a.availability_id = av.id
        WHERE a.id = $appointment_id AND a.consultant_id = $consultant_id
        LIMIT 1"; // 'a.specialty' should be included if present in appointments table
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found or not authorized.']);
    exit;
}
$appt = mysqli_fetch_assoc($result);

$appt['start_time'] = date('g:i A', strtotime($appt['slot_time']));
$appt['end_time'] = date('g:i A', strtotime($appt['slot_time']) + ($appt['duration'] * 60));
$appt['date'] = date('F j, Y', strtotime($appt['slot_date']));

// Only send relevant fields
$response = [
    'success' => true,
    'appointment' => [
        'client_name' => $appt['client_name'],
        'client_email' => $appt['client_email'],
        'date' => $appt['date'],
        'start_time' => $appt['start_time'],
        'end_time' => $appt['end_time'],
        'duration' => $appt['duration'],
        'service' => isset($appt['specialty']) ? $appt['specialty'] : '',
        'status' => $appt['status'],
    ]
];
echo json_encode($response);
