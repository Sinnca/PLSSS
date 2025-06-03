<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php?error=Please login as user");
    exit;
}
require_once '../config/db.php';
$user_id = $_SESSION['user_id'];

// Fetch user info
$sql = "SELECT name, email, created_at FROM users WHERE id = $user_id";
$user = mysqli_fetch_assoc(mysqli_query($conn, $sql));

// Fetch appointment statistics
$sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('confirmed', 'approved') THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM appointments WHERE user_id = $user_id";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $sql));

// Fetch completed appointments (confirmed/approved and in the past)
$today = date('Y-m-d');
$sql = "SELECT COUNT(*) as completed 
        FROM appointments a 
        JOIN availability av ON a.availability_id = av.id 
        WHERE a.user_id = $user_id 
        AND a.status IN ('confirmed', 'approved') 
        AND (av.slot_date < '$today' OR (av.slot_date = '$today' AND av.slot_time < CURTIME()))";
$completed = mysqli_fetch_assoc(mysqli_query($conn, $sql));

// Fetch appointment history (past appointments)
$sql = "SELECT a.*, av.slot_date, av.slot_time, av.duration, c.specialty, cu.name AS consultant_name
        FROM appointments a
        JOIN availability av ON a.availability_id = av.id
        JOIN consultants c ON a.consultant_id = c.id
        JOIN users cu ON c.user_id = cu.id
        WHERE a.user_id = $user_id 
        AND (av.slot_date < '$today' OR (av.slot_date = '$today' AND av.slot_time < CURTIME()))
        ORDER BY av.slot_date DESC, av.slot_time DESC";
$history_result = mysqli_query($conn, $sql);

// Fetch total appointments
$sql = "SELECT COUNT(*) as total FROM appointments WHERE user_id = $user_id";
$total = mysqli_fetch_assoc(mysqli_query($conn, $sql));

