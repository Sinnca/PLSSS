<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load PHPMailer manually
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load email config
$config = require __DIR__ . '/config/email_config.php';

// Create a test email function
function sendTestEmail($toEmail) {
    global $config;
    
    try {
        $mail = new PHPMailer(true);
        
        // Enable verbose debug output
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer ($level): $str");
            echo "<p>PHPMailer ($level): $str</p>";
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['port'];
        
        // Set timeouts
        $mail->Timeout = 30;
        
        // Disable SSL certificate verification (for testing only)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($toEmail);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from Consultancy System';
        $mail->Body = '<h1>Test Email</h1><p>This is a test email from the Consultancy Appointment System.</p>';
        $mail->AltBody = 'This is a test email from the Consultancy Appointment System.';
        
        // Send the email
        echo "<h2>Sending test email to: $toEmail</h2>";
        $result = $mail->send();
        
        if ($result) {
            echo "<p style='color: green;'>Email sent successfully!</p>";
        } else {
            echo "<p style='color: red;'>Failed to send email.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        if (isset($mail)) {
            echo "<p>PHPMailer Error: " . $mail->ErrorInfo . "</p>";
        }
    }
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $toEmail = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        sendTestEmail($toEmail);
    } else {
        echo "<p style='color: red;'>Invalid email address</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Email Sending</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="email"] { padding: 8px; width: 300px; }
        button { padding: 8px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Email Sending</h1>
        <form method="post" action="">
            <div class="form-group">
                <label for="email">Recipient Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit">Send Test Email</button>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background-color: #f5f5f5; border-radius: 5px;">
            <h3>Debug Information:</h3>
            <p>SMTP Server: <?php echo htmlspecialchars($config['host']); ?></p>
            <p>Port: <?php echo htmlspecialchars($config['port']); ?></p>
            <p>Encryption: <?php echo htmlspecialchars($config['smtp_secure']); ?></p>
            <p>From Email: <?php echo htmlspecialchars($config['from_email']); ?></p>
        </div>
    </div>
</body>
</html>
