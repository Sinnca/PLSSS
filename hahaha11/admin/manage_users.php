<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php?error=Please login as admin");
    exit;
}
require_once '../config/db.php';

// Handle search
$search = trim($_GET['search'] ?? '');
$filter_clause = "WHERE u.role != 'admin' AND c.id IS NULL";

// Add search filter if provided
if ($search !== '') {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $filter_clause .= " AND (u.name LIKE '%$search_esc%' OR u.email LIKE '%$search_esc%' OR u.role LIKE '%$search_esc%')";
}

// Fetch users (exclude consultants and admins)
$sql = "SELECT u.* FROM users u
        LEFT JOIN consultants c ON u.id = c.user_id
        $filter_clause";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
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
            color: #4f8cff;
            font-size: 1.2rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        .action-btn:hover {
            color: #ff6b6b;
        }
        .no-users {
            color: #8ca3f8;
            text-align: center;
            padding: 2rem 0;
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
        .admin-logo d-flex align-items-center gap-2 me-4 {
            color: #3b82f6;
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .admin-nav-center d-flex align-items-center gap-4 mx-auto {
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
            <a href="/hahaha11/actions/logout.php" class="btn btn-danger logout-btn">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </nav>
    <div class="container">
        <h1><i class="fa-solid fa-users"></i> Manage Users</h1>
        <form class="search-bar" method="get">
            <input type="text" name="search" placeholder="Search by name, email, or role..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($user = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                        <td><?php echo isset($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : '-'; ?></td>
                        <td>
                            <div class="actions">
                                <button class="action-btn delete-btn" data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['name']); ?>" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="no-users">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Delete User Modal -->
    <div class="modal" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 1.5rem; box-shadow: 0 4px 24px rgba(0,0,0,0.18); overflow: hidden; display: flex; flex-direction: column; width: 500px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                    <button type="button" class="action-btn" id="closeModalBtn" aria-label="Close" style="font-size: 1.5rem;">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div style="background: #22304a; color: #fff; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1rem; display: flex; gap: 1rem; align-items: center;">
                        <div style="background: #ef4444; border-radius: 1rem; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-user-slash" style="font-size: 1.5rem; color: #fff;"></i>
                        </div>
                        <div>
                            <p style="margin: 0; font-weight: 600;">Are you sure you want to delete this user?</p>
                            <p style="margin: 0; color: #b6c6e3;">This action cannot be undone.</p>
                        </div>
                    </div>
                    <p>You are about to delete user: <span id="delete_user_name" style="font-weight: bold;"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                    <form action="../actions/delete_user.php" method="POST" style="display: inline;">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get the modal
        const deleteModal = document.getElementById('deleteUserModal');
        
        // Get the close buttons
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        
        // Delete User
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const userName = this.getAttribute('data-name');
                
                document.getElementById('delete_user_id').value = userId;
                document.getElementById('delete_user_name').textContent = userName;
                
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
        document.querySelector('#deleteUserModal form').addEventListener('submit', function() {
            // This will happen when the form is submitted
            deleteModal.classList.remove('show');
        });
    });
    </script>
</body>
</html>