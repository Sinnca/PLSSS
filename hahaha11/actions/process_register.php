<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($success, $message = '', $data = []) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    
    if (!$success && !empty($_SESSION['register_errors'])) {
        $response['errors'] = $_SESSION['register_errors'];
    }
    
    echo json_encode($response);
    exit;
}

// Log start of script
error_log('=== Starting registration process ===');

require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../includes/email_functions.php');

if (!$conn) {
    error_log('Database connection failed');
    sendJsonResponse(false, 'Database connection failed');
}

error_log('Database connected successfully');

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log('POST data received: ' . print_r($_POST, true));
    
    // Get form data
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, trim($_POST['name'])) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    // Log received data
    error_log("Processing registration for: $name <$email>");

    // Validate input
    $errors = [];

    if (empty($name)) {
        $errors[] = "Please enter your name.";
    }

    if (empty($email)) {
        $errors[] = "Please enter your email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($password)) {
        $errors[] = "Please enter a password.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password != $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Email is already registered.";
    }
    mysqli_stmt_close($stmt);

    // If no errors, proceed with registration
    if (empty($errors)) {
        error_log('No validation errors, proceeding with registration');
        // Generate verification token and expiry
        $verification_token = bin2hex(random_bytes(32));
        $verification_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user with verification token
        $sql = "INSERT INTO users (name, email, password, role, verification_token, verification_token_expires_at, created_at) 
                VALUES (?, ?, ?, 'user', ?, ?, NOW())";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("Prepare failed: " . mysqli_error($conn));
            sendJsonResponse(false, 'Database error. Please try again.');
        }
        
        mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hashed_password, $verification_token, $verification_expiry);

        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($conn);
            
            // Send verification email
            error_log('Sending verification email to: ' . $email);
            if (sendVerificationEmail($email, $name, $verification_token)) {
                error_log('Verification email sent successfully');
                
                // Clear any previous session data
                if (isset($_SESSION['register_errors'])) {
                    unset($_SESSION['register_errors']);
                }
                
                // Return success response with redirect URL
                $redirect_url = '/hahaha11/user/login.php?registered=1';
                sendJsonResponse(true, 'Registration successful! Please check your email to verify your account.', [
                    'redirect' => $redirect_url
                ]);
            } else {
                // If email sending fails, delete the user and show error
                $deleteSql = "DELETE FROM users WHERE id = ?";
                $deleteStmt = mysqli_prepare($conn, $deleteSql);
                if ($deleteStmt) {
                    mysqli_stmt_bind_param($deleteStmt, "i", $user_id);
                    mysqli_stmt_execute($deleteStmt);
                    mysqli_stmt_close($deleteStmt);
                }
                
                sendJsonResponse(false, 'Failed to send verification email. Please try again later.');
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Registration failed: " . mysqli_error($conn));
            sendJsonResponse(false, 'Registration failed. Please try again.');
        }
    }

    // If there were errors, return them as JSON
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_data'] = [
            'name' => $name,
            'email' => $email
        ];
        sendJsonResponse(false, 'Please fix the following errors:', ['errors' => $errors]);
    }
} else {
    // If not a POST request, redirect to registration page
    header("Location: ../user/register.php");
    exit;
}
?>