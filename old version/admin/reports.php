<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php?error=Please login as admin");
    exit;
}
require_once '../config/db.php';

// Get date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch appointment statistics
$sql = "SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN a.status = 'confirmed' THEN 1 ELSE 0 END) as approved_appointments,
            SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_appointments,
            SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
        FROM appointments a
        JOIN availability av ON a.availability_id = av.id
        WHERE av.slot_date BETWEEN '$start_date' AND '$end_date'";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $sql));

// Fetch appointments by consultant
$sql = "SELECT 
            cu.name as consultant_name,
            COUNT(*) as total_appointments,
            SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) as approved_appointments,
            SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_appointments,
            SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
        FROM appointments a
        JOIN consultants c ON a.consultant_id = c.id
        JOIN users cu ON c.user_id = cu.id
        JOIN availability av ON a.availability_id = av.id
        WHERE av.slot_date BETWEEN '$start_date' AND '$end_date'
        GROUP BY cu.name
        ORDER BY total_appointments DESC";
$consultant_stats = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #23272f 0%, #2d3a5a 100%);
            color: #e0e6f7;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: #23272f;
            border-radius: 2rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            padding: 2rem;
        }
        h1 {
            color: #4f8cff;
            margin-bottom: 2rem;
        }
        .filter-bar {
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .filter-bar input[type='date'] {
            padding: 0.7rem 1rem;
            border-radius: 1rem;
            border: none;
            background: #2d3a5a;
            color: #e0e6f7;
            font-size: 1rem;
        }
        .filter-bar button {
            padding: 0.7rem 1.5rem;
            border-radius: 1rem;
            border: none;
            background: #4f8cff;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .filter-bar button:hover {
            background: #2563eb;
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
        table {
            width: 100%;
            border-collapse: collapse;
            background: #2d3a5a;
            border-radius: 1.2rem;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.10);
        }
        th, td {
            padding: 1rem;
            text-align: left;
        }
        th {
            background: #23272f;
            color: #4f8cff;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background: #26304a;
        }
        tr:hover {
            background: #34405a;
        }
        .export-btn {
            margin-top: 2rem;
            display: inline-block;
            background: #4f8cff;
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
        .export-btn:hover {
            background: #2563eb;
        }
        .nav-bar {
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .nav-btn {
            padding: 0.7rem 1.5rem;
            border-radius: 1rem;
            border: none;
            background: #2d3a5a;
            color: #e0e6f7;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
        }
        .nav-btn:hover {
            background: #34405a;
        }
        
        /* Admin Navbar Styles */
        .admin-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #232834;
            border-bottom: 2px solid #2563eb;
            padding: 0.8rem 2rem;
            min-height: 60px;
        }

        .admin-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 1.5rem;
        }

        .admin-title {
            color: #3b82f6;
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .admin-nav-center {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            gap: 1.5rem;
        }

        .admin-navbar .nav-link {
            color: #e0e7ef;
            font-weight: 500;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: color 0.2s;
            text-decoration: none;
        }

        .admin-navbar .nav-link:hover, 
        .admin-navbar .nav-link.active {
            color: #3b82f6;
        }

        .admin-user-area {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-left: 1.5rem;
        }

        .admin-navbar .admin-name {
            color: #fff;
            font-size: 1.05rem;
            font-weight: 600;
        }

        .logout-btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.4rem 1.2rem;
            background: #f87171;
            border: none;
            color: #fff;
            transition: background 0.2s;
            text-decoration: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .logout-btn:hover {
            background: #ef4444;
            color: #fff;
        }
    </style>
</head>
<body>
    <nav class="admin-navbar">
        <div class="admin-logo">
            <i class="fas fa-user-shield fa-lg" style="color:#3b82f6;"></i>
            <span class="admin-title">Admin Panel</span>
        </div>
        <div class="admin-nav-center">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_users.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
            <a href="manage_consultants.php" class="nav-link"><i class="fas fa-user-tie"></i> Consultants</a>
            <a href="manage_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages</a>
            <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
        </div>
        <div class="admin-user-area">
            <span class="admin-name">Admin</span>
            <a href="../actions/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
    <div class="container">
        <h1><i class="fa-solid fa-chart-bar"></i> Admin Reports</h1>    
        <form class="filter-bar" method="get">
            <input type="date" name="start_date" value="<?php echo $start_date; ?>">
            <input type="date" name="end_date" value="<?php echo $end_date; ?>">
            <button type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        </form>
        <div class="stats-grid">
            <div class="stat-card">
                <h2><?php echo $stats['total_appointments']; ?></h2>
                <p>Total Appointments</p>
            </div>
            <div class="stat-card">
                <h2><?php echo $stats['approved_appointments']; ?></h2>
                <p>Approved Appointments</p>
            </div>
            <div class="stat-card">
                <h2><?php echo $stats['pending_appointments']; ?></h2>
                <p>Pending Appointments</p>
            </div>
            <div class="stat-card">
                <h2><?php echo $stats['cancelled_appointments']; ?></h2>
                <p>Cancelled Appointments</p>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Consultant</th>
                    <th>Total Appointments</th>
                    <th>Approved</th>
                    <th>Pending</th>
                    <th>Cancelled</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($consultant_stats) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($consultant_stats)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['consultant_name']); ?></td>
                        <td><?php echo $row['total_appointments']; ?></td>
                        <td><?php echo $row['approved_appointments']; ?></td>
                        <td><?php echo $row['pending_appointments']; ?></td>
                        <td><?php echo $row['cancelled_appointments']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="no-data">No data found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <a href="export_reports.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="export-btn"><i class="fa-solid fa-download"></i> Export Report</a>
    </div>
</body>
</html>