<?php
// Start session
session_start();

// Include database connection
require_once '../config/db.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Debug log
    error_log("Login attempt for email: " . $email);

    // Validate input
    if (empty($email) || empty($password)) {
        header("Location: ../user/login.php?error=Please fill in all fields");
        exit;
    }

    try {
        // Prepare SQL statement to get ALL accounts for this email
        $sql = "SELECT u.*, c.id as consultant_id 
                FROM users u 
                LEFT JOIN consultants c ON u.id = c.user_id 
                WHERE u.email = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            throw new Exception("Database error: " . mysqli_error($conn));
        }

        // Bind parameters
        mysqli_stmt_bind_param($stmt, "s", $email);
        
        // Execute statement
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error executing query: " . mysqli_stmt_error($stmt));
        }

        // Get result
        $result = mysqli_stmt_get_result($stmt);
        
        // Gather all accounts with this email
        $accounts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $accounts[] = $row;
        }
        if (count($accounts) === 0) {
            // No user found
            error_log("No user found with email: " . $email);
            header("Location: ../user/login.php?error=Invalid email or password");
            exit;
        }
        // Try to find a matching password
        $matched = null;
        foreach ($accounts as $account) {
            if (password_verify($password, $account['password'])) {
                if ($matched !== null) {
                    // Ambiguous: multiple accounts with same email and password
                    header("Location: ../user/login.php?error=Multiple accounts with this email and password. Please contact support.");
                    exit;
                }
                $matched = $account;
            }
        }
        if (!$matched) {
            // No matching password
            error_log("Password verification failed for user: " . $email);
            header("Location: ../user/login.php?error=Invalid email or password");
            exit;
        }
        $user = $matched;
        
        // Check if email is verified (only for regular users)
        if ($user['role'] === 'user' && empty($user['is_verified'])) {
            // Generate new verification token
            $verification_token = bin2hex(random_bytes(32));
            $verification_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Update user with new token
            $updateSql = "UPDATE users SET verification_token = ?, verification_token_expires_at = ? WHERE id = ?";
            $updateStmt = mysqli_prepare($conn, $updateSql);
            mysqli_stmt_bind_param($updateStmt, "ssi", $verification_token, $verification_expiry, $user['id']);
            
            if (mysqli_stmt_execute($updateStmt)) {
                // Send new verification email
                require_once __DIR__ . '/../includes/email_functions.php';
                if (sendVerificationEmail($user['email'], $user['name'], $verification_token)) {
                    header("Location: ../user/login.php?error=Please verify your email address before logging in. A new verification link has been sent to your email.");
                } else {
                    header("Location: ../user/login.php?error=Failed to send verification email. Please try again later.");
                }
            } else {
                header("Location: ../user/login.php?error=An error occurred. Please try again later.");
            }
            
            mysqli_stmt_close($updateStmt);
            exit;
        }
        // Debug log
        error_log("User found: " . print_r($user, true));

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        // If user is a consultant, set consultant_id
        if ($user['role'] === 'consultant' && isset($user['consultant_id'])) {
            $_SESSION['consultant_id'] = $user['consultant_id'];
        }

        // Redirect based on role
        switch ($user['role']) {
            case 'admin':
                header("Location: ../admin/dashboard.php");
                break;
            case 'consultant':
                header("Location: ../consultant/dashboard.php");
                break;
            case 'user':
                header("Location: ../user/index.php");
                break;
            default:
                throw new Exception("Invalid user role");
        }
        exit;

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: ../user/login.php?error=An error occurred during login");
        exit;
    }
} else {
    // Not a POST request
    header("Location: ../user/login.php");
    exit;
} 