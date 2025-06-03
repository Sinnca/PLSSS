<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load PHPMailer
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load email config
$config = require __DIR__ . '/config/email_config.php';

// Process form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $toEmail = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config['port'];
            $mail->Timeout = 30;
            
            // Disable SSL verification (for testing only)
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
            $mail->Subject = 'Test Email from Quick Test Script';
            $mail->Body = '<h1>Test Email</h1><p>This is a test email sent from the quick test script.</p>';
            $mail->AltBody = 'This is a test email sent from the quick test script.';
            
            $mail->send();
            $message = "Test email sent successfully to $toEmail";
        } catch (Exception $e) {
            $error = "Error sending email: " . $e->getMessage();
        }
    } else {
        $error = "Invalid email address";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Email Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="email"] { padding: 8px; width: 300px; }
        button { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .success { color: green; margin: 10px 0; padding: 10px; background: #e8f5e9; }
        .error { color: red; margin: 10px 0; padding: 10px; background: #ffebee; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Quick Email Test</h1>
        
        <?php if ($message): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit">Send Test Email</button>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
            <h3>SMTP Configuration:</h3>
            <p><strong>SMTP Server:</strong> <?php echo htmlspecialchars($config['host']); ?></p>
            <p><strong>Port:</strong> <?php echo htmlspecialchars($config['port']); ?></p>
            <p><strong>Encryption:</strong> <?php echo htmlspecialchars($config['smtp_secure']); ?></p>
            <p><strong>From Email:</strong> <?php echo htmlspecialchars($config['from_email']); ?></p>
        </div>
    </div>
</body>
</html>