<?php
// Create a simple test file to confirm this script is being executed
file_put_contents(__DIR__ . '/../logs/update_booking_test.log', date('Y-m-d H:i:s') . " - Script was executed\n", FILE_APPEND);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configure extensive logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Setup custom logging
$logFile = __DIR__ . '/../logs/email_debug.log';

// Create logs directory if it doesn't exist
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}

// Custom debug logging function
function debug_log($message) {
    $logFile = __DIR__ . '/../logs/email_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    error_log($message);
}

debug_log("=== START update_booking.php ===");
debug_log("POST params: " . print_r($_POST, true));

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
    global $emailConfig;
    
    // Log to both file and error log for maximum visibility
    debug_log("\n=== Starting sendAppointmentEmail ===");
    debug_log("Recipient: $toEmail");
    debug_log("Consultant: $consultantName");
    debug_log("Date: $appointmentDate, Time: $appointmentTime");
    debug_log("Status: $status");
    
    if (empty($emailConfig)) {
        debug_log("ERROR: Email configuration is not loaded");
        throw new Exception("Email configuration is not loaded");
    }
    
    // Validate email address
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        debug_log("ERROR: Invalid recipient email address: $toEmail");
        throw new Exception("Invalid recipient email address: " . $toEmail);
    }
    
    try {
        // Create a new PHPMailer instance with exceptions enabled
        $mail = new PHPMailer(true);
        debug_log("PHPMailer instance created");
        
        // Enable verbose debug output
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            debug_log("PHPMailer ($level): $str");
        };
        
        // Server settings - matching the working test configuration
        $mail->isSMTP();
        debug_log("SMTP mode set");
        
        $mail->Host = $emailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['username'];
        $mail->Password = $emailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $emailConfig['port'];
        $mail->Timeout = 30;
        
        debug_log("SMTP Configuration:" . 
                "\nHost: {$emailConfig['host']}" .
                "\nPort: {$emailConfig['port']}" .
                "\nUsername: {$emailConfig['username']}" .
                "\nEncryption: " . PHPMailer::ENCRYPTION_STARTTLS);
        
        // Disable SSL verification (temporary for testing)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Recipients
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($toEmail);
        debug_log("Recipients set: From {$emailConfig['from_email']} to $toEmail");
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Appointment Status Update: " . ucfirst($status);
        
        // Determine status text and colors
        $statusText = $status;
        $statusColor = '#4CAF50'; // Default to confirmed color
        $statusIcon = '✅';
        
        if ($status === 'cancelled' || $status === 'rejected') {
            $statusText = $status === 'cancelled' ? 'cancelled' : 'rejected';
            $statusColor = '#f44336';
            $statusIcon = '❌';
        } else if ($status === 'approved' || $status === 'confirmed') {
            $statusText = $status === 'approved' ? 'approved' : 'confirmed';
            $statusColor = '#4CAF50';
            $statusIcon = '✅';
        } else if ($status === 'pending') {
            $statusText = 'pending';
            $statusColor = '#ff9800';
            $statusIcon = '⏳';
        }
        
        // Create email body
        $mail->Body = createEmailBody($consultantName, $appointmentDate, $appointmentTime, $status);
        $mail->AltBody = "Your appointment with $consultantName on $appointmentDate at $appointmentTime has been $statusText.";
        
        debug_log("\n=== Preparing to send email ===");
        debug_log("To: $toEmail");
        debug_log("Subject: " . $mail->Subject);
        debug_log("SMTP Debug: Enabled");
        debug_log("Calling send() method...");
        
        // Send email
        $result = $mail->send();
        
        debug_log("send() method completed. Result: " . ($result ? 'true' : 'false'));
        debug_log("Error Info: " . $mail->ErrorInfo);
        
        if ($result) {
            debug_log("Email sent successfully to: $toEmail");
            return [
                'success' => true,
                'email_sent' => true,
                'message' => 'Email notification sent successfully'
            ];
        } else {
            debug_log("Email sending failed. ErrorInfo: " . $mail->ErrorInfo);
            throw new Exception("Email could not be sent: " . $mail->ErrorInfo);
        }
        
    } catch (Exception $e) {
        $error = "Email could not be sent. Error: " . $e->getMessage();
        debug_log("EXCEPTION: $error");
        debug_log("Stack trace: " . $e->getTraceAsString());
        
        return [
            'success' => false,
            'email_sent' => false,
            'message' => $error
        ];
    }
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
    // Mark this as a consultant interface call for debugging
    debug_log("\n=== CONSULTANT INTERFACE APPOINTMENT UPDATE ===");
    debug_log("USER: " . ($_SESSION['user_id'] ?? 'not set') . ", ROLE: " . ($_SESSION['user_role'] ?? 'not set'));
    debug_log("POST data: " . print_r($_POST, true));
    
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
        
        // Debug appointment details before sending email
        error_log("DEBUG: About to send email - Appointment details:");
        error_log("Client Email: " . $appointment['client_email']);
        error_log("Consultant: " . $appointment['consultant_name']);
        error_log("Date: " . $appointment['appointment_date']);
        error_log("Time: " . $appointment['appointment_time']);
        error_log("Status: " . $status);
        
        // Send email notification
        $emailResult = sendAppointmentEmail(
            $appointment['client_email'],
            $appointment['consultant_name'],
            $appointment['appointment_date'],
            $appointment['appointment_time'],
            $status
        );
        
        // Debug email result
        error_log("DEBUG: Email send result: " . json_encode($emailResult));
        
        // Prepare response
        $response['success'] = true;
        $response['message'] = "Appointment has been " . ucfirst($status) . " successfully";
        $response['email_sent'] = $emailResult['email_sent'] ?? false;
        $response['email_message'] = $emailResult['message'] ?? 'Unknown email error';
        
        if (!($emailResult['success'] ?? false)) {
            error_log("Email notification failed: " . ($emailResult['message'] ?? 'Unknown error'));
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        $response['message'] = $e->getMessage();
        error_log("Appointment update error: " . $e->getMessage());
    }
    
    // Check if this is a direct form submission or AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($isAjax) {
        // Return JSON response for AJAX requests
        echo json_encode($response);
        exit;
    } else {
        // For direct form submissions, redirect back to the referring page with a message
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Default to appointments page if referer is not available
        if (strpos($referer, 'dashboard.php') !== false) {
            $redirectUrl = '../consultant/dashboard.php';
        } else {
            $redirectUrl = '../consultant/appointments.php';
        }
        
        if ($response['success']) {
            $message = "Appointment has been " . ucfirst($status) . " successfully";
            if (!($response['email_sent'] ?? false)) {
                $message .= " (Email notification could not be sent)";
            }
            $redirectUrl .= "?success=" . urlencode($message);
        } else {
            $redirectUrl .= "?error=" . urlencode($response['message']);
        }
        
        header("Location: $redirectUrl");
        exit;
    }
}

// If not a POST request
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
exit;
