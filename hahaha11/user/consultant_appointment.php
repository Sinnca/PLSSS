<?php
session_start();
require_once '../config/db.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

$today = date('Y-m-d');

// Check if user is logged in
checkUserLogin();

// Get consultant ID from URL
$consultant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($consultant_id <= 0) {
    header("Location: consultants.php");
    exit;
}

// Fetch consultant details
$sql = "SELECT c.*, u.name, u.email FROM consultants c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = $consultant_id";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: consultants.php");
    exit;   
}

$consultant = mysqli_fetch_assoc($result);

// Handle form submission for booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $slot_id = intval($_POST['slot_id']);
    $user_id = $_SESSION['user_id'];
    
    // Check if slot exists and is not booked
    $check_sql = "SELECT * FROM availability WHERE id = $slot_id AND consultant_id = $consultant_id AND is_booked = 0";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $slot = mysqli_fetch_assoc($check_result);
        
        // Create appointment
        $insert_sql = "INSERT INTO appointments (user_id, consultant_id, availability_id, status, created_at) 
                      VALUES ($user_id, $consultant_id, $slot_id, 'pending', NOW())";   
        
        if (mysqli_query($conn, $insert_sql)) {
            // Mark slot as booked
            mysqli_query($conn, "UPDATE availability SET is_booked = 1 WHERE id = $slot_id");
            
            $success_message = "Your appointment has been booked successfully. The consultant will contact you shortly.";
        } else {
            $error_message = "Failed to book appointment. Please try again.";
        }
    } else {
        $error_message = "This time slot is no longer available. Please select another time.";
    }
}

// Fetch consultant's available slots grouped by date
$sql = "SELECT * FROM availability
        WHERE consultant_id = $consultant_id
        AND is_booked = 0
        AND slot_date >= CURDATE()
        ORDER BY slot_date, slot_time";
$availability_result = mysqli_query($conn, $sql);

// Group availability by date
$dates = [];
while ($slot = mysqli_fetch_assoc($availability_result)) {
    $slot['end_time'] = date('H:i:s', strtotime($slot['slot_time']) + ($slot['duration'] * 60));
    $dates[$slot['slot_date']][] = $slot;
}

// Collect all unique durations from the available slots
$all_durations = [];
foreach ($dates as $date => $time_slots) {
    foreach ($time_slots as $slot) {
        $all_durations[$slot['duration']] = true;
    }
}
$all_durations = array_keys($all_durations);
// Sort durations ascending
sort($all_durations);

// Set the selected date to the first available date by default, or to the posted value if set
$selected_date = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_date'])) {
    $selected_date = $_POST['appointment_date'];
} elseif (!empty($dates)) {
    $selected_date = array_key_first($dates);
}

// Get user's upcoming appointments with this consultant
$user_id = $_SESSION['user_id'];
$appointments_sql = "SELECT a.*, av.slot_date, av.slot_time, av.duration, c.specialty, cu.name AS consultant_name, c.id AS consultant_id
                    FROM appointments a
                    JOIN availability av ON a.availability_id = av.id
                    JOIN consultants c ON a.consultant_id = c.id
                    JOIN users cu ON c.user_id = cu.id
                    WHERE a.user_id = $user_id 
                    AND a.consultant_id = $consultant_id
                    AND (av.slot_date > '$today' OR (av.slot_date = '$today' AND av.slot_time >= CURTIME()))
                    ORDER BY av.slot_date ASC, av.slot_time ASC";
