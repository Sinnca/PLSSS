<?php
require_once '../config/db.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

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
    $dates[$slot['slot_date']][] = $slot;
}

// Get user's upcoming appointments with this consultant
$user_id = $_SESSION['user_id'];
$appointments_sql = "SELECT a.*, av.slot_date, av.slot_time 
                    FROM appointments a
                    JOIN availability av ON a.availability_id = av.id
                    WHERE a.user_id = $user_id AND a.consultant_id = $consultant_id
                    AND av.slot_date >= CURDATE()
                    ORDER BY av.slot_date, av.slot_time";
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
            <!-- Left Column - Consultant Info -->
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
                </div>
                
                <!-- Upcoming Appointments Section -->
                <div class="info-card">
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
            
            <!-- Right Column - Booking Form -->
            <div class="content-main">
                <div class="booking-card">
                    <h2>Book an Appointment</h2>
                    
                    <?php if (count($dates) > 0): ?>
                    <form method="POST" action="" id="booking-form">
                        <div class="form-section">
                            <h3>1. Select a Date</h3>
                            <div class="date-selector">
                                <div class="date-tabs">
                                    <?php $first = true; foreach ($dates as $date => $slots): ?>
                                    <button type="button" class="date-tab <?php echo $first ? 'active' : ''; ?>" 
                                            data-date="<?php echo $date; ?>">
                                        <?php 
                                        $date_obj = new DateTime($date);
                                        echo $date_obj->format('D, M j'); // e.g. Mon, Jan 15
                                        ?>
                                    </button>
                                    <?php $first = false; endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>2. Select a Time</h3>
                            <?php $first_date = true; foreach ($dates as $date => $time_slots): ?>
                            <div class="time-slots-container" id="date-<?php echo $date; ?>" 
                                 <?php echo $first_date ? '' : 'style="display: none;"'; ?>>
                                <div class="time-slots">
                                    <?php foreach ($time_slots as $slot): ?>
                                    <div class="time-slot">
                                        <input type="radio" name="slot_id" id="slot-<?php echo $slot['id']; ?>" 
                                               value="<?php echo $slot['id']; ?>" required>
                                        <label for="slot-<?php echo $slot['id']; ?>">
                                            <?php 
                                                $start_time = strtotime($slot['slot_time']);
                                                $end_time = strtotime($slot['slot_time'] . ' +1 hour');
                                                echo date('g:i A', $start_time) . ' - ' . date('g:i A', $end_time);
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
                            <div class="booking-summary">
                                <p>You are about to book a consultation with <strong><?php echo htmlspecialchars($consultant['name']); ?></strong></p>
                                <p>Consultation Rate: <strong>$<?php echo isset($consultant['hourly_rate']) ? htmlspecialchars($consultant['hourly_rate']) : '50'; ?> per hour</strong></p>
                                <p class="disclaimer">* Payment will be processed at the time of the consultation</p>
                            </div>
                            <button type="submit" name="book_appointment" class="btn btn-primary">
                                <i class="fa-solid fa-calendar-check"></i> Confirm Booking
                            </button>
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
        grid-template-columns: 1fr 2fr;
        gap: 2.5rem;
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
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 1rem;
    }

    .time-slot label {
        display: block;
        padding: 0.8rem;
        text-align: center;
        background: #fff;
        border: 1px solid var(--primary-color);
        border-radius: 8px;
        cursor: pointer;
        transition: var(--transition);
        font-size: 1rem;
    }

    .time-slot input:checked + label,
    .time-slot label:hover {
        background: var(--primary-color);
        color: #fff;
        border-color: var(--primary-dark);
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

    @media (max-width: 991px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
        .profile-header-content {
            flex-direction: column;
            text-align: center;
            gap: 1.5rem;
        }
            .main-content, .custom-navbar .container, .profile-header-content {
            padding: 0 1rem;
        }
            .custom-navbar .container {
                flex-direction: column;
                gap: 1rem;
            }
            .custom-navbar .navbar-nav {
                gap: 0.7rem;
            }
            .custom-navbar .navbar-right {
                gap: 0.7rem;
            }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Date tabs functionality
        const dateTabs = document.querySelectorAll('.date-tab');
        const timeSlotContainers = document.querySelectorAll('.time-slots-container');
        
        dateTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                dateTabs.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all time slot containers
                timeSlotContainers.forEach(container => {
                    container.style.display = 'none';
                });
                
                // Show the selected date's time slots
                const date = this.dataset.date;
                document.getElementById('date-' + date).style.display = 'block';
            });
        });
        
        // Form validation
        const bookingForm = document.getElementById('booking-form');
        if (bookingForm) {
            bookingForm.addEventListener('submit', function(event) {
                const selectedSlot = document.querySelector('input[name="slot_id"]:checked');
                
                if (!selectedSlot) {
                    event.preventDefault();
                    alert('Please select a time slot for your appointment.');
                }
            });
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <?php require_once '../includes/footer.php'; ?> 
</body>
</html> 