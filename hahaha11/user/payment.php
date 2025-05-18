<?php
require_once '../config/db.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Check if user is logged in
checkUserLogin();

// Get appointment ID from URL
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointment_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Fetch appointment details
$sql = "SELECT a.*, c.hourly_rate, c.specialty, c.payment_info, u.name as consultant_name 
        FROM appointments a
        JOIN consultants c ON a.consultant_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE a.id = $appointment_id AND a.user_id = {$_SESSION['user_id']}";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: dashboard.php");
    exit;
}

$appointment = mysqli_fetch_assoc($result);

// Display payment information (bank details or QR code)
// You'll implement the HTML/CSS

require_once '../includes/footer.php';
?>