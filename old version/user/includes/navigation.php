<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
    :root {
        --primary-color: #2563eb;
        --primary-light: #3b82f6;
        --primary-dark: #1d4ed8;
        --text-color: #1e293b;
        --light-text: #64748b;
        --background: #f8fafc;
        --white: #ffffff;
        --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .navbar {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)) !important;
        box-shadow: var(--card-shadow);
        padding: 12px 0;
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
    }
    
    .navbar-brand {
        color: var(--white) !important;
        font-weight: 700;
        font-size: 1.5rem;
        letter-spacing: -0.5px;
    }
    
    .nav-link {
        color: var(--white) !important;
        font-weight: 500;
        padding: 8px 16px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        color: var(--white) !important;
    }
    
    .navbar .nav-link.active {
        background: rgba(255, 255, 255, 0.2);
        color: var(--white) !important;
    }
    
    .dropdown-menu {
        border-radius: 12px;
        box-shadow: var(--hover-shadow);
        border: none;
        padding: 8px;
        margin-top: 8px;
    }
    
    .dropdown-item {
        border-radius: 8px;
        padding: 10px 16px;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .dropdown-item:hover {
        background-color: #f0f7ff;
        color: var(--primary-color);
        transform: translateX(5px);
    }

    .page-container {
        position: relative;
        min-height: 100vh;
        padding-top: 72px;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    }

    @media (max-width: 768px) {
        .navbar-collapse {
            background: var(--primary-dark);
            padding: 1rem;
            border-radius: 12px;
            margin-top: 1rem;
        }
    }
</style>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $isLoggedIn ? 'dashboard.php' : 'index.php'; ?>">
            <i class="fa-solid fa-calendar-check me-2"></i>
            Appointment System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <?php if ($isLoggedIn): ?>
                    <!-- Removed Dashboard link from here -->
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="fa-solid fa-home me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'services.php' ? 'active' : ''; ?>" href="services.php">
                        <i class="fa-solid fa-list-check me-1"></i> Services
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'consultants.php' ? 'active' : ''; ?>" href="consultants.php">
                        <i class="fa-solid fa-user-tie me-1"></i> Consultants
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'about.php' ? 'active' : ''; ?>" href="about.php">
                        <i class="fa-solid fa-info-circle me-1"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'contact.php' ? 'active' : ''; ?>" href="contact.php">
                        <i class="fa-solid fa-envelope me-1"></i> Contact
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                            <i class="fa-solid fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="profile.php" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-circle me-1"></i> 
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fa-solid fa-gauge-high me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="messages.php"><i class="fa-solid fa-comment me-2"></i>Consultant Messages</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'login.php' ? 'active' : ''; ?>" href="login.php">
                            <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'register.php' ? 'active' : ''; ?>" href="register.php">
                            <i class="fa-solid fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav> 