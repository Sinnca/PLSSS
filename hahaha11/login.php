<?php
// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    switch ($_SESSION['user_role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'consultant':
            header("Location: consultant/dashboard.php");
            break;
        case 'user':
            header("Location: index.php");
            break;
    }
    exit;
}

// Include database connection
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Appointment System</title>
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
        }

        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            background-color: var(--background);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .login-card {
            background: var(--white);
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: var(--text-color);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--light-text);
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-login {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 500;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-login:hover {
            background: var(--primary-dark);
        }

        .error-message {
            background: var(--error-bg);
            color: var(--error-text);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--light-text);
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Please login to your account</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="actions/login.php" method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <button type="submit" class="btn-login">Login</button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="user/register.php">Register here</a>
            </div>
        </div>
    </div>
</body>
</html> 