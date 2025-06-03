<?php
session_start();

// Debug session data
error_log("=== MANAGE APPOINTMENTS ===");
error_log("Session data: " . print_r($_SESSION, true));

// Allow both admin and consultant roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'consultant'])) {
    $error_msg = "Accès non autorisé - " . 
                "User ID: " . ($_SESSION['user_id'] ?? 'not set') . 
                ", Role: " . ($_SESSION['user_role'] ?? 'not set');
    error_log($error_msg);
    header("Location: login.php?error=Please login as admin or consultant");
    exit;
}

require_once '../config/db.php';
error_log("User authenticated - ID: {$_SESSION['user_id']}, Role: {$_SESSION['user_role']}");

// If user is a consultant, only show their own appointments
$consultant_where = "";
if ($_SESSION['user_role'] === 'consultant') {
    $consultant_id = intval($_SESSION['user_id']);
    $consultant_where = " AND a.consultant_id = $consultant_id";
}

// Handle delete appointment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_appointment_id'])) {
    $appointment_id = intval($_POST['delete_appointment_id']);
    // Optionally, free up the slot in availability
    $get_availability = mysqli_query($conn, "SELECT availability_id FROM appointments WHERE id = $appointment_id");
    if ($row = mysqli_fetch_assoc($get_availability)) {
        $availability_id = $row['availability_id'];
        mysqli_query($conn, "UPDATE availability SET is_booked = 0 WHERE id = $availability_id");
    }
    mysqli_query($conn, "DELETE FROM appointments WHERE id = $appointment_id");
    header("Location: manage_appointments.php?success=Appointment deleted successfully");
    exit;
}

// Handle search and date/time filter
$search = trim($_GET['search'] ?? '');
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';
$where = '';
$filters = [];
if ($search !== '') {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $filters[] = "(u.name LIKE '%$search_esc%' OR u.email LIKE '%$search_esc%' OR a.status LIKE '%$search_esc%' OR cu.name LIKE '%$search_esc%')";
}
if ($start_date !== '' && $end_date !== '') {
    $filters[] = "(a.appointment_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "')";
} elseif ($start_date !== '') {
    $filters[] = "(a.appointment_date >= '" . mysqli_real_escape_string($conn, $start_date) . "')";
} elseif ($end_date !== '') {
    $filters[] = "(a.appointment_date <= '" . mysqli_real_escape_string($conn, $end_date) . "')";
}
if ($start_time !== '' && $end_time !== '') {
    $filters[] = "(a.appointment_time BETWEEN '" . mysqli_real_escape_string($conn, $start_time) . "' AND '" . mysqli_real_escape_string($conn, $end_time) . "')";
} elseif ($start_time !== '') {
    $filters[] = "(a.appointment_time >= '" . mysqli_real_escape_string($conn, $start_time) . "')";
} elseif ($end_time !== '') {
    $filters[] = "(a.appointment_time <= '" . mysqli_real_escape_string($conn, $end_time) . "')";
}
if (count($filters) > 0) {
    $where = 'WHERE ' . implode(' AND ', $filters);
}

// Add consultant filter if needed
if (!empty($consultant_where)) {
    $where .= $where ? $consultant_where : ' WHERE ' . ltrim($consultant_where, ' AND ');
}

