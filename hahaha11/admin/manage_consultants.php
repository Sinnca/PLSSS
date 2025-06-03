<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php?error=Please login as admin");
    exit;
}
require_once '../config/db.php';

// Handle search
$search = trim($_GET['search'] ?? '');
$where = '';
if ($search !== '') {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $where = "AND (u.name LIKE '%$search_esc%' OR u.email LIKE '%$search_esc%' OR c.specialty LIKE '%$search_esc%')";
}

// Fetch consultants
$sql = "SELECT c.id, u.name, u.email, c.specialty, c.created_at 
        FROM consultants c
        JOIN users u ON c.user_id = u.id
        WHERE 1=1 $where
        ORDER BY c.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Consultants</title>
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
        .no-consultants {
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
        .form-control {
            display: block;
            width: 100%;
            padding: 0.7rem 1rem;
            border-radius: 0.8rem;
            border: none;
            background: #2d3a5a;
            color: #e0e6f7;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        .form-label {
            color: #8ca3f8;
            margin-bottom: 0.5rem;
            display: block;
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
        .btn-primary {
            background: #4f8cff;
            color: #fff;
        }
        .btn-primary:hover {
            background: #2563eb;
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
        .mb-3 {
            margin-bottom: 1rem;
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
        .admin-nav-center {
            flex: 1;
            justify-content: center !important;
            display: flex !important;
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
        <h1><i class="fa-solid fa-user-tie"></i> Manage Consultants</h1>
        <nav class="nav-bar">
            <a href="dashboard.php" class="nav-btn"><i class="fa-solid fa-tachometer-alt" style="color: #4f8cff;"></i> Dashboard</a>
        </nav>
        <form class="search-bar" method="get">
            <input type="text" name="search" placeholder="Search by name, email, or specialty..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Specialty</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($consultant = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($consultant['name']); ?></td>
                        <td><?php echo htmlspecialchars($consultant['email']); ?></td>
                        <td><?php echo htmlspecialchars($consultant['specialty']); ?></td>
                        <td><?php echo isset($consultant['created_at']) ? date('M j, Y', strtotime($consultant['created_at'])) : '-'; ?></td>
                        <td>
                            <div class="actions">
                                <button class="action-btn edit-btn" data-id="<?php echo $consultant['id']; ?>" data-name="<?php echo htmlspecialchars($consultant['name']); ?>" data-email="<?php echo htmlspecialchars($consultant['email']); ?>" data-specialization="<?php echo htmlspecialchars($consultant['specialty']); ?>" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button class="action-btn delete-btn" data-id="<?php echo $consultant['id']; ?>" data-name="<?php echo htmlspecialchars($consultant['name']); ?>" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="no-consultants">No consultants found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Consultant Modal -->
<div class="modal" id="editConsultantModal" tabindex="-1" aria-labelledby="editConsultantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 1.5rem; box-shadow: 0 4px 24px rgba(0,0,0,0.18); overflow: hidden; display: flex; flex-direction: row; min-width: 600px;">
            <!-- Left illustration/message -->
            <div style="background: #22304a; color: #fff; flex: 1 1 0; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem;">
                <div style="background: #4f8cff; border-radius: 1.2rem; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                    <i class="fa-solid fa-user-tie" style="font-size: 2.5rem; color: #fff;"></i>
                </div>
                <h4 style="margin-bottom: 0.5rem;">Edit Consultant</h4>
                <p style="font-size: 1rem; color: #b6c6e3; text-align: center;">Update consultant data here.</p>
            </div>
            <!-- Right form -->
            <div style="background: #23272f; flex: 1 1 0; display: flex; flex-direction: column; justify-content: center; padding: 2rem;">
                <h5 class="modal-title" id="editConsultantModalLabel" style="color: #4f8cff; margin-bottom: 1rem;">Edit Form</h5>
                <form id="editConsultantForm" action="../actions/update_consultant.php" method="POST">
                    <input type="hidden" name="consultant_id" id="edit_consultant_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="edit_specialization" name="specialization" required>
                    </div>
                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button type="button" class="btn btn-secondary" id="editCloseBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Consultant Modal -->
<div class="modal" id="deleteConsultantModal" tabindex="-1" aria-labelledby="deleteConsultantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 1.5rem; box-shadow: 0 4px 24px rgba(0,0,0,0.18); overflow: hidden; display: flex; flex-direction: row; min-width: 500px;">
            <!-- Left illustration/message -->
            <div style="background: #22304a; color: #fff; flex: 1 1 0; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem;">
                <div style="background: #ef4444; border-radius: 1.2rem; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                    <i class="fa-solid fa-user-tie" style="font-size: 2.5rem; color: #fff;"></i>
                </div>
                <h4 style="margin-bottom: 0.5rem;">Delete Consultant?</h4>
                <p style="font-size: 1rem; color: #b6c6e3; text-align: center;">This action cannot be undone.<br>Please make sure you're certain!</p>
            </div>
            <!-- Right confirmation -->
            <div style="background: #23272f; flex: 1 1 0; display: flex; flex-direction: column; justify-content: center; padding: 2rem;">
                <h5 class="modal-title" id="deleteConsultantModalLabel" style="color: #4f8cff; margin-bottom: 1rem;">Delete Confirmation</h5>
                <p style="color: #fff;">Are you sure you want to delete consultant: <span id="delete_consultant_name" style="font-weight: bold;"></span>?</p>
                <form action="../actions/delete_consultant.php" method="POST" style="margin-top: 1.5rem;">
                    <input type="hidden" name="consultant_id" id="delete_consultant_id">
                    <div style="display: flex; gap: 1rem;">
                        <button type="button" class="btn btn-secondary" id="deleteCloseBtn">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get the modals
        const editModal = document.getElementById('editConsultantModal');
        const deleteModal = document.getElementById('deleteConsultantModal');
        
        // Get the close buttons
        const editCloseBtn = document.getElementById('editCloseBtn');
        const deleteCloseBtn = document.getElementById('deleteCloseBtn');
        
        // Edit Consultant
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const consultantId = this.getAttribute('data-id');
                const consultantName = this.getAttribute('data-name');
                const consultantEmail = this.getAttribute('data-email');
                const consultantSpecialization = this.getAttribute('data-specialization');
                
                document.getElementById('edit_consultant_id').value = consultantId;
                document.getElementById('edit_name').value = consultantName;
                document.getElementById('edit_email').value = consultantEmail;
                document.getElementById('edit_specialization').value = consultantSpecialization;
                
                // Show the modal
                editModal.classList.add('show');
            });
        });
        
        // Delete Consultant
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const consultantId = this.getAttribute('data-id');
                const consultantName = this.getAttribute('data-name');
                
                document.getElementById('delete_consultant_id').value = consultantId;
                document.getElementById('delete_consultant_name').textContent = consultantName;
                
                // Show the modal
                deleteModal.classList.add('show');
            });
        });
        
        // Close edit modal
        editCloseBtn.addEventListener('click', function() {
            editModal.classList.remove('show');
        });
        
        // Close delete modal
        deleteCloseBtn.addEventListener('click', function() {
            deleteModal.classList.remove('show');
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target == editModal) {
                editModal.classList.remove('show');
            }
            if (event.target == deleteModal) {
                deleteModal.classList.remove('show');
            }
        });
        
        // Form submissions should close modals
        document.getElementById('editConsultantForm').addEventListener('submit', function() {
            // This will happen when the form is submitted
            editModal.classList.remove('show');
        });
        
        document.querySelector('#deleteConsultantModal form').addEventListener('submit', function() {
            // This will happen when the form is submitted
            deleteModal.classList.remove('show');
        });
    });
    </script>
</body>
</html>