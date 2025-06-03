<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
checkUserLogin();

// Get user information from database
$user_id = $_SESSION['user_id'];
$sql = "SELECT name, email FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Set user information
$user_name = $user['name'] ?? '';
$user_email = $user['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    // Validate inputs
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $_SESSION['error'] = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
    } else {
        // Insert message into database with user_id
        $sql = "INSERT INTO contact_messages (user_id, name, email, subject, message, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issss", $user_id, $name, $email, $subject, $message);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Message sent successfully!";
        } else {    
            $_SESSION['error'] = "Error sending message: " . mysqli_error($conn);       
        }
    }
    
    header("Location: contact.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --text-color: #1e293b;
            --light-text: #64748b;
            --background: #f0f7ff;
            --white: #ffffff;
            --error-bg: #fee2e2;
            --error-text: #b91c1c;
            --success-bg: #dcfce7;
            --success-text: #166534;
            --gradient-start: #2563eb;
            --gradient-end: #1e40af;
            --border-color: #e5e7eb;
            --input-focus: rgba(37, 99, 235, 0.1);
        }

        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            background-color: var(--background);
            background-image: linear-gradient(135deg, #f0f7ff 0%, #e0f2fe 100%);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .page-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            z-index: 1;
        }
        
        .page-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5z' fill='%232563eb' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
            z-index: -1;
        }
        
        .contact-container {
            width: 100%;
            max-width: 850px;
            padding: 2rem;
            background: var(--white);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        .contact-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
        }

        .contact-header {
            text-align: center;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .contact-header h1 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 2rem;
            letter-spacing: -0.5px;
        }

        .contact-header p {
            color: var(--light-text);
            font-size: 1rem;
            margin-bottom: 0.5rem;
            max-width: 550px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.5;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.4rem;
            font-size: 0.95rem;
            display: block;
        }

        .form-control {
            border: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            height: auto;
            width: 100%;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px var(--input-focus);
            outline: none;
        }
        
        .form-control::placeholder {
            color: #a0aec0;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            border: none;
            padding: 0.9rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }
        
        .btn-primary:hover::before {
            transform: translateX(100%);
        }

        .alert {
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            border: none;
            font-size: 0.9rem;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .alert-danger {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .contact-info {
            background: linear-gradient(to right, #f0f7ff, #e6f0fd);
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            border: 1px solid rgba(37, 99, 235, 0.1);
        }

        .contact-info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .contact-info-item i {
            color: white;
            font-size: 1rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        }

        .contact-info-item strong {
            font-size: 0.85rem;
            display: block;
            margin-bottom: 0.2rem;
            color: var(--light-text);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .contact-info-item div:last-child {
            font-size: 1rem;
            color: var(--text-color);
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .contact-container {
                margin: 1rem;
                padding: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="page-wrapper">
        <div class="contact-container">
        <div class="contact-header">
            <h1>Contact Us</h1>
            <p>We're here to help! Send us a message and we'll get back to you as soon as possible.</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="contact-info">
            <div class="contact-info-item">
                <i class="fas fa-user"></i>
                <div>
                    <strong>Your Name:</strong>
                    <div><?php echo htmlspecialchars($user_name); ?></div>
                </div>
            </div>
            <div class="contact-info-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <strong>Your Email:</strong>
                    <div><?php echo htmlspecialchars($user_email); ?></div>
                </div>
            </div>
        </div>

        <form method="POST" action="">
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <input type="text" class="form-control" id="subject" name="subject" placeholder="What is your message about?" required>
            </div>
            <div class="mb-4">
                <label for="message" class="form-label">Message</label>
                <textarea class="form-control" id="message" name="message" rows="4" placeholder="Type your message here..." required></textarea>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Send Message
                </button>
            </div>
        </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
        