// Fetch rejected count based on status = 'rejected' or 'cancelled', or rejectiontimes if the field exists
$rejected_count = 0;
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM appointments LIKE 'rejectiontimes'");
if (mysqli_num_rows($check_col) > 0) {
    $sql = "SELECT SUM(rejectiontimes) as rejected FROM appointments WHERE user_id = $user_id";
    $row = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    $rejected_count = (int)($row['rejected'] ?? 0);
} else {
    $sql = "SELECT COUNT(*) as rejected FROM appointments WHERE user_id = $user_id AND (status = 'rejected' OR status = 'cancelled')";
    $row = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    $rejected_count = (int)($row['rejected'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f4f8ff;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }
        .container {
            max-width: 700px;
            margin: 48px auto;
            background: #fff;
            border-radius: 2rem;
            box-shadow: 0 4px 32px rgba(80,120,255,0.10);
            padding: 2.5rem 2rem 2rem 2rem;
        }
        h1 {
            color: #2563eb;
            margin-bottom: 2rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .profile-card {
            background: #e3edfa;
            border-radius: 1.5rem;
            padding: 2rem 1.5rem;
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: #2563eb;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(80,120,255,0.10);
        }
        .profile-info {
            flex: 1;
        }
        .profile-info h2 {
            margin: 0 0 0.3rem 0;
            color: #2563eb;
            font-size: 1.5rem;
        }
        .profile-info p {
            margin: 0.2rem 0;
            color: #174ea6;
            font-size: 1.05rem;
        }
        .profile-info .reg-date {
            color: #64748b;
            font-size: 0.98rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
        }
        .stat-card {
            background: #f4f8ff;
            border-radius: 1.2rem;
            padding: 1.5rem 1rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(80,120,255,0.07);
        }
        .stat-card h2 {
            color: #2563eb;
            font-size: 2rem;
            margin: 0 0 0.5rem 0;
        }
        .stat-card p {
            color: #64748b;
            font-size: 1.1rem;
            margin: 0;
        }
        @media (max-width: 600px) {
            .container { padding: 1rem; }
            .profile-card { flex-direction: column; gap: 1rem; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h1><i class="fa-solid fa-user"></i> My Profile</h1>
            <a href="index.php" style="background: #2563eb; color: white; padding: 0.6rem 1.2rem; border-radius: 0.8rem; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </div>
        <div class="profile-card">
            <div class="profile-avatar">
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                <p class="reg-date"><i class="fa-solid fa-calendar-plus"></i> Registered: <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div class="stat-card">
                <h2><?php echo $total['total']; ?></h2>
                <p>Total Appointments</p>
            </div>
            <div class="stat-card">
                <h2><?php echo $completed['completed']; ?></h2>
                <p>Completed Appointments</p>
            </div>
        </div>
        <div style="margin: 2rem 0 1.5rem 0; display: flex; gap: 1rem; justify-content: center;">
            <button style="background:#3b82f6;color:#fff;border:none;border-radius:1em;padding:0.6em 1.3em;font-weight:600;font-size:1rem;cursor:default;">
                <i class='fa-solid fa-circle-check'></i> Approved (<?php echo $stats['approved']; ?>)
            </button>
            <button style="background:#f59e0b;color:#fff;border:none;border-radius:1em;padding:0.6em 1.3em;font-weight:600;font-size:1rem;cursor:default;">
                <i class='fa-solid fa-hourglass-half'></i> Pending (<?php echo $stats['pending']; ?>)
            </button>
            <button style="background:#ef4444;color:#fff;border:none;border-radius:1em;padding:0.6em 1.3em;font-weight:600;font-size:1rem;cursor:default;">
                <i class='fa-solid fa-circle-xmark'></i> Rejected (<?php echo $rejected_count; ?>)
            </button>
        </div>
    </div>
    <div class="container" style="max-width:950px; margin-top: 2.5rem;">
        <h2 style="color:#2563eb; margin-bottom:1.5rem; font-size:1.4rem; font-weight:700;"><i class="fa-solid fa-clock-rotate-left"></i> Appointment History</h2>
        <table style="width:100%; border-collapse:separate; border-spacing:0; background:#f8fbff; border-radius:1.2rem; overflow:hidden; box-shadow:0 2px 10px rgba(80,120,255,0.07);">
            <thead>
                <tr>
                    <th style="background:#e3edfa;color:#2563eb;font-weight:700;border-bottom:2px solid #dbeafe;padding:1rem;">Date</th>
                    <th style="background:#e3edfa;color:#2563eb;font-weight:700;border-bottom:2px solid #dbeafe;padding:1rem;">Time</th>
                    <th style="background:#e3edfa;color:#2563eb;font-weight:700;border-bottom:2px solid #dbeafe;padding:1rem;">Consultant</th>
                    <th style="background:#e3edfa;color:#2563eb;font-weight:700;border-bottom:2px solid #dbeafe;padding:1rem;">Specialty</th>
                    <th style="background:#e3edfa;color:#2563eb;font-weight:700;border-bottom:2px solid #dbeafe;padding:1rem;">Feedback</th>
                    <th style="background:#e3edfa;color:#2563eb;font-weight:700;border-bottom:2px solid #dbeafe;padding:1rem;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($history_result) > 0): ?>
                <?php while ($appt = mysqli_fetch_assoc($history_result)): ?>
                    <tr>
                        <td style="padding:1rem;"><?php echo date('M j, Y', strtotime($appt['slot_date'])); ?></td>
                        <td style="padding:1rem;"><?php echo date('g:i A', strtotime($appt['slot_time'])); ?></td>
                        <td style="padding:1rem;"><?php echo htmlspecialchars($appt['consultant_name']); ?></td>
                        <td style="padding:1rem;"><?php echo htmlspecialchars($appt['specialty']); ?></td>
                        <td style="padding:1rem;">
                            <?php
                            // Fetch feedback for this appointment
                            $appt_id = $appt['id'];
                            $feedback_sql = "SELECT * FROM appointment_feedback WHERE appointment_id = $appt_id LIMIT 1";
                            $feedback_result = mysqli_query($conn, $feedback_sql);
                            if ($feedback = mysqli_fetch_assoc($feedback_result)) {
                                // Show star rating
                                echo '<div style="color:#ffc107;">';
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $feedback['rating']) {
                                        echo '<i class="fa-solid fa-star"></i>';
                                    } else {
                                        echo '<i class="fa-regular fa-star"></i>';
                                    }
                                }
                                echo '</div>';
                                // Show feedback text
                                echo '<div style="margin-top:0.3rem;">' . htmlspecialchars($feedback['feedback']) . '</div>';
                            } else if (in_array($appt['status'], ['approved', 'confirmed'])) {
                                // Show feedback button only for completed/approved appointments
                                echo '<button class="give-feedback-btn" style="background:#2563eb;color:#fff;border:none;border-radius:1em;padding:0.5em 1.2em;font-weight:600;font-size:1rem;box-shadow:0 2px 8px rgba(80,120,255,0.10);display:inline-flex;align-items:center;gap:0.5em;transition:background 0.2s;" data-appointment-id="' . $appt_id . '" data-consultant-id="' . $appt['consultant_id'] . '"><i class="fa-solid fa-star"></i> Give Feedback</button>';
                            } else {
                                echo '<span style="color:#64748b;">-</span>';
                            }
                            ?>
                        </td>
                        <td style="padding:1rem;">
                            <?php 
                            $status_class = 'badge-secondary';
                            switch ($appt['status']) {
                                case 'confirmed': $status_class = 'badge-success'; break;
                                case 'pending': $status_class = 'badge-warning'; break;
                                case 'cancelled': $status_class = 'badge-danger'; break;
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($appt['status']); ?></span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="no-history" style="color:#2563eb;text-align:center;padding:2rem 0;">No past appointments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="modal" tabindex="-1" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="background:#fff; border-radius:1.2rem; max-width:400px; width:90%; margin:auto; box-shadow:0 4px 32px rgba(80,120,255,0.15);">
    <div class="modal-content" style="border:none; padding:2rem;">
      <div class="modal-header" style="border:none; display:flex; justify-content:space-between; align-items:center;">
        <h5 class="modal-title" style="color:#2563eb; font-weight:700;">Give Feedback</h5>
        <button type="button" id="closeFeedbackModal" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
      </div>
      <div class="modal-body">
        <form action="/hahaha11/actions/submit_feedback.php" method="POST" id="feedbackForm">
          <input type="hidden" name="appointment_id" id="feedback_appointment_id">
          <input type="hidden" name="consultant_id" id="feedback_consultant_id">
          <div style="margin-bottom:1rem;">
            <label style="font-weight:600; color:#2563eb;">Rating:</label>
            <div id="starRating" style="font-size:1.7rem; color:#ffc107; cursor:pointer;">
              <i class="fa-regular fa-star" data-value="1"></i>
              <i class="fa-regular fa-star" data-value="2"></i>
              <i class="fa-regular fa-star" data-value="3"></i>
              <i class="fa-regular fa-star" data-value="4"></i>
              <i class="fa-regular fa-star" data-value="5"></i>
            </div>
            <input type="hidden" name="rating" id="feedback_rating" required>
          </div>
          <div style="margin-bottom:1rem;">
            <label for="feedback_text" style="font-weight:600; color:#2563eb;">Feedback:</label>
            <textarea name="feedback" id="feedback_text" class="form-control" rows="3" style="width:100%; border-radius:0.7rem; border:1px solid #dbeafe; padding:0.7rem;" required></textarea>
          </div>
          <div id="feedbackError" style="color:#ef4444; margin-bottom:0.7rem; display:none;"></div>
          <button type="submit" class="btn btn-primary" style="background:#2563eb; color:#fff; border:none; border-radius:0.7rem; padding:0.6em 1.3em; font-weight:600;">Submit Feedback</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Modal logic
const feedbackModal = document.getElementById('feedbackModal');
const closeFeedbackModal = document.getElementById('closeFeedbackModal');
const feedbackForm = document.getElementById('feedbackForm');
const starRating = document.getElementById('starRating');
const feedbackRating = document.getElementById('feedback_rating');
const feedbackError = document.getElementById('feedbackError');
let selectedRating = 0;

// Open modal on button click
Array.from(document.getElementsByClassName('give-feedback-btn')).forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById('feedback_appointment_id').value = this.getAttribute('data-appointment-id');
    document.getElementById('feedback_consultant_id').value = this.getAttribute('data-consultant-id');
    feedbackForm.reset();
    selectedRating = 0;
    updateStars(0);
    feedbackError.style.display = 'none';
    feedbackModal.style.display = 'flex';
  });
});

