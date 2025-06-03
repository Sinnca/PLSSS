<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
checkUserLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get appointments
$sql = "SELECT a.*, u.name as consultant_name, c.specialty, av.slot_date, av.slot_time, av.duration 
        FROM appointments a 
        JOIN consultants c ON a.consultant_id = c.id 
        JOIN users u ON c.user_id = u.id
        JOIN availability av ON a.availability_id = av.id 
        WHERE a.user_id = ? 
        AND (av.slot_date > CURDATE() OR (av.slot_date = CURDATE() AND av.slot_time >= CURTIME()))
        AND a.status IN ('approved', 'confirmed')
        ORDER BY av.slot_date ASC, av.slot_time ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$appointments = array();
while ($row = mysqli_fetch_assoc($result)) {
    $row['appointment_date'] = date('F j, Y', strtotime($row['slot_date']));
    $row['appointment_time'] = date('g:i A', strtotime($row['slot_time']));
    $row['duration'] = isset($row['duration']) ? (int)$row['duration'] : 60;
    $appointments[] = $row;
}

// Return appointments as JSON
header('Content-Type: application/json');
echo json_encode($appointments); 