$appointments_result = mysqli_query($conn, $appointments_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultant Appointment - Appointment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --text-color: #1e293b;
            --light-text: #64748b;
            --background: #f8fafc;
            --white: #ffffff;
            --error-bg: #fee2e2;
            --error-text: #b91c1c;
            --success-color: #10b981;
            --border-color: #e2e8f0;
            --input-bg: #f1f5f9;
            --input-focus: #e0f2fe;
            --gradient-start: #2563eb;
            --gradient-end: #1d4ed8;
        }
        .navbar {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            box-shadow: 0 2px 15px rgba(37, 99, 235, 0.2);
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
            transform: translateY(-2px);
        }
        .navbar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white) !important;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
        }
        .dropdown-item {
            padding: 0.7rem 1.5rem;
            transition: all 0.3s ease;
        }
        .dropdown-item:hover {
            background-color: var(--background);
            color: var(--primary-color);
        }
        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: #e5e7eb;
        }
        .appointments-list {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }
        .time-slot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 10px;
            background-color: var(--white);
            transition: all 0.3s ease;
        }
        
        .time-slot:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
        }
        
        .time-range {
            font-weight: 500;
            color: var(--text-color);
        }
        
        .duration {
            font-size: 0.9rem;
            color: var(--light-text);
            background-color: var(--input-bg);
            padding: 4px 8px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <!-- Consultant Profile Header -->
    <section class="profile-header">
        <div class="container">
            <div class="profile-header-content">
                <div class="profile-avatar">
                    <?php 
                    if (!empty($consultant['profile_photo'])) {
                        echo '<img src="../' . htmlspecialchars($consultant['profile_photo']) . '" alt="' . htmlspecialchars($consultant['name']) . '">';
                    } else {
                        $initials = strtoupper(substr($consultant['name'], 0, 1));
                        echo $initials;
                    }
                    ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($consultant['name']); ?></h1>
                    <span class="profile-specialty"><?php echo htmlspecialchars($consultant['specialty']); ?></span>
                    <?php if (isset($consultant['location'])): ?>
                    <span class="profile-location">
                        <i class="fa-solid fa-location-dot"></i>
                        <?php echo htmlspecialchars($consultant['location']); ?>
                    </span>
                    <?php endif; ?>
                    <div class="profile-rating">
                        <?php 
                        $rating = isset($consultant['rating']) ? $consultant['rating'] : 0;
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="fa-solid fa-star"></i>';
                            } else {
                                echo '<i class="fa-regular fa-star"></i>';
                            }
                        }
                        if (isset($consultant['reviews_count'])): ?>
                        <span class="reviews-count">(<?php echo $consultant['reviews_count']; ?> reviews)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container main-content">
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="content-sidebar">
                <div class="info-card">
                    <h3>About the Consultant</h3>
                    <div class="consultant-bio">
                        <?php 
                        if (!empty($consultant['bio'])) {
                            echo '<p>' . nl2br(htmlspecialchars($consultant['bio'])) . '</p>';
                        } else {
                            echo '<p>Professional consultant specializing in ' . htmlspecialchars($consultant['specialty']) . '.</p>';
                        }
                        ?>
                    </div>
                    
                    <div class="info-section">
                        <h4>Expertise</h4>
                        <div class="expertise-tags">
                            <?php
                            // Display main specialty
                            echo '<span class="tag">' . htmlspecialchars($consultant['specialty']) . '</span>';
                            
                            // If there are additional specialties/skills, display them
                            if (!empty($consultant['skills'])) {
                                $skills = explode(',', $consultant['skills']);
                                foreach ($skills as $skill) {
                                    echo '<span class="tag">' . htmlspecialchars(trim($skill)) . '</span>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    
                    <?php if (isset($consultant['experience'])): ?>
                    <div class="info-section">
                        <h4>Experience</h4>
                        <p><i class="fa-solid fa-briefcase"></i> <?php echo htmlspecialchars($consultant['experience']); ?> Years</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-section">
                        <h4>Consultation Rate</h4>
                        <p class="consultation-rate">
                            <i class="fa-solid fa-dollar-sign"></i>
                            <?php echo isset($consultant['hourly_rate']) ? htmlspecialchars($consultant['hourly_rate']) : '50'; ?> / hour
                        </p>
                    </div>

                    <?php if (!empty($consultant['cv_file'])): ?>
                    <div class="info-section">
                        <h4>Professional CV</h4>
                        <a href="../<?php echo htmlspecialchars($consultant['cv_file']); ?>" class="btn btn-outline-primary" target="_blank">
                            <i class="fas fa-file-alt me-2"></i>View CV/Resume
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Feedback Section -->
                    <div class="info-section" style="margin-top:2rem;">
                        <h4 style="color:#2563eb;">User Feedback</h4>
                        <?php
                        // Fetch all feedback for this consultant
                        $feedback_sql = "SELECT af.*, u.name as user_name FROM appointment_feedback af JOIN users u ON af.user_id = u.id WHERE af.consultant_id = ? ORDER BY af.created_at DESC";
                        $stmt = mysqli_prepare($conn, $feedback_sql);
                        mysqli_stmt_bind_param($stmt, 'i', $consultant['id']);
                        mysqli_stmt_execute($stmt);
                        $feedbacks = mysqli_stmt_get_result($stmt);
                        $total_feedback = 0;
                        $sum_rating = 0;
                        $feedback_list = [];
                        while ($row = mysqli_fetch_assoc($feedbacks)) {
                            $feedback_list[] = $row;
                            $sum_rating += $row['rating'];
                            $total_feedback++;
                        }
                        $avg_rating = $total_feedback ? round($sum_rating / $total_feedback, 2) : 0;
                        ?>
                        <div style="margin-bottom:0.7rem;">
                            <span style="font-size:1.3rem; color:#ffc107;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= round($avg_rating)): ?>
                                        <i class="fa-solid fa-star"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </span>
                            <span style="color:#2563eb; font-weight:600; margin-left:0.5rem;">
                                <?php echo $avg_rating; ?> / 5.0 (<?php echo $total_feedback; ?> reviews)
                            </span>
                        </div>
                        <?php if ($total_feedback > 0): ?>
                            <div style="max-height:220px; overflow-y:auto;">
                                <?php foreach ($feedback_list as $fb): ?>
                                    <div style="background:#f8f9ff; border-radius:0.7rem; padding:0.8rem 1rem; margin-bottom:0.7rem; box-shadow:0 1px 4px rgba(80,120,255,0.06);">
                                        <div style="color:#ffc107; font-size:1.1rem;">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $fb['rating']): ?>
                                                    <i class="fa-solid fa-star"></i>
                                                <?php else: ?>
                                                    <i class="fa-regular fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <div style="margin:0.3rem 0; color:#222; font-size:1rem;">
                                            <?php echo nl2br(htmlspecialchars($fb['feedback'])); ?>
                                        </div>
                                        <div style="color:#64748b; font-size:0.95rem;">
                                            <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($fb['user_name']); ?>
                                            <span style="margin-left:0.7rem;"><i class="fa-solid fa-clock"></i> <?php echo date('M j, Y', strtotime($fb['created_at'])); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="color:#64748b;">No feedback yet for this consultant.</div>
                        <?php endif; ?>

                        <!-- Add Give Feedback Button -->
                        <?php
                        // Check if user has completed appointments with this consultant
                        $check_appointment_sql = "SELECT a.id FROM appointments a 
                                                JOIN availability av ON a.availability_id = av.id 
                                                WHERE a.user_id = ? 
                                                AND a.consultant_id = ? 
                                                AND a.status = 'completed' 
                                                AND NOT EXISTS (
                                                    SELECT 1 FROM appointment_feedback 
                                                    WHERE appointment_id = a.id
                                                )
                                                LIMIT 1";
                        $stmt = mysqli_prepare($conn, $check_appointment_sql);
                        mysqli_stmt_bind_param($stmt, 'ii', $_SESSION['user_id'], $consultant_id);
                        mysqli_stmt_execute($stmt);
                        $appointment_result = mysqli_stmt_get_result($stmt);
                        
                        if ($appointment = mysqli_fetch_assoc($appointment_result)) {
                            echo '<button class="btn btn-primary mt-3" onclick="openFeedbackModal(' . $appointment['id'] . ', ' . $consultant_id . ')">
                                    <i class="fa-solid fa-star me-2"></i>Give Feedback
                                  </button>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="content-main">
                <div class="booking-card">
                    <h2>Book an Appointment</h2>
                    <?php if (count($dates) > 0): ?>
                    <form method="POST" action="" id="booking-form">
                        <div class="form-section">
                            <h3>1. Select a Date</h3>
                            <div class="mb-3">
                                <label for="appointment-date" class="form-label fw-bold">1. Select a Date</label>
                                <select id="appointment-date" class="form-select" name="appointment_date" required>
                                    <?php foreach (array_keys($dates) as $date): ?>
                                        <option value="<?php echo $date; ?>" <?php if ($date === $selected_date) echo 'selected'; ?>>
                                            <?php echo date('D, M j', strtotime($date)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>2. Select a Time</h3>
                            <div class="mb-3">
                                <label for="duration-filter" class="form-label fw-bold">Filter by Duration</label>
                                <select id="duration-filter" class="form-select">
                                    <option value="all">All Durations</option>
                                    <?php foreach ($all_durations as $duration): ?>
                                        <option value="<?php echo $duration; ?>">
                                            <?php 
                                                if ($duration == 30) echo '30 minutes';
                                                elseif ($duration == 60) echo '1 hour';
                                                elseif ($duration == 90) echo '1.5 hours';
                                                elseif ($duration == 120) echo '2 hours';
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php $first_date = true; foreach ($dates as $date => $time_slots): ?>
                            <div class="time-slots-container" id="date-<?php echo $date; ?>" <?php echo $first_date ? '' : 'style="display: none;"'; ?>>
                                <div class="time-slots">
                                    <?php foreach ($time_slots as $slot): ?>
                                    <div class="time-slot" data-duration="<?php echo $slot['duration']; ?>">
                                        <input type="radio" 
                                               name="slot_id" 
                                               id="slot-<?php echo $slot['id']; ?>" 
                                               value="<?php echo $slot['id']; ?>" 
                                               data-duration="<?php echo $slot['duration']; ?>"
                                               required>
                                        <label for="slot-<?php echo $slot['id']; ?>">
                                            <?php 
                                                $start_time = date('g:i A', strtotime($slot['slot_time']));
                                                $end_time = date('g:i A', strtotime($slot['slot_time']) + ($slot['duration'] * 60));
                                                echo $start_time . ' - ' . $end_time;
                                                echo ' (' . ($slot['duration'] == 30 ? '30 min' : ($slot['duration'] == 60 ? '1 hr' : ($slot['duration'] / 60) . ' hrs')) . ')';
                                            ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php $first_date = false; endforeach; ?>
                        </div>
                        
                        <div class="form-section">
                            <h3>3. Confirm Booking</h3>
                            <div class="booking-summary" style="background: #f8f9fa; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem;">
                                <h4 style="color: #2d3748; margin-bottom: 1.25rem; font-size: 1.1rem; font-weight: 600;">
                                    <i class="fas fa-calendar-check me-2" style="color: #2563eb;"></i>
                                    Appointment Summary
                                </h4>
                                
                                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0;">
                                    <span style="color: #4a5568;">Consultant:</span>
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($consultant['name']); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0;">
                                    <span style="color: #4a5568;">Specialty:</span>
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($consultant['specialty']); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0;">
                                    <span style="color: #4a5568;">Rate:</span>
                                    <span style="font-weight: 500;">
                                        $<?php echo isset($consultant['hourly_rate']) ? htmlspecialchars($consultant['hourly_rate']) : '50'; ?>/hour
                                        <span style="color: #4a5568; font-size: 0.9rem;">(billed after session)</span>
                                    </span>
                                </div>
                                
                                <div style="background: #f0f7ff; padding: 0.75rem; border-radius: 6px; margin-top: 1rem; margin-bottom: 1.5rem; border-left: 4px solid #3b82f6;">
                                    <div style="display: flex; align-items: flex-start;">
                                        <i class="fas fa-credit-card" style="color: #3b82f6; margin-right: 0.5rem; margin-top: 0.2rem;"></i>
                                        <div>
                                            <p style="margin: 0 0 10px 0; color: #1e40af; font-size: 0.95rem; font-weight: 500;">
                                                Payment will be processed during your consultation session.
                                            </p>
                                            <p style="margin: 0 0 10px 0; color: #2563eb; font-size: 0.9rem;">
                                                Please prepare your preferred payment method for the session.
                                            </p>
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openPaymentModal()">
                                                <i class="fas fa-wallet me-1"></i> View Payment Options
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Payment method is now optional -->
                                <input type="hidden" name="payment_method" id="payment-method" value="pay_later">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="book_appointment" class="btn btn-primary btn-lg" id="confirm-booking-btn">
                                    <i class="fa-solid fa-calendar-check me-2"></i> Confirm Booking
                                </button>
                                <p class="text-muted text-center mt-2" style="font-size: 0.85rem;">
                                    <i class="fas fa-lock me-1"></i> Your information is secure and encrypted
                                </p>
                            </div>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="no-availability">
                        <div class="no-results-icon">
                            <i class="fa-solid fa-calendar-xmark"></i>
                        </div>
                        <h3>No Available Slots</h3>
                        <p>This consultant doesn't have any available slots at the moment. Please check back later or choose another consultant.</p>
                        <a href="consultants.php" class="btn btn-outline">
                            <i class="fa-solid fa-arrow-left"></i> Back to Consultants
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-card your-appointments">
                <h3>Your Upcoming Appointments</h3>
                <?php if (mysqli_num_rows($appointments_result) > 0): ?>
                <div class="appointments-list">
                    <?php while ($appointment = mysqli_fetch_assoc($appointments_result)): ?>
                    <div class="appointment-item">
                        <div class="appointment-date">
                            <i class="fa-solid fa-calendar-day"></i>
                            <?php echo date('F j, Y', strtotime($appointment['slot_date'])); ?>
                        </div>
                        <div class="appointment-time">
                            <i class="fa-solid fa-clock"></i>
                            <?php echo date('g:i A', strtotime($appointment['slot_time'])); ?>
                        </div>
                        <div class="appointment-status status-<?php echo strtolower($appointment['status']); ?>">
                            <?php echo ucfirst($appointment['status']); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="no-appointments">
                    <p>You don't have any upcoming appointments with this consultant.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment Methods Modal -->
    </div>

    <!-- Payment Methods Modal -->
    <div id="paymentMethodsModal" class="modal" tabindex="-1" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
        <div class="modal-dialog" style="background:#fff; border-radius:1.2rem; max-width:500px; width:90%; margin:auto; box-shadow:0 4px 32px rgba(80,120,255,0.15);">
            <div class="modal-content" style="border:none; padding:2rem;">
                <div class="modal-header" style="border-bottom:1px solid #eee; padding-bottom:1rem; margin-bottom:1.5rem;">
                    <h5 class="modal-title" style="font-size:1.5rem; font-weight:600; color:#2563eb;">Payment Methods</h5>
                    <button type="button" class="close-btn" onclick="closeAllModals()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#666;">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="payment-methods-grid" style="display: grid; gap: 1rem;">
                        <div class="payment-option" style="cursor: pointer; padding: 1.5rem; border: 2px solid #e2e8f0; border-radius: 0.75rem; transition: all 0.3s ease;" 
                             onmouseover="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 2px rgba(37, 99, 235, 0.2)';" 
                             onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';"
                             onclick="showPaymentDetails('paypal')">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 50px; height: 50px; background-color: #003087; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                    <i class="fab fa-paypal"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; color: #1a202c; font-size: 1.1rem; font-weight: 600;">PayPal</h4>
                                    <p style="margin: 0.25rem 0 0; color: #4a5568; font-size: 0.9rem;">Pay with PayPal or credit card</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="payment-option" style="cursor: pointer; padding: 1.5rem; border: 2px solid #e2e8f0; border-radius: 0.75rem; transition: all 0.3s ease;"
                             onmouseover="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 2px rgba(37, 99, 235, 0.2)';" 
                             onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';"
                             onclick="showPaymentDetails('bank')">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 50px; height: 50px; background-color: #1a365d; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; color: #1a202c; font-size: 1.1rem; font-weight: 600;">Bank Transfer</h4>
                                    <p style="margin: 0.25rem 0 0; color: #4a5568; font-size: 0.9rem;">Direct bank transfer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:1.5rem; margin-top:1.5rem; display:flex; justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeAllModals()" style="background:#6c757d; color:white; border:none; padding:0.5rem 1.5rem; border-radius:4px; cursor:pointer;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- PayPal Payment Details Modal -->
    <div id="paypalDetailsModal" class="modal" tabindex="-1" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
        <div class="modal-dialog" style="background:#fff; border-radius:1.2rem; max-width:500px; width:90%; margin:auto; box-shadow:0 4px 32px rgba(80,120,255,0.15);">
            <div class="modal-content" style="border:none; padding:2rem;">
                <div class="modal-header" style="border-bottom:1px solid #eee; padding-bottom:1rem; margin-bottom:1.5rem;">
                    <h5 class="modal-title" style="font-size:1.5rem; font-weight:600; color:#003087;">
                        <i class="fab fa-paypal"></i> PayPal Payment
                    </h5>
                    <button type="button" class="close-btn" onclick="closeAllModals()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#666;">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="payment-instructions">
                        <div style="background:#f0f7ff; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border-left:4px solid #009cde;">
                            <p style="margin:0; color:#003087; font-weight:500;">
                                <i class="fas fa-info-circle"></i> Secure payment via PayPal
                            </p>
                        </div>
                        
                        <div style="margin-bottom:1.5rem;">
                            <p style="font-weight:600; margin-bottom:0.5rem; color:#2d3748;">Amount to Pay:</p>
                            <div style="background:#f8f9fa; padding:1rem; border-radius:8px; margin-bottom:1.5rem; text-align:center;">
                                <p style="font-size:1.5rem; font-weight:700; color:#2d3748; margin:0;">$<span id="paypalAmount"></span>.00</p>
                                <p style="margin:0.25rem 0 0; color:#a0aec0; font-size:0.9rem;">For <span class="duration-display"></span> consultation</p>
                                <input type="hidden" id="hourlyRate" value="<?php echo isset($consultant['hourly_rate']) ? $consultant['hourly_rate'] : '50'; ?>">
                            </div>
                            
                            <div style="margin-bottom:1.5rem;">
                                <p style="font-weight:600; margin-bottom:0.75rem; color:#2d3748;">How to Pay:</p>
                                <div style="background: #fff5f5; border-left: 4px solid #f56565; padding: 1rem; margin-bottom: 1rem; border-radius: 0.375rem;">
                                    <p style="margin:0 0 0.5rem 0; color:#2d3748; font-weight:500;">
                                        <i class="fas fa-envelope me-2" style="color:#e53e3e;"></i>
                                        Send payment to: <strong>C&C@consultant.com</strong>
                                    </p>
                                    <p style="margin:0; color:#718096; font-size:0.875rem;">
                                        Please include your name and booking reference in the payment notes.
                                    </p>
                                </div>
                                <ol style="margin:0; padding-left:1.25rem; color:#4a5568;">
                                    <li style="margin-bottom:0.5rem;">Click the PayPal button below</li>
                                    <li style="margin-bottom:0.5rem;">Log in to your PayPal account or pay with a credit/debit card</li>
                                    <li style="margin-bottom:0.5rem;">Send payment to: <strong>C&C@consultant.com</strong></li>
                                    <li>Review the payment amount: <strong>$<span id="paypalAmount2"><?php echo isset($consultant['hourly_rate']) ? htmlspecialchars($consultant['hourly_rate']) : '50'; ?></span>.00</strong></li>
                                </ol>
                            </div>
                            
                            <!-- PayPal Button -->
                            
                            <p style="color:#718096; font-size:0.9rem; margin:1rem 0 0; text-align:center;">
                                <i class="fas fa-lock"></i> Secure payment processed by PayPal
                            </p>
                        </div>
{{ ... }}
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:1.5rem; margin-top:1.5rem; display:flex; justify-content:space-between; align-items:center;">
                    <button type="button" class="btn btn-link text-muted" onclick="showPaymentMethods()" style="background:none; border:none; cursor:pointer; padding:0;">
                        <i class="fas fa-arrow-left me-1"></i> Back to Payment Methods
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeAllModals()" style="background:#6c757d; color:white; border:none; padding:0.5rem 1.5rem; border-radius:4px; cursor:pointer;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bank Transfer Details Modal -->
    <div id="bankDetailsModal" class="modal" tabindex="-1" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
        <div class="modal-dialog" style="background:#fff; border-radius:1.2rem; max-width:500px; width:90%; margin:auto; box-shadow:0 4px 32px rgba(80,120,255,0.15);">
            <div class="modal-content" style="border:none; padding:2rem;">
                <div class="modal-header" style="border-bottom:1px solid #eee; padding-bottom:1rem; margin-bottom:1.5rem;">
                    <h5 class="modal-title" style="font-size:1.5rem; font-weight:600; color:#2c5282;">
                        <i class="fas fa-university"></i> Bank Transfer
                    </h5>
                    <button type="button" class="close-btn" onclick="closeAllModals()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#666;">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="payment-instructions">
                        <div style="background:#ebf8ff; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border-left:4px solid #3182ce;">
                            <p style="margin:0; color:#2c5282; font-weight:500;">
                                <i class="fas fa-info-circle"></i> Please transfer the exact amount to the following bank account
                            </p>
                        </div>
                        
                        <div style="margin-bottom:1.5rem;">
                            <div style="background:#f8f9fa; padding:1.5rem; border-radius:8px; margin-bottom:1.5rem; border:1px solid #e2e8f0;">
                                <div style="display:flex; align-items:center; margin-bottom:1.25rem; padding-bottom:1rem; border-bottom:1px solid #e2e8f0;">
                                    <div style="width:48px; height:48px; background-color:#2c5282; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-right:1rem; flex-shrink:0;">
                                        <i class="fas fa-university" style="color:white; font-size:1.25rem;"></i>
                                    </div>
                                    <div>
                                        <p style="margin:0 0 0.25rem; color:#4a5568; font-size:0.9rem;">Bank Name</p>
                                        <p style="margin:0; font-weight:600; color:#2d3748; font-size:1.1rem;">C&C Business Bank</p>
                                    </div>
                                </div>
                                
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                                    <div>
                                        <p style="margin:0 0 0.25rem; color:#4a5568; font-size:0.9rem;">Account Name</p>
                                        <p style="margin:0; font-weight:500; color:#2d3748;">C&C Consulting Services Inc.</p>
                                    </div>
                                    <div>
                                        <p style="margin:0 0 0.25rem; color:#4a5568; font-size:0.9rem;">Account Number</p>
                                        <p style="margin:0; font-weight:500; color:#2d3748;">1234 5678 9012 3456</p>
                                    </div>
                                    <div>
                                        <p style="margin:0 0 0.25rem; color:#4a5568; font-size:0.9rem;">SWIFT/BIC Code</p>
                                        <p style="margin:0; font-weight:500; color:#2d3748;">CCBBPHMMXXX</p>
                                    </div>
                                    <div>
                                        <p style="margin:0 0 0.25rem; color:#4a5568; font-size:0.9rem;">Amount</p>
                                        <p style="margin:0; font-weight:500; color:#2d3748;">$<span id="bankAmount"><?php echo isset($consultant['hourly_rate']) ? htmlspecialchars($consultant['hourly_rate']) : '50'; ?></span>.00</p>
                                    </div>
                                </div>
                                
                                <div style="background:#f0f9ff; padding:1rem; border-radius:8px; border:1px solid #e0f2fe;">
                                    <p style="margin:0 0 0.5rem; color:#0369a1; font-weight:600; font-size:0.95rem;">
                                        <i class="fas fa-info-circle"></i> Important Instructions:
                                    </p>
                                    <ul style="margin:0; padding-left:1.25rem; color:#4a5568; font-size:0.9rem;">
                                        <li style="margin-bottom:0.5rem;">Use your name as the payment reference</li>
                                        <li>Keep a record of your transaction for reference</li>
                                    </ul>
                                </div>
                            </div>
                            

                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee; padding-top:1.5rem; margin-top:1.5rem; display:flex; justify-content:space-between; align-items:center;">
                    <button type="button" class="btn btn-link text-muted" onclick="showPaymentMethods()" style="background:none; border:none; cursor:pointer; padding:0;">
                        <i class="fas fa-arrow-left me-1"></i> Back to Payment Methods
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeAllModals()" style="background:#6c757d; color:white; border:none; padding:0.5rem 1.5rem; border-radius:4px; cursor:pointer;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Feedback Modal -->
    <div id="feedbackModal" class="modal" tabindex="-1" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
        <div class="modal-dialog" style="background:#fff; border-radius:1.2rem; max-width:400px; width:90%; margin:auto; box-shadow:0 4px 32px rgba(80,120,255,0.15);">
            <div class="modal-content" style="border:none; padding:2rem;">
                <div class="modal-header" style="border:none; display:flex; justify-content:space-between; align-items:center;">
                    <h5 class="modal-title" style="color:#2563eb; font-weight:700;">Give Feedback</h5>
                    <button type="button" class="close-modal" onclick="closeFeedbackModal()" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
                </div>
                <div class="modal-body">
                    <form action="../actions/submit_feedback.php" method="POST" id="feedbackForm" onsubmit="return validateFeedbackForm()">
                        <?php
                        // Debug output
                        if (isset($_SESSION['error'])) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
                            unset($_SESSION['error']);
                        }
                        if (isset($_SESSION['success'])) {
                            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
                            unset($_SESSION['success']);
                        }
                        ?>
                        <input type="hidden" name="appointment_id" id="appointment_id">
                        <input type="hidden" name="consultant_id" id="consultant_id">
                        <input type="hidden" name="rating" id="rating_input">
                        
                        <div class="rating-container" style="text-align:center; margin-bottom:1.5rem;">
                            <div class="stars" style="font-size:2rem; color:#ffc107;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa-regular fa-star" data-rating="<?php echo $i; ?>" onclick="setRating(<?php echo $i; ?>)" style="cursor:pointer; margin:0 0.2rem;"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <textarea name="feedback" id="feedbackText" class="form-control" rows="4" placeholder="Share your experience..." required style="width:100%; padding:0.8rem; border:1px solid #e2e8f0; border-radius:0.5rem; margin-bottom:1rem;"></textarea>
                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            <i class="fa-solid fa-paper-plane me-2"></i>Submit Feedback
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
    :root {
        --primary-color: #4e6cff;
        --primary-dark: #3d5be0;
        --secondary-color: #f5f7fa;
        --accent-color: #ff6b6b;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --error-color: #dc3545;
        --text-color: #222;
        --text-light: #6c757d;
        --border-radius: 18px;
        --card-shadow: 0 4px 24px rgba(78, 108, 255, 0.08);
        --transition: all 0.2s cubic-bezier(.4,0,.2,1);
    }

    body {
        font-family: 'Inter', Arial, sans-serif;
        background: var(--secondary-color);
        color: var(--text-color);
        margin: 0;
        padding: 0;
    }

        .custom-navbar {
            background: #3163d4;
            color: #fff;
            box-shadow: none;
            padding: 0.7rem 0;
        }

        .custom-navbar .container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }

        .custom-navbar .navbar-brand {
            font-size: 1.5rem;
        font-weight: 700;
            color: #fff;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

        .custom-navbar .navbar-nav {
        display: flex;
        align-items: center;
            gap: 1.5rem;
    }

        .custom-navbar .nav-link {
            color: #fff;
        text-decoration: none;
        font-weight: 500;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            transition: background 0.2s;
        display: flex;
        align-items: center;
            gap: 0.4rem;
    }

        .custom-navbar .nav-link.active, .custom-navbar .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }

        .custom-navbar .navbar-right {
        display: flex;
        align-items: center;
            gap: 1.2rem;
        }

        .custom-navbar .navbar-right .nav-link {
            color: #fff;
            font-weight: 500;
        font-size: 1rem;
    }

    .profile-header {
        background: linear-gradient(120deg, var(--primary-color) 60%, var(--primary-dark) 100%);
        color: #fff;
        padding: 3rem 0 2rem 0;
        margin-bottom: 2.5rem;
        border-radius: 0 0 32px 32px;
        box-shadow: 0 8px 32px rgba(78, 108, 255, 0.10);
    }

    .profile-header-content {
        display: flex;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        gap: 2.5rem;
            margin-top: 15px;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: #fff;
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: 700;
        box-shadow: 0 2px 12px rgba(78, 108, 255, 0.10);
        overflow: hidden;
        border: 4px solid #fff;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

        .profile-info {
            margin-top: 20px;
        }

    .profile-info h1 {
        font-size: 2.2rem;
        margin: 0 0 0.5rem 0;
        font-weight: 700;
    }

    .profile-specialty, .profile-location {
        display: inline-block;
        padding: 0.3rem 1rem;
        background: rgba(255,255,255,0.18);
        border-radius: 20px;
        font-size: 1rem;
        font-weight: 500;
        margin-right: 1rem;
        margin-bottom: 0.5rem;
    }

    .profile-rating {
        margin-top: 1rem;
        color: #ffd700;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .reviews-count {
        color: rgba(255,255,255,0.85);
        font-size: 1rem;
        margin-left: 0.7rem;
    }

    .main-content {
        max-width: 1200px;
        margin: 0 auto;
        padding-bottom: 3rem;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 340px minmax(600px, 2fr) 340px;
        gap: 2.5rem;
    }

    .content-sidebar {
        grid-column: 1;
    }
    .content-main {
        grid-column: 2;
    }
    .info-card.your-appointments {
        grid-column: 3;
        align-self: start;
        height: auto;
    }
    @media (max-width: 1200px) {
        .content-grid {
            grid-template-columns: 1fr 2fr 1fr;
        }
    }
    @media (max-width: 991px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
        .content-sidebar, .content-main, .info-card.your-appointments {
            grid-column: auto;
            width: 100%;
            margin-left: 0;
            margin-right: 0;
        }
    }

    .info-card, .booking-card {
        background: #fff;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        padding: 2rem;
        margin-bottom: 2rem;
        transition: box-shadow 0.2s;
    }

    .info-card:hover, .booking-card:hover {
        box-shadow: 0 8px 32px rgba(78, 108, 255, 0.12);
    }

    .info-card h3, .booking-card h2 {
        margin-top: 0;
        margin-bottom: 1.2rem;
        font-size: 1.3rem;
        color: var(--primary-color);
        font-weight: 700;
    }

    .expertise-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .tag {
        display: inline-block;
        padding: 0.4rem 1rem;
        background: rgba(78, 108, 255, 0.10);
        color: var(--primary-color);
        border-radius: 20px;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .appointments-list {
        margin-top: 1rem;
    }

    .appointment-item {
        background: #f5f7ff;
        border-radius: 10px;
        padding: 1rem 1.2rem;
        margin-bottom: 0.8rem;
        border-left: 4px solid var(--primary-color);
        box-shadow: 0 2px 8px rgba(78, 108, 255, 0.04);
        transition: box-shadow 0.2s;
    }

    .appointment-item:hover {
        box-shadow: 0 4px 16px rgba(78, 108, 255, 0.10);
    }

    .appointment-date, .appointment-time {
        display: flex;
        align-items: center;
        margin-bottom: 0.3rem;
        font-size: 1rem;
    }

    .appointment-status {
        display: inline-block;
        margin-top: 0.5rem;
        padding: 0.2rem 0.8rem;
        border-radius: 4px;
        font-size: 0.95rem;
        font-weight: 600;
    }

    .status-pending { background: #fff3cd; color: #856404; }
    .status-confirmed { background: #d4edda; color: #155724; }
    .status-completed { background: #cce5ff; color: #004085; }
    .status-cancelled { background: #f8d7da; color: #721c24; }

    .no-appointments p {
        color: var(--text-light);
    }

    .booking-card h2 {
        font-size: 1.5rem;
        color: var(--primary-dark);
    }

    .form-section {
        margin-bottom: 2rem;
    }

    .form-section h3 {
        font-size: 1.1rem;
        margin-bottom: 1rem;
        color: var(--primary-color);
        font-weight: 600;
    }

    .date-tabs {
        display: flex;
        gap: 0.7rem;
        padding-bottom: 0.5rem;
    }

    .date-tab {
        background: #f5f7ff;
        border: 1px solid var(--primary-color);
        border-radius: 8px;
        padding: 0.7rem 1.2rem;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        white-space: nowrap;
    }

    .date-tab.active, .date-tab:hover {
        background: var(--primary-color);
        color: #fff;
        border-color: var(--primary-color);
    }

    .time-slots {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }

    .time-slot {
        display: block;
        padding: 0;
        border: none;
        background: none;
        box-shadow: none;
        margin-bottom: 0;
    }
    .time-slot input[type="radio"] {
        display: none;
    }
    .time-slot label {
        display: block;
        padding: 1.2rem 0.5rem;
        text-align: center;
        background: #fff;
        border: 2px solid #2563eb;
        border-radius: 12px;
        cursor: pointer;
        font-size: 1.15rem;
        font-weight: 500;
        color: #222;
        transition: background 0.2s, color 0.2s, border 0.2s;
        min-width: 120px;
    }
    .time-slot input[type="radio"]:checked + label {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
    }
    .time-slot label:hover {
        background: #f0f4ff;
        color: #2563eb;
    }

    .booking-summary {
        background: #f9f9f9;
        border-radius: 8px;
        padding: 1.2rem;
        margin-bottom: 1.2rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 1rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        border: none;
        font-size: 1.1rem;
    }

    .btn-primary {
        background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
        color: #fff;
        width: 100%;
        padding: 1.1rem;
        font-size: 1.1rem;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(78, 108, 255, 0.08);
    }

    .btn-primary:hover {
        background: linear-gradient(90deg, var(--primary-dark), var(--primary-color));
        box-shadow: 0 4px 16px rgba(78, 108, 255, 0.16);
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateSelect = document.getElementById('appointment-date');
        const durationSelect = document.getElementById('duration-filter');
        const timeSlotContainers = document.querySelectorAll('.time-slots-container');

        function filterSlots() {
            const selectedDuration = durationSelect.value;
            timeSlotContainers.forEach(container => {
                const slots = container.querySelectorAll('.time-slot');
                slots.forEach(slot => {
                    if (selectedDuration === 'all' || slot.getAttribute('data-duration') === selectedDuration) {
                        slot.style.display = '';
                    } else {
                        slot.style.display = 'none';
                    }
                });
            });
        }

        if (durationSelect) {
            durationSelect.addEventListener('change', filterSlots);
        }

        if (dateSelect) {
            dateSelect.addEventListener('change', function() {
                // Hide all time slot containers
                timeSlotContainers.forEach(container => {
                    container.style.display = 'none';
                });

                // Show the selected date's time slots
                const selectedDate = this.value;
                const selectedContainer = document.getElementById('date-' + selectedDate);
                if (selectedContainer) {
                    selectedContainer.style.display = 'block';
                    filterSlots(); // Re-apply filter when date changes
                }
            });
        }

        // Initial filter on page load
        filterSlots();
    });
    </script>

    <script>
    let selectedRating = 0;

    function openFeedbackModal(appointmentId, consultantId) {
        console.log('Opening modal with:', { appointmentId, consultantId });
        document.getElementById('appointment_id').value = appointmentId;
        document.getElementById('consultant_id').value = consultantId;
        document.getElementById('feedbackModal').style.display = 'flex';
    }

    function closeFeedbackModal() {
        document.getElementById('feedbackModal').style.display = 'none';
        resetFeedbackForm();
    }

    function resetFeedbackForm() {
        selectedRating = 0;
        document.getElementById('feedbackText').value = '';
        document.getElementById('rating_input').value = '';
        document.querySelectorAll('.stars i').forEach(star => {
            star.className = 'fa-regular fa-star';
        });
    }

    function setRating(rating) {
        console.log('Setting rating:', rating);
        selectedRating = rating;
        document.getElementById('rating_input').value = rating;
        document.querySelectorAll('.stars i').forEach((star, index) => {
            star.className = index < rating ? 'fa-solid fa-star' : 'fa-regular fa-star';
        });
    }

    function validateFeedbackForm() {
        console.log('Validating form...');
        const appointmentId = document.getElementById('appointment_id').value;
        const consultantId = document.getElementById('consultant_id').value;
        const rating = document.getElementById('rating_input').value;
        const feedback = document.getElementById('feedbackText').value.trim();

        console.log('Form data:', {
            appointmentId,
            consultantId,
            rating,
            feedback
        });

        if (!appointmentId || !consultantId) {
            alert('System error: Missing appointment or consultant information.');
            return false;
        }

        if (selectedRating === 0) {
            alert('Please select a rating');
            return false;
        }
        
        if (!feedback) {
            alert('Please enter your feedback');
            return false;
        }

        // Show loading state
        const submitButton = document.querySelector('#feedbackForm button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Submitting...';

        return true;
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('feedbackModal');
        if (event.target === modal) {
            closeFeedbackModal();
        }
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
    // Payment Modal Functions
    function openPaymentModal() {
        document.getElementById('paymentMethodsModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
    }
    
    function closeAllModals() {
        document.getElementById('paymentMethodsModal').style.display = 'none';
        document.getElementById('paypalDetailsModal').style.display = 'none';
        document.getElementById('bankDetailsModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showPaymentMethods() {
        closeAllModals();
        document.getElementById('paymentMethodsModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function showPaymentDetails(method) {
        closeAllModals();
        if (method === 'paypal') {
            document.getElementById('paypalDetailsModal').style.display = 'flex';
        } else if (method === 'bank') {
            document.getElementById('bankDetailsModal').style.display = 'flex';
        }
        document.body.style.overflow = 'hidden';
    }
    
    function showPaymentMethods() {
        closeAllModals();
        document.getElementById('paymentMethodsModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function showPaymentDetails(method) {
        closeAllModals();
        if (method === 'paypal') {
            document.getElementById('paypalDetailsModal').style.display = 'flex';
        } else if (method === 'bank') {
            document.getElementById('bankDetailsModal').style.display = 'flex';
        }
        document.body.style.overflow = 'hidden';
    }
    
    function confirmPaymentMethod(method) {
        const paymentMethod = document.getElementById('payment-method');
        const selectedMethodText = document.getElementById('selected-method-text');
        const selectedMethodSection = document.getElementById('selected-payment-method');
        const confirmBtn = document.getElementById('confirm-booking-btn');
        
        closeAllModals();
    }
    
    function clearPaymentMethod() {
        // No action needed as payment is optional
    }
    
    // Handle file upload via drag and drop
    function handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.style.borderColor = '#4299e1';
        e.currentTarget.style.backgroundColor = '#ebf8ff';
    }
    
    function handleDrop(e, type) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.style.borderColor = '#cbd5e0';
        e.currentTarget.style.backgroundColor = '';
        
        const fileInput = document.getElementById(type + 'Receipt');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelect(fileInput, type);
        }
    }
    
    function handleFileSelect(input, type) {
        const file = input.files[0];
        if (file) {
            const fileSize = file.size / 1024 / 1024; // in MB
            if (fileSize > 5) {
                alert('File size exceeds 5MB limit');
                return;
            }
            
            const fileName = file.name.length > 25 ? file.name.substring(0, 22) + '...' : file.name;
            document.getElementById(type + 'FileName').textContent = fileName;
            document.getElementById(type + 'FileInfo').style.display = 'block';
        }
    }
    
    function removeFile(type) {
        document.getElementById(type + 'Receipt').value = '';
        document.getElementById(type + 'FileInfo').style.display = 'none';
    }
    
    function submitReceipt(type) {
        const fileInput = document.getElementById(type + 'Receipt');
        if (!fileInput.files.length) {
            alert('Please select a file to upload');
            return;
        }
        
        // Here you would typically submit the form via AJAX
        // For now, we'll just show a success message
        alert('Receipt uploaded successfully!');
        closeAllModals();
        confirmPaymentMethod(type === 'bank' ? 'bank_transfer' : type);
    }
    
    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeAllModals();
        }
    });
    
    // Calculate payment amount based on duration and hourly rate
    function calculateAmount(duration, hourlyRate) {
        let multiplier = 1;
        
        // Set multiplier based on duration
        if (duration === 30) {
            multiplier = 0.5; // 30 minutes = half the hourly rate
        } else if (duration === 60) {
            multiplier = 1; // 1 hour = full hourly rate
        } else if (duration === 90) {
            multiplier = 1.5; // 1.5 hours = 1.5x hourly rate
        } else if (duration === 120) {
            multiplier = 2; // 2 hours = 2x hourly rate
        } else {
            // For any other duration, calculate based on minutes
            multiplier = duration / 60;
        }
        
        return (hourlyRate * multiplier).toFixed(2);
    }
    
    // Update payment amount when time slot is selected
    function updatePaymentAmount(duration, hourlyRate) {
        const amount = calculateAmount(duration, hourlyRate);
        
        // Update all amount displays
        document.querySelectorAll('#paypalAmount, #paypalAmount2, #bankAmount').forEach(el => {
            el.textContent = amount;
        });
        
        // Update duration display
        let durationText = '';
        if (duration === 30) durationText = '30 minutes';
        else if (duration === 60) durationText = '1 hour';
        else if (duration === 90) durationText = '1.5 hours';
        else if (duration === 120) durationText = '2 hours';
        else durationText = duration + ' minutes';
        
        // Update duration display in both modals
        document.querySelectorAll('.duration-display').forEach(el => {
            el.textContent = durationText;
        });
    }
    
    // Initialize PayPal button
    function initPayPalButton() {
        paypal.Buttons({
            createOrder: function(data, actions) {
                const amount = document.getElementById('paypalAmount').textContent;
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: amount,
                            currency_code: 'USD'
                        }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    alert('Payment completed successfully!');
                    closeAllModals();
                    confirmPaymentMethod('paypal');
                });
            },
            onError: function(err) {
                console.error('PayPal error:', err);
                alert('An error occurred with PayPal. Please try again or choose another payment method.');
            }
        }).render('#paypal-button-container');
    }
    
    // Load PayPal script
    function loadPayPalScript() {
        const script = document.createElement('script');
        script.src = 'https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=USD';
        script.onload = initPayPalButton;
        document.head.appendChild(script);
    }
    
    // Function to update payment details
    function updatePaymentDetails() {
        const selectedSlot = document.querySelector('input[name="slot_id"]:checked');
        if (selectedSlot) {
            const duration = parseInt(selectedSlot.getAttribute('data-duration'));
            const hourlyRate = parseFloat(document.getElementById('hourlyRate').value);
            updatePaymentAmount(duration, hourlyRate);
        }
    }

    // Load PayPal when document is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize PayPal script if needed
        if (typeof paypal === 'undefined') {
            loadPayPalScript();
        }
        
        // Update payment amount when time slot is selected
        document.querySelectorAll('input[name="slot_id"]').forEach(radio => {
            radio.addEventListener('change', updatePaymentDetails);
            
            // If this is the first radio and it's checked, update payment details
            if (radio.checked) {
                updatePaymentDetails();
            }
        });

        // Close modals with escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAllModals();
            }
        });
        
        // Update payment details when modal is shown
        document.getElementById('paymentModal').addEventListener('shown.bs.modal', updatePaymentDetails);
    });
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <?php require_once '../includes/footer.php'; ?> 
</body>
</html>