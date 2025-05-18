<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Set content type to JSON for API responses
header('Content-Type: application/json');

// Check if user is logged in and has the right role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'consultant'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$isConsultant = ($_SESSION['user_role'] === 'consultant');

// Load email configuration
$emailConfigPath = __DIR__ . '/../config/email_config.php';

if (!file_exists($emailConfigPath)) {
    $error = "Email configuration file not found";
    error_log($error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

$emailConfig = require $emailConfigPath;

// Verify required email config values
$required = ['host', 'username', 'password', 'port', 'from_email', 'from_name'];
$missingConfigs = [];
foreach ($required as $key) {
    if (empty($emailConfig[$key])) {
        $missingConfigs[] = $key;
    }
}

if (!empty($missingConfigs)) {
    $error = 'Missing email configuration: ' . implode(', ', $missingConfigs);
    error_log($error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

/**
 * Send email notification for appointment status changes
 */
function sendAppointmentEmail($toEmail, $consultantName, $appointmentDate, $appointmentTime, $status) {
    global $emailConfig, $isConsultant;
    
    // Initialize response
    $response = [
        'success' => false,
        'message' => '',
        'email_sent' => false
    ];
    
    // Setup logging
    $logMessage = function($message) use ($isConsultant) {
        if ($isConsultant) {
            error_log('EMAIL NOTIFICATION: ' . $message);
        }
    };
    
    // Validate email
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address: $toEmail";
        $logMessage($error);
        $response['message'] = $error;
        return $response;
    }
    
    // Set email parameters
    $fromEmail = $emailConfig['from_email'];
    $fromName = $emailConfig['from_name'];
    $subject = "Appointment " . ucfirst($status) . ": Consultation with $consultantName";
    
    // Create email body
    $emailBody = createEmailBody($consultantName, $appointmentDate, $appointmentTime, $status);
    
    try {
        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $emailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['username'];
        $mail->Password = $emailConfig['password'];
        $mail->SMTPSecure = $emailConfig['smtp_secure'] ?? 'tls';
        $mail->Port = $emailConfig['port'];
        
        // Enable debug for consultant interface
        if ($isConsultant) {
            $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
            $mail->Debugoutput = function($str) use ($logMessage) {
                $logMessage($str);
            };
        }
        
        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);
        $mail->addReplyTo($fromEmail, $fromName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $emailBody));
        
        // Send email
        $mail->send();
        
        $response['success'] = true;
        $response['message'] = 'Email sent successfully';
        $response['email_sent'] = true;
        $logMessage("Email sent to $toEmail");
        
    } catch (Exception $e) {
        $error = "Email could not be sent. Error: " . $e->getMessage();
        $logMessage($error);
        $response['message'] = $error;
    }
    
    return $response;
}

/**
 * Create HTML email body
 */
function createEmailBody($consultantName, $appointmentDate, $appointmentTime, $status) {
    global $emailConfig;
    
    $formattedDate = date('F j, Y', strtotime($appointmentDate));
    $formattedTime = date('g:i A', strtotime($appointmentTime));
    
    $statusColor = [
        'approved' => '#28a745',
        'rejected' => '#dc3545',
        'cancelled' => '#ffc107',
        'pending' => '#17a2b8',
        'completed' => '#6f42c1'
    ][$status] ?? '#6c757d';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
        <title>Appointment " . ucfirst($status) . "</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; }
            .status { 
                display: inline-block; 
                padding: 8px 16px; 
                border-radius: 20px; 
                color: white;
                font-weight: bold;
                background: $statusColor;
                margin: 5px 0;
            }
            .details { 
                background: #f8f9fa; 
                padding: 20px; 
                border-radius: 5px; 
                margin: 20px 0;
                border-left: 4px solid $statusColor;
            }
            .footer { 
                margin-top: 30px; 
                padding: 20px; 
                text-align: center;
                font-size: 0.9em; 
                color: #6c757d;
                border-top: 1px solid #eee;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                margin: 15px 0;
                background: #007bff;
                color: white !important;
                text-decoration: none;
                border-radius: 5px;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0;'>Appointment " . ucfirst($status) . "</h1>
            </div>
            <div class='content'>
                <p>Hello,</p>
                <p>Your appointment with <strong>" . htmlspecialchars($consultantName) . "</strong> has been <span class='status'>" . ucfirst($status) . "</span>.</p>
                
                <div class='details'>
                    <h3 style='margin-top:0; color: $statusColor;'>Appointment Details</h3>
                    <p><strong>Date:</strong> " . htmlspecialchars($formattedDate) . "</p>
                    <p><strong>Time:</strong> " . htmlspecialchars($formattedTime) . "</p>
                    <p><strong>Status:</strong> <span class='status'>" . ucfirst($status) . "</span></p>
                </div>
                
                <p>" . (
                    $status === 'approved' ? 
                    'We look forward to seeing you! Please arrive 10 minutes before your scheduled time.' : 
                    'If you have any questions or need to reschedule, please contact us.'
                ) . "</p>
                
                <a href='mailto:" . htmlspecialchars($emailConfig['from_email']) . "' class='button'>Contact Us</a>
                
                <p>Best regards,<br>" . htmlspecialchars($emailConfig['from_name']) . " Team</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " " . htmlspecialchars($emailConfig['from_name']) . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
}

