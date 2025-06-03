<?php
// Start session
session_start();

// Include database connection
require_once '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Initialize variables
$email = '';
$message = '';
$message_type = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND role = 'user'");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Set timezone and generate token
            date_default_timezone_set('Asia/Manila'); // Set to your timezone
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            error_log("=== TOKEN GENERATION ===");
            error_log("Generated token: " . $token);
            error_log("Expires at: " . $expires);
            error_log("For user ID: " . $user['id'] . ", Email: " . $user['email']);
            
            // Store token in database with debugging info
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            if ($stmt === false) {
                error_log("Prepare failed: " . $conn->error);
            }
            
            $bind_result = $stmt->bind_param('ssi', $token, $expires, $user['id']);
            if ($bind_result === false) {
                error_log("Bind failed: " . $stmt->error);
            }
            
            if ($stmt->execute()) {
                // Send reset email
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/hahaha11/user/reset_password.php?token=" . $token;
                $subject = "Password Reset Request";
                $message = "
                    <html>
                    <head>
                        <title>Password Reset</title>
                    </head>
                    <body>
                        <h2>Password Reset Request</h2>
                        <p>Hello " . htmlspecialchars($user['name']) . ",</p>
                        <p>You have requested to reset your password. Click the link below to set a new password:</p>
                        <p><a href='" . $reset_link . "'>Reset Password</a></p>
                        <p>This link will expire in 1 hour.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                    </body>
                    </html>
                ";
                
                // Set content-type header for sending HTML email
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: <noreply@example.com>' . "\r\n";
                
                // Send email using PHPMailer
                require_once '../vendor/autoload.php';
                
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                try {
                    // Enable verbose debug output
                    $mail->SMTPDebug = 2; // Enable verbose debug output
                    $mail->Debugoutput = function($str, $level) {
                        error_log("PHPMailer: $str");
                    };
                    
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'vaniverana@gmail.com';
                    $mail->Password = 'mcua nvsb amob aqto';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];
                    
                    // Recipients
                    $mail->setFrom('vaniverana@gmail.com', 'Appointment System');
                    $mail->addAddress($email, $user['name']);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $message;
                    
                    $mail->send();
                    $message = 'Password reset link has been sent to your email address.';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = 'Failed to send reset email. Error: ' . $mail->ErrorInfo;
                    $message_type = 'error';
                }
            } else {
                $message = 'An error occurred. Please try again later.';
                $message_type = 'error';
            }
        } else {
            // For security, don't reveal if email exists
            $message = 'If your email exists in our system, you will receive a password reset link.';
            $message_type = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Appointment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --text-color: #1e293b;
            --light-text: #64748b;
            --background: #f8fafc;
            --white: #ffffff;
            --error-bg: #fee2e2;
            --error-text: #b91c1c;
            --success-color: #10b981;
            --border-color: #e2e8f0;
            --input-bg: #f1f5f9;
            --input-focus: #e0f2fe;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: var(--text-color);
            min-height: 100vh;
            margin: 0;
        }
        
        .page-container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            padding-top: 72px;
        }
        
        .wave-top {
            height: 150px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border-radius: 0 0 50% 50% / 0 0 100px 100px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .content-area {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
            position: relative;
            z-index: 1;
        }
        
        .form-container {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        }
        
        h1 {
            color: var(--text-color);
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 2px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: var(--input-bg);
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background-color: var(--white);
        }
        
        .btn {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--light-text);
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }
        
        .message.error {
            background-color: var(--error-bg);
            color: var(--error-text);
            border-left: 4px solid var(--error-text);
        }
        
        .message.success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .wave-bottom {
            height: 150px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            border-radius: 50% 50% 0 0 / 100px 100px 0 0;
            width: 100%;
            position: relative;
            margin-top: auto;
        }
        
        @media (max-width: 576px) {
            .form-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); position: fixed; top: 0; width: 100%; z-index: 1000;">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fa-solid fa-calendar-check me-2"></i>
                Appointment System
            </a>
        </div>
    </nav>

    <div class="page-container">
        <div class="wave-top"></div>
        
        <div class="content-area">
            <div class="form-container">
                <h1>Reset Your Password</h1>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form action="forgot_password.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email address" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">Send Reset Link</button>
                    </div>
                </form>
                
                <div class="login-link">
                    Remember your password? <a href="login.php">Sign in</a>
                </div>
            </div>
        </div>
        
        <div class="wave-bottom"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>