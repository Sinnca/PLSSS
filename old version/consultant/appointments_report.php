<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/auth.php';


// Check if user is admin
$is_admin = ($_SESSION['user_role'] === 'admin');
$is_consultant = ($_SESSION['user_role'] === 'consultant');

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$consultant_id = isset($_GET['consultant_id']) ? intval($_GET['consultant_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Initialize the WHERE clause parts
$where_conditions = ["av.slot_date <= CURRENT_DATE()"];
$params = [];
$param_types = "";

// Add date range filters
if (!empty($start_date) && !empty($end_date)) {
    $where_conditions[] = "av.slot_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $param_types .= "ss";
}

// Add status filter
if ($status !== 'all') {
    $where_conditions[] = "a.status = ?";
    $params[] = $status;
    $param_types .= "s";
}

// Apply user-specific filters
if (!$is_admin) {
    if ($is_consultant) {
        $consultant_id = $_SESSION['consultant_id'] ?? 0;
        $where_conditions[] = "a.consultant_id = ?";
        $params[] = $consultant_id;
        $param_types .= "i";
    } else {
        // Regular user can only see their own appointments
        $where_conditions[] = "a.user_id = ?";
        $params[] = $_SESSION['user_id'];
        $param_types .= "i";
    }
} else {
    // Admin can filter by specific consultant or user
    if ($consultant_id > 0) {
        $where_conditions[] = "a.consultant_id = ?";
        $params[] = $consultant_id;
        $param_types .= "i";
    }
    
    if ($user_id > 0) {
        $where_conditions[] = "a.user_id = ?";
        $params[] = $user_id;
        $param_types .= "i";
    }
}

// Construct the WHERE clause
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM appointments a 
              JOIN availability av ON a.availability_id = av.id 
              $where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}

$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_appointments = $total_row['total'];

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_appointments / $per_page);

// Build the SQL query for appointments with pagination
$sql = "SELECT a.*, 
        u.name as client_name, 
        u.email as client_email,
        c.specialty, 
        c.hourly_rate,
        c.currency,
        cu.name as consultant_name,
        cu.email as consultant_email,
        av.duration,
        av.slot_date,
        av.slot_time
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN consultants c ON a.consultant_id = c.id
        JOIN users cu ON c.user_id = cu.id
        JOIN availability av ON a.availability_id = av.id
        $where_clause
        ORDER BY av.slot_date DESC, av.slot_time DESC
        LIMIT ?, ?";

// Add pagination parameters
$params[] = $offset;
$params[] = $per_page;
$param_types .= "ii";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get consultants for the filter dropdown
$consultants_sql = "SELECT c.id, u.name FROM consultants c JOIN users u ON c.user_id = u.id ORDER BY u.name";
$consultants_result = $conn->query($consultants_sql);

// Get users for the filter dropdown (admin only)
$users_sql = "SELECT id, name, email FROM users WHERE role = 'user' ORDER BY name";
$users_result = $conn->query($users_sql);

// Set $consultant_name for navbar
$consultant_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Consultant';

