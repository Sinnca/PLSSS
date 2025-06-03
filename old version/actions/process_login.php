<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);
    $user_type = isset($_POST['user_type']) ? $_POST['user_type'] : 'user';
    
    // Validate input
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "Please enter your email.";
    }
    
    if (empty($password)) {
        $errors[] = "Please enter your password.";
    }
    
    // If no errors, proceed with login
    if (empty($errors)) {
        // Query depends on user type
        if ($user_type == 'admin') {
            $sql = "SELECT * FROM users WHERE email = '$email' AND role = 'admin'";
        } elseif ($user_type == 'consultant') {
            $sql = "SELECT u.*, c.id as consultant_id FROM users u 
                    JOIN consultants c ON u.id = c.user_id 
                    WHERE u.email = '$email' AND u.role = 'consultant'";
        } else {
            $sql = "SELECT * FROM users WHERE email = '$email' AND role = 'user'";
        }
        
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Store user data in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                
                if ($user_type == 'consultant') {
                    $_SESSION['consultant_id'] = $user['consultant_id'];
                }
                
                // Redirect based on user type
                if ($user_type == 'admin') {
                    header("Location: /hahaha11/admin/dashboard.php");
                } elseif ($user_type == 'consultant') {
                    header("Location: /hahaha11/consultant/dashboard.php");
                } else {
                    header("Location: /hahaha11/index.php");
                }
                exit;
            } else {         
                $errors[] = "Invalid email or password.";
            }
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
    
    // If there were errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        $_SESSION['login_email'] = $email;
        
        if ($user_type == 'admin') {
            header("Location: /hahaha11/admin/dashboard.php");
        } elseif ($user_type == 'consultant') {
            header("Location: /hahaha11/consultant/login.php");
        } else {
            header("Location: /hahaha11/user/login.php");
        }
        exit;
    }
} else {
    // If not a POST request, redirect to login page
    header("Location: /hahaha11/user/login.php");
    exit;
}

if (isset($_POST['admin_login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Check password and admin role
    if ($user && password_verify($password, $user['password']) && ($user['role'] == 'admin' || $user['is_admin'] == 1)) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_email'] = $user['email'];
        header('Location: dashboard.php');
        exit;
    } else {
        header('Location: login.php?error=Invalid credentials or not an admin');
        exit;
    }
}
?>