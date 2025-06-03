<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/auth.php';

// This is an AJAX endpoint to fetch appointments
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get date parameter and view type
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$view = isset($_GET['view']) ? $_GET['view'] : 'day';
$consultant_id = isset($_GET['consultant_id']) ? intval($_GET['consultant_id']) : 0;

// Validate date format and ensure it's a valid date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !DateTime::createFromFormat('Y-m-d', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format or date does not exist']);
    exit;
}

// Define date range based on view
$start_date = $date;
$end_date = $date;

if ($view === 'week') {
    // For week view, get appointments for the next 7 days
    $end_date = date('Y-m-d', strtotime($date . ' +6 days'));
}

try {
    // Check if user is consultant or client
    if ($_SESSION['user_role'] == 'consultant') {
        $consultant_id = $_SESSION['consultant_id'] ?? 0;
        
        if (!$consultant_id) {
            echo json_encode(['success' => false, 'message' => 'Consultant ID not found']);
            exit;
        }
        
        // Fetch all appointments for the consultant on the given date range
        $sql = "SELECT a.*, u.name as client_name, av.slot_date, av.slot_time, av.duration
                FROM appointments a
                JOIN users u ON a.user_id = u.id
                JOIN availability av ON a.availability_id = av.id
                WHERE a.consultant_id = ? 
                AND av.slot_date BETWEEN ? AND ? 
                AND a.status = 'approved'
                ORDER BY av.slot_date, av.slot_time";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $consultant_id, $start_date, $end_date);
    } else {
        // Get user's appointments or available slots based on parameters
        $user_id = $_SESSION['user_id'];
        
        if ($consultant_id > 0) {
            // Validate consultant_id
            if ($consultant_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid consultant ID']);
                exit;
            }

            // Fetch available slots for the given consultant and date range
            $sql = "SELECT av.*, 
                    CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END as is_booked_by_me
                    FROM availability av
                    LEFT JOIN appointments a ON av.appointment_id = a.id AND a.user_id = ? 
                    WHERE av.consultant_id = ? 
                    AND av.slot_date BETWEEN ? AND ? 
                    ORDER BY av.slot_date, av.slot_time";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $user_id, $consultant_id, $start_date, $end_date);
        } else {
            // Fetch user's appointments for the given date range
            $sql = "SELECT a.*, c.specialty, u.name as consultant_name, av.slot_date, av.slot_time
                    FROM appointments a
                    JOIN consultants c ON a.consultant_id = c.id
                    JOIN users u ON c.user_id = u.id
                    JOIN availability av ON a.availability_id = av.id
                    WHERE a.user_id = ? 
                    AND av.slot_date BETWEEN ? AND ? 
                    ORDER BY av.slot_date, av.slot_time";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $user_id, $start_date, $end_date);
        }
    }

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();
    $appointments = [];

    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    if (!empty($appointments)) {
        echo json_encode(['success' => true, 'appointments' => $appointments]);
    } else {
        echo json_encode(['success' => true, 'appointments' => [], 'message' => 'No appointments found']);
    }

    exit;

} catch (Exception $e) {
    error_log("Error in fetch_appointments.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
