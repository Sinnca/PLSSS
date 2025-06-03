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
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$message = '';
$message_type = '';
$valid_token = false;
$email = '';

// Set timezone for consistent time handling
date_default_timezone_set('Asia/Manila'); // Set to your timezone

// For debugging
$current_time = date('Y-m-d H:i:s');
error_log("Current time: " . $current_time);

// Validate token
if (!empty($token)) {
    // First check if token exists and get expiration time
    $stmt = $conn->prepare("SELECT id, email, reset_expires, reset_token FROM users WHERE reset_token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        error_log("Token found. Expires at: " . $user['reset_expires']);
        error_log("Token value: " . $user['reset_token']);
        
        // Check if token is still valid
        if (strtotime($user['reset_expires']) > time()) {
            $valid_token = true;
            $email = $user['email'];
            error_log("Token is valid");
        } else {
            $message = 'This reset link has expired. Please request a new one.';
            $message_type = 'error';
            error_log("Token expired. Current time: " . $current_time . ", Expiry: " . $user['reset_expires']);
        }
    } else {
        $message = 'Invalid reset link. Please request a new one.';
        $message_type = 'error';
        error_log("Token not found in database");
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate password
    if (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear reset token
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        $stmt->bind_param('ss', $hashed_password, $token);
        
        if ($stmt->execute()) {
            $message = 'Your password has been reset successfully. You can now login with your new password.';
            $message_type = 'success';
            $valid_token = false; // Prevent showing the form again
        } else {
            $message = 'An error occurred while resetting your password. Please try again.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Appointment System</title>
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
        
        input[type="password"] {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: var(--input-bg);
        }
        
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
                
                <?php if ($valid_token || $message_type === 'success'): ?>
                    <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your new password" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn">Reset Password</button>
                        </div>
                    </form>
                <?php endif; ?>
                
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