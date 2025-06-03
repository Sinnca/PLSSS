<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set your project base path here
$base_path = '/hahaha11';

// Function to check if user is logged in
function checkUserLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /hahaha11/user/login.php?error=Please login to access this page");
        exit;
    }
}

// Function to check if user is admin
function checkAdminLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: /hahaha11/user/login.php?error=Please login as admin to access this page");
        exit;
    }
}

// Function to check if user is consultant
function checkConsultantLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'consultant') {
        header("Location: /hahaha11/user/login.php?error=Please login as consultant to access this page");
        exit;
    }
}

// Function to check if user is regular user
function checkRegularUserLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
        header("Location: /hahaha11/user/login.php?error=Please login as user to access this page");
        exit;
    }
}

// Function to get user role
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Function to check if user has specific role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current user name
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? null;
}

// Function to get current user email
function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

// Function to get consultant ID if user is consultant
function getConsultantId() {
    return $_SESSION['consultant_id'] ?? null;
}

/**
 * Send an email using PHPMailer
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML supported)
 * @param string $altBody Plain text version (optional)
 * @return bool True on success, false on failure
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../config/email.php'; // This should contain your SMTP settings
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Set email format to HTML
        $mail->isHTML(true);
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = !empty($altBody) ? $altBody : strip_tags($body);
        
        // Send the email
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to send a simple HTML email (fallback if PHPMailer is not available)
function sendSimpleEmail($to, $subject, $body, $altBody = '') {
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $headers[] = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';
    
    $message = '<!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>' . $subject . '</title>
    </head>
    <body>
        ' . $body . '
    </body>
    </html>';
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}
?>
