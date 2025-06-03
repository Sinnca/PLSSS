<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
checkUserLogin();

// Get user's messages and replies
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$sql = "SELECT cm.*, mr.reply_content, mr.replied_at, u.name as admin_name 
        FROM contact_messages cm 
        LEFT JOIN message_replies mr ON cm.id = mr.message_id 
        LEFT JOIN users u ON mr.replied_by = u.id 
        WHERE cm.user_id = $user_id 
        ORDER BY cm.created_at DESC";
$result = mysqli_query($conn, $sql);

// Consultant message filters
$filter_consultant_date = isset($_GET['filter_consultant_date']) ? $_GET['filter_consultant_date'] : '';
$filter_consultant_name = isset($_GET['filter_consultant_name']) ? trim($_GET['filter_consultant_name']) : '';
$sql = "SELECT a.id as appointment_id, cu.name as consultant_name, cu.email as consultant_email, c.specialty, av.slot_date, av.slot_time, a.status
        FROM appointments a
        JOIN consultants c ON a.consultant_id = c.id
        JOIN users cu ON c.user_id = cu.id
        JOIN availability av ON a.availability_id = av.id
        WHERE a.user_id = ? AND a.status NOT IN ('cancelled', 'rejected')";
$params = [$user_id];
$types = "i";
if ($filter_consultant_date) {
    $sql .= " AND av.slot_date = ?";
    $params[] = $filter_consultant_date;
    $types .= "s";
}
if ($filter_consultant_name) {
    $sql .= " AND cu.name LIKE ?";
    $params[] = '%' . $filter_consultant_name . '%';
    $types .= "s";
}
$sql .= " ORDER BY av.slot_date DESC, av.slot_time DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$appointment_result = mysqli_stmt_get_result($stmt);

$sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM appointments WHERE user_id = $user_id";

$sql = "SELECT COUNT(*) as completed FROM appointments a JOIN availability av ON a.availability_id = av.id WHERE a.user_id = $user_id AND a.status = 'approved' AND av.slot_date < '$today'";

