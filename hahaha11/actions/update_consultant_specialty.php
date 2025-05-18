<?php
session_start();
require_once '../config/db.php';

// Check if consultant is logged in
if (!isset($_SESSION['consultant_id'])) {
    header('Location: ../login.php');
    exit;
}

$consultant_id = $_SESSION['consultant_id'];

// Define cybersecurity specialties
$cybersecurity_specialties = [
    'Network Security',
    'Application Security',
    'Cloud Security',
    'Security Operations',
    'Risk Management',
    'Incident Response'
];

// Get current specialty
$sql = "SELECT specialty FROM consultants WHERE id = $consultant_id";
$result = mysqli_query($conn, $sql);
$current = mysqli_fetch_assoc($result);

// Find the next available specialty
$new_specialty = null;
foreach ($cybersecurity_specialties as $specialty) {
    // Check if this specialty is already taken
    $check_sql = "SELECT COUNT(*) as count FROM consultants WHERE specialty = '" . mysqli_real_escape_string($conn, $specialty) . "'";
    $check_result = mysqli_query($conn, $check_sql);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['count'] == 0) {
        $new_specialty = $specialty;
        break;
    }
}

// If no unique specialty found, use the first one
if ($new_specialty === null) {
    $new_specialty = $cybersecurity_specialties[0];
}

// Update the specialty
$update_sql = "UPDATE consultants SET specialty = '" . mysqli_real_escape_string($conn, $new_specialty) . "' WHERE id = $consultant_id";
if (mysqli_query($conn, $update_sql)) {
    $_SESSION['success_message'] = "Your specialty has been updated to " . $new_specialty;
} else {
    $_SESSION['error_message'] = "Error updating specialty: " . mysqli_error($conn);
}

header('Location: ../consultant/profile.php');
exit;
?> 