<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is admin
$is_admin = ($_SESSION['user_role'] === 'admin');
$is_consultant = ($_SESSION['user_role'] === 'consultant');

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="appointments_report_' . date('Y-m-d') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Set the column headers for the CSV file
fputcsv($output, [
    'ID', 
    'Date', 
    'Time', 
    'Client Name', 
    'Client Email', 
    'Consultant Name',  
    'Specialty', 
    'Purpose', 
    'Duration (min)', 
    'Rate', 
    'Status', 
    'Created At', 
    'Updated At'
]);

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$consultant_id = isset($_GET['consultant_id']) ? intval($_GET['consultant_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Initialize the WHERE clause parts
$where_conditions = ["av.slot_date <= CURRENT_DATE()"];
$params = [];
$param_types = "";

// Add date range filters
if (!empty($start_date) && !empty($end_date)) {
    $where_conditions[] = "av.slot_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $param_types .= "ss";
}

// Add status filter
if ($status !== 'all') {
    $where_conditions[] = "a.status = ?";
    $params[] = $status;
    $param_types .= "s";
}

// Apply user-specific filters
if (!$is_admin) {
    if ($is_consultant) {
        $consultant_id = $_SESSION['consultant_id'] ?? 0;
        $where_conditions[] = "a.consultant_id = ?";
        $params[] = $consultant_id;
        $param_types .= "i";
    } else {
        // Regular user can only see their own appointments
        $where_conditions[] = "a.user_id = ?";
        $params[] = $_SESSION['user_id'];
        $param_types .= "i";
    }
} else {
    // Admin can filter by specific consultant or user
    if ($consultant_id > 0) {
        $where_conditions[] = "a.consultant_id = ?";
        $params[] = $consultant_id;
        $param_types .= "i";
    }
    
    if ($user_id > 0) {
        $where_conditions[] = "a.user_id = ?";
        $params[] = $user_id;
        $param_types .= "i";
    }
}

// Construct the WHERE clause
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Build the SQL query for appointments (without pagination for export)
$sql = "SELECT a.*, 
        u.name as client_name, 
        u.email as client_email,
        c.specialty, 
        c.hourly_rate,
        c.currency,
        cu.name as consultant_name,
        cu.email as consultant_email,
        av.duration,
        av.slot_date,
        av.slot_time
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN consultants c ON a.consultant_id = c.id
        JOIN users cu ON c.user_id = cu.id
        JOIN availability av ON a.availability_id = av.id
        $where_clause
        ORDER BY av.slot_date DESC, av.slot_time DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Output each row of the data
while ($row = $result->fetch_assoc()) {
    // Format the data for the CSV
    $csv_row = [
        $row['id'],
        $row['slot_date'],
        date('h:i A', strtotime($row['slot_time'])),
        $row['client_name'],
        $row['client_email'],
        $row['consultant_name'],
        $row['specialty'],
        $row['purpose'],
        $row['duration'] ?? 60,
        $row['currency'] . ' ' . $row['hourly_rate'],
        ucfirst($row['status']),
        $row['created_at'],
        $row['updated_at']
    ];
    
    fputcsv($output, $csv_row);
}

// Close the file pointer
fclose($output);
exit;