$page_title = "Past Appointments Report";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Consultant Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --primary-light: #e0e7ff;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --text-dark: #1e293b;
    --text-muted: #6b7280;
    --border-light: #e5e7eb;
    --bg-light: #f9fafb;
    --bg-gray: #f3f4f6;
    --white: #ffffff;
    --shadow: 0 8px 32px rgba(67,97,238,0.10);
    --shadow-soft: 0 2px 12px rgba(37,99,235,0.09);
    --radius: 1.4rem;
    --primary-color: #2563eb;
    --primary-light: #3b82f6;
    --primary-dark: #1d4ed8;
    --text-color: #1e293b;
    --light-text: #64748b;
    --background: #f0f7ff;
    --white: #ffffff;
    --hover-bg: #e6f0ff;
    --border-color: #dbeafe;
    --gradient-start: #2563eb;
    --gradient-end: #1d4ed8;
    --glass-bg: rgba(255,255,255,0.55);
    --glass-blur: 14px;
}
body {
    background: linear-gradient(120deg, #e0e7ff 0%, #f8fafc 100%);
    min-height: 100vh;
    color: var(--text-dark);
    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
    font-size: 1.08rem;
    letter-spacing: 0.01em;
}
.fade-in {
    animation: fadeIn 0.7s ease-in;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: none; }
}
.header-section {
    background: linear-gradient(90deg, #2563eb 60%, #3b82f6 100%);
    color: #fff;
    border-radius: 1.6rem 1.6rem 0 0;
    box-shadow: var(--shadow);
    padding: 2.8rem 2rem 2.2rem 2rem;
    margin-bottom: 2.2rem;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.3s;
    animation: fadeIn 0.8s;
}
.header-section .page-title {
    color: #fff;
    font-size: 2.2rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    letter-spacing: -1px;
    text-shadow: 0 2px 8px rgba(37,99,235,0.10);
}
.header-section .page-subtitle {
    color: #e0e7ff;
    font-size: 1.13rem;
    font-weight: 500;
    margin-bottom: 0;
}
.filters {
    background: var(--glass-bg);
    border-radius: var(--radius);
    box-shadow: var(--shadow-soft);
    padding: 2rem 1.5rem 1.5rem 1.5rem;
    margin-bottom: 2.2rem;
    border: 1.5px solid var(--border-light);
    display: block;
    backdrop-filter: blur(var(--glass-blur));
    -webkit-backdrop-filter: blur(var(--glass-blur));
    animation: fadeIn 0.9s;
    transition: box-shadow 0.3s, background 0.3s;
}
.filters label {
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 0.2rem;
}
.filters input, .filters select {
    border-radius: 1.1rem !important;
    border: 1.5px solid var(--border-light) !important;
    padding: 0.7rem 1rem !important;
    font-size: 1.06rem !important;
    background: rgba(248,250,252,0.95) !important;
    color: var(--text-dark) !important;
    margin-bottom: 0.7rem;
    box-shadow: 0 1px 4px rgba(37,99,235,0.04);
    transition: box-shadow 0.2s, border 0.2s;
}
.filters input:focus, .filters select:focus {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 3px #c7d2fe !important;
    background: #fff !important;
}
.btn-primary, .btn-secondary {
    font-weight: 600;
    border-radius: 1.5rem;
    padding: 0.65rem 2.2rem;
    box-shadow: 0 2px 12px rgba(37,99,235,0.09);
    transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.1s;
    font-size: 1.04rem;
}
.btn-primary {
    background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
    border: none;
    color: #fff;
}
.btn-primary:hover, .btn-primary:focus {
    background: linear-gradient(90deg, var(--gradient-end), var(--gradient-start));
    color: #fff;
    box-shadow: 0 6px 18px rgba(67,97,238,0.16);
    transform: translateY(-2px) scale(1.04);
}
.btn-secondary {
    background: #e0e7ff;
    color: var(--primary-dark);
    border: none;
}
.btn-secondary:hover, .btn-secondary:focus {
    background: #c7d2fe;
    color: var(--primary-dark);
    box-shadow: 0 2px 8px rgba(37,99,235,0.10);
}
.card, .card-body {
    border-radius: var(--radius);
    box-shadow: var(--shadow-soft);
    animation: fadeIn 0.9s;
    background: var(--white);
    transition: box-shadow 0.3s, background 0.3s;
}
.card-header {
    background: linear-gradient(90deg, #2563eb 60%, #3b82f6 100%);
    color: #fff;
    padding: 1.5rem 1.5rem 1rem 1.5rem;
    border-bottom: none;
    font-weight: 700;
    font-size: 1.2rem;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(67,97,238,0.08);
    border-radius: var(--radius) var(--radius) 0 0;
}
.table {
    border-radius: 1.2rem;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(67,97,238,0.06);
    animation: fadeIn 1s;
}
.table th, .table td {
    vertical-align: middle;
    padding: 1.18rem 0.8rem;
    font-size: 1.06rem;
    border: none;
    background: transparent;
    transition: background 0.2s;
}
.table th {
    background: #e0e7ff;
    color: #2563eb;
    font-weight: 700;
}
.table-striped > tbody > tr:nth-of-type(odd) {
    background-color: #f8fafc;
}
.table-hover tbody tr {
    transition: background 0.22s, box-shadow 0.18s;
}
.table-hover tbody tr:hover {
    background-color: #e0e7ff;
    box-shadow: 0 2px 8px rgba(37,99,235,0.09);
}
.badge {
    border-radius: 1em;
    padding: 0.38em 1.1em;
    font-size: 0.98em;
    font-weight: 600;
    box-shadow: 0 1px 4px rgba(37,99,235,0.06);
    letter-spacing: 0.01em;
}
.pagination {
    justify-content: center;
    margin-top: 2.5rem;
    gap: 0.3rem;
    animation: fadeIn 1.1s;
}
.page-link {
    color: #2563eb;
    border-radius: 1.2rem !important;
    margin: 0 0.2rem;
    border: none;
    background: #e0e7ff;
    font-weight: 600;
    transition: background 0.2s, color 0.2s, box-shadow 0.2s;
    min-width: 38px;
    min-height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 4px rgba(37,99,235,0.06);
}
.page-link.active, .page-link:hover {
    background: #2563eb;
    color: #fff;
    box-shadow: 0 3px 12px rgba(37,99,235,0.13);
}
@media (max-width: 700px) {
    .header-section {
        padding: 1.2rem 0.7rem 1.2rem 0.7rem;
    }
    .header-section .page-title {
        font-size: 1.3rem;
    }
    .filters {
        padding: 1.1rem 0.7rem 1.1rem 0.7rem;
    }
    .table th, .table td {
        padding: 0.7rem 0.4rem;
        font-size: 0.97rem;
    }
    .card-body {
        padding: 1rem 0.5rem;
    }
}
.navbar {
    background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.15);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}
.navbar-brand, .nav-link {
    color: white !important;
    transition: all 0.3s ease;
}
.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    letter-spacing: 0.5px;
}
.nav-link:hover {
    transform: translateY(-2px);
    color: rgba(255, 255, 255, 0.95) !important;
}
.dropdown-menu {
    border: none;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.15);
    border-radius: 1rem;
    padding: 0.5rem;
    animation: fadeIn 0.3s ease-out;
}
.dropdown-item {
    padding: 0.8rem 1.2rem;
    border-radius: 0.7rem;
    transition: all 0.3s ease;
}
.dropdown-item:hover {
    background-color: var(--hover-bg);
    color: var(--primary-color);
    transform: translateX(5px);
}
.dropdown-item i {
    color: var(--primary-color);
    transition: transform 0.3s ease;
}
.dropdown-item:hover i {
    transform: scale(1.2);
}
.modal-content {
    border-radius: var(--radius);
    box-shadow: 0 8px 32px rgba(37,99,235,0.13);
    animation: fadeIn 0.7s;
    background: var(--glass-bg);
    backdrop-filter: blur(var(--glass-blur));
}

            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #e0e7ff;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text-dark: #1e293b;
            --text-muted: #6b7280;
            --border-light: #e5e7eb;
            --bg-light: #f9fafb;
            --bg-gray: #f3f4f6;
            --white: #ffffff;
            --shadow: 0 4px 24px rgba(67,97,238,0.10);
            --radius: 1.2rem;
            /* New navbar variables */
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --text-color: #1e293b;
            --light-text: #64748b;
            --background: #f0f7ff;
            --white: #ffffff;
            --hover-bg: #e6f0ff;
            --border-color: #dbeafe;
            --gradient-start: #2563eb;
            --gradient-end: #1d4ed8;
        }
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
            color: var(--text-dark);
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
        }
        .header-section {
            background: linear-gradient(90deg, #2563eb 60%, #3b82f6 100%);
            color: #fff;
            border-radius: 1.5rem 1.5rem 0 0;
            box-shadow: var(--shadow);
            padding: 2.5rem 2rem 2rem 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        .header-section .page-title {
            color: #fff;
            font-size: 2.1rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
            text-shadow: 0 2px 8px rgba(37,99,235,0.10);
        }
        .header-section .page-subtitle {
            color: #e0e7ff;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0;
        }
        .card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(90deg, #2563eb 60%, #3b82f6 100%);
            color: #fff;
            padding: 1.5rem 1.5rem 1rem 1.5rem;
            border-bottom: none;
            font-weight: 700;
            font-size: 1.2rem;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(67,97,238,0.08);
        }
        .card-title {
            color: #fff;
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0;
        }
        .card-body {
            padding: 2rem 1.5rem;
        }
        .btn-primary {
            background: linear-gradient(90deg, #4361ee, #2563eb);
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 2rem;
            box-shadow: 0 2px 8px rgba(67,97,238,0.10);
            transition: background 0.2s, transform 0.2s;
        }
        .btn-primary:hover {
            background: #3a56d4;
            transform: translateY(-2px) scale(1.04);
        }
        input, select {
            border-radius: 0.7rem !important;
            border: 1.5px solid var(--border-light) !important;
            padding: 0.7rem 1rem !important;
            font-size: 1rem !important;
            background: #f8fafc !important;
            color: var(--text-dark) !important;
            margin-bottom: 0.7rem;
        }
        input:focus, select:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 2px #c7d2fe !important;
        }
        .table {
            background: #fff;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(67,97,238,0.06);
        }
        .table th {
            background: #e0e7ff;
            color: #2563eb;
            font-weight: 700;
            border: none;
        }
        .table td {
            border: none;
            vertical-align: middle;
        }
        .badge {
            border-radius: 1em;
            padding: 0.3em 1em;
            font-size: 0.9em;
            font-weight: 600;
        }
        .badge-success {
            background: #d1fae5;
            color: #059669;
        }
        .badge-danger {
            background: #fee2e2;
            color: #dc2626;
        }
        .badge-warning {
            background: #fef9c3;
            color: #f59e0b;
        }
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }
        .page-link {
            color: #2563eb;
            border-radius: 0.7rem !important;
            margin: 0 0.2rem;
            border: none;
            background: #e0e7ff;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
        }
        .page-link.active, .page-link:hover {
            background: #2563eb;
            color: #fff;
        }
        @media (max-width: 700px) {
            .header-section {
                padding: 1.2rem 0.7rem 1.2rem 0.7rem;
            }
            .header-section .page-title {
                font-size: 1.3rem;
            }
            .card-body {
                padding: 1rem 0.5rem;
            }
        }
        .navbar {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .navbar-brand, .nav-link {
            color: white !important;
            transition: all 0.3s ease;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
        }
        .nav-link:hover {
            transform: translateY(-2px);
            color: rgba(255, 255, 255, 0.95) !important;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.15);
            border-radius: 1rem;
            padding: 0.5rem;
            animation: fadeIn 0.3s ease-out;
        }
        .dropdown-item {
            padding: 0.8rem 1.2rem;
            border-radius: 0.7rem;
            transition: all 0.3s ease;
        }
        .dropdown-item:hover {
            background-color: var(--hover-bg);
            color: var(--primary-color);
            transform: translateX(5px);
        }
        .dropdown-item i {
            color: var(--primary-color);
            transition: transform 0.3s ease;
        }
        .dropdown-item:hover i {
            transform: scale(1.2);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fa-solid fa-user-md me-2"></i>
                Consultant Message
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
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" href="messages.php">
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

    <div class="container mt-4">
        <div class="header-section mb-4">
            <div>
                <h1 class="page-title"><?php echo $page_title; ?></h1>
                <p class="page-subtitle">View and filter your past appointments and reports</p>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="filters">
            <form method="GET" action="" class="row">
                <div class="col-md-3 mb-3">
                    <label for="start_date">Start Date</label>
                    <input type="date" class="form-control date-picker" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="end_date">End Date</label>
                    <input type="date" class="form-control date-picker" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <?php if ($is_admin || $is_consultant): ?>
                <div class="col-md-3 mb-3">
                    <?php if ($is_admin): ?>
                    <label for="consultant_id">Consultant</label>
                    <select class="form-control" id="consultant_id" name="consultant_id">
                        <option value="0">All Consultants</option>
                        <?php while ($consultant = $consultants_result->fetch_assoc()): ?>
                        <option value="<?php echo $consultant['id']; ?>" <?php echo $consultant_id == $consultant['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($consultant['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($is_admin): ?>
                <div class="col-md-3 mb-3">
                    <label for="user_id">Client</label>
                    <select class="form-control" id="user_id" name="user_id">
                        <option value="0">All Clients</option>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="appointments_report.php" class="btn btn-secondary ml-2">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </form>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-check"></i> 
                    Found <?php echo $total_appointments; ?> past appointment(s)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0" id="appointments-table">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Date & Time</th>
                                    <th>Client</th>
                                    <th>Consultant</th>
                                    <th>Duration</th>
                                    <th>Rate</th>
                                    <th>Status</th>
                                    <?php if ($is_admin): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $result->fetch_assoc()): ?>
                                <tr class="<?php echo $appointment['status']; ?>">
                                    <td><?php echo $appointment['id']; ?></td>
                                    <td>
                                        <?php 
                                        echo date('M d, Y', strtotime($appointment['slot_date'])) . '<br>';
                                        echo date('h:i A', strtotime($appointment['slot_time']));
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($appointment['client_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($appointment['client_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($appointment['consultant_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($appointment['specialty']); ?></small>
                                    </td>
                                    <td><?php echo $appointment['duration'] ?? 60; ?> mins</td>
                                    <td><?php echo $appointment['currency'] . ' ' . $appointment['hourly_rate']; ?></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        $status_style = '';
                                        switch ($appointment['status']) {
                                            case 'approved':
                                                $status_class = 'badge-success';
                                                $status_style = 'color: #222; background: #d4edda;'; // black text, green bg
                                                break;
                                            case 'pending':
                                                $status_class = 'badge-warning';
                                                $status_style = 'color: #222; background: #fff3cd;'; // black text, yellow bg
                                                break;
                                            case 'cancelled':
                                                $status_class = 'badge-danger';
                                                $status_style = 'color: #222; background: #f8d7da;'; // black text, red bg
                                                break;
                                            default:
                                                $status_class = 'badge-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>" style="<?php echo $status_style; ?>"><?php echo ucfirst($appointment['status']); ?></span>
                                    </td>
                                    <?php if ($is_admin): ?>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info" 
                                                    data-toggle="modal" 
                                                    data-target="#appointmentDetailsModal" 
                                                    data-appointment-id="<?php echo $appointment['id']; ?>"
                                                    data-client="<?php echo htmlspecialchars($appointment['client_name']); ?>"
                                                    data-consultant="<?php echo htmlspecialchars($appointment['consultant_name']); ?>"
                                                    data-date="<?php echo $appointment['slot_date']; ?>"
                                                    data-time="<?php echo $appointment['slot_time']; ?>"
                                                    data-status="<?php echo $appointment['status']; ?>"
                                                    data-created="<?php echo $appointment['created_at']; ?>"
                                                    data-updated="<?php echo $appointment['updated_at']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($appointment['status'] != 'cancelled'): ?>
                                            <a href="../admin/edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info m-3">
                        No past appointments found for the selected filters.
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo getQueryParams(['page']); ?>" aria-label="First">
                                <span aria-hidden="true">&laquo;&laquo;</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page - 1) . getQueryParams(['page']); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i . getQueryParams(['page']); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page + 1) . getQueryParams(['page']); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages . getQueryParams(['page']); ?>" aria-label="Last">
                                <span aria-hidden="true">&raquo;&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Appointment Details Modal -->
    <div class="modal fade" id="appointmentDetailsModal" tabindex="-1" role="dialog" aria-labelledby="appointmentDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appointmentDetailsModalLabel">Appointment Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Client:</strong> <span id="modal-client"></span></p>
                            <p><strong>Consultant:</strong> <span id="modal-consultant"></span></p>
                            <p><strong>Date:</strong> <span id="modal-date"></span></p>
                            <p><strong>Time:</strong> <span id="modal-time"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> <span id="modal-status"></span></p>
                            <p><strong>Created:</strong> <span id="modal-created"></span></p>
                            <p><strong>Last Updated:</strong> <span id="modal-updated"></span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
            // Initialize date pickers
            $(".date-picker").flatpickr({
                dateFormat: "Y-m-d",
                allowInput: true
            });
            
            // Initialize appointment details modal
            $('#appointmentDetailsModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var modal = $(this);
                
                // Format date and time for display
                var appointmentDate = new Date(button.data('date'));
                var formattedDate = appointmentDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                
                var timeStr = button.data('time');
                var timeParts = timeStr.split(':');
                var hours = parseInt(timeParts[0]);
                var minutes = timeParts[1];
                var ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12;
                var formattedTime = hours + ':' + minutes + ' ' + ampm;
                
                // Format created and updated timestamps
                var createdAt = new Date(button.data('created'));
                var updatedAt = new Date(button.data('updated'));
                var formattedCreated = createdAt.toLocaleString();
                var formattedUpdated = updatedAt.toLocaleString();
                
                // Update modal content
                modal.find('#modal-client').text(button.data('client'));
                modal.find('#modal-consultant').text(button.data('consultant'));
                modal.find('#modal-date').text(formattedDate);
                modal.find('#modal-time').text(formattedTime);
                
                // Format status with badge
                var status = button.data('status');
                var statusClass = '';
                switch (status) {
                    case 'approved':
                        statusClass = 'badge-success';
                        break;
                    case 'pending':
                        statusClass = 'badge-warning';
                        break;
                    case 'cancelled':
                        statusClass = 'badge-danger';
                        break;
                    default:
                        statusClass = 'badge-secondary';
                }
                modal.find('#modal-status').html('<span class="badge ' + statusClass + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>');
                
                modal.find('#modal-created').text(formattedCreated);
                modal.find('#modal-updated').text(formattedUpdated);
            });
        });
        
        // Print report function
        function printReport() {
            window.print();
        }
        
        // Export to CSV function
        function exportCSV() {
            window.location.href = '../actions/export_appointments.php' + window.location.search;
        }
    </script>
</body>
</html>

<?php
// Helper function to maintain query parameters
function getQueryParams($exclude = []) {
    $params = [];
    foreach ($_GET as $key => $value) {
        if (!in_array($key, $exclude) && $value !== '') {
            $params[] = $key . '=' . urlencode($value);
        }
    }
    return !empty($params) ? '&' . implode('&', $params) : '';
}
?>