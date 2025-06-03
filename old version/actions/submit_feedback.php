<?php
ob_clean();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/auth.php';

$today = date('Y-m-d');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Please log in to submit feedback.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$appointment_id = intval($_POST['appointment_id'] ?? 0);
$consultant_id = intval($_POST['consultant_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$feedback = trim($_POST['feedback'] ?? '');

// Validate input
if (!$appointment_id || !$consultant_id || $rating < 1 || $rating > 5 || empty($feedback)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required and rating must be 1-5.']);
    exit;
}

// Verify appointment exists and belongs to user
$check_sql = "SELECT id FROM appointments 
              WHERE id = ? AND user_id = ? AND consultant_id = ? AND status = 'approved'
              AND (appointment_date < CURDATE() OR (appointment_date = CURDATE() AND appointment_time < CURTIME()))";
$stmt = mysqli_prepare($conn, $check_sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error occurred.']);
    exit;
}
mysqli_stmt_bind_param($stmt, 'iii', $appointment_id, $user_id, $consultant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid appointment or not authorized.']);
    exit;
}

// Check if feedback already exists
$check_feedback_sql = "SELECT id FROM appointment_feedback WHERE appointment_id = ?";
$stmt = mysqli_prepare($conn, $check_feedback_sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error occurred.']);
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $appointment_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    echo json_encode(['success' => false, 'error' => 'Feedback already submitted for this appointment.']);
    exit;
}
mysqli_stmt_close($stmt);

// Insert feedback
$insert_sql = "INSERT INTO appointment_feedback (appointment_id, user_id, consultant_id, rating, feedback, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
$stmt = mysqli_prepare($conn, $insert_sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error occurred.']);
    exit;
}
mysqli_stmt_bind_param($stmt, 'iiiis', $appointment_id, $user_id, $consultant_id, $rating, $feedback);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to submit feedback. Please try again.']);
}
mysqli_stmt_close($stmt);
exit;