<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if admin is logged in
checkAdminLogin();

// Create message_replies table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS message_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    reply_content TEXT NOT NULL,
    replied_by INT NOT NULL,
    replied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES contact_messages(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_table);

// Add user_id column to contact_messages table if it doesn't exist
$check_column = "SHOW COLUMNS FROM contact_messages LIKE 'user_id'";
$column_exists = mysqli_query($conn, $check_column);

if (mysqli_num_rows($column_exists) == 0) {
    $add_column = "ALTER TABLE contact_messages ADD COLUMN user_id INT NULL AFTER id";
    mysqli_query($conn, $add_column);
}

// Add is_replied column to contact_messages table if it doesn't exist
$check_column = "SHOW COLUMNS FROM contact_messages LIKE 'is_replied'";
$column_exists = mysqli_query($conn, $check_column);

if (mysqli_num_rows($column_exists) == 0) {
    $add_column = "ALTER TABLE contact_messages ADD COLUMN is_replied BOOLEAN DEFAULT FALSE";
    mysqli_query($conn, $add_column);
}

// Add replied_at column to contact_messages table if it doesn't exist
$check_column = "SHOW COLUMNS FROM contact_messages LIKE 'replied_at'";
$column_exists = mysqli_query($conn, $check_column);

if (mysqli_num_rows($column_exists) == 0) {
    $add_column = "ALTER TABLE contact_messages ADD COLUMN replied_at DATETIME NULL";
    mysqli_query($conn, $add_column);
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $message_id = intval($_POST['message_id']);
    $reply_content = mysqli_real_escape_string($conn, $_POST['reply_content']);
    
    // Get the original message
    $get_message = "SELECT * FROM contact_messages WHERE id = $message_id";
    $message_result = mysqli_query($conn, $get_message);
    $message = mysqli_fetch_assoc($message_result);
    
    if ($message) {
        // Store the reply in the database
        $store_reply = "INSERT INTO message_replies (message_id, reply_content, replied_by, replied_at) 
                       VALUES ($message_id, '$reply_content', '{$_SESSION['user_id']}', NOW())";
        
        if (mysqli_query($conn, $store_reply)) {
            // Update message status
            $update_sql = "UPDATE contact_messages SET is_replied = 1, replied_at = NOW() WHERE id = $message_id";
            mysqli_query($conn, $update_sql);
            $_SESSION['success_message'] = "Reply stored successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to store reply. Please try again.";
        }
    }
    
    header("Location: messages.php");
    exit;
}

// Get all messages with their replies
$sql = "SELECT cm.*, mr.reply_content, mr.replied_at, u.name as admin_name 
        FROM contact_messages cm 
        LEFT JOIN message_replies mr ON cm.id = mr.message_id 
        LEFT JOIN users u ON mr.replied_by = u.id 
        ORDER BY cm.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #1e293b;
            --primary-light: #3b82f6;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text-light: #f1f5f9;
            --text-muted: #cbd5e1;
            --border-light: #334155;
            --bg-dark: #1e293b;
            --bg-gradient: linear-gradient(135deg, #1e293b 0%, #2563eb 100%);
            --card-bg: #22305a;
            --shadow: 0 4px 24px rgba(30,64,175,0.18);
            --radius: 1.2rem;
        }
        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            color: var(--text-light);
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
        }
        .header-section {
            background: linear-gradient(90deg, #1e40af 60%, #2563eb 100%);
            color: #fff;
            border-radius: 1.5rem 1.5rem 0 0;
            box-shadow: 0 4px 24px rgba(30,64,175,0.18);
            padding: 2.5rem 2rem 2rem 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        .header-section .page-title {
            color: #fff;
            font-size: 2.1rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
            text-shadow: 0 2px 8px rgba(30,64,175,0.18);
        }
        .header-section .page-subtitle {
            color: #c7d2fe;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0;
        }
        .page-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .message-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
            transition: transform 0.2s ease;
            color: var(--text-light);
        }
        .message-card:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 24px rgba(30,64,175,0.22);
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .message-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .message-avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #2563eb 60%, #3b82f6 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.3rem;
            box-shadow: 0 2px 8px rgba(30,64,175,0.10);
        }
        .message-meta {
            display: flex;
            flex-direction: column;
        }
        .message-sender {
            font-weight: 600;
            color: var(--text-light);
        }
        .message-date {
            font-size: 0.95rem;
            color: var(--text-muted);
        }
        .message-content {
            background: #24345c;
            padding: 1rem;
            border-radius: 0.7rem;
            margin-bottom: 1rem;
            border: 1px solid var(--primary-light);
            color: var(--text-light);
        }
        .message-subject {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #60a5fa;
        }
        .message-text {
            color: var(--text-light);
            line-height: 1.6;
        }
        .reply-form {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--primary-light);
        }
        .reply-textarea {
            width: 100%;
            min-height: 100px;
            padding: 0.75rem;
            border-radius: 0.7rem;
            border: 1.5px solid var(--border-light);
            background: #1e293b;
            font-size: 1rem;
            color: var(--text-light);
            margin-bottom: 0.7rem;
        }
        .reply-textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px #3b82f6;
        }
        .btn-primary {
            background: linear-gradient(90deg, #4361ee, #2563eb);
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 2rem;
            box-shadow: 0 2px 8px rgba(30,64,175,0.10);
            transition: background 0.2s, transform 0.2s;
            padding: 0.7rem 2rem;
        }
        .btn-primary:hover {
            background: #1e40af;
            transform: translateY(-2px) scale(1.04);
        }
        .alert {
            border-radius: 12px;
            border-left: 5px solid #2563eb;
            background: #22305a;
            color: #c7d2fe;
            box-shadow: 0 2px 8px rgba(30,64,175,0.08);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 700px) {
            .header-section {
                padding: 1.2rem 0.7rem 1.2rem 0.7rem;
            }
            .header-section .page-title {
                font-size: 1.3rem;
            }
            .page-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="page-container">
        <div class="header-section mb-4">
            <div>
                <h1 class="page-title">Users Message</h1>
                <p class="page-subtitle">Reply to all users concern from contact page</p>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($message = mysqli_fetch_assoc($result)): ?>
                <div class="message-card">
                    <div class="message-header">
                        <div class="message-info">
                            <div class="message-avatar">
                                <?php echo strtoupper(substr($message['name'], 0, 1)); ?>
                            </div>
                            <div class="message-meta">
                                <span class="message-sender"><?php echo htmlspecialchars($message['name']); ?></span>
                                <span class="message-date"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                            </div>
                        </div>
                        <span class="status-badge <?php echo $message['is_replied'] ? 'status-replied' : 'status-pending'; ?>">
                            <?php echo $message['is_replied'] ? 'Replied' : 'Pending'; ?>
                        </span>
                    </div>

                    <div class="message-content">
                        <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                        <div class="message-text"><?php echo nl2br(htmlspecialchars($message['message'])); ?></div>
                    </div>

                    <?php if (!$message['is_replied']): ?>
                        <div class="reply-form">
                            <form action="messages.php" method="POST">
                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                <textarea name="reply_content" class="reply-textarea" placeholder="Type your reply here..." required></textarea>
                                <button type="submit" name="reply" class="btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Send Reply
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">
                No messages found.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 