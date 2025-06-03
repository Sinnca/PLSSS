<?php
require_once '../config/db.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Check if consultant is logged in
checkConsultantLogin();

// Get consultant information
$consultant_id = $_SESSION['consultant_id'];
$sql = "SELECT u.name, u.email FROM consultants c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $consultant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$consultant = mysqli_fetch_assoc($result);
$consultant_name = $consultant['name'] ?? '';

// Get all appointments
$sql = "SELECT a.id, u.name as client_name, 
        av.slot_date, av.slot_time,
        DATE_ADD(av.slot_time, INTERVAL 1 HOUR) as end_time,
        a.status, c.specialty
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN consultants c ON a.consultant_id = c.id
        JOIN availability av ON a.availability_id = av.id
        WHERE a.consultant_id = ?
        ORDER BY av.slot_date ASC, av.slot_time ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $consultant_id);
mysqli_stmt_execute($stmt);
$appointments = mysqli_stmt_get_result($stmt);

// Handle filters
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

if ($filter_date !== '' || ($filter_status !== '' && $filter_status !== 'all')) {
    $sql = "SELECT a.id, u.name as client_name, 
            av.slot_date, av.slot_time,
            DATE_ADD(av.slot_time, INTERVAL 1 HOUR) as end_time,
            a.status, c.specialty
            FROM appointments a
            JOIN users u ON a.user_id = u.id
            JOIN consultants c ON a.consultant_id = c.id
            JOIN availability av ON a.availability_id = av.id
            WHERE a.consultant_id = ?";
    $types = "i";
    $params = [$consultant_id];
    if ($filter_date !== '') {
        $sql .= " AND av.slot_date = ?";
        $types .= "s";
        $params[] = $filter_date;
    }
    if ($filter_status !== '' && $filter_status !== 'all') {
        $sql .= " AND a.status = ?";
        $types .= "s";
        $params[] = $filter_status;
    }
    $sql .= " ORDER BY av.slot_date ASC, av.slot_time ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $appointments = mysqli_stmt_get_result($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Consultant Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        /* Modern filter bar styling */
        .filter-bar {
            background: var(--bg-light);
            border-radius: 1rem;
            padding: 1rem 1.5rem 0.5rem 1.5rem;
            box-shadow: 0 2px 12px rgba(67,97,238,0.07);
            margin-bottom: 2rem !important;
            border: 1px solid var(--border-light);
        }
        .filter-bar label {
            color: var(--primary-dark);
            font-size: 0.95rem;
            letter-spacing: 0.01em;
        }
        .filter-bar .filter-input {
            border-radius: 0.5rem;
            border: 1px solid var(--border-light);
            font-size: 1rem;
            background: var(--white);
            transition: border-color 0.2s;
        }
        .filter-bar .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(67,97,238,0.10);
        }
        .filter-bar .filter-btn {
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 1rem;
        }
        @media (max-width: 600px) {
            .filter-bar {
                padding: 0.5rem 0.5rem 0.5rem 0.5rem;
            }
        }

        /* Modern page background */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.5;
            color: var(--text-dark);
            background: linear-gradient(120deg, #f0f7ff 0%, #eef2ff 100%);
            min-height: 100vh;
        }

        /* Card-based appointments container */
        .appointments-container {
            max-width: 900px;
            margin: 2.5rem auto 2rem auto;
            background: var(--white);
            border-radius: 1.25rem;
            box-shadow: 0 8px 32px rgba(67,97,238,0.09);
            padding: 2rem 2.5rem 2rem 2.5rem;
            border: 1px solid var(--border-light);
        }
        @media (max-width: 700px) {
            .appointments-container {
                padding: 1rem 0.5rem 1.5rem 0.5rem;
            }
        }

        /* Page header */
        .page-header {
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 1.2rem;
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .page-title {
            font-weight: 700;
            font-size: 2.1rem;
            color: var(--primary-dark);
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
        }
        .page-header .text-muted {
            font-size: 1.05rem;
            margin-top: 0.2rem;
        }

        /* Appointments list and items */
        .appointments-list {
            margin-top: 0.5rem;
        }
        .appointment-item {
            background: var(--bg-light);
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(67,97,238,0.07);
            padding: 1.2rem 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-light);
            display: flex;
            gap: 1.5rem;
            align-items: center;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .appointment-item:hover {
            background: #f8fafc;
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 24px rgba(67,97,238,0.13);
        }
        .appointment-avatar {
            width: 60px;
            height: 60px;
            background: var(--primary-light);
            color: var(--white);
            font-size: 2rem;
            font-weight: 600;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(67,97,238,0.07);
            flex-shrink: 0;
        }
        .appointment-info {
            flex: 1;
            min-width: 0;
        }
        .appointment-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .client-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary-dark);
            letter-spacing: 0.01em;
        }
        .appointment-date {
            font-size: 1rem;
            color: var(--primary);
            font-weight: 500;
        }
        .appointment-meta {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 1rem;
            font-size: 0.98rem;
            color: var(--light-text);
            margin-top: 0.5rem;
        }
        .specialty {
            background: #e0f2fe;
            color: var(--primary-color);
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-weight: 500;
            font-size: 0.96rem;
        }
        .status-badge {
            padding: 0.3rem 0.9rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.01em;
            text-transform: capitalize;
            box-shadow: 0 1px 3px rgba(67,97,238,0.07);
            border: 1px solid var(--border-light);
        }
        .status-pending {
            background: #fffbe6;
            color: #b59d00;
            border-color: #ffe066;
        }
        .status-approved {
            background: #e6fffa;
            color: #059669;
            border-color: #34d399;
        }
        .status-rejected, .status-cancelled {
            background: #ffe6e6;
            color: #d32f2f;
            border-color: #ef4444;
        }
        .appointment-item:last-child {
            margin-bottom: 0;
        }
        /* Buttons */
        .btn {
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 1.02rem;
            letter-spacing: 0.01em;
            box-shadow: 0 1px 3px rgba(67,97,238,0.04);
            transition: background 0.2s, box-shadow 0.2s, color 0.2s;
        }
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
        }
        .btn-primary:hover, .btn-primary:focus {
            background: var(--gradient-hover);
            color: #fff;
        }
        .btn-outline-secondary {
            border: 1.5px solid var(--border-light);
            color: var(--primary-dark);
            background: var(--white);
        }
        .btn-outline-secondary:hover, .btn-outline-secondary:focus {
            background: var(--primary-light);
            color: #fff;
            border-color: var(--primary);
        }
        /* Alerts */
        .alert {
            border-radius: 0.8rem;
            font-size: 1.05rem;
            box-shadow: 0 2px 8px rgba(67,97,238,0.07);
        }
        /* Responsive tweaks */
        @media (max-width: 700px) {
            .appointment-item {
                flex-direction: column;
                align-items: flex-start;
                padding: 1rem 0.8rem;
                gap: 1rem;
            }
            .appointment-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.2rem;
            }
        }

        /* Modern filter bar styling */
        .filter-bar {
            background: var(--bg-light);
            border-radius: 1rem;
            padding: 1rem 1.5rem 0.5rem 1.5rem;
            box-shadow: 0 2px 12px rgba(67,97,238,0.07);
            margin-bottom: 2rem !important;
            border: 1px solid var(--border-light);
        }
        .filter-bar label {
            color: var(--primary-dark);
            font-size: 0.95rem;
            letter-spacing: 0.01em;
        }
        .filter-bar .filter-input {
            border-radius: 0.5rem;
            border: 1px solid var(--border-light);
            font-size: 1rem;
            background: var(--white);
            transition: border-color 0.2s;
        }
        .filter-bar .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(67,97,238,0.10);
        }
        .filter-bar .filter-btn {
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 1rem;
        }
        @media (max-width: 600px) {
            .filter-bar {
                padding: 0.5rem 0.5rem 0.5rem 0.5rem;
            }
        }
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --text-color: #1e293b;
            --light-text: #64748b;
            --background: #f0f7ff;
            --white: #ffffff;
            --gradient-start: #2563eb;
            --gradient-end: #1d4ed8;
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #eef2ff;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-light: #e5e7eb;
            --bg-light: #f9fafb;
            --bg-gray: #f3f4f6;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 0.5rem;
            --gradient-primary: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%);
            --gradient-hover: linear-gradient(135deg, #3a56d4 0%, #2d44b0 100%);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.5;
            color: var(--text-dark);
            background-color: var(--background);
        }

        .navbar {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            box-shadow: 0 2px 15px rgba(37, 99, 235, 0.2);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.4rem;
            color: var(--white);
        }

        .nav-link {
            font-weight: 500;
            color: #ffffff !important;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            opacity: 1;
        }

        .nav-link:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff !important;
            opacity: 1;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.3);
            color: #ffffff !important;
            opacity: 1;
        }

        .page-header {
            background: var(--white);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.1);
            border: 1px solid rgba(37, 99, 235, 0.1);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
        }

        .page-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }

        .page-subtitle {
            color: var(--light-text);
            font-size: 1.1rem;
        }

        .appointments-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }

        .appointments-list {
            background: var(--white);
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.1);
            border: 1px solid rgba(37, 99, 235, 0.1);
        }

        .appointment-item {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            border-radius: 1rem;
            margin-bottom: 0.5rem;
        }

        .appointment-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .appointment-item:hover {
            background: #f8fafc;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.1);
        }

        .appointment-avatar {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-right: 1.25rem;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        }

        .appointment-info {
            flex-grow: 1;
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .client-name {
            font-weight: 600;
            color: var(--text-color);
            font-size: 1.1rem;
        }

        .appointment-date {
            font-size: 0.9rem;
            color: var(--light-text);
            background: #f0f7ff;
            padding: 0.4rem 0.8rem;
            border-radius: 2rem;
        }

        .appointment-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--light-text);
        }

        .specialty {
            background: #e0f2fe;
            color: var(--primary-color);
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-weight: 500;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-scheduled {
            background: #e0f2fe;
            color: var(--primary-color);
        }

        .status-completed {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .no-appointments {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--light-text);
        }

        .no-appointments i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            opacity: 0.8;
        }

        .no-appointments h3 {
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fa-solid fa-user-md me-2"></i>
                Consultant Appointments
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fa-solid fa-gauge-high me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">
                            <i class="fa-solid fa-calendar-check me-1"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">
                            <i class="fa-solid fa-envelope me-1"></i> Messages
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="consultantDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-circle me-1"></i> 
                            <?php echo htmlspecialchars($consultant_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../actions/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="appointments-container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <span class="alert-message"><?php echo htmlspecialchars($_GET['success']); ?></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <span class="alert-message"><?php echo htmlspecialchars($_GET['error']); ?></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2 class="page-title">
                <i class="fa-solid fa-calendar me-2"></i>
                Appointments
            </h2>
            <p class="text-muted">View and manage your upcoming appointments</p>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <a href="past_appointments.php" class="btn btn-primary">
                <i class="fas fa-history me-1"></i> View Past Appointments
            </a>
        </div>

        <!-- Filters (Date & Status) -->
        <form class="filter-bar row gx-2 gy-2 mb-4 align-items-end justify-content-start" method="get" action="appointments.php">
            <div class="col-md-auto col-12">
                <label for="filter_date" class="form-label mb-1 fw-semibold">Date</label>
                <input type="date" class="form-control filter-input" id="filter_date" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : ''; ?>">
            </div>
            <div class="col-md-auto col-12">
                <label for="filter_status" class="form-label mb-1 fw-semibold">Status</label>
                <select class="form-select filter-input" id="filter_status" name="filter_status">
                    <option value="all" <?php echo (!isset($_GET['filter_status']) || $_GET['filter_status'] === 'all') ? 'selected' : ''; ?>>All</option>
                    <option value="pending" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] === 'approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    <option value="cancelled" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-auto col-12 d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-primary filter-btn shadow-sm px-4">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                <?php if ((isset($_GET['filter_date']) && $_GET['filter_date'] !== '') || (isset($_GET['filter_status']) && $_GET['filter_status'] !== '' && $_GET['filter_status'] !== 'all')): ?>
                    <a href="appointments.php" class="btn btn-outline-secondary filter-btn px-3">
                        <i class="fa-solid fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <div class="appointments-list">
            <?php if (mysqli_num_rows($appointments) > 0): ?>
                <?php while ($appointment = mysqli_fetch_assoc($appointments)): ?>
                    <div class="appointment-item">
                        <div class="appointment-avatar">
                            <?php echo strtoupper(substr($appointment['client_name'], 0, 1)); ?>
                        </div>
                        <div class="appointment-info">
                            <div class="appointment-header">
                                <span class="client-name"><?php echo htmlspecialchars($appointment['client_name']); ?></span>
                                <span class="appointment-date">
                                    <?php 
                                        $start_time = date('g:i A', strtotime($appointment['slot_time']));
                                        $end_time = date('g:i A', strtotime($appointment['end_time']));
                                        echo date('F j, Y', strtotime($appointment['slot_date'])) . ' at ' . $start_time . ' - ' . $end_time;
                                    ?>
                                </span>
                            </div>
                            <div class="appointment-meta">
                                <span class="specialty"><?php echo htmlspecialchars($appointment['specialty']); ?></span>
                                <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                                <?php if ($appointment['status'] === 'pending'): ?>
                                    <div class="mt-2">
                                        <form method="post" action="../actions/update_booking.php" class="d-inline">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="btn btn-sm btn-success me-2">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="post" action="../actions/update_booking.php" class="d-inline">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="btn btn-sm btn-danger me-2">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($appointment['status'] === 'approved'): ?>
                                    <div class="mt-2">
                                        <form method="post" action="../actions/update_booking.php" class="d-inline">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" class="btn btn-sm btn-warning">
                                                <i class="fas fa-ban"></i> Cancel
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-appointments">
                    <i class="fas fa-calendar"></i>
                    <h3>No Appointments</h3>
                    <p>You don't have any appointments scheduled.</p>
                    <p class="mt-3">
                        <a href="../actions/update_booking_test.php" class="btn btn-primary" target="_blank">Test Email System</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Function to show alert message
    function showAlert(type, message) {
        // Create alert element if it doesn't exist
        let alertDiv = document.getElementById('statusAlert');
        if (!alertDiv) {
            alertDiv = document.createElement('div');
            alertDiv.id = 'statusAlert';
            alertDiv.className = 'alert alert-dismissible fade show';
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
                <span class="alert-message"></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.querySelector('.appointments-container').prepend(alertDiv);
        }
        
        // Update alert content
        const alertMessage = alertDiv.querySelector('.alert-message');
        alertMessage.textContent = message;
        
        // Set alert type
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 5000);
    }
    
    // Function to update appointment status
    function updateAppointmentStatus(appointmentId, status, button) {
        // Show loading state
        const card = button.closest('.appointment-item');
        const originalText = button.innerHTML;
        const actionText = status === 'approved' ? 'Approving...' : 
                         status === 'rejected' ? 'Rejecting...' : 'Cancelling...';
        
        // Disable all action buttons during processing
        const actionButtons = document.querySelectorAll('.btn-action');
        if (actionButtons) {
            actionButtons.forEach(btn => {
                btn.disabled = true;
            });
        }
        
        button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            ${actionText}
        `;
        
        // Add processing class to card
        if (card) {
            card.classList.add('processing');
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('appointment_id', appointmentId);
        formData.append('status', status);
        
        // Debug form data before sending
        console.log('Sending appointment update request:');
        console.log('Appointment ID:', appointmentId);
        console.log('Status:', status);
        
        // Send AJAX request
        console.log('Sending request to:', '../actions/update_booking.php');
        fetch('../actions/update_booking.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data);
            
            if (data.success) {
                // Show success message
                let message = data.message || 'Appointment updated successfully';
                if (data.email_sent === false) {
                    message += ' (Email notification could not be sent)';
                }
                
                showAlert('success', message);
                
                // Reload after a short delay to show the success message
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to update appointment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Error: ' + (error.message || 'Failed to update appointment'));
            
            // Re-enable buttons
            if (actionButtons) {
                actionButtons.forEach(btn => {
                    btn.disabled = false;
                    // Reset button text based on data-status attribute
                    const btnStatus = btn.getAttribute('data-status');
                    if (btnStatus) {
                        btn.innerHTML = btnStatus === 'approved' ? 'Approve' :
                                      btnStatus === 'rejected' ? 'Reject' : 'Cancel';
                    }
                });
            }
            
            // Remove processing class
            if (card) {
                card.classList.remove('processing');
            }
        });
    }
    
    // Add event listeners to status buttons after page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded, initializing appointment buttons...');
        const actionButtons = document.querySelectorAll('.btn-action');
        console.log(`Found ${actionButtons.length} action buttons`);
        
        // Add click handlers to all action buttons
        actionButtons.forEach((button, index) => {
            console.log(`Initializing button ${index + 1}:`, button);
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const appointmentId = this.getAttribute('data-appointment-id');
                const status = this.getAttribute('data-status');
                if (appointmentId && status) {
                    updateAppointmentStatus(appointmentId, status, this);
                }
            });
        });
    });
    </script>
    </body>
    </html>