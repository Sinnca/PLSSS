<?php 
require_once '../config/db.php'; 
require_once '../includes/header.php'; 
require_once '../includes/auth.php';

// Check if consultant is logged in
checkConsultantLogin();

$consultant_id = $_SESSION['consultant_id'];

// Fetch pending appointments
$sql = "SELECT a.*, u.name as client_name, u.email as client_email, av.slot_time, av.slot_date, av.duration
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN availability av ON a.availability_id = av.id
        WHERE a.consultant_id = $consultant_id AND a.status = 'pending'
        ORDER BY av.slot_date, av.slot_time";
$pending_appointments = mysqli_query($conn, $sql);

// Fetch upcoming appointments
$sql = "SELECT a.*, u.name as client_name, u.email as client_email, av.slot_time, av.slot_date, av.duration
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN availability av ON a.availability_id = av.id
        WHERE a.consultant_id = $consultant_id AND a.status = 'approved'
        AND (av.slot_date > CURDATE() OR 
             (av.slot_date = CURDATE() AND av.slot_time >= CURTIME()))
        ORDER BY av.slot_date, av.slot_time";
$upcoming_appointments = mysqli_query($conn, $sql);

// Fetch past appointments
$sql = "SELECT a.*, u.name as client_name, av.slot_time, av.slot_date, DATE_ADD(av.slot_time, INTERVAL 1 HOUR) as end_time
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN availability av ON a.availability_id = av.id
        WHERE a.consultant_id = $consultant_id AND a.status = 'approved'
        AND (av.slot_date < CURDATE() OR 
             (av.slot_date = CURDATE() AND av.slot_time < CURTIME()))
        ORDER BY av.slot_date DESC, av.slot_time DESC";
$past_appointments = mysqli_query($conn, $sql);

