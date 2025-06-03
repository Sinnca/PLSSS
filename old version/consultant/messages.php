<?php
require_once '../config/db.php';
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

// Handle date filter for chats
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$sql = "SELECT DISTINCT a.id as appointment_id, 
        u.name as client_name, 
        u.email as client_email,
        c.specialty,
        av.slot_date,
        av.slot_time,
        DATE_ADD(av.slot_time, INTERVAL 1 HOUR) as end_time,
        a.status,
        (SELECT COUNT(*) FROM appointment_messages am 
         WHERE am.appointment_id = a.id AND am.sender_type = 'user' AND am.is_read = FALSE) as unread_count,
        (SELECT MAX(created_at) FROM appointment_messages am 
         WHERE am.appointment_id = a.id) as last_message_time
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN consultants c ON a.consultant_id = c.id
        JOIN availability av ON a.availability_id = av.id
        LEFT JOIN appointment_messages am ON a.id = am.appointment_id
        WHERE a.consultant_id = ? 
        AND a.status != 'cancelled'";
$params = [$consultant_id];
$types = 'i';
if ($filter_date !== '') {
    $sql .= " AND av.slot_date = ?";
    $types .= 's';
    $params[] = $filter_date;
}
$sql .= " AND av.slot_date >= CURDATE() GROUP BY a.id ORDER BY last_message_time DESC, av.slot_date ASC, av.slot_time ASC";
$stmt = mysqli_prepare($conn, $sql);
if (count($params) === 2) {
    mysqli_stmt_bind_param($stmt, $types, $params[0], $params[1]);
} else {
    mysqli_stmt_bind_param($stmt, $types, $params[0]);
}
mysqli_stmt_execute($stmt);
$appointments = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Consultant Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
:root {
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
    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
    background-color: var(--background);
    color: var(--text-color);
    line-height: 1.6;
    min-height: 100vh;
}

.messages-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 1rem;
    animation: fadeIn 0.5s ease-out;
}

.page-header {
    background: var(--white);
    border-radius: 1.5rem;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
    border: 1px solid var(--border-color);
    transform: translateY(0);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.page-header:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(37, 99, 235, 0.12);
}
.page-title {
    color: var(--primary-color);
    font-weight: 800;
    margin-bottom: 0.5rem;
    font-size: 2rem;
    letter-spacing: -0.5px;
}

/* Filter Bar */
.filter-bar {
    background: var(--white);
    border-radius: 1rem;
    padding: 1rem 1.5rem 0.5rem 1.5rem;
    box-shadow: 0 2px 12px rgba(67,97,238,0.07);
    margin-bottom: 2rem !important;
    border: 1px solid var(--border-color);
}
.filter-bar label {
    color: var(--primary-dark);
    font-size: 0.95rem;
    letter-spacing: 0.01em;
}
.filter-bar .filter-input {
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
    font-size: 1rem;
    background: var(--white);
    transition: border-color 0.2s;
}
.filter-bar .filter-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(67,97,238,0.10);
}
.filter-bar .filter-btn {
    border-radius: 0.5rem;
    font-weight: 500;
    font-size: 1rem;
}

/* Chat List & Items */
.chat-list {
    background: var(--white);
    border-radius: 1.5rem;
    padding: 2rem;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
    border: 1px solid var(--border-color);
}
.chat-item {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    color: var(--text-color);
    border-radius: 1rem;
    margin-bottom: 1rem;
    position: relative;
    overflow: hidden;
}
.chat-item:last-child {
    margin-bottom: 0;
}
.chat-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(to bottom, var(--gradient-start), var(--gradient-end));
    opacity: 0;
    transition: opacity 0.3s ease;
}
.chat-item:hover {
    background: var(--hover-bg);
    transform: translateX(8px) scale(1.01);
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.1);
}
.chat-item:hover::before {
    opacity: 1;
}
.chat-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 1.5rem;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
    transition: transform 0.3s ease;
}
.chat-item:hover .chat-avatar {
    transform: scale(1.1) rotate(5deg);
}
.client-name {
    font-weight: 700;
    color: var(--primary-dark);
    font-size: 1.2rem;
    margin-bottom: 0.3rem;
}
.appointment-date {
    font-size: 0.9rem;
    color: var(--light-text);
    background: var(--hover-bg);
    padding: 0.4rem 1rem;
    border-radius: 2rem;
    font-weight: 500;
    transition: all 0.3s ease;
}
.chat-item:hover .appointment-date {
    background: var(--primary-light);
    color: white;
}
.specialty {
    background: var(--hover-bg);
    padding: 0.3rem 0.8rem;
    border-radius: 1rem;
    color: var(--primary-color);
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}
.chat-item:hover .specialty {
    background: var(--primary-color);
    color: white;
}
.unread-badge {
    background: var(--primary-color);
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 2rem;
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.no-chats {
    text-align: center;
    padding: 5rem 2rem;
    color: var(--light-text);
    background: var(--hover-bg);
    border-radius: 1.5rem;
    margin: 2rem 0;
    animation: fadeIn 0.5s ease-out;
}
.no-chats i {
    font-size: 5rem;
    margin-bottom: 2rem;
    color: var(--primary-color);
    opacity: 0.8;
    animation: float 3s ease-in-out infinite;
}
@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}
.no-chats h3 {
    color: var(--primary-dark);
    font-weight: 700;
    margin-bottom: 1rem;
    font-size: 1.8rem;
}

