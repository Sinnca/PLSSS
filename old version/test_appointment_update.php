<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/actions/update_appointment_status.php';

// Test email and appointment details
$testEmail = 'vaniverana@gmail.com'; // Replace with your email
$appointmentId = 1; // Replace with a valid appointment ID from your database
$status = 'confirmed'; // or 'cancelled'

// Simulate GET parameters
$_GET['id'] = $appointmentId;
$_GET['status'] = $status;

// Start session and set admin user (for testing)
session_start();
$_SESSION['user_id'] = 1; // Replace with a valid admin user ID
$_SESSION['user_role'] = 'admin';

// Include the update script
require __DIR__ . '/actions/update_appointment_status.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Appointment Update</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Appointment Status Update</h1>
        <p>Testing appointment ID: <?php echo htmlspecialchars($appointmentId); ?></p>
        <p>New status: <?php echo htmlspecialchars($status); ?></p>
        <p>Email sent to: <?php echo htmlspecialchars($testEmail); ?></p>
        
        <h2>Check your email inbox and PHP error log for results.</h2>
        
        <h3>Debug Information:</h3>
        <pre>
<?php
// Display recent error log entries
$logFile = ini_get('error_log');
echo "Error log file: " . $logFile . "\n\n";
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $logLines = array_slice(explode("\n", $logContent), -20); // Show last 20 lines
    echo implode("\n", $logLines);
} else {
    echo "Error log file not found.";
}
?>
        </pre>
    </div>
</body>
</html>
