<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit;
}
?>
<nav class="admin-navbar">
    <div class="nav-brand">
        <i class="fa-solid fa-user-shield"></i>
        <span>Admin Panel</span>
    </div>
    <div class="nav-links">
        <a href="../admin/dashboard.php" class="nav-link">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>
        <a href="../admin/manage_users.php" class="nav-link">
            <i class="fa-solid fa-users"></i>
            <span>Users</span>
        </a>
        <a href="../admin/manage_consultants.php" class="nav-link">
            <i class="fa-solid fa-user-tie"></i>
            <span>Consultants</span>
        </a>
        <a href="../admin/manage_appointments.php" class="nav-link">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Appointments</span>
        </a>
        <a href="../admin/messages.php" class="nav-link">
            <i class="fa-solid fa-envelope"></i>
            <span>Messages</span>
        </a>
        <a href="../admin/reports.php" class="nav-link">
            <i class="fa-solid fa-chart-bar"></i>
            <span>Reports</span>
        </a>
    </div>
    <div class="nav-profile">
        <span class="admin-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin User'); ?></span>
        <a href="../actions/logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>

<style>
.admin-navbar {
    background: #23272f;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.nav-brand {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    color: #4f8cff;
    font-size: 1.5rem;
    font-weight: 600;
}

.nav-brand i {
    font-size: 1.8rem;
}

.nav-links {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}

.nav-link {
    color: #e0e6f7;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

.nav-link:hover {
    background: #2d3a5a;
    color: #4f8cff;
}

.nav-link i {
    font-size: 1.2rem;
}

.nav-profile {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.admin-name {
    color: #e0e6f7;
    font-weight: 500;
}

.logout-btn {
    background: #ff6b6b;
    color: white;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.2s ease;
}

.logout-btn:hover {
    background: #d90429;
}

@media (max-width: 1024px) {
    .nav-links {
        display: none;
    }
    
    .admin-navbar {
        padding: 1rem;
    }
}
</style> 