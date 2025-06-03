<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
checkUserLogin();

// Get user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT name, email FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
$user_name = $user['name'] ?? '';

// Create appointment_messages table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS appointment_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_type ENUM('user', 'consultant') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_table);

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $message = trim(mysqli_real_escape_string($conn, $_POST['message']));
    
    $sql = "INSERT INTO appointment_messages (appointment_id, sender_id, sender_type, message) 
            VALUES (?, ?, 'user', ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iis", $appointment_id, $user_id, $message);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Message sent successfully!";
    } else {
        $_SESSION['error'] = "Error sending message: " . mysqli_error($conn);
    }
    
    header("Location: appointment_messages.php?appointment_id=" . $appointment_id);
    exit;
}

// Get appointment ID from URL
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;

// Get appointment details
$sql = "SELECT a.*, u.name as client_name, u.email as client_email, 
        cu.name as consultant_name, cu.email as consultant_email,
        c.specialty, av.slot_date, av.slot_time 
        FROM appointments a 
        JOIN users u ON a.user_id = u.id 
        JOIN consultants c ON a.consultant_id = c.id
        JOIN users cu ON c.user_id = cu.id
        JOIN availability av ON a.availability_id = av.id 
        WHERE a.id = ? AND a.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointment = mysqli_fetch_assoc($result);

if (!$appointment) {
    header("Location: dashboard.php");
    exit;
}

// Get messages for this appointment
$sql = "SELECT am.*, 
        CASE 
            WHEN am.sender_type = 'user' THEN u.name 
            ELSE cu.name 
        END as sender_name
        FROM appointment_messages am
        LEFT JOIN users u ON am.sender_id = u.id AND am.sender_type = 'user'
        LEFT JOIN consultants c ON am.sender_id = c.id AND am.sender_type = 'consultant'
        LEFT JOIN users cu ON c.user_id = cu.id AND am.sender_type = 'consultant'
        WHERE am.appointment_id = ?
        ORDER BY am.created_at ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$messages_result = mysqli_stmt_get_result($stmt);

// Mark all consultant messages as read when user views the conversation
$update_sql = "UPDATE appointment_messages 
              SET is_read = 1 
              WHERE appointment_id = ? AND sender_type = 'consultant' AND is_read = 0";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, "i", $appointment_id);
mysqli_stmt_execute($update_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Messages</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        .btn-meeting {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-meeting:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: white;
        }
        .btn-meeting i {
            font-size: 1.1em;
        }
        
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
            --border-color: #e5e7eb;
            --light-blue: #dbeafe;
            --light-blue-hover: #bfdbfe;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: var(--text-color);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            min-height: 100vh;
        }

        .messages-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 1rem;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--white);
            color: var(--primary-dark);
            border: none;
            border-radius: 30px;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            transform: translateX(-5px);
            background: var(--light-blue);
            color: var(--primary-dark);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
        }

        .appointment-header {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .appointment-header:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-light));
        }

        .appointment-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .appointment-title i {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .appointment-details {
            display: flex;
            gap: 1.5rem;
            color: var(--light-text);
            flex-wrap: wrap;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px dashed rgba(0, 0, 0, 0.1);
        }
        
        .appointment-details div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        
        .appointment-details i {
            color: var(--primary-color);
        }

        .messages-box {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            height: 550px;
            display: flex;
            flex-direction: column;
        }

        .messages-list {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
        }

        .message {
            margin-bottom: 1.5rem;
            max-width: 80%;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.user {
            align-self: flex-end;
            margin-left: auto;
        }

        .message.consultant {
            align-self: flex-start;
            margin-right: auto;
        }

        .message-content {
            padding: 1rem 1.25rem;
            border-radius: 18px;
            word-break: break-word;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: relative;
            line-height: 1.6;
        }

        .user .message-content {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .user .message-content:before {
            content: '';
            position: absolute;
            bottom: 0;
            right: -8px;
            width: 16px;
            height: 16px;
            background: var(--primary-dark);
            clip-path: polygon(0 0, 0% 100%, 100% 100%);
        }

        .consultant .message-content {
            background: white;
            color: var(--text-color);
            border-bottom-left-radius: 4px;
            border-left: 3px solid var(--light-blue);
        }
        
        .consultant .message-content:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: -8px;
            width: 16px;
            height: 16px;
            background: white;
            clip-path: polygon(100% 0, 0% 100%, 100% 100%);
        }

        .message-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            color: var(--light-text);
            padding: 0 0.5rem;
        }
        
        .sender-name {
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .message-time {
            opacity: 0.8;
        }

        .message-form {
            display: flex;
            gap: 0.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            margin-top: auto;
        }

        .message-input {
            flex-grow: 1;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 24px;
            padding: 0.9rem 1.25rem;
            resize: none;
            font-family: inherit;
            font-size: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
        }

        .message-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-send {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        }

        .btn-send:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3);
        }
        
        .btn-send i {
            font-size: 1.2rem;
        }

        .alert {
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: none;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fa-solid fa-calendar-check me-2"></i>
                Appointment System
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
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fa-solid fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>" href="services.php">
                            <i class="fa-solid fa-list-check me-1"></i> Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'consultants.php' ? 'active' : ''; ?>" href="consultants.php">
                            <i class="fa-solid fa-user-tie me-1"></i> Consultants
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                            <i class="fa-solid fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-circle me-1"></i> 
                            <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fa-solid fa-gauge-high me-2"></i>Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="messages-container">
        <a href="messages.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Messages
        </a>
        
        <div class="appointment-header">
            <h2 class="appointment-title">
                <i class="fa-solid fa-calendar-check me-2"></i>
                Appointment with <?php echo htmlspecialchars($appointment['consultant_name']); ?>
            </h2>
            <div class="appointment-details">
                <div><i class="fa-solid fa-calendar-day me-2"></i> <?php echo date('F j, Y', strtotime($appointment['slot_date'])); ?></div>
                <div><i class="fa-solid fa-clock me-2"></i> <?php echo date('g:i A', strtotime($appointment['slot_time'])); ?></div>
                <div><i class="fa-solid fa-tag me-2"></i> <?php echo htmlspecialchars($appointment['specialty']); ?></div>
                <?php if (!empty($appointment['meeting_link'])): ?>
                    <div class="mt-2">
                        <a href="<?php echo htmlspecialchars($appointment['meeting_link']); ?>" 
                           target="_blank" 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-video me-1"></i> Join Meeting
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="messages-box">
            <div class="messages-list" id="messagesList">
                <?php while ($message = mysqli_fetch_assoc($messages_result)): ?>
                    <div class="message <?php echo $message['sender_type']; ?>">
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>
                        <div class="message-info">
                            <span class="sender-name"><?php echo htmlspecialchars($message['sender_name']); ?></span>
                            <span class="message-time"><?php echo date('M j, g:i A', strtotime($message['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <form method="POST" action="" class="message-form">
                <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                <textarea name="message" class="message-input" rows="2" placeholder="Type your message here..." required></textarea>
                <button type="submit" name="send_message" class="btn-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom of messages
        const messagesList = document.getElementById('messagesList');
        messagesList.scrollTop = messagesList.scrollHeight;

        // Auto-refresh messages every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html> 