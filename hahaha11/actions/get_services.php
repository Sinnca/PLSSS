<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

// This is an AJAX endpoint to fetch services/specialties
header('Content-Type: application/json');

// Build query to get all specialties with counts
$sql = "SELECT specialty, COUNT(*) as consultant_count 
        FROM consultants 
        GROUP BY specialty 
        ORDER BY consultant_count DESC, specialty ASC";

$result = mysqli_query($conn, $sql);
$services = [];

while ($row = mysqli_fetch_assoc($result)) {
    $services[] = [
        'name' => $row['specialty'],
        'consultant_count' => $row['consultant_count']
    ];
}

echo json_encode(['success' => true, 'services' => $services]);
exit;
?>