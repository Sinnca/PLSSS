<?php
require_once '../config/db.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Check if user is logged in
checkUserLogin();

// Get appointment ID from session
if (!isset($_SESSION['pending_appointment_id'])) {
    header("Location: dashboard.php");
    exit;
}

$appointment_id = $_SESSION['pending_appointment_id'];

// Fetch appointment details with availability information
$sql = "SELECT a.*, c.hourly_rate, c.specialty, c.id as consultant_id, 
        u.name as consultant_name, u.email as consultant_email,
        av.slot_date, av.slot_time, av.duration
        FROM appointments a
        JOIN consultants c ON a.consultant_id = c.id
        JOIN users u ON c.user_id = u.id
        JOIN availability av ON a.availability_id = av.id
        WHERE a.id = $appointment_id AND a.user_id = {$_SESSION['user_id']}";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: dashboard.php");
    exit;
}

$appointment = mysqli_fetch_assoc($result);

// Get user details
$user_sql = "SELECT name, email, phone FROM users WHERE id = {$_SESSION['user_id']}";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

// Format date and time
$appointment_date = date('l, F j, Y', strtotime($appointment['slot_date']));
$appointment_time = date('g:i A', strtotime($appointment['slot_time']));
$duration = isset($appointment['duration']) ? $appointment['duration'] : 60; // Default to 60 minutes
$end_time = date('g:i A', strtotime($appointment['slot_time'] . " +{$duration} minutes"));

// Calculate total cost
$hourly_rate = $appointment['hourly_rate'];
$total_cost = ($duration / 60) * $hourly_rate;

// Generate a random confirmation number if not already in database
if (empty($appointment['confirmation_code'])) {
    $confirmation_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    // Update appointment with confirmation code
    mysqli_query($conn, "UPDATE appointments SET confirmation_code = '$confirmation_code' WHERE id = $appointment_id");
} else {
    $confirmation_code = $appointment['confirmation_code'];
}

// Clear the pending appointment ID from session after showing this page
// This prevents refreshing to cause issues
// Keep commented during development, uncomment in production
// unset($_SESSION['pending_appointment_id']);
?>

<!-- Navigation Bar is included from header.php -->

<!-- Confirmation Header -->
<section class="confirmation-header">
    <div class="container">
        <div class="confirmation-header-content">
            <div class="confirmation-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h1>Booking Confirmed!</h1>
            <p>Your appointment has been successfully scheduled.</p>
        </div>
    </div>
</section>

