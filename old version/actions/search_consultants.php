<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

// This is an AJAX endpoint to search for consultants
header('Content-Type: application/json');

// Get search parameters
$specialty = isset($_GET['specialty']) ? $_GET['specialty'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Build query
$sql = "SELECT c.id, c.specialty, c.hourly_rate, c.bio, c.is_featured, 
        u.name, u.email,
        COUNT(DISTINCT a.id) as available_slots 
        FROM consultants c 
        JOIN users u ON c.user_id = u.id";

// If date is provided, join with availability
if (!empty($date)) {
    $sql .= " LEFT JOIN availability a ON c.id = a.consultant_id AND a.slot_date = '$date' AND a.is_booked = 0";
} else {
    $sql .= " LEFT JOIN availability a ON c.id = a.consultant_id AND a.slot_date >= CURDATE() AND a.is_booked = 0";
}

$sql .= " WHERE 1=1";

if (!empty($specialty)) {
    $specialty = mysqli_real_escape_string($conn, $specialty);
    $sql .= " AND c.specialty LIKE '%$specialty%'";
}

if (!empty($name)) {
    $name = mysqli_real_escape_string($conn, $name);
    $sql .= " AND u.name LIKE '%$name%'";
}

$sql .= " GROUP BY c.id";

// Add having clause if date is provided
if (!empty($date)) {
    $sql .= " HAVING available_slots > 0";
}

$sql .= " ORDER BY c.is_featured DESC, available_slots DESC";

$result = mysqli_query($conn, $sql);
$consultants = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Get available dates
    $consultant_id = $row['id'];
    $dates_sql = "SELECT DISTINCT slot_date FROM availability 
                WHERE consultant_id = $consultant_id 
                AND is_booked = 0 
                AND slot_date >= CURDATE()
                ORDER BY slot_date
                LIMIT 5";
    $dates_result = mysqli_query($conn, $dates_sql);
    
    $available_dates = [];
    while ($date_row = mysqli_fetch_assoc($dates_result)) {
        $available_dates[] = $date_row['slot_date'];
    }
    
    $row['available_dates'] = $available_dates;
    $consultants[] = $row;
}

echo json_encode(['success' => true, 'consultants' => $consultants]);
exit;
?>