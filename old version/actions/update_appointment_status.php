<?php
// Configuration du logging
$logFile = __DIR__ . '/../logs/email_debug.log';

// Créer le dossier logs s'il n'existe pas
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}

// Custom logging function
if (!function_exists('debug_log')) {
    function debug_log($message) {
        $logFile = __DIR__ . '/../logs/email_debug.log';
        $logDir = dirname($logFile);
        
        // Create logs directory if it doesn't exist
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        
        // Write to both the log file and PHP's error log
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        error_log($message);
    }
}

session_start();

// Debug session data
error_log("Session data: " . print_r($_SESSION, true));
error_log("User role: " . ($_SESSION['user_role'] ?? 'not set'));
error_log("User ID: " . ($_SESSION['user_id'] ?? 'not set'));

// Allow both admin and consultant roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'consultant'])) {
    $error_msg = "Accès non autorisé - " . 
                "User ID: " . ($_SESSION['user_id'] ?? 'not set') . 
                ", Role: " . ($_SESSION['user_role'] ?? 'not set');
    debug_log($error_msg);
    error_log($error_msg);
    header("Location: ../user/login.php?error=Please login as admin or consultant");
    exit;
}

require_once __DIR__ . '/../config/db.php';
// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load email configuration
$emailConfig = require __DIR__ . '/../config/email_config.php';

// Debug email config
error_log("Email Config: " . print_r($emailConfig, true));

function debug_log($message) {
    $logFile = __DIR__ . '/../logs/email_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    error_log($message);
}

