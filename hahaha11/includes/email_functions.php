<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($email, $name, $token) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'vaniverana@gmail.com';
        $mail->Password = 'mcua nvsb amob aqto'; // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Disable SSL certificate verification for local development
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Enable verbose debug output
        $mail->SMTPDebug = 0; // 0 = off (for production), 2 = client messages
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer: $str");
        };
        
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom('vaniverana@gmail.com', 'Consultant Booking');
        $mail->addAddress($email, $name);

        // Content - Using HTTP for local development to avoid SSL certificate issues
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
        $verificationLink = $protocol . '://' . $_SERVER['HTTP_HOST'] . "/hahaha11/actions/verify_email.php?token=" . $token;
        
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email Address';
        $mail->Body = "
            <h2>Email Verification</h2>
            <p>Hello " . htmlspecialchars($name) . ",</p>
            <p>Thank you for registering. Please click the button below to verify your email address:</p>
            <p><a href=\"$verificationLink\" style=\"background-color: #4CAF50; color: white; padding: 10px 20px; text-align: center; text-decoration: none; display: inline-block; border-radius: 4px;\">Verify Email</a></p>
            <p>Or copy and paste this link into your browser:</p>
            <p>$verificationLink</p>
            <p>This link will expire in 24 hours.</p>
            <p>If you didn't create an account, you can safely ignore this email.</p>
        ";
        
        $mail->AltBody = "Hello " . $name . ",\n\nThank you for registering. Please visit the following link to verify your email address:\n" . $verificationLink . "\n\nThis link will expire in 24 hours.\n\nIf you didn't create an account, you can safely ignore this email.";

        $mail->send();
        error_log("Verification email sent successfully to: $email");
        return true;
    } catch (Exception $e) {
        $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        error_log($error);
        // For debugging, you can uncomment the next line to see the full error
        // throw new Exception($error);
        return false;
    }
}
?>