// Fetch appointments
$sql = "SELECT a.id, u.name as user_name, u.email as user_email, cu.name as consultant_name, 
        av.slot_date, av.slot_time, a.status, a.created_at 
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN consultants c ON a.consultant_id = c.id
        JOIN users cu ON c.user_id = cu.id
        JOIN availability av ON a.availability_id = av.id
        $where
        ORDER BY (CASE WHEN a.status = 'pending' THEN 0 ELSE 1 END), av.slot_date DESC, av.slot_time DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Appointments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
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
        .search-bar {
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
        }
        .search-bar input[type='text'] {
            flex: 1;
            padding: 0.7rem 1rem;
            border-radius: 1rem;
            border: none;
            background: #2d3a5a;
            color: #e0e6f7;
            font-size: 1rem;
        }
        .search-bar button {
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
        .search-bar button:hover {
            background: #2563eb;
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
        .actions {
            display: flex;
            gap: 1rem;
        }
        .action-btn {
            background: none;
            border: none;
            color: #aab8d0;
            cursor: pointer;
            padding: 5px 8px;
            border-radius: 4px;
            transition: all 0.2s;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            margin: 0 2px;
        }
        .action-btn:hover {
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
        }
        .confirm-btn:hover {
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }
        .cancel-btn:hover {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }
        .no-appointments {
            color: #8ca3f8;
            text-align: center;
            padding: 2rem 0;
        }
        .nav-bar {
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
        }
        .nav-btn {
            padding: 0.7rem 1.5rem;
            border-radius: 1rem;
            border: none;
            background: #2d3a5a;
            color: #4f8cff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .nav-btn:hover {
            background: #34405a;
        }
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-pending {
            background: #f59e0b;
            color: #fff;
        }
        .status-confirmed {
            background: #10b981;
            color: #fff;
        }
        .status-completed {
            background: #3b82f6;
            color: #fff;
        }
        .status-cancelled {
            background: #ef4444;
            color: #fff;
        }
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-dialog {
            position: relative;
            width: auto;
            margin: 0.5rem;
            pointer-events: none;
        }
        .modal-dialog-centered {
            display: flex;
            align-items: center;
            min-height: calc(100% - 1rem);
        }
        .modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            pointer-events: auto;
            background-color: #23272f;
            background-clip: padding-box;
            border: 1px solid rgba(0,0,0,0.2);
            border-radius: 1.5rem;
            outline: 0;
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid #2d3a5a;
        }
        .modal-title {
            color: #4f8cff;
            margin: 0;
        }
        .modal-body {
            padding: 1.5rem;
            color: #e0e6f7;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            padding: 1.5rem;
            border-top: 1px solid #2d3a5a;
            gap: 1rem;
        }
        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 0.8rem;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-secondary {
            background: #2d3a5a;
            color: #e0e6f7;
        }
        .btn-secondary:hover {
            background: #34405a;
        }
        .btn-danger {
            background: #ef4444;
            color: #fff;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .text-danger {
            color: #ef4444;
        }
        .admin-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #232834;
            border-bottom: 2px solid #2563eb;
            min-height: 60px;
            padding-left: 2rem;
            padding-right: 2rem;
        }
        .admin-logo .admin-title {
            color: #3b82f6;
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .admin-nav-center {
            flex: 1;
            justify-content: center !important;
            display: flex !important;
        }
        .admin-navbar .nav-link {
            color: #e0e7ef;
            font-weight: 500;
            font-size: 1.05rem;
            margin-right: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: color 0.2s;
            text-decoration: none;
        }
        .admin-navbar .nav-link:hover, .admin-navbar .nav-link.active {
            color: #3b82f6;
        }
        .admin-navbar .admin-name {
            color: #fff;
            font-size: 1.05rem;
        }
        .logout-btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.4rem 1.2rem;
            background: #f87171;
            border: none;
            color: #fff;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: #ef4444;
            color: #fff;
        }
    </style>
