<?php
// Direct approval test page
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if consultant is logged in
checkConsultantLogin();

// Get consultant information
$consultant_id = $_SESSION['consultant_id'];

// Get pending appointments
$sql = "SELECT a.id, u.name as client_name, u.email as client_email, 
        av.slot_date, av.slot_time, a.status, c.specialty
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN consultants c ON a.consultant_id = c.id
        JOIN availability av ON a.availability_id = av.id
        WHERE a.consultant_id = ? AND a.status = 'pending'
        ORDER BY av.slot_date ASC, av.slot_time ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $consultant_id);
mysqli_stmt_execute($stmt);
$appointments = mysqli_stmt_get_result($stmt);

// Process form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['status'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $status = $_POST['status'];
    
    // Create log entry
    file_put_contents(__DIR__ . '/../logs/direct_approval.log', 
        date('Y-m-d H:i:s') . " - Direct approval: ID=$appointment_id, Status=$status\n", 
        FILE_APPEND);
    
    // Update appointment status directly
    $update_sql = "UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "si", $status, $appointment_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        // Get appointment details for email
        $details_sql = "SELECT u.email as client_email, cu.name as consultant_name, 
                       av.slot_date as appointment_date, av.slot_time as appointment_time
                       FROM appointments a
                       JOIN users u ON a.user_id = u.id
                       JOIN availability av ON a.availability_id = av.id
                       JOIN consultants c ON a.consultant_id = c.id
                       JOIN users cu ON c.user_id = cu.id
                       WHERE a.id = ?";
        $details_stmt = mysqli_prepare($conn, $details_sql);
        mysqli_stmt_bind_param($details_stmt, "i", $appointment_id);
        mysqli_stmt_execute($details_stmt);
        $result = mysqli_stmt_get_result($details_stmt);
        $details = mysqli_fetch_assoc($result);
        
        // Load email configuration
        $emailConfig = require __DIR__ . '/../config/email_config.php';
        
        // Send email
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $emailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $emailConfig['username'];
            $mail->Password = $emailConfig['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $emailConfig['port'];
            
            // Recipients
            $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
            $mail->addAddress($details['client_email']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "Appointment Status Update: " . ucfirst($status);
            
            // Create email body
            $mail->Body = "Your appointment with {$details['consultant_name']} on {$details['appointment_date']} at {$details['appointment_time']} has been $status.";
            $mail->AltBody = "Your appointment with {$details['consultant_name']} on {$details['appointment_date']} at {$details['appointment_time']} has been $status.";
            
            $mail->send();
            $message = "Appointment has been $status and email notification sent successfully.";
        } catch (Exception $e) {
            $message = "Appointment has been $status but email notification could not be sent: " . $mail->ErrorInfo;
        }
    } else {
        $message = "Failed to update appointment status: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Appointment Approval</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Direct Appointment Approval</h1>
        <p>Use this page to directly approve or reject appointments.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Pending Appointments</h2>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($appointments) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Date/Time</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($appointment = mysqli_fetch_assoc($appointments)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                                    <td>
                                        <?php echo date('F j, Y', strtotime($appointment['slot_date'])); ?> at
                                        <?php echo date('g:i A', strtotime($appointment['slot_time'])); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['client_email']); ?></td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                        <form method="post" class="d-inline ms-2">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No pending appointments found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="appointments.php" class="btn btn-primary">Back to Appointments</a>
        </div>
    </div>
</body>
</html>
