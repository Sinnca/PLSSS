<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php?error=Please login as admin");
    exit;
}
require_once '../config/db.php';

// Example: Fetch some stats
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$total_consultants = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='consultant'"))['count'];
$total_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments"))['count'];
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status='pending'"))['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #23272f 0%, #2d3a5a 100%);
            color: #e0e6f7;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
        }
        .dashboard-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 2rem;
            background: #23272f;
            border-radius: 2rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
        }
        .dashboard-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2.5rem;
        }
        .dashboard-header i {
            font-size: 2.5rem;
            color: #4f8cff;
        }
        .dashboard-header h1 {
            color: #4f8cff;
            font-size: 2.2rem;
            margin: 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: #2d3a5a;
            border-radius: 1.2rem;
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.10);
        }
        .stat-card h2 {
            color: #4f8cff;
            font-size: 2.2rem;
            margin: 0 0 0.5rem 0;
        }
        .stat-card p {
            color: #8ca3f8;
            font-size: 1.1rem;
            margin: 0;
        }
        .quick-links {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }
        .quick-link {
            background: #2d3a5a;
            color: #e0e6f7;
            border-radius: 1rem;
            padding: 1.2rem 2rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.10);
            transition: background 0.2s, color 0.2s;
        }
        .quick-link i {
            color: #4f8cff;
            font-size: 1.3rem;
        }
        .quick-link:hover {
            background: #4f8cff;
            color: #fff;
        }
        .quick-link:hover i {
            color: #fff;
        }
        .logout-btn {
            margin-top: 2.5rem;
            display: inline-block;
            background: #ff6b6b;
            color: #fff;
            border: none;
            border-radius: 1rem;
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: #d90429;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <i class="fa-solid fa-user-shield"></i>
            <h1>Admin Dashboard</h1>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <h2><?php echo $total_users; ?></h2>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <h2><?php echo $total_consultants; ?></h2>
                <p>Total Consultants</p>
            </div>
            <div class="stat-card">
                <h2><?php echo $total_appointments; ?></h2>
                <p>Total Appointments</p>
            </div>
            <div class="stat-card">
                <h2><?php echo $total_pending; ?></h2>
                <p>Pending Appointments</p>
            </div>
        </div>
        <div class="quick-links">
            <a href="manage_users.php" class="quick-link"><i class="fa-solid fa-users"></i> Manage Users</a>
            <a href="manage_consultants.php" class="quick-link"><i class="fa-solid fa-user-tie"></i> Manage Consultants</a>
            <a href="manage_appointments.php" class="quick-link"><i class="fa-solid fa-calendar-check"></i> Manage Appointments</a>
            <a href="messages.php" class="quick-link"><i class="fa-solid fa-envelope"></i> User Messages</a>
            <a href="reports.php" class="quick-link"><i class="fa-solid fa-chart-bar"></i> Reports</a>
        </div>
        <a href="../actions/logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</body>
</html>