/* Responsive Design Improvements */
@media (max-width: 768px) {
    .messages-container {
        padding: 0.5rem;
    }
    .page-header {
        padding: 1.5rem;
    }
    .chat-list {
        padding: 1rem;
    }
    .chat-item {
        padding: 1rem;
    }
    .chat-avatar {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
        margin-right: 1rem;
    }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}


            font-size: 0.95rem;
            letter-spacing: 0.01em;
        }
        .filter-bar .filter-input {
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
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
            background: linear-gradient(120deg, #f0f7ff 0%, #eef2ff 100%);
            color: var(--text-color);
            min-height: 100vh;
        }
        /* Card-based messages container */
        .messages-container {
            max-width: 900px;
            margin: 2.5rem auto 2rem auto;
            background: var(--white);
            border-radius: 1.25rem;
            box-shadow: 0 8px 32px rgba(67,97,238,0.09);
            padding: 2rem 2.5rem 2rem 2.5rem;
            border: 1px solid var(--border-color);
        }
        @media (max-width: 700px) {
            .messages-container {
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
        /* Chat list and chat items */
        .chat-list {
            margin-top: 0.5rem;
        }
        .chat-item {
            background: var(--bg-light);
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(67,97,238,0.07);
            padding: 1.2rem 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            display: flex;
            gap: 1.5rem;
            align-items: center;
            text-decoration: none;
            color: inherit;
            transition: box-shadow 0.2s, transform 0.2s, background 0.2s;
        }
        .chat-item:hover {
            background: #f8fafc;
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 24px rgba(67,97,238,0.13);
            text-decoration: none;
        }
        .chat-avatar {
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
        .chat-info {
            flex: 1;
            min-width: 0;
        }
        .chat-header {
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
        .chat-meta {
            display: flex;
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
        .unread-badge {
            background: #ffe066;
            color: #b59d00;
            border-radius: 1rem;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.2rem 0.8rem;
            margin-left: 0.5rem;
            box-shadow: 0 1px 3px rgba(67,97,238,0.07);
            border: 1px solid #ffe066;
        }
        .chat-item:last-child {
            margin-bottom: 0;
        }
        /* Empty state */
        .no-chats {
            text-align: center;
            padding: 4rem 0 2rem 0;
        }
        .no-chats i {
            font-size: 5rem;
            margin-bottom: 2rem;
            color: var(--primary-color);
            opacity: 0.8;
            animation: float 3s ease-in-out infinite;
        }
        .no-chats h3 {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .no-chats p {
            color: var(--light-text);
            font-size: 1.1rem;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        /* Responsive tweaks */
        @media (max-width: 700px) {
            .chat-item {
                flex-direction: column;
                align-items: flex-start;
                padding: 1rem 0.8rem;
                gap: 1rem;
            }
            .chat-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.2rem;
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
            --hover-bg: #e6f0ff;
            --border-color: #dbeafe;
            --gradient-start: #2563eb;
            --gradient-end: #1d4ed8;
        }

        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            background-color: var(--background);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
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

        .nav-link {
            color: white !important;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.5rem 1rem;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: white;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .nav-link:hover {
            transform: translateY(-2px);
            color: rgba(255, 255, 255, 0.95) !important;
        }

        .messages-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-header {
            background: var(--white);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
            border: 1px solid var(--border-color);
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .page-header:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(37, 99, 235, 0.12);
        }

        .page-title {
            color: var(--primary-color);
            font-weight: 800;
            margin-bottom: 0.5rem;
            font-size: 2rem;
            letter-spacing: -0.5px;
        }

        .chat-list {
            background: var(--white);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
            border: 1px solid var(--border-color);
        }

        .chat-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: var(--text-color);
            border-radius: 1rem;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
        }

        .chat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--gradient-start), var(--gradient-end));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .chat-item:hover {
            background: var(--hover-bg);
            transform: translateX(8px) scale(1.01);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.1);
        }

        .chat-item:hover::before {
            opacity: 1;
        }

        .chat-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1.5rem;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
            transition: transform 0.3s ease;
        }

        .chat-item:hover .chat-avatar {
            transform: scale(1.1) rotate(5deg);
        }

        .client-name {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
        }

        .appointment-date {
            font-size: 0.9rem;
            color: var(--light-text);
            background: var(--hover-bg);
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .chat-item:hover .appointment-date {
            background: var(--primary-light);
            color: white;
        }

        .specialty {
            background: var(--hover-bg);
            padding: 0.3rem 0.8rem;
            border-radius: 1rem;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .chat-item:hover .specialty {
            background: var(--primary-color);
            color: white;
        }

        .unread-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .no-chats {
            text-align: center;
            padding: 5rem 2rem;
            color: var(--light-text);
            background: var(--hover-bg);
            border-radius: 1.5rem;
            margin: 2rem 0;
            animation: fadeIn 0.5s ease-out;
        }

        .no-chats i {
            font-size: 5rem;
            margin-bottom: 2rem;
            color: var(--primary-color);
            opacity: 0.8;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .no-chats h3 {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1.8rem;
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

        /* Responsive Design Improvements */
        @media (max-width: 768px) {
            .messages-container {
                padding: 0.5rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .chat-list {
                padding: 1rem;
            }

            .chat-item {
                padding: 1rem;
            }

            .chat-avatar {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
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

    <div class="messages-container">
        <div class="page-header">
            <h2 class="page-title">
                <i class="fa-solid fa-comments me-2"></i>
                Messages
            </h2>
            <p class="text-muted">View and manage your conversations with clients</p>
        </div>

        <!-- Date Filter Bar -->
        <form class="filter-bar row gx-2 gy-2 mb-4 align-items-end justify-content-start" method="get" action="messages.php">
            <div class="col-md-auto col-12">
                <label for="filter_date" class="form-label mb-1 fw-semibold">Filter by Date</label>
                <input type="date" class="form-control filter-input" id="filter_date" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : ''; ?>">
            </div>
            <div class="col-md-auto col-12 d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-primary filter-btn shadow-sm px-4">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                <?php if (isset($_GET['filter_date']) && $_GET['filter_date'] !== ''): ?>
                    <a href="messages.php" class="btn btn-outline-secondary filter-btn px-3">
                        <i class="fa-solid fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <div class="chat-list">
            <?php if (mysqli_num_rows($appointments) > 0): ?>
                <?php while ($appointment = mysqli_fetch_assoc($appointments)): ?>
                    <a href="appointment_messages.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" class="chat-item">
                        <div class="chat-avatar">
                            <?php echo strtoupper(substr($appointment['client_name'], 0, 1)); ?>
                        </div>
                        <div class="chat-info">
                            <div class="chat-header">
                                <span class="client-name"><?php echo htmlspecialchars($appointment['client_name']); ?></span>
                                <span class="appointment-date">
                                    <?php 
                                        $start_time = date('g:i A', strtotime($appointment['slot_time']));
                                        $end_time = date('g:i A', strtotime($appointment['end_time']));
                                        echo date('F j, Y', strtotime($appointment['slot_date'])) . ' at ' . $start_time . ' - ' . $end_time;
                                    ?>
                                </span>
                            </div>
                            <div class="chat-meta">
                                <span class="specialty"><?php echo htmlspecialchars($appointment['specialty']); ?></span>
                                <?php if ($appointment['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?php echo $appointment['unread_count']; ?> new</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-chats">
                    <i class="fas fa-comments"></i>
                    <h3>No Messages Yet</h3>
                    <p>You don't have any active conversations with clients.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 