$sql = "SELECT a.*, av.slot_date, av.slot_time, av.duration, c.specialty, cu.name AS consultant_name
        FROM appointments a
        JOIN availability av ON a.availability_id = av.id
        JOIN consultants c ON a.consultant_id = c.id
        JOIN users cu ON c.user_id = cu.id
        WHERE a.user_id = $user_id AND av.slot_date < '$today'
        ORDER BY av.slot_date DESC, av.slot_time DESC";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Messages - User Dashboard</title>
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

        .page-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 2rem;
        }

        .messages-header {
            margin-bottom: 2.5rem;
            text-align: center;
            position: relative;
        }
        
        .messages-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }
        
        .messages-header h1:after {
            content: '';
            position: absolute;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }
        
        .messages-header p {
            color: var(--light-text);
            font-size: 1.1rem;
            margin-top: 1rem;
        }

        .messages-container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .message-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .message-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }
        
        .message-card:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-light));
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .message-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .message-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        }

        .message-meta {
            display: flex;
            flex-direction: column;
        }

        .message-sender {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-color);
        }

        .message-date {
            font-size: 0.875rem;
            color: var(--light-text);
            margin-top: 0.25rem;
        }

        .message-content {
            margin: 1rem 0;
            padding-left: 0.5rem;
        }

        .message-subject {
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 0.75rem;
            color: var(--primary-dark);
        }

        .message-text {
            line-height: 1.7;
            color: var(--text-color);
            font-size: 1rem;
        }

        .reply-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px dashed rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .reply-section:before {
            content: '';
            position: absolute;
            left: 24px;
            top: 0;
            height: 1.5rem;
            width: 2px;
            background: rgba(0, 0, 0, 0.1);
        }

        .reply-content {
            background: var(--light-blue);
            padding: 1.25rem;
            border-radius: 12px;
            margin-top: 0.75rem;
            position: relative;
            border-top-left-radius: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
            transition: background 0.2s ease;
        }
        
        .reply-content:hover {
            background: var(--light-blue-hover);
        }
        
        .reply-content:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 15px;
            height: 15px;
            background: var(--light-blue);
            clip-path: polygon(0 0, 0% 100%, 100% 0);
        }

        .status-badge {
            padding: 0.35rem 1rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .status-replied {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 2.5rem 0 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
            position: relative;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -2px;
            width: 80px;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        }
        
        .list-group {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .list-group-item {
            padding: 1.25rem;
            border-left: none;
            border-right: none;
            transition: all 0.2s ease;
            background: var(--white);
        }
        
        .list-group-item:hover {
            background: rgba(219, 234, 254, 0.3);
        }
        
        .list-group-item:first-child {
            border-top: none;
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 30px;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3);
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        }
        
        .alert-info {
            background: rgba(219, 234, 254, 0.5);
            border: 1px solid rgba(37, 99, 235, 0.2);
            border-radius: 12px;
            padding: 1.25rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .alert-info:before {
            content: '\f05a';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            font-size: 1.5rem;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="page-container">
        <div class="messages-header">
            <h1>My Messages</h1>
            <p>View your messages and replies from administrators and consultants</p>
        </div>
        
        <div class="messages-container">
            <div class="section-title">Contact Messages</div>
            
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($message = mysqli_fetch_assoc($result)): ?>
                    <div class="message-card">
                        <div class="message-header">
                            <div class="message-info">
                                <div class="message-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="message-meta">
                                    <span class="message-sender">You</span>
                                    <span class="message-date"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                                </div>
                            </div>
                            <span class="status-badge <?php echo $message['is_replied'] ? 'status-replied' : 'status-pending'; ?>">
                                <i class="fas <?php echo $message['is_replied'] ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                                <?php echo $message['is_replied'] ? 'Replied' : 'Pending'; ?>
                            </span>
                        </div>

                        <div class="message-content">
                            <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                            <div class="message-text"><?php echo nl2br(htmlspecialchars($message['message'])); ?></div>
                        </div>

                        <?php if ($message['is_replied'] && $message['reply_content']): ?>
                            <div class="reply-section">
                                <div class="message-info">
                                    <div class="message-avatar">
                                        <i class="fas fa-user-shield"></i>
                                    </div>
                                    <div class="message-meta">
                                        <span class="message-sender">Reply from <?php echo htmlspecialchars($message['admin_name']); ?></span>
                                        <span class="message-date"><?php echo date('M j, Y g:i A', strtotime($message['replied_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="reply-content">
                                    <?php echo nl2br(htmlspecialchars($message['reply_content'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    You haven't sent any contact messages yet.
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section-title">Consultant Messages</div>
        <form method="get" class="row g-3 align-items-end mb-4" style="max-width: 700px;">
            <div class="col-auto">
                <label for="filter_consultant_date" class="form-label mb-1 fw-bold text-primary">Filter by Date</label>
                <input type="date" class="form-control form-control-lg border-0 shadow-sm" style="background:#e0e7ff;color:#2563eb;" id="filter_consultant_date" name="filter_consultant_date" value="<?php echo htmlspecialchars($filter_consultant_date); ?>">
            </div>
            <div class="col-auto">
                <label for="filter_consultant_name" class="form-label mb-1 fw-bold text-primary">Filter by Consultant Name</label>
                <input type="text" class="form-control form-control-lg border-0 shadow-sm" style="background:#e0e7ff;color:#2563eb;min-width:170px;" id="filter_consultant_name" name="filter_consultant_name" placeholder="Enter consultant name" value="<?php echo htmlspecialchars($filter_consultant_name); ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-lg px-4" style="border-radius:0.7rem;background:linear-gradient(90deg,#2563eb 0%,#60a5fa 100%);border:none;">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
            </div>
            <?php if ($filter_consultant_date || $filter_consultant_name): ?>
            <div class="col-auto">
                <a href="messages.php" class="btn btn-outline-secondary btn-lg px-4" style="border-radius:0.7rem;">
                    <i class="fa-solid fa-times"></i> Clear
                </a>
            </div>
            <?php endif; ?>
        </form>
        <div class="list-group">
            <?php while ($row = mysqli_fetch_assoc($appointment_result)): ?>
                <?php
                $appointmentDateTime = strtotime($row['slot_date'] . ' ' . $row['slot_time']);
                if ($appointmentDateTime < time()) continue;
                // Check if there is a new reply from consultant for this appointment
                $reply_sql = "SELECT COUNT(*) as unread_count FROM appointment_messages WHERE appointment_id = ? AND sender_type = 'consultant' AND is_read = 0";
                $reply_stmt = mysqli_prepare($conn, $reply_sql);
                mysqli_stmt_bind_param($reply_stmt, "i", $row['appointment_id']);
                mysqli_stmt_execute($reply_stmt);
                $reply_result = mysqli_stmt_get_result($reply_stmt);
                $reply_data = mysqli_fetch_assoc($reply_result);
                $unread_count = $reply_data['unread_count'] ?? 0;
                ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?php echo htmlspecialchars($row['consultant_name']); ?></strong>
                        <div>
                            <small><?php echo htmlspecialchars($row['specialty']); ?></small>
                            <br>
                            <small><?php echo date('F j, Y', strtotime($row['slot_date'])) . ' at ' . date('g:i A', strtotime($row['slot_time'])); ?></small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-success">1 new</span>
                        <?php endif; ?>
                        <?php 
                        // Check if there's a meeting link for this appointment
                        $meeting_sql = "SELECT meeting_link FROM appointments WHERE id = ?";
                        $meeting_stmt = mysqli_prepare($conn, $meeting_sql);
                        mysqli_stmt_bind_param($meeting_stmt, "i", $row['appointment_id']);
                        mysqli_stmt_execute($meeting_stmt);
                        $meeting_result = mysqli_stmt_get_result($meeting_stmt);
                        $meeting_data = mysqli_fetch_assoc($meeting_result);
                        $meeting_link = $meeting_data['meeting_link'] ?? '';
                        ?>
                        <?php if (!empty($meeting_link)): 
                            // Ensure the link has https://
                            $formatted_link = $meeting_link;
                            if (!preg_match("~^(?:f|ht)tps?://~i", $meeting_link)) {
                                $formatted_link = 'https://' . ltrim($meeting_link, '/');
                            }
                        ?>
                            <a href="<?php echo htmlspecialchars($formatted_link); ?>" 
                               target="_blank" 
                               class="btn btn-success"
                               style="background: linear-gradient(135deg, #10b981, #059669); border: none;"
                               title="Join meeting: <?php echo htmlspecialchars($formatted_link); ?>">
                                <i class="fas fa-video"></i> Join Meeting
                            </a>
                        <?php endif; ?>
                        <a href="appointment_messages.php?appointment_id=<?php echo $row['appointment_id']; ?>" 
                           class="btn btn-primary"
                           style="background: linear-gradient(135deg, #3b82f6, #2563eb); border: none;">
                            <i class="fas fa-comments"></i> Message
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 