<div class="container main-content">
    <!-- Confirmation Card -->
    <div class="confirmation-card">
        <div class="confirmation-status">
            <div class="confirmation-badge">
                <span class="status-label">Confirmation #:</span>
                <span class="confirmation-number"><?php echo $confirmation_code; ?></span>
            </div>
            <div class="confirmation-actions">
                <button class="btn btn-outline btn-sm" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Print
                </button>
                <a href="#" class="btn btn-outline btn-sm" id="add-to-calendar">
                    <i class="fa-solid fa-calendar-plus"></i> Add to Calendar
                </a>
            </div>
        </div>
        
        <!-- Appointment Details -->
        <div class="details-section">
            <h2>Appointment Details</h2>
            
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fa-solid fa-calendar-day"></i>
                    </div>
                    <div class="detail-content">
                        <h3>Date</h3>
                        <p><?php echo $appointment_date; ?></p>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="detail-content">
                        <h3>Time</h3>
                        <p><?php echo $appointment_time; ?> - <?php echo $end_time; ?></p>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                    <div class="detail-content">
                        <h3>Duration</h3>
                        <p><?php echo $duration; ?> minutes</p>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fa-solid fa-tag"></i>
                    </div>
                    <div class="detail-content">
                        <h3>Service Type</h3>
                        <p><?php echo htmlspecialchars($appointment['specialty']); ?> Consultation</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cost Summary -->
        <div class="cost-summary">
            <div class="cost-row">
                <span>Consultation Rate:</span>
                <span>$<?php echo number_format($hourly_rate, 2); ?> / hour</span>
            </div>
            <div class="cost-row">
                <span>Duration:</span>
                <span><?php echo $duration; ?> minutes</span>
            </div>
            <div class="cost-row total">
                <span>Total Cost:</span>
                <span>$<?php echo number_format($total_cost, 2); ?></span>
            </div>
            <p class="payment-note">Payment will be processed at the time of consultation</p>
        </div>
        
        <!-- Consultant Info -->
        <div class="details-section">
            <h2>Your Consultant</h2>
            
            <div class="consultant-info-card">
                <div class="consultant-avatar">
                    <?php 
                    // This would typically use the consultant's profile photo
                    // Using initials as a fallback
                    $initials = strtoupper(substr($appointment['consultant_name'], 0, 1));
                    echo $initials;
                    ?>
                </div>
                <div class="consultant-details">
                    <h3><?php echo htmlspecialchars($appointment['consultant_name']); ?></h3>
                    <p class="consultant-title"><?php echo htmlspecialchars($appointment['specialty']); ?> Specialist</p>
                    <div class="consultant-contact">
                        <a href="mailto:<?php echo htmlspecialchars($appointment['consultant_email']); ?>" class="contact-link">
                            <i class="fa-solid fa-envelope"></i> Contact via Email
                        </a>
                        <a href="consultant-details.php?id=<?php echo $appointment['consultant_id']; ?>" class="contact-link">
                            <i class="fa-solid fa-user"></i> View Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Information -->
        <div class="details-section">
            <h2>Additional Information</h2>
            
            <div class="info-box">
                <h3><i class="fa-solid fa-circle-info"></i> What to Expect</h3>
                <ul class="info-list">
                    <li>Your consultant will contact you prior to the appointment to confirm details.</li>
                    <li>The consultation will take place via video conference, unless otherwise specified.</li>
                    <li>Be prepared with any questions or materials you wish to discuss.</li>
                    <li>If you need to reschedule, please do so at least 24 hours in advance.</li>
                </ul>
            </div>
            
            <?php if (!empty($appointment['message'])): ?>
            <div class="message-box">
                <h3><i class="fa-solid fa-message"></i> Your Message to the Consultant</h3>
                <div class="message-content">
                    <?php echo nl2br(htmlspecialchars($appointment['message'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Next Steps -->
        <div class="next-steps">
            <h2>Next Steps</h2>
            <div class="steps-grid">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <h3>Check Your Email</h3>
                    <p>A confirmation has been sent to your email address with all appointment details.</p>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <h3>Prepare for Consultation</h3>
                    <p>Gather any documents or questions you want to discuss during your session.</p>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <h3>Join the Meeting</h3>
                    <p>You'll receive connection details before your appointment time.</p>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="confirmation-actions-footer">
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fa-solid fa-home"></i> Go to Dashboard
            </a>
            <a href="appointments.php" class="btn btn-outline">
                <i class="fa-solid fa-calendar-days"></i> View All Appointments
            </a>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #4e6cff;
    --primary-dark: #3d5be0;
    --secondary-color: #6c757d;
    --accent-color: #ff6b6b;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --error-color: #dc3545;
    --light-bg: #f8f9fa;
    --dark-bg: #222;
    --text-color: #333;
    --text-light: #6c757d;
    --border-color: #e9ecef;
    --card-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s ease;
}

/* Confirmation Header */
.confirmation-header {
    background: linear-gradient(135deg, var(--success-color), #218838);
    color: white;
    padding: 60px 0;
    text-align: center;
    margin-bottom: 40px;
}

.confirmation-header-content {
    max-width: 700px;
    margin: 0 auto;
}

.confirmation-icon {
    margin-bottom: 20px;
    font-size: 60px;
    height: 80px;
    width: 80px;
    line-height: 80px;
    text-align: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.confirmation-header h1 {
    font-size: 36px;
    margin-bottom: 15px;
    font-weight: 700;
}

.confirmation-header p {
    font-size: 18px;
    opacity: 0.9;
}

/* Main Content */
.main-content {
    padding-bottom: 80px;
}

/* Confirmation Card */
.confirmation-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    max-width: 850px;
    margin: 0 auto;
}

/* Confirmation Status */
.confirmation-status {
    padding: 25px 30px;
    background-color: #f8f9fa;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
}

.confirmation-badge {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.status-label {
    font-weight: 600;
    color: var(--text-color);
}

.confirmation-number {
    background-color: var(--success-color);
    color: white;
    padding: 8px 15px;
    border-radius: 6px;
    font-weight: 600;
    letter-spacing: 1px;
}

.confirmation-actions {
    display: flex;
    gap: 10px;
}

/* Details Section */
.details-section {
    padding: 30px;
    border-bottom: 1px solid var(--border-color);
}

.details-section h2 {
    margin-top: 0;
    margin-bottom: 25px;
    font-size: 22px;
    color: var(--text-color);
    font-weight: 600;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.detail-item {
    display: flex;
    align-items: flex-start;
}

.detail-icon {
    width: 40px;
    height: 40px;
    background-color: #f0f5ff;
    color: var(--primary-color);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-right: 15px;
    flex-shrink: 0;
}

.detail-content h3 {
    font-size: 16px;
    margin: 0 0 5px 0;
    color: var(--text-light);
    font-weight: 500;
}

.detail-content p {
    font-size: 18px;
    margin: 0;
    color: var(--text-color);
    font-weight: 500;
}

/* Cost Summary */
.cost-summary {
    padding: 30px;
    background-color: #f9f9f9;
    border-bottom: 1px solid var(--border-color);
}

.cost-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
}

.cost-row.total {
    border-bottom: none;
    font-size: 20px;
    font-weight: 600;
    color: var(--text-color);
    padding-top: 15px;
}

.payment-note {
    margin-top: 15px;
    color: var(--text-light);
    font-size: 14px;
    font-style: italic;
}

/* Consultant Info */
.consultant-info-card {
    display: flex;
    align-items: center;
    background-color: #f9fbff;
    border-radius: 10px;
    padding: 20px;
    border: 1px solid #e1e8ff;
}

.consultant-avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    font-weight: bold;
    margin-right: 20px;
    flex-shrink: 0;
}

.consultant-details h3 {
    margin: 0 0 5px 0;
    font-size: 20px;
}

.consultant-title {
    color: var(--primary-color);
    font-weight: 500;
    margin: 0 0 15px 0;
}

.consultant-contact {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.contact-link {
    display: inline-flex;
    align-items: center;
    color: var(--text-color);
    text-decoration: none;
    font-size: 15px;
    transition: var(--transition);
}

.contact-link i {
    margin-right: 8px;
    color: var(--primary-color);
}

.contact-link:hover {
    color: var(--primary-color);
}

/* Additional Information */
.info-box {
    background-color: #f0f7ff;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 25px;
}

.info-box h3 {
    display: flex;
    align-items: center;
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
    color: var(--primary-dark);
}

.info-box h3 i {
    margin-right: 10px;
}

.info-list {
    margin: 0;
    padding-left: 20px;
}

.info-list li {
    margin-bottom: 10px;
    color: var(--text-color);
}

.info-list li:last-child {
    margin-bottom: 0;
}

.message-box {
    background-color: #f9f9f9;
    border-radius: 10px;
    padding: 25px;
}

.message-box h3 {
    display: flex;
    align-items: center;
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
    color: var(--text-color);
}

.message-box h3 i {
    margin-right: 10px;
    color: var(--primary-color);
}

.message-content {
    background-color: white;
    border-radius: 8px;
    padding: 15px;
    border-left: 4px solid var(--primary-color);
    color: var(--text-color);
    line-height: 1.6;
}

/* Next Steps */
.next-steps {
    padding: 30px;
    border-bottom: 1px solid var(--border-color);
}

.next-steps h2 {
    margin-top: 0;
    margin-bottom: 25px;
    font-size: 22px;
    color: var(--text-color);
    font-weight: 600;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 25px;
}

.step-item {
    position: relative;
    padding-left: 60px;
}

.step-number {
    position: absolute;
    left: 0;
    top: 0;
    width: 45px;
    height: 45px;
    background-color: var(--primary-color);
    color: white;
    font-size: 22px;
    font-weight: 600;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.step-item h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
    color: var(--text-color);
}

.step-item p {
    margin: 0;
    color: var(--text-light);
    line-height: 1.6;
}

/* Action Buttons */
.confirmation-actions-footer {
    padding: 30px;
    display: flex;
    justify-content: center;
    gap: 20px;
}

/* Button Styles */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    border: none;
    font-size: 16px;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 14px;
}

.btn i {
    margin-right: 8px;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}

.btn-outline {
    background-color: transparent;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-outline:hover {
    background-color: var(--primary-color);
    color: white;
}

/* Print Styles */
@media print {
    .navbar, 
    .confirmation-actions, 
    .confirmation-actions-footer,
    #add-to-calendar {
        display: none !important;
    }
    
    .confirmation-header {
        background: none !important;
        color: #000 !important;
        padding: 20px 0 !important;
    }
    
    .main-content {
        padding: 0 !important;
    }
    
    .confirmation-card {
        box-shadow: none !important;
    }
    
    .confirmation-number {
        border: 2px solid #000 !important;
        color: #000 !important;
        background: none !important;
    }
    
    .contact-link, 
    .step-number {
        color: #000 !important;
    }
    
    .step-number {
        border: 2px solid #000 !important;
        background: none !important;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .confirmation-status {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .details-grid,
    .steps-grid {
        grid-template-columns: 1fr;
    }
    
    .consultant-info-card {
        flex-direction: column;
        text-align: center;
    }
    
    .consultant-avatar {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .consultant-contact {
        justify-content: center;
    }
    
    .confirmation-actions-footer {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .details-section, 
    .next-steps, 
    .confirmation-actions-footer {
        padding: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add to Calendar functionality
    const addToCalendarBtn = document.getElementById('add-to-calendar');
    if (addToCalendarBtn) {
        addToCalendarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get appointment details from PHP variables
            const appointmentDate = '<?php echo $appointment['slot_date']; ?>';
            const appointmentTime = '<?php echo $appointment['slot_time']; ?>';
            const appointmentDuration = <?php echo $duration; ?>;
            const consultantName = '<?php echo addslashes($appointment['consultant_name']); ?>';
            const appointmentType = '<?php echo addslashes($appointment['specialty']); ?> Consultation';
            
            // Create start and end dates for calendar event
            const startDate = new Date(`${appointmentDate}T${appointmentTime}`);
            const endDate = new Date(startDate.getTime() + appointmentDuration * 60000);
            
            // Format dates for Google Calendar
            const formattedStart = startDate.toISOString().replace(/-|:|\.\d+/g, '');
            const formattedEnd = endDate.toISOString().replace(/-|:|\.\d+/g, '');
            
            // Create event details
            const eventDetails = {
                title: `Consultation with ${consultantName}`,
                description: `${appointmentType}\nConfirmation #: <?php echo $confirmation_code; ?>`,
                start: formattedStart,
                end: formattedEnd,
                location: 'Online Meeting'
            };
            
            // Create Google Calendar URL
            const googleCalendarUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(eventDetails.title)}&dates=${eventDetails.start}/${eventDetails.end}&details=${encodeURIComponent(eventDetails.description)}&location=${encodeURIComponent(eventDetails.location)}`;
            
            // Open Google Calendar in a new tab
            window.open(googleCalendarUrl, '_blank');
        });
    }
    
    // Mobile menu functionality if it exists
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navbarNav = document.querySelector('.navbar-nav');
    
    if (mobileMenuBtn && navbarNav) {
        mobileMenuBtn.addEventListener('click', function() {
            navbarNav.classList.toggle('active');
        });
    }
});
</script>
