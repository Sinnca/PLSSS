<?php
require_once __DIR__ . '/config/email_config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require __DIR__ . '/config/email_config.php';

echo "<h2>Testing Email Configuration</h2>";
echo "<p>Attempting to send test email to: " . htmlspecialchars($config['from_email']) . "</p>";

try {
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->Port = $config['port'];
    $mail->SMTPDebug = 2; // Enable verbose debug output
    
    // Recipients
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['from_email']); // Sending to yourself for testing
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from Consultant System';
    $mail->Body = "<h2>Test Email</h2>
    <p>This is a test email sent from the Consultant Appointment System.</p>
    <p>If you're receiving this, the email configuration is working correctly!</p>";
    
    $mail->send();
    echo "<p style='color: green;'>✅ Test email sent successfully!</p>";
    echo "<p>Check your email inbox for the test message.</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Email sending failed. Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check the following:</p>";
    echo "<ul>";
    echo "<li>Verify your Gmail account has 2-Step Verification enabled</li>";
    echo "<li>Check that you're using an App Password (not your regular Gmail password)</li>";
    echo "<li>Ensure 'Less secure app access' is enabled in your Google Account settings</li>";
    echo "</ul>";
    echo "<p>Error details for debugging:</p>";
    echo "<pre>" . htmlspecialchars(print_r(error_get_last(), true)) . "</pre>";
}