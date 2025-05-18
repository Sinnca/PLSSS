<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the user login check
require_once '../includes/auth.php';

// Database connection
require_once '../config/db.php';

// Get current user information
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;
$today = date('Y-m-d');

// Add user_id column to contact_messages table if it doesn't exist
$check_column = "SHOW COLUMNS FROM contact_messages LIKE 'user_id'";
$column_exists = mysqli_query($conn, $check_column);

if (mysqli_num_rows($column_exists) == 0) {
    $add_column = "ALTER TABLE contact_messages ADD COLUMN user_id INT NULL AFTER id";
    mysqli_query($conn, $add_column);
}

// Add is_replied column if it doesn't exist
$check_column = "SHOW COLUMNS FROM contact_messages LIKE 'is_replied'";
$column_exists = mysqli_query($conn, $check_column);

if (mysqli_num_rows($column_exists) == 0) {
    $add_column = "ALTER TABLE contact_messages ADD COLUMN is_replied BOOLEAN DEFAULT FALSE";
    mysqli_query($conn, $add_column);
}

// Add replied_at column if it doesn't exist
$check_column = "SHOW COLUMNS FROM contact_messages LIKE 'replied_at'";
$column_exists = mysqli_query($conn, $check_column);

if (mysqli_num_rows($column_exists) == 0) {
    $add_column = "ALTER TABLE contact_messages ADD COLUMN replied_at DATETIME NULL";
    mysqli_query($conn, $add_column);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3f37c9;
            --primary-gradient: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            --secondary-color: #4cc9f0;
            --text-color: #0b132b;
            --light-text: #4a5568;
            --background: #f5f7fa;
            --white: #ffffff;
            --error-bg: #fee2e2;
            --error-text: #b91c1c;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --panel-bg: #ffffff;
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --card-shadow: 0 7px 20px rgba(0, 0, 0, 0.06);
            --input-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            background-color: var(--background);
            color: var(--text-color);
            min-height: 100vh;
            margin: 0;
            font-size: 16px;
        }

        /* Navbar Styling */
        .navbar {
            background: var(--primary-gradient) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            padding: 14px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand {
            color: var(--white) !important;
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
        }
        
        .nav-link {
            color: var(--white) !important;
            font-weight: 500;
            transition: var(--transition);
            padding: 10px 16px;
            border-radius: 8px;
            margin: 0 4px;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        
        .navbar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .dropdown-menu {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
            padding: 12px;
            min-width: 200px;
            margin-top: 12px;
        }
        
        .dropdown-item {
            border-radius: 8px;
            padding: 10px 16px;
            transition: var(--transition);
            margin-bottom: 4px;
            font-weight: 500;
        }
        
        .dropdown-item:hover {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            transform: translateX(3px);
        }
        
        /* Container and Layout */
        .page-container {
            position: relative;
            min-height: calc(100vh - 76px);
            overflow: hidden;
            padding-bottom: 50px;
        }
        
        .dashboard-title {
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            margin-bottom: 36px;
            font-size: 2.2rem;
            text-align: center;
            padding-top: 40px;
            letter-spacing: -0.5px;
        }
        
        .wave-top {
            height: 130px;
            background: var(--primary-gradient);
            border-radius: 0 0 50% 50% / 0 0 120px 120px;
            width: 100%;
            margin-bottom: 40px;
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
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            border-radius: 0 0 50% 50% / 0 0 120px 120px;
        }
        
        .content-area {
            padding: 0 30px 60px;
            z-index: 10;
            position: relative;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Date Navigator */
        .date-navigator {
            background-color: var(--panel-bg);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--box-shadow);
            margin-bottom: 40px;
            border-top: 5px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .date-navigator:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }
        
        .input-group {
            flex-wrap: nowrap;
        }
        
        .input-group .form-control,
        .input-group .input-group-text,
        .input-group .btn {
            border-radius: 10px;
            padding: 12px 18px;
            height: auto;
            font-size: 1rem;
        }
        
        .input-group > :not(:first-child) {
            margin-left: 10px;
        }
        
        .input-group .form-control {
            border: 2px solid #e2e8f0;
            font-size: 1rem;
            transition: var(--transition);
            box-shadow: var(--input-shadow);
            background-color: rgba(255, 255, 255, 0.9);
        }
        
        .input-group .form-control:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.15);
            background-color: var(--white);
        }
        
        .input-group-text {
            background-color: #edf2f7;
            border: 2px solid #e2e8f0;
            color: var(--primary-dark);
            font-weight: 500;
        }
        
        /* Buttons */
        .btn {
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: var(--transition);
            text-transform: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            color: white;
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            border: none;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            background: transparent;
            box-shadow: none;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.25);
        }
        
        /* Appointment Cards */
        .appointment-card {
            background-color: var(--panel-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            border: none;
            overflow: hidden;
            transition: var(--transition);
            border-left: 5px solid var(--primary-color);
            position: relative;
        }
        
        .appointment-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, transparent 50%, rgba(67, 97, 238, 0.05) 50%);
            border-radius: 0 0 0 100px;
            z-index: 0;
        }
        
        .appointment-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }
        
        .appointment-card .card-body {
            padding: 30px;
            z-index: 10;
            position: relative;
        }
        
        .appointment-card .card-title {
            font-weight: 700;
            color: var(--text-color);
            font-size: 1.3rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .appointment-card .card-title i {
            color: var(--primary-color);
        }
        
        .appointment-status {
            font-size: 0.8rem;
            padding: 6px 14px;
            border-radius: 30px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
        }
        
        .status-upcoming {
            background-color: rgba(59, 130, 246, 0.15);
            color: var(--info);
        }
        
        .status-completed {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }
        
        .status-cancelled {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }
        
        .card-text {
            color: var(--light-text);
            margin-bottom: 20px;
            line-height: 1.8;
            font-size: 1.05rem;
        }
        
        .card-info {
            background-color: rgba(67, 97, 238, 0.05);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .card-info p {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-info p i {
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .card-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-outline-warning {
            color: var(--warning);
            border: 2px solid var(--warning);
            background: transparent;
        }
        
        .btn-outline-warning:hover {
            background-color: var(--warning);
            color: white;
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.2);
        }
        
        .btn-outline-danger {
            color: var(--danger);
            border: 2px solid var(--danger);
            background: transparent;
        }
        
        .btn-outline-danger:hover {
            background-color: var(--danger);
            color: white;
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.2);
        }
        
        /* Empty State */
        .no-appointments {
            text-align: center;
            padding: 70px 40px;
            background-color: var(--panel-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .no-appointments::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--primary-gradient);
        }
        
        .no-appointments i {
            color: var(--primary-color);
            font-size: 4rem;
            margin-bottom: 25px;
            opacity: 0.6;
        }
        
        .no-appointments h4 {
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 15px;
            font-size: 1.8rem;
        }
        
        .no-appointments p {
            color: var(--light-text);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto 25px;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 991px) {
            .content-area {
                padding: 0 20px 40px;
            }
            
            .dashboard-title {
                font-size: 1.8rem;
                padding-top: 30px;
            }
            
            .wave-top {
                height: 100px;
            }
        }
        
        @media (max-width: 767px) {
            .appointment-card .card-body {
                padding: 20px;
            }
            
            .date-navigator {
                padding: 20px;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .input-group > * {
                width: 100%;
                margin-bottom: 10px;
                margin-left: 0 !important;
            }
            
            .no-appointments {
                padding: 40px 20px;
            }
            
            .no-appointments i {
                font-size: 3rem;
            }
            
            .no-appointments h4 {
                font-size: 1.5rem;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .appointment-card {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .appointment-card:nth-child(2) {
            animation-delay: 0.1s;
        }
        
        .appointment-card:nth-child(3) {
            animation-delay: 0.2s;
        }
        
        .appointment-card:nth-child(4) {
            animation-delay: 0.3s;
        }
        
        .appointment-card:nth-child(5) {
            animation-delay: 0.4s;
        }
        
        .no-appointments p {
            color: var(--light-text);
            margin-bottom: 20px;
        }
        
        @media (max-width: 767px) {
            .date-navigator .row .col-md-6:last-child {
                margin-top: 15px;
            }
            
            .btn-group {
                width: 100%;
                margin-top: 10px;
            }
            
            .btn-group .btn {
                flex: 1;
            }
        }

        .appointment-details {
            padding: 1rem;
        }
        .detail-item {
            margin-bottom: 1rem;
            padding: 0.5rem;
            border-radius: 8px;
            background-color: #f8fafc;
        }
        .detail-item i {
            color: var(--primary-color);
            width: 20px;
        }
        .modal-content {
            border-radius: 16px;
            border: none;
        }
        .modal-header {
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
        }
        .modal-footer {
            border-top: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
        }

        .quick-links {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: #2563eb;
            font-weight: 500;
        }

        .quick-link:hover {
            text-decoration: underline;
        }

        .dashboard-section {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header h2 {
            font-size: 1.5rem;
            color: #1f2937;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-all {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        .messages-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message-card {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            transition: transform 0.2s ease;
        }

        .message-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .message-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            background: #2563eb;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .message-meta {
            display: flex;
            flex-direction: column;
        }

        .message-subject {
            font-weight: 600;
            color: #1f2937;
        }

        .message-date {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .message-preview {
            color: #4b5563;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .reply-preview {
            font-size: 0.875rem;
            color: #2563eb;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-replied {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .no-messages {
            text-align: center;
            color: #6b7280;
            padding: 2rem;
        }

        .no-messages a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .no-messages a:hover {
            text-decoration: underline;
        }

        #appointments-container {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fa-solid fa-calendar-check me-2"></i>
                Appointment System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fa-solid fa-gauge-high me-1"></i> Dashboard
                        </a>
                    </li>
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
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                            <i class="fa-solid fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-circle me-1"></i> 
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fa-solid fa-gauge-high me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="messages.php"><i class="fa-solid fa-comments me-2"></i>Consultant Messages</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="page-container">
        <div class="wave-top"></div>
        
        <div class="content-area container">
            <h2 class="dashboard-title">
                <i class="fa-solid fa-calendar-day me-2"></i>
                Upcoming Appointments
            </h2>

            <!-- Appointments Container -->
            <div id="appointments-container">
                <div class="text-center p-5">
                    <div class="spinner-border" style="color: var(--primary-color);" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3" style="color: var(--light-text);">Loading appointments...</p>
                </div>
            </div>

            <div class="quick-links">
                <a href="appointments.php" class="quick-link">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>My Appointments</span>
                </a>
                <a href="messages.php" class="quick-link">
                    <i class="fa-solid fa-envelope"></i>
                    <span>My Messages</span>
                </a>
                <a href="profile.php" class="quick-link">
                    <i class="fa-solid fa-user"></i>
                    <span>Edit Profile</span>
                </a>
            </div>

            <!-- Messages Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fa-solid fa-envelope"></i> Recent Messages</h2>
                    <a href="messages.php" class="view-all">View All Messages</a>
                </div>
                <div class="messages-list">
                    <?php
                    // Get recent messages
                    $user_id = $_SESSION['user_id'];
                    $sql = "SELECT cm.*, mr.reply_content, mr.replied_at, u.name as admin_name 
                            FROM contact_messages cm 
                            LEFT JOIN message_replies mr ON cm.id = mr.message_id 
                            LEFT JOIN users u ON mr.replied_by = u.id 
                            WHERE cm.user_id = $user_id 
                            ORDER BY cm.created_at DESC LIMIT 3";
                    $result = mysqli_query($conn, $sql);

                    if (mysqli_num_rows($result) > 0) {
                        while ($message = mysqli_fetch_assoc($result)) {
                            ?>
                            <div class="message-card">
                                <div class="message-header">
                                    <div class="message-info">
                                        <div class="message-avatar">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="message-meta">
                                            <span class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></span>
                                            <span class="message-date"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <span class="status-badge <?php echo $message['is_replied'] ? 'status-replied' : 'status-pending'; ?>">
                                        <?php echo $message['is_replied'] ? 'Replied' : 'Pending'; ?>
                                    </span>
                                </div>
                                <div class="message-preview">
                                    <?php echo htmlspecialchars(substr($message['message'], 0, 100)) . '...'; ?>
                                </div>
                                <?php if ($message['is_replied']): ?>
                                    <div class="reply-preview">
                                        <i class="fas fa-reply"></i>
                                        Reply from <?php echo htmlspecialchars($message['admin_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="no-messages">No messages yet. <a href="contact.php">Send a message</a></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment Details Modal -->
    <div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appointmentModalLabel">Appointment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="appointment-details">
                        <!-- Appointment details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load appointments immediately when page loads
            loadAppointments();
        });

        function loadAppointments() {
            const container = document.getElementById('appointments-container');
            
            // Show loading state
            container.innerHTML = `
                <div class="text-center p-5">
                    <div class="spinner-border" style="color: var(--primary-color);" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3" style="color: var(--light-text);">Loading appointments...</p>
                </div>
            `;
            
            // Fetch appointments
            fetch('../actions/get_appointments.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    const appointments = Array.isArray(data) ? data : [];
                    
                    if (appointments.length === 0) {
                        container.innerHTML = `
                            <div class="no-appointments">
                                <i class="fas fa-calendar-xmark fa-3x mb-3"></i>
                                <h4>No Upcoming Appointments</h4>
                                <p>You don't have any upcoming appointments scheduled.</p>
                                <a href="consultants.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Book an Appointment
                                </a>
                            </div>
                        `;
                        return;
                    }
                    
                    let html = '';
                    appointments.forEach(appointment => {
                        const statusClass = appointment.status === 'completed' ? 'status-completed' : 
                                          appointment.status === 'cancelled' ? 'status-cancelled' : 
                                          'status-upcoming';
                        
                        // Calculate end time using duration from backend
                        const startTime = parseTime(appointment.appointment_time);
                        const duration = parseInt(appointment.duration) || 60; // Default to 60 if not set
                        const endTimeObj = addMinutes(startTime, duration);
                        const endTime = formatTime(endTimeObj);

                        html += `
                            <div class="appointment-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title">${appointment.consultant_name}</h5>
                                        <span class="appointment-status ${statusClass}">${appointment.status}</span>
                                    </div>
                                    <p class="card-text">
                                        <i class="fas fa-calendar-day me-2"></i>${appointment.appointment_date}<br>
                                        <i class="fas fa-clock me-2"></i>${appointment.appointment_time} - ${endTime}<br>
                                        <i class="fas fa-tag me-2"></i>${appointment.specialty}
                                    </p>
                                </div>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                })
                .catch(error => {
                    container.innerHTML = `
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Error loading appointments: ${error.message}
                        </div>
                    `;
                });
        }
        
        function parseTime(timeStr) {
            const [time, modifier] = timeStr.split(' ');
            let [hours, minutes] = time.split(':').map(Number);
            if (modifier === 'PM' && hours !== 12) hours += 12;
            if (modifier === 'AM' && hours === 12) hours = 0;
            return { hours, minutes };
        }

        function addMinutes(timeObj, mins) {
            let totalMinutes = timeObj.hours * 60 + timeObj.minutes + mins;
            let hours = Math.floor(totalMinutes / 60) % 24;
            let minutes = totalMinutes % 60;
            return { hours, minutes };
        }

        function formatTime(timeObj) {
            let hours = timeObj.hours % 12;
            hours = hours ? hours : 12;
            let ampm = timeObj.hours >= 12 ? 'PM' : 'AM';
            let minutes = timeObj.minutes.toString().padStart(2, '0');
            return `${hours}:${minutes} ${ampm}`;
        }

        function renderAppointments(appointments) {
            const container = document.getElementById('appointments-container');
            let html = '<div class="row">';
            
            appointments.forEach(appointment => {
                // Format date and time
                const appointmentDate = new Date(appointment.slot_date);
                const formattedDate = appointmentDate.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
                const timeString = appointment.slot_time ? formatTimeRange(appointment.slot_time) : 'N/A';
                
                // Determine status class
                let statusClass = 'status-upcoming';
                let statusText = 'Upcoming';
                
                if (appointment.status === 'completed') {
                    statusClass = 'status-completed';
                    statusText = 'Completed';
                } else if (appointment.status === 'cancelled') {
                    statusClass = 'status-cancelled';
                    statusText = 'Cancelled';
                }
                
                // Generate card based on user role
                html += `
                    <div class="col-md-6 col-lg-4">
                        <div class="card appointment-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0">
                                        ${
                                            '<?php echo $user_role; ?>' === 'consultant' 
                                            ? `<i class="fa-solid fa-user me-1"></i> ${appointment.client_name || 'Client'}`
                                            : `<i class="fa-solid fa-user-tie me-1"></i> ${appointment.consultant_name || 'Consultant'}`
                                        }
                                    </h5>
                                    <span class="appointment-status ${statusClass}">${statusText}</span>
                                </div>
                                
                                <p class="card-text">
                                    <i class="fa-solid fa-calendar-day me-1"></i> ${formattedDate}<br>
                                    <i class="fa-solid fa-clock me-1"></i> ${timeString}
                                    ${appointment.specialty ? `<br><i class="fa-solid fa-tag me-1"></i> ${appointment.specialty}` : ''}
                                </p>
                                
                                <div class="d-flex justify-content-between mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="showAppointmentDetails(${JSON.stringify(appointment).replace(/"/g, '&quot;')})">
                                        <i class="fa-solid fa-eye me-1"></i> Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        function showAppointmentDetails(appointmentId) {
            fetch(`../actions/get_appointment.php?id=${appointmentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const appointment = data.appointment;
                        const detailsContainer = document.getElementById('appointment-details');
                        
                        detailsContainer.innerHTML = `
                            <div class="appointment-details">
                                <div class="appointment-card">
                                    <div class="card-header">
                                        <i class="fas fa-user-circle"></i>
                                        <h6>Consultant Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <p>
                                            <strong><i class="fas fa-user"></i>Name:</strong>
                                            <span>${appointment.consultant_name}</span>
                                        </p>
                                        <p>
                                            <strong><i class="fas fa-envelope"></i>Email:</strong>
                                            <span>${appointment.consultant_email}</span>
                                        </p>
                                        <p>
                                            <strong><i class="fas fa-briefcase"></i>Specialty:</strong>
                                            <span>${appointment.specialty}</span>
                                        </p>
                                        <div class="mt-3">
                                            <a href="appointment_messages.php?appointment_id=${appointment.id}" class="btn btn-primary">
                                                <i class="fas fa-comments me-1"></i>Message Consultant
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="appointment-card">
                                    <div class="card-header">
                                        <i class="fas fa-calendar-alt"></i>
                                        <h6>Appointment Schedule</h6>
                                    </div>
                                    <div class="card-body">
                                        <p>
                                            <strong><i class="fas fa-calendar"></i>Date:</strong>
                                            <span>${appointment.formatted_date}</span>
                                        </p>
                                        <p>
                                            <strong><i class="fas fa-clock"></i>Time:</strong>
                                            <span>${appointment.formatted_start_time} - ${appointment.formatted_end_time}</span>
                                        </p>
                                        <p>
                                            <strong><i class="fas fa-info-circle"></i>Status:</strong>
                                            <span class="status-badge ${getStatusBadgeClass(appointment.status)}">${capitalizeFirstLetter(appointment.status)}</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="appointment-card">
                                    <div class="card-header">
                                        <i class="fas fa-sticky-note"></i>
                                        <h6>Additional Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <p>
                                            <strong><i class="fas fa-comment"></i>Notes:</strong>
                                            <span>${appointment.notes || 'No notes provided'}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        const modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
                        modal.show();
                    } else {
                        alert(data.message || 'Error loading appointment details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading appointment details. Please try again.');
                });
        }

        function getStatusBadgeClass(status) {
            switch(status.toLowerCase()) {
                case 'completed':
                    return 'status-completed';
                case 'cancelled':
                    return 'status-cancelled';
                case 'pending':
                    return 'status-upcoming';
                default:
                    return 'status-upcoming';
            }
        }

        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
        }

        // Add function to cancel appointment
        function cancelAppointment(appointmentId) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                fetch('cancel_appointment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${appointmentId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Appointment cancelled successfully');
                        loadAppointments();
                    } else {
                        alert(data.message || 'Error cancelling appointment');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error cancelling appointment');
                });
            }
        }
    </script>
</body> 
</html>