// Process appointment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize response
    $response = [
        'success' => false,
        'message' => '',
        'email_sent' => false
    ];
    
    try {
        // Verify required parameters
        $required = ['appointment_id', 'status'];
        $missing = [];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception('Missing required parameters: ' . implode(', ', $missing));
        }
        
        $appointmentId = (int)$_POST['appointment_id'];
        $status = strtolower(trim($_POST['status']));
        $allowedStatuses = ['approved', 'rejected', 'cancelled', 'completed'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception('Invalid status. Allowed values: ' . implode(', ', $allowedStatuses));
        }
        
        // Get appointment details
        $stmt = $conn->prepare("SELECT a.*, u.name as client_name, u.email as client_email, 
                              av.slot_date as appointment_date, av.slot_time as appointment_time,
                              c.user_id as consultant_id, cu.name as consultant_name
                              FROM appointments a
                              JOIN users u ON a.user_id = u.id
                              JOIN availability av ON a.availability_id = av.id
                              JOIN consultants c ON a.consultant_id = c.id
                              JOIN users cu ON c.user_id = cu.id
                              WHERE a.id = ?");
        
        $stmt->bind_param('i', $appointmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Appointment not found');
        }
        
        $appointment = $result->fetch_assoc();
        $stmt->close();
        
        // Check authorization (admin or owning consultant)
        if ($isConsultant && $appointment['consultant_id'] != $_SESSION['user_id']) {
            throw new Exception('Unauthorized to update this appointment');
        }
        
        // Update appointment status
        $updateStmt = $conn->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->bind_param('si', $status, $appointmentId);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update appointment: ' . $updateStmt->error);
        }
        
        $updateStmt->close();
        
        // Send email notification
        $emailResult = sendAppointmentEmail(
            $appointment['client_email'],
            $appointment['consultant_name'],
            $appointment['appointment_date'],
            $appointment['appointment_time'],
            $status
        );
        
        // Prepare response
        $response['success'] = true;
        $response['message'] = "Appointment has been " . ucfirst($status) . " successfully";
        $response['email_sent'] = $emailResult['email_sent'];
        $response['email_message'] = $emailResult['message'];
        
        if (!$emailResult['success']) {
            error_log("Email notification failed: " . $emailResult['message']);
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        $response['message'] = $e->getMessage();
        error_log("Appointment update error: " . $e->getMessage());
    }
    
    // Return JSON response
    echo json_encode($response);
    exit;
}

// If not a POST request
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
exit;
