<?php
require_once '../config/db.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Check if user is logged in
checkUserLogin();

// Get consultant ID from URL
$consultant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch consultant details
$sql = "SELECT c.*, u.name FROM consultants c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.id = $consultant_id";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: consultants.php");
    exit;
}

$consultant = mysqli_fetch_assoc($result);

// Fetch consultant's available slots
$sql = "SELECT * FROM availability 
        WHERE consultant_id = $consultant_id 
        AND is_booked = 0 
        AND slot_date >= CURDATE()
        ORDER BY slot_date, slot_time";
$availability = mysqli_query($conn, $sql);

// Display booking form
// You'll implement the HTML/CSS

// Now it's safe to include HTML output
include 'includes/user_navbar.php';

// Get current user ID from session
session_start();
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch upcoming appointments
$sql_upcoming = "SELECT a.*, c.id as consultant_id, u.name as consultant_name, av.slot_date, av.slot_time
    FROM appointments a
    JOIN consultants c ON a.consultant_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN availability av ON a.availability_id = av.id
    WHERE a.user_id = $user_id
      AND a.status = 'approved'
      AND (av.slot_date > CURDATE() OR (av.slot_date = CURDATE() AND av.slot_time >= CURTIME()))
    ORDER BY av.slot_date, av.slot_time";
$upcoming = mysqli_query($conn, $sql_upcoming);

// Fetch past appointments
$sql_past = "SELECT a.*, c.id as consultant_id, u.name as consultant_name, av.slot_date, av.slot_time
    FROM appointments a
    JOIN consultants c ON a.consultant_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN availability av ON a.availability_id = av.id
    WHERE a.user_id = $user_id
      AND a.status = 'approved'
      AND (av.slot_date < CURDATE() OR (av.slot_date = CURDATE() AND av.slot_time < CURTIME()))
    ORDER BY av.slot_date DESC, av.slot_time DESC";
$past = mysqli_query($conn, $sql_past);
?>

<div class="container" style="max-width: 900px; margin: 0 auto;">
    <h2 class="section-title" style="margin-top:2rem;">Upcoming Appointments</h2>
    <?php if(mysqli_num_rows($upcoming) > 0): ?>
        <div class="appointments-list">
            <?php while($row = mysqli_fetch_assoc($upcoming)): ?>
                <div class="appointment-card">
                    <div>
                        <strong><?php echo htmlspecialchars($row['consultant_name']); ?></strong>
                        <span class="appointment-date">
                            <?php echo date('M j, Y', strtotime($row['slot_date'])); ?>
                            at <?php 
                                $start_time = strtotime($row['slot_time']);
                                $end_time = strtotime($row['slot_time'] . ' +1 hour');
                                echo date('g:i A', $start_time) . ' - ' . date('g:i A', $end_time);
                            ?>
                        </span>
                    </div>
                    <div>
                        <span class="badge badge-success">Confirmed</span>
                        <!-- Optionally, add a cancel button here -->
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-appointments">No upcoming appointments.</div>
    <?php endif; ?>

    <h2 class="section-title" style="margin-top:2.5rem;">Past Appointments</h2>
    <?php if(mysqli_num_rows($past) > 0): ?>
        <div class="appointments-list">
            <?php while($row = mysqli_fetch_assoc($past)): ?>
                <div class="appointment-card">
                    <div>
                        <strong><?php echo htmlspecialchars($row['consultant_name']); ?></strong>
                        <span class="appointment-date">
                            <?php echo date('M j, Y', strtotime($row['slot_date'])); ?>
                            at <?php 
                                $start_time = strtotime($row['slot_time']);
                                $end_time = strtotime($row['slot_time'] . ' +1 hour');
                                echo date('g:i A', $start_time) . ' - ' . date('g:i A', $end_time);
                            ?>
                        </span>
                    </div>
                    <div>
                        <span class="badge badge-secondary">Completed</span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-appointments">No past appointments.</div>
    <?php endif; ?>
</div>

<style>
.appointments-list {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    margin-bottom: 2rem;
}
.appointment-card {
    background: #2d3a5a;
    color: #e0e6f7;
    border-radius: 1.2rem;
    padding: 1.2rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.10);
}
.appointment-date {
    display: block;
    color: #8ca3f8;
    font-size: 1rem;
    margin-top: 0.2rem;
}
.badge {
    display: inline-block;
    padding: 0.4em 1em;
    border-radius: 1em;
    font-size: 0.95em;
    font-weight: 600;
}
.badge-success {
    background: #10b981;
    color: #fff;
}
.badge-secondary {
    background: #64748b;
    color: #fff;
}
.no-appointments {
    color: #8ca3f8;
    background: #23272f;
    border-radius: 1rem;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 2rem;
}
</style>