function sendAppointmentConfirmation($toEmail, $consultantName, $appointmentDate, $appointmentTime, $status) {
    global $emailConfig;
    
    // Log to both file and error log for maximum visibility
    error_log("=== sendAppointmentConfirmation CALLED ===");
    error_log("To: $toEmail, Consultant: $consultantName, Date: $appointmentDate, Time: $appointmentTime, Status: $status");
    
    debug_log("\n=== Starting sendAppointmentConfirmation ===");
    debug_log("Recipient: " . var_export($toEmail, true));
    debug_log("Consultant: " . var_export($consultantName, true));
    debug_log("Date: " . var_export($appointmentDate, true) . ", Time: " . var_export($appointmentTime, true));
    debug_log("Status: " . var_export($status, true));
    
    if (empty($emailConfig)) {
        throw new Exception("Email configuration is not loaded");
    }
    
    // Validate email address
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid recipient email address: " . $toEmail);
    }
    
    try {
        // Create a new PHPMailer instance with exceptions enabled
        $mail = new PHPMailer(true);
        
        // Enable verbose debug output
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            $log = "PHPMailer ($level): $str";
            error_log($log);
            debug_log($log);
        };
        
        debug_log("PHPMailer instance created");
        
        // Enable verbose debug output
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer ($level): $str");
        };
        
        // Server settings
        $mail->isSMTP();
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
        
        error_log("SMTP configured - Host: {$emailConfig['host']}, Port: {$emailConfig['port']}");
        
        // Disable SSL verification (same as working test)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Enable debugging
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer ($level): $str");
        };
        
        // Recipients
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($toEmail);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Appointment Status Update: " . ucfirst($status);
        
        // Determine status text and colors
        $statusText = $status;
        $statusColor = '#4CAF50'; // Default to confirmed color
        $statusIcon = '✅';
        
        if ($status === 'cancelled') {
            $statusText = 'cancelled';
            $statusColor = '#f44336';
            $statusIcon = '❌';
        } else if ($status === 'confirmed') {
            $statusText = 'confirmed';
            $statusColor = '#4CAF50';
            $statusIcon = '✅';
        } else if ($status === 'pending') {
            $statusText = 'pending';
            $statusColor = '#FFA500';
            $statusIcon = '⏳';
        } else if ($status === 'completed') {
            $statusText = 'completed';
            $statusColor = '#2196F3';
            $statusIcon = '✅';
        }
        
        $mail->Body = "<div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
            <h2 style='color: #333; margin-bottom: 20px;'>Appointment Status Update</h2>
            <div style='background: {$statusColor}; color: white; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px;'>
                <h3 style='margin: 0; font-size: 24px;'>{$statusIcon} Your Appointment Has Been {$statusText}</h3>
            </div>
            
            <div style='background: #f5f5f5; padding: 20px; border-radius: 5px;'>
                <h3 style='color: #333;'>Appointment Details</h3>
                <p><strong>Consultant:</strong> {$consultantName}</p>
                <p><strong>Date:</strong> {$appointmentDate}</p>
                <p><strong>Time:</strong> {$appointmentTime}</p>
            </div>
            
            <p style='margin-top: 20px; color: #666;'>Thank you for choosing our services.</p>
            <p style='color: #666;'>Best regards,</p>
            <p style='color: #666; font-weight: bold;'>Consultant Appointment System Team</p>
        </div>";
        
        $mail->AltBody = "Appointment Status Update\n\n" .
                        "Your appointment with {$consultantName} on {$appointmentDate} at {$appointmentTime} has been {$statusText}.\n\n" .
                        "Thank you for choosing our services.\n\n" .
                        "Best regards,\nConsultant Appointment System Team";
        
        // Send the email with detailed logging
        debug_log("\n=== Preparing to send email ===");
        debug_log("To: " . $toEmail);
        debug_log("Subject: " . $mail->Subject);
        debug_log("SMTP Debug: " . ($mail->SMTPDebug ? 'Enabled' : 'Disabled'));
        
        try {
            debug_log("Calling send() method...");
            $result = $mail->send();
            $errorInfo = $mail->ErrorInfo;
            
            debug_log("send() method completed. Result: " . ($result ? 'true' : 'false'));
            debug_log("Error Info: " . $errorInfo);
            
            if ($result) {
                debug_log("Email sent successfully to: " . $toEmail);
                return true;
            } else {
                debug_log("Failed to send email to: " . $toEmail);
                debug_log("PHPMailer Error: " . $errorInfo);
                
                // Log additional SMTP debug info
                if (isset($mail->SMTPDebug) && $mail->SMTPDebug) {
                    debug_log("SMTP Debug Info: " . print_r($mail->getSMTPInstance()->getError(), true));
                }
                
                return false;
            }
        } catch (Exception $e) {
            $errorMsg = "Exception during email sending: " . $e->getMessage();
            debug_log($errorMsg);
            
            if (isset($mail)) {
                debug_log("PHPMailer ErrorInfo: " . $mail->ErrorInfo);
            }
            
            debug_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
        
    } catch (Exception $e) {
        $error = "Email sending failed: " . $e->getMessage();
        error_log($error);
        
        // Only try to access ErrorInfo if $mail is an object and has the ErrorInfo property
        if (isset($mail) && is_object($mail) && property_exists($mail, 'ErrorInfo')) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
        } else {
            error_log("PHPMailer Error: Could not retrieve error details");
        }
        
        return false;
    }
}

// Log the start of the script
error_log("=== START update_appointment_status.php ===");
error_log("GET params: " . print_r($_GET, true));

// Get appointment ID and status from URL
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

error_log("Processing appointment ID: $appointment_id, Status: $status");

// Debug log
error_log("==================================================");
error_log("Starting appointment status update");
error_log("Appointment ID: $appointment_id");
error_log("New status: $status");
error_log("Script location: " . __FILE__);

// Validate status
$valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
if (!in_array($status, $valid_statuses)) {
    error_log("Invalid status: $status");
    header("Location: ../admin/manage_appointments.php?error=Invalid status");
    exit;
}

