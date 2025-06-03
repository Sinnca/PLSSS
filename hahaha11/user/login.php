<?php
// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    switch ($_SESSION['user_role']) {
        case 'admin':
            header("Location: ../admin/dashboard.php");
            break;
        case 'consultant':
            header("Location: ../consultant/dashboard.php");
            break;
        case 'user':
            header("Location: dashboard.php");
            break;
    }
    exit;
}

// Include database connection
require_once '../config/db.php';

// Display login errors if any
$errors = isset($_SESSION['login_errors']) ? $_SESSION['login_errors'] : [];
$email = isset($_SESSION['login_email']) ? $_SESSION['login_email'] : '';

// Clear session variables
unset($_SESSION['login_errors']);
unset($_SESSION['login_email']);
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
            --success-color: #10b981;
            --border-color: #e2e8f0;
            --input-bg: #f1f5f9;
            --input-focus: #e0f2fe;
            --gradient-start: #2563eb;
            --gradient-end: #1d4ed8;
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
        
        .wave-top::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            animation: wave 8s linear infinite;
        }
        
        .wave-bottom {
            height: 150px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            border-radius: 50% 50% 0 0 / 100px 100px 0 0;
            width: 100%;
            margin-top: auto;
            position: relative;
            overflow: hidden;
        }
        
        .wave-bottom::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            animation: wave 8s linear infinite;
        }
        
        @keyframes wave {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .content-area {
            display: flex;
            justify-content: center;
            flex: 1;
            padding: 20px;
            margin-top: -50px;
            margin-bottom: -50px;
            z-index: 10;
        }
        
        .form-container {
            background-color: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            margin: auto 0;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInUp 0.6s ease-out;
        }
        
        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            animation: gradientFlow 3s ease infinite;
        }
        
        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            animation: fadeInUp 0.8s ease-out;
        }
        
        h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        h1:hover::after {
            width: 100px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
            animation: fadeInUp 0.8s ease-out;
            animation-fill-mode: both;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: var(--input-bg);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
            background-color: var(--input-focus);
            transform: translateY(-2px);
        }
        
        .form-group input:focus + label {
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .btn {
            display: block;
            width: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: var(--white);
            padding: 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
            animation: fadeInUp 0.8s ease-out;
            animation-delay: 0.3s;
            animation-fill-mode: both;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }
        
        .btn:hover::after {
            transform: translateX(100%);
        }
        
        .error-container {
            background-color: var(--error-bg);
            color: var(--error-text);
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--error-text);
            animation: shake 0.5s ease-in-out;
            box-shadow: 0 2px 10px rgba(185, 28, 28, 0.1);
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .error-list {
            margin: 0;
            padding-left: 20px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            animation: fadeInUp 0.8s ease-out;
            animation-delay: 0.2s;
            animation-fill-mode: both;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .remember-me input[type="checkbox"]:hover {
            transform: scale(1.1);
        }
        
        .remember-me label {
            cursor: pointer;
            user-select: none;
            transition: color 0.3s ease;
        }
        
        .remember-me label:hover {
            color: var(--primary-color);
        }
        
        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            position: relative;
            animation: fadeInUp 0.8s ease-out;
            animation-delay: 0.2s;
            animation-fill-mode: both;
        }
        
        .forgot-password::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
        }
        
        .forgot-password:hover::after {
            width: 100%;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: var(--light-text);
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            animation: fadeInUp 0.8s ease-out;
            animation-delay: 0.4s;
            animation-fill-mode: both;
        }
        
        .register-link button {
            background: none;
            border: none;
            color: var(--primary-color);
            font-weight: 500;
            cursor: pointer;
            padding: 0;
            text-decoration: none;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .register-link button::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
        }
        
        .register-link button:hover::after {
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 30px 20px;
                margin: 20px;
                border-radius: 12px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .wave-top,
            .wave-bottom {
                height: 100px;
            }
            
            .form-group input {
                padding: 10px 12px;
            }
            
            .btn {
                padding: 12px;
            }
        }

        .navbar {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            box-shadow: 0 2px 15px rgba(37, 99, 235, 0.2);
            padding: 12px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            color: var(--white) !important;
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
        }

        .nav-link {
            color: var(--white) !important;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--white) !important;
            transform: translateY(-2px);
        }

        .navbar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white) !important;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
        }

        .dropdown-item {
            padding: 0.7rem 1.5rem;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: var(--background);
            color: var(--primary-color);
        }

        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: #e5e7eb;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            line-height: 1.5;
            animation: fadeIn 0.3s ease-out;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fa-solid fa-calendar-check me-2"></i>
                Appointment System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fa-solid fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>" href="services.php">
                            <i class="fa-solid fa-list-check me-1"></i> Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'consultants.php' ? 'active' : ''; ?>" href="consultants.php">
                            <i class="fa-solid fa-user-tie me-1"></i> Consultants
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>" href="about.php">
                            <i class="fa-solid fa-info-circle me-1"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>" href="contact.php">
                            <i class="fa-solid fa-envelope me-1"></i> Contact
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>" href="login.php">
                            <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>" href="register.php">
                            <i class="fa-solid fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-container">
        <div class="wave-top"></div>
        
        <div class="content-area">
            <div class="form-container">
                <h1>Welcome Back!</h1>
                
                <?php
                // Display verification success message if exists
                if (isset($_SESSION['verification_success'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['verification_success']) . '</div>';
                    unset($_SESSION['verification_success']);
                }
                
                // Display verification error if exists
                if (isset($_SESSION['verification_error'])) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['verification_error']) . '</div>';
                    unset($_SESSION['verification_error']);
                }
                
                // Display registration success message if exists
                if (isset($_SESSION['registration_success'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['registration_success']) . '</div>';
                    unset($_SESSION['registration_success']);
                }
                
                // Display errors if any
                if (!empty($errors)) {
                    echo '<div class="error-container">';
                    echo '<ul class="error-list">';
                    foreach ($errors as $error) {
                        echo '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                
                // Display error from URL parameter
                if (isset($_GET['error'])) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
                }
                ?>
                
                <form action="../actions/login.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <div class="flex-row">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">Sign In</button>
                    </div>
                </form>
                
                <div class="register-link">
                    Don't have an account? 
                    <form action="register.php" method="GET" style="display: inline;">
                        <button type="submit" style="background: none; border: none; color: var(--primary-color); font-weight: 500; cursor: pointer; padding: 0; text-decoration: none;">Sign up</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="wave-bottom"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>