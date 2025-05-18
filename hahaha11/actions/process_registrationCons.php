<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../config/db.php');

if (!$conn) {
    die('DB connection failed.');
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $specialty = isset($_POST['specialty']) ? mysqli_real_escape_string($conn, trim($_POST['specialty'])) : '';
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $terms = isset($_POST['terms']) ? true : false;
    
    // Default values for bio and hourly rate
    $bio = "New consultant";
    $hourly_rate = 0.00;
    
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
    
    if (empty($specialty)) {
        $errors[] = "Please select your specialty.";
    }
    
    if (!$terms) {
        $errors[] = "You must agree to the Terms of Service and Privacy Policy.";
    }
    
    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Email is already registered.";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert user with consultant role
            $sql = "INSERT INTO users (name, email, password, role, created_at) 
                    VALUES ('$name', '$email', '$hashed_password', 'consultant', NOW())";
            
            if (mysqli_query($conn, $sql)) {
                // Get the user ID
                $user_id = mysqli_insert_id($conn);
                
                // Insert consultant specific information based on your actual schema
                $sql = "INSERT INTO consultants (user_id, specialty, hourly_rate, bio, is_featured) 
                        VALUES ('$user_id', '$specialty', '$hourly_rate', '$bio', FALSE)";
                
                if (mysqli_query($conn, $sql)) {
                    // Commit transaction
                    mysqli_commit($conn);
                    
                    // Set success message
                    $_SESSION['success_message'] = "Registration successful! You can now log in to your consultant account.";
                    
                    // Redirect to login
                    header("Location: /hahaha11/user/login.php");
                    exit;
                } else {
                    // Roll back on failure
                    mysqli_rollback($conn);
                    $errors[] = "Registration failed: " . mysqli_error($conn);
                }
            } else {
                // Roll back on failure
                mysqli_rollback($conn);
                $errors[] = "Registration failed: " . mysqli_error($conn);
            }
        } catch (Exception $e) {
            // Roll back on exception
            mysqli_rollback($conn);
            $errors[] = "An error occurred: " . $e->getMessage();
        }
    }
    
    // If there were errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'specialty' => $specialty
        ];
        header("Location: register.php");
        exit;
    }
} else {
    // If not a POST request, redirect to registration page
    header("Location: register.php");
    exit;
}
?>