// Close modal
closeFeedbackModal.onclick = () => feedbackModal.style.display = 'none';
window.onclick = (e) => { if (e.target === feedbackModal) feedbackModal.style.display = 'none'; };

// Star rating logic
function updateStars(rating) {
  Array.from(starRating.children).forEach((star, idx) => {
    if (idx < rating) {
      star.classList.remove('fa-regular');
      star.classList.add('fa-solid');
    } else {
      star.classList.remove('fa-solid');
      star.classList.add('fa-regular');
    }
  });
  feedbackRating.value = rating;
}
Array.from(starRating.children).forEach(star => {
  star.addEventListener('mouseover', function() {
    updateStars(parseInt(this.getAttribute('data-value')));
  });
  star.addEventListener('mouseout', function() {
    updateStars(selectedRating);
  });
  star.addEventListener('click', function() {
    selectedRating = parseInt(this.getAttribute('data-value'));
    updateStars(selectedRating);
  });
});

// AJAX submit feedback
feedbackForm.onsubmit = function(e) {
  e.preventDefault();
  if (!feedbackRating.value || feedbackRating.value < 1 || feedbackRating.value > 5) {
    feedbackError.textContent = 'Please select a rating.';
    feedbackError.style.display = 'block';
    return;
  }
  feedbackError.style.display = 'none';
  const formData = new FormData(feedbackForm);
  fetch('/hahaha11/actions/submit_feedback.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      feedbackModal.style.display = 'none';
      window.location.reload();
    } else {
      feedbackError.textContent = data.error || 'Failed to submit feedback.';
      feedbackError.style.display = 'block';
    }
  })
  .catch(() => {
    feedbackError.textContent = 'Failed to submit feedback.';
    feedbackError.style.display = 'block';
  });
};
</script>
</body>
</html>