</head>
<body>
    <nav class="admin-navbar d-flex align-items-center justify-content-between px-4 py-2">
        <div class="admin-logo d-flex align-items-center gap-2 me-4">
            <i class="fas fa-user-shield fa-lg" style="color:#3b82f6;"></i>
            <span class="admin-title">Admin Panel</span>
        </div>
        <div class="admin-nav-center d-flex align-items-center gap-4 mx-auto">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_users.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
            <a href="manage_consultants.php" class="nav-link"><i class="fas fa-user-tie"></i> Consultants</a>
            <a href="manage_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages</a>
            <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a>
        </div>
        <div class="d-flex align-items-center gap-3 ms-4">
            <span class="admin-name fw-bold">Admin</span>
            <a href="../actions/logout.php" class="btn btn-danger logout-btn">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </nav>
    <div class="container">
        <h1><i class="fa-solid fa-calendar-check"></i> Manage Appointments</h1>
        <form class="search-bar" method="get">
            <input type="text" name="search" placeholder="Search by client, consultant, email, or status..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Consultant</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($appointment = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($appointment['user_name']); ?><br>
                            <small><?php echo htmlspecialchars($appointment['user_email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($appointment['consultant_name']); ?></td>
                        <td>
                            <?php echo date('M j, Y', strtotime($appointment['slot_date'])); ?><br>
                            <small><?php echo date('h:i A', strtotime($appointment['slot_time'])); ?></small>
                        </td>
                        <td>
                            <?php 
                            $status_class = '';
                            switch(strtolower($appointment['status'])) {
                                case 'pending':
                                    $status_class = 'status-pending';
                                    break;
                                case 'confirmed':
                                    $status_class = 'status-confirmed';
                                    break;
                                case 'completed':
                                    $status_class = 'status-completed';
                                    break;
                                case 'cancelled':
                                    $status_class = 'status-cancelled';
                                    break;
                                default:
                                    $status_class = '';
                            }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars(ucfirst($appointment['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo isset($appointment['created_at']) ? date('M j, Y', strtotime($appointment['created_at'])) : '-'; ?></td>
                        <td>
                            <div class="actions">
    <button
        type="button"
        class="action-btn view-btn"
        data-id="<?php echo $appointment['id']; ?>"
        data-client="<?php echo htmlspecialchars($appointment['user_name']); ?>"
        data-client-email="<?php echo htmlspecialchars($appointment['user_email']); ?>"
        data-consultant="<?php echo htmlspecialchars($appointment['consultant_name']); ?>"
        data-date="<?php echo date('M j, Y', strtotime($appointment['slot_date'])); ?>"
        data-time="<?php echo date('h:i A', strtotime($appointment['slot_time'])); ?>"
        data-status="<?php echo htmlspecialchars($appointment['status']); ?>"
        data-created="<?php echo date('M j, Y', strtotime($appointment['created_at'])); ?>"
        title="View Details"
    >
        <i class="fa-solid fa-eye"></i>
    </button>
    <?php if (strtolower($appointment['status']) !== 'rejected'): ?>
        <?php if (strtolower($appointment['status']) !== 'confirmed' && strtolower($appointment['status']) !== 'approved'): ?>
        <a href="../actions/update_appointment_status.php?id=<?php echo $appointment['id']; ?>&status=confirmed" class="action-btn confirm-btn" title="Confirm">
            <i class="fa-solid fa-check"></i>
        </a>
        <?php endif; ?>
        <?php if (strtolower($appointment['status']) !== 'cancelled'): ?>
        <a href="../actions/update_appointment_status.php?id=<?php echo $appointment['id']; ?>&status=cancelled" class="action-btn cancel-btn" title="Cancel">
            <i class="fa-solid fa-times"></i>
        </a>
        <?php endif; ?>
        <button type="button" class="action-btn delete-btn" data-id="<?php echo $appointment['id']; ?>" data-client="<?php echo htmlspecialchars($appointment['user_name']); ?>" data-date="<?php echo date('M j, Y', strtotime($appointment['slot_date'])); ?>" data-time="<?php echo date('h:i A', strtotime($appointment['slot_time'])); ?>" title="Delete">
            <i class="fa-solid fa-trash"></i>
        </button>
    <?php endif; ?>
</div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="no-appointments">No appointments found.</td></tr>
            <?php endif; ?>
            </tbody>    
        </table>
    </div>

    <!-- Delete Appointment Modal -->
    <div class="modal" id="deleteAppointmentModal" tabindex="-1" aria-labelledby="deleteAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 1.5rem; box-shadow: 0 4px 24px rgba(0,0,0,0.18); overflow: hidden; display: flex; flex-direction: column; width: 500px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAppointmentModalLabel">Delete Appointment</h5>
                    <button type="button" class="action-btn" id="closeModalBtn" aria-label="Close" style="font-size: 1.5rem;">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div style="background: #22304a; color: #fff; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1rem; display: flex; gap: 1rem; align-items: center;">
                        <div style="background: #ef4444; border-radius: 1rem; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-calendar-xmark" style="font-size: 1.5rem; color: #fff;"></i>
                        </div>
                        <div>
                            <p style="margin: 0; font-weight: 600;">Are you sure you want to delete this appointment?</p>
                            <p style="margin: 0; color: #b6c6e3;">This action cannot be undone.</p>
                        </div>
                    </div>
                    <p>You are about to delete appointment for: <span id="delete_client_name" style="font-weight: bold;"></span></p>
                    <p>Scheduled for: <span id="delete_appointment_datetime" style="font-weight: bold;"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="delete_appointment_id" id="delete_appointment_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Appointment Modal -->
    <div class="modal" id="viewAppointmentModal" tabindex="-1" aria-labelledby="viewAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewAppointmentModalLabel">Appointment Details</h5>
                    <button type="button" class="action-btn" id="closeViewModalBtn" aria-label="Close">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div><strong>Client:</strong> <span id="modal-client"></span></div>
                    <div><strong>Client Email:</strong> <span id="modal-client-email"></span></div>
                    <div><strong>Consultant:</strong> <span id="modal-consultant"></span></div>
                    <div><strong>Date:</strong> <span id="modal-date"></span></div>
                    <div><strong>Time:</strong> <span id="modal-time"></span></div>
                    <div><strong>Status:</strong> <span id="modal-status"></span></div>
                    <div><strong>Booked On:</strong> <span id="modal-created"></span></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get the modal
        const deleteModal = document.getElementById('deleteAppointmentModal');
        
        // Get the close buttons
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        
        // Delete Appointment
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const appointmentId = this.getAttribute('data-id');
                const clientName = this.getAttribute('data-client');
                const appointmentDate = this.getAttribute('data-date');
                const appointmentTime = this.getAttribute('data-time');
                
                document.getElementById('delete_appointment_id').value = appointmentId;
                document.getElementById('delete_client_name').textContent = clientName;
                document.getElementById('delete_appointment_datetime').textContent = appointmentDate + ' at ' + appointmentTime;
                
                // Show the modal
                deleteModal.classList.add('show');
            });
        });
        
        // Close modal with X button
        closeModalBtn.addEventListener('click', function() {
            deleteModal.classList.remove('show');
        });
        
        // Close modal with Cancel button
        cancelBtn.addEventListener('click', function() {
            deleteModal.classList.remove('show');
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target == deleteModal) {
                deleteModal.classList.remove('show');
            }
        });
        
        // Form submission should close modal
        document.querySelector('#deleteAppointmentModal form').addEventListener('submit', function() {
            // This will happen when the form is submitted
            deleteModal.classList.remove('show');
        });

        // View Appointment
        document.querySelectorAll('.view-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('modal-client').textContent = this.getAttribute('data-client');
                document.getElementById('modal-client-email').textContent = this.getAttribute('data-client-email');
                document.getElementById('modal-consultant').textContent = this.getAttribute('data-consultant');
                document.getElementById('modal-date').textContent = this.getAttribute('data-date');
                document.getElementById('modal-time').textContent = this.getAttribute('data-time');
                document.getElementById('modal-status').textContent = this.getAttribute('data-status');
                document.getElementById('modal-created').textContent = this.getAttribute('data-created');
                document.getElementById('viewAppointmentModal').classList.add('show');
            });
        });

        document.getElementById('closeViewModalBtn').addEventListener('click', function() {
            document.getElementById('viewAppointmentModal').classList.remove('show');
        });
    });
    </script>
</body>
</html>