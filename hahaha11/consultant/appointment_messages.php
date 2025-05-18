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
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    $sql = "INSERT INTO appointment_messages (appointment_id, sender_id, sender_type, message) 
            VALUES (?, ?, 'consultant', ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iis", $appointment_id, $consultant_id, $message);
    
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
$sql = "SELECT a.*, u.name as client_name, u.email as client_email, av.slot_date, av.slot_time, c.specialty 
        FROM appointments a 
        JOIN users u ON a.user_id = u.id 
        JOIN consultants c ON a.consultant_id = c.id
        JOIN availability av ON a.availability_id = av.id 
        WHERE a.id = ? AND a.consultant_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $consultant_id);
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

// Mark messages as read
$sql = "UPDATE appointment_messages 
        SET is_read = TRUE 
        WHERE appointment_id = ? AND sender_type = 'user' AND is_read = FALSE";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
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
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --text-color: #1e293b;
            --light-text: #64748b;
            --background: #f8fafc;
            --white: #fff;
            --border-color: #e5e7eb;
        }
        body {
            background: var(--background);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            min-height: 100vh;
            color: var(--text-color);
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
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            font-size: 1.05rem;
            box-shadow: 0 2px 8px rgba(37,99,235,0.04);
            margin-bottom: 1.5rem;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
        }
        .back-button:hover {
            background: var(--primary-light);
            color: var(--white);
        }
        .appointment-header {
            background: var(--white);
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(37,99,235,0.06);
            padding: 1.5rem 2rem 1rem 2rem;
            margin-bottom: 1.25rem;
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
        .appointment-details {
            display: flex;
            gap: 2rem;
            color: var(--light-text);
            font-size: 1.05rem;
            flex-wrap: wrap;
        }
        .messages-box {
            background: var(--white);
            border-radius: 1rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255,255,255,0.2);
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
            display: flex;
            flex-direction: column;
            margin-bottom: 1.2rem;
        }
        .user {
            align-self: flex-start;
            flex-direction: row;
        }
        .consultant {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        .user .avatar {
            width: 38px;
            height: 38px;
            background: #e0edff;
            color: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-right: 0.5rem;
            box-shadow: 0 2px 8px rgba(37,99,235,0.07);
        }
        .consultant .avatar {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-left: 0.5rem;
            box-shadow: 0 2px 8px rgba(37,99,235,0.13);
        }
        .message-content {
            padding: 1.05rem 1.5rem;
            border-radius: 1.5rem;
            font-size: 1.08rem;
            background: #f1f5f9;
            color: var(--text-color);
            position: relative;
            box-shadow: 0 4px 16px rgba(37,99,235,0.07);
            line-height: 1.6;
            border: 1.5px solid #e0edff;
            min-width: 70px;
            word-break: break-word;
        }
        .user .message-content {
            background: #e7f3ff;
            color: #2563eb;
            border: 1.5px solid #b6d6ff;
            border-bottom-left-radius: 0.5rem;
            border-top-left-radius: 0.5rem;
            box-shadow: 0 2px 12px rgba(37,99,235,0.06);
        }
        .consultant .message-content {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: #fff;
            border-bottom-right-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            border: none;
            box-shadow: 0 4px 18px rgba(37,99,235,0.10);
        }
        .message-info {
            font-size: 0.95rem;
            color: var(--light-text);
            margin-top: 0.25rem;
            display: flex;
            gap: 1.25rem;
            align-items: center;
        }
        .sender-name {
            font-weight: 600;
        }
        .message-form {
            display: flex;
            gap: 0.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(0,0,0,0.05);
            margin-top: auto;
        }
        .message-input {
            flex-grow: 1;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 24px;
            padding: 0.9rem 1.25rem;
            resize: none;
            font-family: inherit;
            font-size: 1.08rem;
            background: #f8fafc;
            box-shadow: none;
            outline: none;
        }
        .message-input:focus {
            border-color: var(--primary-color);
            background: #fff;
        }
        .btn-send {
            background: var(--primary-color);
            color: white;
            border-radius: 24px;
            padding: 0.75rem 1.5rem;
            font-size: 1.2rem;
            border: none;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
            transition: background 0.15s;
        }
        .btn-send:hover {
            background: var(--primary-dark);
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
        @media (max-width: 700px) {
            .messages-container {
                max-width: 100%;
                padding: 0.5rem;
            }
            .appointment-header {
                padding: 1rem 0.5rem 0.5rem 0.5rem;
            }
            .messages-box {
                height: 400px;
            }
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
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>" href="appointments.php">
                            <i class="fa-solid fa-calendar me-1"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                            <i class="fa-solid fa-user me-1"></i> Profile
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
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fa-solid fa-gauge-high me-2"></i>Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../actions/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
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
                Appointment with <?php echo htmlspecialchars($appointment['client_name']); ?>
            </h2>
            <div class="appointment-details">
                <div><i class="fa-solid fa-calendar-day me-2"></i> <?php echo date('F j, Y', strtotime($appointment['slot_date'])); ?></div>
                <div><i class="fa-solid fa-clock me-2"></i> <?php echo date('g:i A', strtotime($appointment['slot_time'])); ?></div>
                <div><i class="fa-solid fa-tag me-2"></i> <?php echo htmlspecialchars($appointment['specialty']); ?></div>
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
        <?php if ($message['sender_type'] === 'user'): ?>
            <div class="avatar"><?php echo strtoupper(substr($message['sender_name'], 0, 1)); ?></div>
            <div>
                <div class="message-content">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </div>
                <div class="message-info">
                    <span class="sender-name"><?php echo htmlspecialchars($message['sender_name']); ?></span>
                    <span class="message-time"><?php echo date('M j, g:i A', strtotime($message['created_at'])); ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="avatar"><?php echo strtoupper(substr($consultant_name, 0, 1)); ?></div>
            <div>
                <div class="message-content">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </div>
                <div class="message-info">
                    <span class="sender-name"><?php echo htmlspecialchars($message['sender_name']); ?></span>
                    <span class="message-time"><?php echo date('M j, g:i A', strtotime($message['created_at'])); ?></span>
                </div>
            </div>
        <?php endif; ?>
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