// Get today's date for the calendar
$today = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$view = isset($_GET['view']) ? $_GET['view'] : 'day';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultant Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --secondary: #2563eb;
            --success: #4bb543;
            --warning: #ffb703;
            --danger: #f72585;  
            --info: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --white: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-light: #dbeafe;
            --card-bg: #ffffff;
            --hover-bg: #e6f0ff;
            --shadow-sm: 0 1px 2px 0 rgba(37, 99, 235, 0.08), 0 1px 3px 1px rgba(37, 99, 235, 0.04);
            --shadow-md: 0 3px 6px rgba(37, 99, 235, 0.10), 0 3px 6px rgba(37, 99, 235, 0.12);
            --shadow-lg: 0 10px 25px -5px rgba(37, 99, 235, 0.12), 0 10px 10px -5px rgba(37, 99, 235, 0.08);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --transition: all 0.2s ease;
            --background: #f0f7ff;
            --gradient-start: #2563eb;
            --gradient-end: #1d4ed8;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e7f1ff 100%);
            font-family: 'Google Sans', 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1440px;
            padding: 20px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            background: var(--card-bg);
            border: none;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            margin-bottom: 1.5rem;
            overflow: hidden;
            position: relative;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            opacity: 0;
            transition: var(--transition);
        }

        .card:hover::before {
            opacity: 1;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .card-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            pointer-events: none;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .card-body {
            padding: 1.5rem;
        }

        .btn {
            font-family: 'Google Sans', 'Roboto', sans-serif;
            font-weight: 500;
            padding: 0.625rem 1.25rem;
            border-radius: 100px;
            transition: var(--transition);
            text-transform: none;
            letter-spacing: 0.2px;
            box-shadow: none;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            opacity: 0;
            transition: var(--transition);
            border-radius: inherit;
        }

        .btn:hover::after {
            opacity: 1;
        }

        .list-group-item {
            border: none;
            padding: 1rem 1.5rem;
            transition: var(--transition);
            border-radius: var(--radius-sm) !important;
            margin-bottom: 0.5rem;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
        }

        .list-group-item:hover {
            background: var(--hover-bg);
            transform: translateX(5px);
        }

        .list-group-item i {
            width: 24px;
            text-align: center;
            margin-right: 0.75rem;
            color: var(--primary);
            transition: var(--transition);
        }

        .list-group-item:hover i {
            transform: scale(1.2);
        }

        .table {
            margin: 0;
        }

        .table th {
            font-weight: 600;
            color: var(--text-secondary);
            border-bottom: 2px solid var(--border-light);
            background: rgba(255, 255, 255, 0.9);
        }

        .table td {
            vertical-align: middle;
            color: var(--text-primary);
            transition: var(--transition);
        }

        .table tr:hover td {
            background: var(--hover-bg);
        }

        .badge {
            padding: 0.5em 1em;
            border-radius: 100px;
            font-weight: 500;
            transition: var(--transition);
        }

        .badge:hover {
            transform: scale(1.05);
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            opacity: 0;
            transition: var(--transition);
        }

        .status-badge:hover::before {
            opacity: 1;
        }

        .status-badge:hover {
            transform: scale(1.05);
        }

        .status-pending {
            background: linear-gradient(135deg, #e7f1ff 0%, #0d6efd 100%);
            color: #0a58ca;
        }

        .status-confirmed {
            background: linear-gradient(135deg, #e7f1ff 0%, #0d6efd 100%);
            color: #0a58ca;
        }

        .status-completed {
            background: linear-gradient(135deg, #e7f1ff 0%, #0d6efd 100%);
            color: #0a58ca;
        }

        .status-cancelled {
            background: linear-gradient(135deg, #e7f1ff 0%, #0d6efd 100%);
            color: #0a58ca;
        }

        .appointment-card {
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 0.5rem;
            cursor: pointer;
            color: white;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .appointment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            opacity: 0;
            transition: var(--transition);
        }

        .appointment-card:hover::before {
            opacity: 1;
        }

        .appointment-card:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: var(--shadow-md);
        }

        .appointment-card .time {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .appointment-card .client {
            font-weight: 600;
            margin: 0.5rem 0;
        }

        .appointment-card .status {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            font-size: 0.75rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }



        .appointment-actions {
            margin-top: 0.5rem;
            opacity: 0;
            transform: translateY(10px);
            transition: var(--transition);
        }

        .appointment-card:hover .appointment-actions {
            opacity: 1;
            transform: translateY(0);
        }

        .appointment-actions .btn {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border: none;
        }

        .appointment-actions .btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .card {
                margin-bottom: 1rem;
            }
            
            .btn {
                padding: 0.5rem 1rem;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary);
        }

        .table-calendar {
            color: #000000;
        }

        .table-calendar th,
        .table-calendar td {
            color: #000000;
            border-color: #dee2e6;
        }

        .table-calendar .time-cell {
            font-weight: 600;
            color: #000000;
        }

        .table-calendar .appointment-cell {
            min-width: 150px;
            height: 80px;
            vertical-align: top;
            padding: 5px;
        }

        /* New Modern Elements */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            pointer-events: none;
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
            opacity: 0;
            transition: var(--transition);
        }

        .stats-card:hover::before {
            opacity: 1;
        }

        .stats-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            color: var(--primary);
        }

        .stats-card p {
            margin: 0.5rem 0 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .action-card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .action-card:hover i {
            transform: scale(1.2);
        }

        .action-card h4 {
            margin: 0;
            color: var(--text-primary);
            font-weight: 600;
        }

        .action-card p {
            margin: 0.5rem 0 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Loading Animation */
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--primary-light);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Modal Enhancements */
        .modal-content {
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border-light);
            padding: 1rem 2rem;
        }
        /* Profile Icon & Dropdown Styling */
        .profile-navbar {
            background: linear-gradient(135deg, #4361ee 0%, #2563eb 100%);
            border-radius: 0 0 1.5rem 1.5rem;
            box-shadow: 0 2px 16px rgba(67,97,238,0.07);
            margin-bottom: 2rem;
            padding: 0.5rem 2rem;
        }
        .profile-navbar .dropdown-toggle {
            color: #22304a;
            font-weight: 500;
            font-size: 1.15rem;
            border: none;
            background: none;
            transition: color 0.2s;
        }
        .profile-navbar .dropdown-toggle:focus,
        .profile-navbar .dropdown-toggle:hover {
            color: #4361ee;
        }
        .profile-navbar .fa-user-circle {
            color: #fff;
            background: #4361ee;
            border-radius: 50%;
            padding: 0.15em;
            margin-right: 0.5em;
            box-shadow: 0 2px 8px rgba(67,97,238,0.08);
        }
        .profile-navbar .dropdown-menu {
            min-width: 160px;
            border-radius: 1rem;
            box-shadow: 0 6px 24px rgba(67,97,238,0.12);
            border: none;
            margin-top: 0.75rem;
        }
        .profile-navbar .dropdown-item {
            font-size: 1rem;
            padding: 0.75rem 1.25rem;
            color: #22304a;
            border-radius: 0.75rem;
            transition: background 0.2s, color 0.2s;
        }
        .profile-navbar .dropdown-item:hover,
        .profile-navbar .dropdown-item:focus {
            background: #e7f1ff;
            color: #4361ee;
        }
        .profile-navbar .fa-right-from-bracket {
            color: #ef4444;
            margin-right: 0.5em;
        }
    </style>
</head>
<body>
    <!-- Profile Icon with Logout Dropdown -->
    <nav class="navbar profile-navbar navbar-expand-lg navbar-light">
        <div class="container-fluid justify-content-end">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-user-circle fa-2x me-2"></i>
                    <span class="d-none d-md-inline">Profile</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-header">
            <h1>Consultant Dashboard</h1>
            <p class="mb-0">Welcome back! Here's your overview for today.</p>
        </div>
        
        <div class="row">
            <!-- Left Sidebar - Quick Stats -->
            <div class="col-md-3">
                <div class="stats-card mb-4">
                    <h3><?php echo mysqli_num_rows($pending_appointments); ?></h3>
                    <p>Pending Appointments</p>
                </div>
                
                <div class="stats-card mb-4">
                    <h3><?php echo mysqli_num_rows($upcoming_appointments); ?></h3>
                    <p>Upcoming Appointments</p>
                </div>
                
                <div class="stats-card mb-4">
                    <h3><?php echo mysqli_num_rows($past_appointments); ?></h3>
                    <p>Completed Appointments</p>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="set_availability.php" class="action-card">
                                <i class="fas fa-calendar-plus"></i>
                                <h4>Manage Availability</h4>
                                <p>Set your working hours</p>
                            </a>
                            <a href="appointments.php" class="action-card">
                                <i class="fas fa-calendar-check"></i>
                                <h4>Appointments</h4>
                                <p>Manage all appointments</p>
                            </a>
                            <a href="profile.php" class="action-card">
                                <i class="fas fa-user-edit"></i>
                                <h4>Edit Profile</h4>
                                <p>Update your information</p>
                            </a>
                            <a href="messages.php" class="action-card">
                                <i class="fas fa-envelope"></i>
                                <h4>Messages</h4>
                                <p>View client messages</p>
                            </a>
                            <a href="appointments_report.php" class="action-card">
                                <i class="fas fa-chart-bar"></i>
                                <h4>Reports</h4>
                                <p>View appointment analytics</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Calendar View -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Appointment Calendar</h5>
                        <div class="btn-group">
                            <a href="?view=day&date=<?php echo date('Y-m-d', strtotime($today)); ?>" class="btn btn-sm <?php echo $view == 'day' ? 'btn-light' : 'btn-outline-light'; ?>">Day</a>
                            <a href="?view=week&date=<?php echo date('Y-m-d', strtotime($today)); ?>" class="btn btn-sm <?php echo $view == 'week' ? 'btn-light' : 'btn-outline-light'; ?>">Week</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <?php if($view == 'day'): ?>
                                <a href="?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d', strtotime($today . ' -1 day')); ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-chevron-left"></i> Previous Day
                                </a>
                                <h5><?php echo date('l, F j, Y', strtotime($today)); ?></h5>
                                <a href="?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d', strtotime($today . ' +1 day')); ?>" class="btn btn-outline-secondary">
                                    Next Day <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <a href="?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d', strtotime($today . ' -7 days')); ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-chevron-left"></i> Previous Week
                                </a>
                                <h5>Week of <?php echo date('F j', strtotime($today)); ?> - <?php echo date('F j, Y', strtotime($today . ' +6 days')); ?></h5>
                                <a href="?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d', strtotime($today . ' +7 days')); ?>" class="btn btn-outline-secondary">
                                    Next Week <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div id="calendar-container" class="mb-3">
                            <div class="text-center my-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p>Loading appointments...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pending Appointments Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Pending Appointments</h5>
                    </div>
                    <div class="card-body">
                        <?php if(mysqli_num_rows($pending_appointments) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($appointment = mysqli_fetch_assoc($pending_appointments)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                                                <td><?php echo date('F j, Y', strtotime($appointment['slot_date'])); ?></td>
                                                <td><?php 
                                                    $start_time = date('g:i A', strtotime($appointment['slot_time']));
                                                    $end_time = date('g:i A', strtotime($appointment['slot_time']) + ($appointment['duration'] * 60));
                                                    echo $start_time . ' - ' . $end_time;
                                                ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <form method="post" action="../actions/update_booking.php" class="d-inline">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            <input type="hidden" name="status" value="approved">
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                        </form>
                                                        <form method="post" action="../actions/update_booking.php" class="d-inline ms-1">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            <input type="hidden" name="status" value="rejected">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No pending appointments.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Appointments Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <h5 class="mb-0">Upcoming Appointments</h5>
                        <form method="get" class="d-flex align-items-center gap-2" style="margin-top:0.5rem;">
                            <input type="date" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : ''; ?>" class="form-control form-control-sm date-filter-input" style="max-width:160px; border-radius: var(--radius-sm); border: 1px solid var(--border-light); background: var(--background); color: var(--text-primary); box-shadow: var(--shadow-sm);">
                            <button type="submit" class="btn btn-primary btn-sm px-3" style="border-radius: var(--radius-sm); background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end)); border: none;">
                                <i class="fa-solid fa-filter me-1"></i> Filter
                            </button>
                            <?php if (isset($_GET['filter_date']) && $_GET['filter_date'] !== ''): ?>
                                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm px-2" style="border-radius: var(--radius-sm);"><i class="fa-solid fa-times"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php if(mysqli_num_rows($upcoming_appointments) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
    // Filter by date if set
    $filtered_upcoming_appointments = [];
    if (isset($_GET['filter_date']) && $_GET['filter_date'] !== '') {
        $filter_date = $_GET['filter_date'];
        mysqli_data_seek($upcoming_appointments, 0);
        while ($appt = mysqli_fetch_assoc($upcoming_appointments)) {
            if ($appt['slot_date'] === $filter_date) {
                $filtered_upcoming_appointments[] = $appt;
            }
        }
    } else {
        mysqli_data_seek($upcoming_appointments, 0);
        while ($appt = mysqli_fetch_assoc($upcoming_appointments)) {
            $filtered_upcoming_appointments[] = $appt;
        }
    }
?>
<?php foreach($filtered_upcoming_appointments as $appt): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($appt['client_name']); ?></td>
                                                <td><?php 
                                                    $start_time = date('g:i A', strtotime($appt['slot_time']));
                                                    $end_time = date('g:i A', strtotime($appt['slot_time']) + ($appt['duration'] * 60));
                                                    echo date('F j, Y', strtotime($appt['slot_date'])) . ' at ' . $start_time . ' - ' . $end_time;
                                                ?></td>
                                                <td>
                                                    <span class="status-badge status-confirmed">Confirmed</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-info view-btn" data-id="<?php echo $appt['id']; ?>">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                        <a href="appointment_messages.php?appointment_id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-comments"></i> Message
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-danger cancel-btn" data-id="<?php echo $appt['id']; ?>">
                                                            <i class="fas fa-ban"></i> Cancel
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No upcoming appointments.</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Past Appointments Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Past Appointments</h5>
                        <button class="btn btn-sm btn-light" id="toggle-past">Show/Hide</button>
                    </div>
                    <div class="card-body" id="past-appointments" style="display: none;">
                        <?php if(mysqli_num_rows($past_appointments) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($appt = mysqli_fetch_assoc($past_appointments)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($appt['client_name']); ?></td>
                                                <td><?php 
                                                    $start_time = date('g:i A', strtotime($appt['slot_time']));
                                                    $end_time = date('g:i A', strtotime($appt['end_time']));
                                                    echo date('F j, Y', strtotime($appt['slot_date'])) . ' at ' . $start_time . ' - ' . $end_time;
                                                ?></td>
                                                <td>
                                                    <a href="past_appointments.php" class="btn btn-sm btn-info">
    <i class="fas fa-eye"></i> View
</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No past appointments.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modern Modal for Appointment Details -->
    <div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appointmentModalLabel">
                        <i class="fas fa-calendar-check me-2"></i>Appointment Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="appointment-details">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading appointment details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    // Wait for both DOM and Bootstrap to be fully loaded
    window.addEventListener('load', function() {
        // Initialize Bootstrap modal
        const appointmentModal = new bootstrap.Modal(document.getElementById('appointmentModal'));
        
        // Toggle past appointments
        document.getElementById('toggle-past').addEventListener('click', function() {
            const pastAppts = document.getElementById('past-appointments');
            if (pastAppts.style.display === 'none') {
                pastAppts.style.display = 'block';
            } else {
                pastAppts.style.display = 'none';
            }
        });

        // Load appointments for calendar
        loadAppointments();

        // Handle appointment approval
        document.querySelectorAll('.approve-btn').forEach(button => {
            button.addEventListener('click', function() {
                const appointmentId = this.getAttribute('data-id');
                updateAppointmentStatus(appointmentId, 'approved');
            });
        });

        // Handle appointment rejection
        document.querySelectorAll('.reject-btn').forEach(button => {
            button.addEventListener('click', function() {
                const appointmentId = this.getAttribute('data-id');
                updateAppointmentStatus(appointmentId, 'rejected');
            });
        });

        // Handle view button clicks
        document.querySelectorAll('.view-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const appointmentId = this.getAttribute('data-id');
                if (!appointmentId) {
                    console.error('No appointment ID found on view button');
                    return;
                }
                showAppointmentDetails(appointmentId);
            });
        });

        // Handle cancel button clicks
        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function() {
                const appointmentId = this.getAttribute('data-id');
                cancelAppointment(appointmentId);
            });
        });
    });

    function loadAppointments() {
        const calendarContainer = document.getElementById('calendar-container');
        const date = '<?php echo $today; ?>';
        const view = '<?php echo $view; ?>';
        
        // Show loading state
        calendarContainer.innerHTML = `
            <div class="text-center my-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading appointments...</p>
            </div>
        `;
        
        fetch(`../actions/fetch_appointments.php?date=${date}&view=${view}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (view === 'day') {
                        renderDayView(data.appointments, date);
                    } else {
                        renderWeekView(data.appointments, date);
                    }
                } else {
                    calendarContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error fetching appointments:', error);
                calendarContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Failed to load appointments. Please try again.
                        <small class="d-block mt-2">Error: ${error.message}</small>
                    </div>
                `;
            });
    }

    function renderDayView(appointments, date) {
        const calendarContainer = document.getElementById('calendar-container');
        const hours = [];
        
        // Generate hours from 8 AM to 6 PM
        for (let i = 8; i <= 18; i++) {
            hours.push(`${i > 12 ? i - 12 : i}:00 ${i >= 12 ? 'PM' : 'AM'}`);
        }
        
        let html = `
            <div class="table-responsive">
                <table class="table table-bordered table-calendar">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Appointment</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        hours.forEach(hour => {
            const hourFormatted = hour.replace(' ', ''); // Remove space for comparison
            const appts = appointments.filter(a => {
                const apptTime = new Date(`${a.slot_date} ${a.slot_time}`);
                const apptHour = apptTime.getHours();
                const displayHour = apptHour > 12 ? `${apptHour - 12}:00 PM` : `${apptHour}:00 AM`;
                return displayHour === hourFormatted;
            });
            
            html += `
                <tr>
                    <td class="time-cell">${hour}</td>
                    <td class="appointment-cell">
            `;
            
            if (appts.length > 0) {
                appts.forEach(appt => {
                    const clientName = appt.client_name || 'Client';
                    const statusClass = appt.status === 'pending' ? 'bg-warning' : 'bg-success';
                    
                    html += `
                        <div class="appointment-card ${statusClass}" data-id="${appt.id}">
                            <div class="time">${formatTime(appt.slot_time, appt.duration)}</div>
                            <div class="client">${clientName}</div>
                            <div class="status badge bg-light text-dark">${capitalizeFirstLetter(appt.status)}</div>
                            <div class="appointment-actions mt-2">
                                <a href="appointment_messages.php?appointment_id=${appt.id}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-comments me-1"></i>Message
                                </a>
                            </div>
                        </div>
                    `;
                });
            }
            
            html += `
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        calendarContainer.innerHTML = html;
        
        // Add event listeners for appointment cards
        document.querySelectorAll('.appointment-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on the message button
                if (!e.target.closest('.appointment-actions')) {
                    const appointmentId = this.getAttribute('data-id');
                    showAppointmentDetails(appointmentId);
                }
            });
        });
    }

    function renderWeekView(appointments, startDate) {
        const calendarContainer = document.getElementById('calendar-container');
        const days = [];
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        // Generate 7 days starting from the given date
        for (let i = 0; i < 7; i++) {
            const currentDate = new Date(startDate);
            currentDate.setDate(currentDate.getDate() + i);
            const formattedDate = currentDate.toISOString().split('T')[0];
            const dayName = dayNames[currentDate.getDay()];
            const displayDate = `${dayName.substring(0, 3)} ${currentDate.getDate()}/${currentDate.getMonth() + 1}`;
            
            days.push({
                date: formattedDate,
                display: displayDate
            });
        }
        
        let html = `
            <div class="table-responsive">
                <table class="table table-bordered table-calendar">
                    <thead>
                        <tr>
                            <th>Time</th>
        `;
        
        days.forEach(day => {
            html += `<th>${day.display}</th>`;
        });
        
        html += `
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        // Generate hours from 8 AM to 6 PM
        for (let i = 8; i <= 18; i++) {
            const hour = `${i > 12 ? i - 12 : i}:00 ${i >= 12 ? 'PM' : 'AM'}`;
            
            html += `
                <tr>
                    <td class="time-cell">${hour}</td>
            `;
            
            days.forEach(day => {
                const appts = appointments.filter(a => {
                    const apptTime = new Date(`${a.slot_date} ${a.slot_time}`);
                    const apptHour = apptTime.getHours();
                    const displayHour = apptHour;
                    return a.slot_date === day.date && displayHour === i;
                });
                
                html += `<td class="appointment-cell">`;
                
                if (appts.length > 0) {
                    appts.forEach(appt => {
                        const clientName = appt.client_name || 'Client';
                        const statusClass = appt.status === 'pending' ? 'bg-warning' : 'bg-success';
                        
                        html += `
                            <div class="appointment-card ${statusClass}" data-id="${appt.id}">
                                <div class="time">${formatTime(appt.slot_time, appt.duration)}</div>
                                <div class="client">${clientName}</div>
                                <div class="appointment-actions mt-2">
                                    <a href="appointment_messages.php?appointment_id=${appt.id}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-comments me-1"></i>Message
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                }
                
                html += `</td>`;
            });
            
            html += `</tr>`;
        }
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        calendarContainer.innerHTML = html;
        
        // Add event listeners for appointment cards
        document.querySelectorAll('.appointment-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on the message button
                if (!e.target.closest('.appointment-actions')) {
                    const appointmentId = this.getAttribute('data-id');
                    showAppointmentDetails(appointmentId);
                }
            });
        });
    }

    function showAppointmentDetails(appointmentId) {
        console.log('Fetching appointment details for ID:', appointmentId);
        fetch(`../actions/get_appointment.php?id=${appointmentId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Appointment data:', data);
                if (data.success) {
                    const appointment = data.appointment;
                    const detailsContainer = document.getElementById('appointment-details');
                    
                    detailsContainer.innerHTML = `
                        <div class="appointment-details">
                            <div class="appointment-card">
                                <div class="card-header">
                                    <i class="fas fa-user-circle"></i>
                                    <h6>Client Information</h6>
                                </div>
                                <div class="card-body">
                                    <p>
                                        <strong><i class="fas fa-user"></i>Name:</strong>
                                        <span>${appointment.client_name}</span>
                                    </p>
                                    <p>
                                        <strong><i class="fas fa-envelope"></i>Email:</strong>
                                        <span>${appointment.client_email}</span>
                                    </p>
                                    <div class="mt-3">
                                        <a href="appointment_messages.php?appointment_id=${appointment.id}" class="btn btn-primary">
                                            <i class="fas fa-comments me-1"></i>Message Client
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
                                        <span>${formatDate(appointment.slot_date)}</span>
                                    </p>
                                    <p>
                                        <strong><i class="fas fa-clock"></i>Time:</strong>
                                        <span>${formatTime(appointment.slot_time, appointment.duration)}</span>
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
                    alert('Error loading appointment details: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading appointment details: ' + error.message);
            });
    }

    function cancelAppointment(appointmentId) {
        if (!confirm('Are you sure you want to cancel this appointment?')) {
            return;
        }
        
        fetch('../actions/cancel_appointment.php', {
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
                location.reload();
            } else {
                alert(data.message || 'Error cancelling appointment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error cancelling appointment');
        });
    }

    function updateAppointmentStatus(appointmentId, status) {
        if (!confirm(`Are you sure you want to ${status} this appointment?`)) {
            return;
        }
        
        fetch('../actions/update_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${appointmentId}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Appointment ${status} successfully`);
                location.reload();
            } else {
                alert(data.message || `Error ${status} appointment`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(`Error ${status} appointment`);
        });
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }

    function formatTime(timeStr, duration) {
        const timeParts = timeStr.split(':');
        let hours = parseInt(timeParts[0]);
        const minutes = timeParts[1];
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; // Convert 0 to 12
        
        // Calculate end time using duration (in minutes)
        let startDate = new Date(0, 0, 0, parseInt(timeParts[0]), parseInt(timeParts[1]));
        let endDate = new Date(startDate.getTime() + (duration ? duration : 60) * 60000);
        let endHours = endDate.getHours();
        let endMinutes = endDate.getMinutes();
        let endAmpm = endHours >= 12 ? 'PM' : 'AM';
        endHours = endHours % 12;
        endHours = endHours ? endHours : 12;
        let endMinutesStr = endMinutes.toString().padStart(2, '0');
        
        return `${hours}:${minutes} ${ampm} - ${endHours}:${endMinutesStr} ${endAmpm}`;
    }

    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function getStatusBadgeClass(status) {
        switch (status.toLowerCase()) {
            case 'pending':
                return 'status-pending';
            case 'approved':
                return 'status-confirmed';
            case 'completed':
                return 'status-completed';
            case 'cancelled':
            case 'rejected':
                return 'status-cancelled';
            default:
                return '';
        }
    }
    </script>
</body>
</html>