if ($appointment_id <= 0) {
    error_log("Invalid appointment ID: $appointment_id");
    header("Location: ../admin/manage_appointments.php?error=Invalid appointment ID");
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get the availability_id for this appointment
    $get_availability_sql = "SELECT availability_id, status FROM appointments WHERE id = ?";
    $stmt = mysqli_prepare($conn, $get_availability_sql);
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $appointment = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$appointment) {
        throw new Exception("Appointment not found");
    }

    error_log("Current appointment status: " . $appointment['status']);
    $availability_id = $appointment['availability_id'];

    // Update appointment status
    $update_appointment_sql = "UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_appointment_sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $appointment_id);
    $update_result = mysqli_stmt_execute($stmt);
    
    if (!$update_result) {
        error_log("Failed to update appointment status: " . mysqli_error($conn));
        throw new Exception("Failed to update appointment status");
    }
    
    error_log("Appointment status updated successfully");
    
    // Send email for both confirmed and cancelled statuses
    if ($status === 'confirmed' || $status === 'cancelled') {
        error_log("Starting email sending process for status: " . $status);
        error_log("Appointment ID: " . $appointment_id);
        error_log("Status: " . $status);
        error_log("Availability ID: " . $availability_id);
        
        // Get user and appointment details
        $get_details_sql = "SELECT u.email, cu.name as consultant_name, av.slot_date as date, av.slot_time as time 
                           FROM appointments a 
                           JOIN users u ON a.user_id = u.id 
                           JOIN availability av ON a.availability_id = av.id 
                           JOIN consultants c ON av.consultant_id = c.id 
                           JOIN users cu ON c.user_id = cu.id 
                           WHERE a.id = ?;";
        $stmt = mysqli_prepare($conn, $get_details_sql);
        
        if (!$stmt) {
            error_log("Failed to prepare SQL statement: " . mysqli_error($conn));
            throw new Exception("Failed to prepare SQL statement");
        }
        
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Failed to execute SQL statement: " . mysqli_error($conn));
            throw new Exception("Failed to execute SQL statement");
        }
        
        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            error_log("Failed to get result: " . mysqli_error($conn));
            throw new Exception("Failed to get result");
        }
        
        $details = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$details) {
            error_log("No appointment details found for ID: " . $appointment_id);
            throw new Exception("No appointment details found");
        }
        
        // Log the fetched details
        error_log("Fetched appointment details: " . print_r($details, true));
        
        // Verify required fields are present
        $required_fields = ['email', 'consultant_name', 'date', 'time'];
        foreach ($required_fields as $field) {
            if (empty($details[$field])) {
                error_log("Missing required field in appointment details: " . $field);
            }
        }
        
        error_log("Appointment details found:");
        error_log("User email: " . $details['email']);
        error_log("Consultant name: " . $details['consultant_name']);
        error_log("Appointment date: " . $details['date']);
        error_log("Appointment time: " . $details['time']);
        
        try {
            error_log("Attempting to send email to: " . $details['email']);
            // Send email
            // Format time as 12-hour for email
            $formatted_time = date('h:i A', strtotime($details['time']));
            $emailSent = sendAppointmentConfirmation(
                $details['email'],
                $details['consultant_name'],
                $details['date'],
                $formatted_time,
                $status
            );
            
            if ($emailSent) {
                error_log("Successfully sent email to: " . $details['email']);
            } else {
                error_log("Failed to send email to: " . $details['email']);
                // Log the last error
                error_log("Last error: " . print_r(error_get_last(), true));
                
                // Try to get PHPMailer error if available
                if (isset($mail) && is_object($mail) && method_exists($mail, 'ErrorInfo')) {
                    error_log("PHPMailer Error: " . $mail->ErrorInfo);
                }
            }
        } catch (Exception $e) {
            error_log("Exception while sending email: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
    }

    // Update availability status based on appointment status
    if ($status === 'confirmed') {
        $update_availability_sql = "UPDATE availability SET is_booked = 1 WHERE id = ?";
    } else if ($status === 'cancelled') {
        $update_availability_sql = "UPDATE availability SET is_booked = 0 WHERE id = ?";
    }

    if (isset($update_availability_sql)) {
        $stmt = mysqli_prepare($conn, $update_availability_sql);
        mysqli_stmt_bind_param($stmt, "i", $availability_id);
        $update_result = mysqli_stmt_execute($stmt);
        
        if (!$update_result) {
            error_log("Failed to update availability status: " . mysqli_error($conn));
            throw new Exception("Failed to update availability status");
        }
        
        error_log("Availability status updated successfully");
        mysqli_stmt_close($stmt);
    } // Close if (isset($update_availability_sql))

    // Commit transaction
    mysqli_commit($conn);
    error_log("Transaction committed successfully");
    header("Location: ../admin/manage_appointments.php?success=Appointment status updated successfully");
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    error_log("Error updating appointment: " . $e->getMessage());
    header("Location: ../admin/manage_appointments.php?error=Failed to update appointment status: " . $e->getMessage());
} // Close try-catch block

mysqli_close($conn); // Close database connection 