<?php
// Simple test file to debug email sending
session_start();

// Log that this script was accessed
file_put_contents(__DIR__ . '/../logs/email_test.log', date('Y-m-d H:i:s') . " - Test script was accessed\n", FILE_APPEND);

// Set content type to JSON for API responses
header('Content-Type: application/json');

// Include the email configuration
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load email configuration
$emailConfig = require __DIR__ . '/../config/email_config.php';

// Simple test to send email
try {
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        file_put_contents(__DIR__ . '/../logs/email_test.log', date('Y-m-d H:i:s') . " - [DEBUG] $str\n", FILE_APPEND);
    };
    
    $mail->isSMTP();
    $mail->Host = $emailConfig['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $emailConfig['username'];
    $mail->Password = $emailConfig['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $emailConfig['port'];
    
    // Recipients
    $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
    $mail->addAddress('ryzenshimura@gmail.com'); // Add a static test email
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from Consultant Interface';
    $mail->Body = 'This is a test email to verify SMTP connection works.';
    
    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Test email sent successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $mail->ErrorInfo]);
    file_put_contents(__DIR__ . '/../logs/email_test.log', date('Y-m-d H:i:s') . " - [ERROR] " . $e->getMessage() . "\n", FILE_APPEND);
}
?>
