<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php?error=Please login as admin");
    exit;
}
require_once '../config/db.php';

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="appointment_report_' . $start_date . '_to_' . $end_date . '.csv"');

$output = fopen('php://output', 'w');

// Overall stats
$sql = "SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN a.status = 'confirmed' THEN 1 ELSE 0 END) as approved_appointments,
            SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_appointments,
            SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
        FROM appointments a
        JOIN availability av ON a.availability_id = av.id
        WHERE av.slot_date BETWEEN '$start_date' AND '$end_date'";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $sql));

fputcsv($output, ["Appointment Report"]);
fputcsv($output, ["Date Range", $start_date . " to " . $end_date]);
fputcsv($output, []);
fputcsv($output, ["Total Appointments", $stats['total_appointments']]);
fputcsv($output, ["Approved Appointments", $stats['approved_appointments']]);
fputcsv($output, ["Pending Appointments", $stats['pending_appointments']]);
fputcsv($output, ["Cancelled Appointments", $stats['cancelled_appointments']]);
fputcsv($output, []);

// Consultant breakdown
fputcsv($output, ["Consultant Breakdown"]);
fputcsv($output, ["Consultant Name", "Total Appointments", "Approved", "Pending", "Cancelled"]);
$sql = "SELECT 
            cu.name as consultant_name,
            COUNT(*) as total_appointments,
            SUM(CASE WHEN a.status = 'confirmed' THEN 1 ELSE 0 END) as approved_appointments,
            SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_appointments,
            SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
        FROM appointments a
        JOIN consultants c ON a.consultant_id = c.id
        JOIN users cu ON c.user_id = cu.id
        JOIN availability av ON a.availability_id = av.id
        WHERE av.slot_date BETWEEN '$start_date' AND '$end_date'
        GROUP BY cu.name
        ORDER BY total_appointments DESC";
$consultant_stats = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($consultant_stats)) {
    fputcsv($output, [
        $row['consultant_name'],
        $row['total_appointments'],
        $row['approved_appointments'],
        $row['pending_appointments'],
        $row['cancelled_appointments']
    ]);
}
fclose($output);
exit; 