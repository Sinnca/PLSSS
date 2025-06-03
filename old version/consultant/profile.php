<?php
require_once '../includes/header.php';
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if consultant is logged in
checkConsultantLogin();

$consultant_id = $_SESSION['consultant_id'];

// Fetch consultant profile
$sql = "SELECT c.*, u.name, u.email
        FROM consultants c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = $consultant_id";
$result = mysqli_query($conn, $sql);
$profile = mysqli_fetch_assoc($result);

// Check if consultant_specialties table exists, if not, use specialty from consultants table
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'consultant_specialties'");
$specialties = [];

if(mysqli_num_rows($table_check) > 0) {
    // Fetch consultant specialties from the dedicated table
    $sql = "SELECT * FROM consultant_specialties WHERE consultant_id = $consultant_id";
    $specialties_result = mysqli_query($conn, $sql);
    
    while ($row = mysqli_fetch_assoc($specialties_result)) {
        $specialties[] = $row['specialty'];
    }
} else {
    // Use the specialty field from the consultants table
    if(!empty($profile['specialty'])) {
        $specialties[] = $profile['specialty'];
    }
}

// Fetch availability slots
$sql = "SELECT * FROM availability 
        WHERE consultant_id = $consultant_id 
        ORDER BY slot_date ASC, slot_time ASC";
$availability_result = mysqli_query($conn, $sql);

// Alert messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            ' . $_SESSION['success_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['upload_error'])) {
    echo "Error: " . $_SESSION['upload_error'];
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            ' . $_SESSION['upload_error'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['upload_error']);
}

if (isset($_SESSION['availability_errors'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">';
    foreach ($_SESSION['availability_errors'] as $error) {
        echo '<li>' . $error . '</li>';
    }
    echo '</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['availability_errors']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Consultant Dashboard</title>
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
            --gradient-start: #2563eb;
            --gradient-end: #1d4ed8;
        }

        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            background-color: var(--background);
            color: var(--text-color);
            line-height: 1.6;
        }

        .navbar {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            box-shadow: 0 2px 15px rgba(37, 99, 235, 0.2);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.4rem;
        }

        .nav-link {
            font-weight: 500;
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 1) !important;
        }

        .nav-link:hover {
            transform: translateY(-2px);
            color: rgba(255, 255, 255, 1) !important;
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
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fa-solid fa-user-md me-2"></i>
                Consultant Profile
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fa-solid fa-gauge-high me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">
                            <i class="fa-solid fa-envelope me-1"></i> Messages
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="consultantDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-circle me-1"></i> 
                            <?php echo htmlspecialchars($profile['name']); ?>
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

    <?php
    echo '<div class="consultant-info">';
    echo '<div class="consultant-id">Consultant ID: <span>' . $_SESSION['consultant_id'] . '</span></div>';
    echo '<div class="user-id">User ID: <span>' . $_SESSION['user_id'] . '</span></div>';
    echo '</div>';
    ?>

    <div class="profile-container">
        <div class="profile-header">
            <img src="<?php echo !empty($profile['profile_photo']) ? '../' . $profile['profile_photo'] : '../uploads/profiles/default.jpg'; ?>" alt="Profile Photo" class="profile-photo">
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($profile['name']); ?></h1>
                <p class="lead"><?php echo !empty($profile['title']) ? htmlspecialchars($profile['title']) : 'Consultant'; ?></p>
                <p><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($profile['email']); ?></p>
                <p>
                    <span class="status-badge <?php echo isset($profile['status']) && $profile['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo ucfirst(isset($profile['status']) ? $profile['status'] : 'active'); ?>
                    </span>
                </p>
                <div class="mt-2">
                    <?php foreach ($specialties as $specialty): ?>
                        <span class="badge-specialty"><?php echo htmlspecialchars($specialty); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs profile-tabs" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">Profile</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="availability-tab" data-bs-toggle="tab" data-bs-target="#availability" type="button" role="tab" aria-controls="availability" aria-selected="false">Availability</button>
            </li>
        </ul>
        
        <div class="tab-content" id="profileTabsContent">
            <!-- Profile Tab -->
            <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                <div class="profile-section">
                    <h3>Profile Information</h3>
                    <form action="../actions/update_profile.php" method="post" class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($profile['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="title" class="form-label">Professional Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($profile['title'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label for="specialty" class="form-label">Specialty</label>
                            <input type="text" class="form-control" id="specialty" name="specialty" value="<?php echo htmlspecialchars($profile['specialty'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="hourly_rate" class="form-label">Hourly Rate</label>
                            <input type="number" step="0.01" class="form-control" id="hourly_rate" name="hourly_rate" value="<?php echo htmlspecialchars($profile['hourly_rate'] ?? '0.00'); ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="experience" class="form-label">Experience (years)</label>
                            <input type="number" class="form-control" id="experience" name="experience" value="<?php echo htmlspecialchars($profile['experience'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label for="qualification" class="form-label">Qualification</label>
                            <input type="text" class="form-control" id="qualification" name="qualification" value="<?php echo htmlspecialchars($profile['qualification'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($profile['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
                
                <div class="profile-section">
                    <h3>Update Profile Photo</h3>
                    <form action="../actions/upload_profile.php" method="post" enctype="multipart/form-data" class="row g-3">
                        <div class="col-md-8">
                            <input class="form-control" type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/gif" required>
                            <div class="form-text">Max file size: 2MB. Accepted formats: JPG, PNG, GIF</div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">Upload Photo</button>
                        </div>
                    </form>
                </div>
                
                <div class="profile-section">
                    <h3>Upload CV/Resume</h3>
                    <form action="../actions/upload_cv.php" method="post" enctype="multipart/form-data" class="row g-3">
                        <div class="col-md-8">
                            <input class="form-control" type="file" id="cv_file" name="cv_file" accept=".pdf,.doc,.docx" required>
                            <div class="form-text" style="color: #8ca3f8;">Max file size: 5MB. Accepted formats: PDF, DOC, DOCX</div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">Upload CV</button>
                        </div>
                    </form>
                    <?php if (!empty($profile['cv_file'])): ?>
                        <div class="mt-3">
                            <a href="../<?php echo htmlspecialchars($profile['cv_file']); ?>" class="btn btn-outline-primary" target="_blank">
                                <i class="fas fa-file-alt me-2"></i>View Current CV
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            
            <!-- Availability Tab -->
            <div class="tab-pane fade" id="availability" role="tabpanel" aria-labelledby="availability-tab">
                <div class="profile-section">
                    <h3>Current Availability</h3>
                    <?php if (mysqli_num_rows($availability_result) > 0): ?>
                        <div class="row">
                            <?php
                            $current_date = '';
                            while ($slot = mysqli_fetch_assoc($availability_result)):
                                $date = date('F j, Y', strtotime($slot['slot_date']));
                                $time = date('h:i A', strtotime($slot['slot_time']));
                                $duration = isset($slot['duration']) ? $slot['duration'] : 60; // Default to 60 minutes if duration not set
                                $end_time = date('h:i A', strtotime($slot['slot_time']) + ($duration * 60));
                                
                                if ($date != $current_date) {
                                    if ($current_date != '') {
                                        echo '</div></div>';
                                    }
                                    echo '<div class="mb-4">';
                                    echo '<h5>' . $date . '</h5>';
                                    echo '<div class="row">';
                                    $current_date = $date;
                                }
                            ?>
                                <div class="col-md-4 mb-2">
                                    <div class="availability-slot <?php echo $slot['is_booked'] ? 'booked' : ''; ?>">
                                        <div>
                                            <span class="time-range"><?php echo $time . ' - ' . $end_time; ?></span>
                                            <span class="badge bg-<?php echo $slot['is_booked'] ? 'secondary' : 'success'; ?> ms-2">
                                                <?php echo $slot['is_booked'] ? 'Booked' : 'Available'; ?>
                                            </span>
                                        </div>
                                        <?php if (!$slot['is_booked']): ?>
                                            <a href="../actions/set_availability.php?delete=<?php echo $slot['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this availability slot?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            You have not set any availability slots yet.
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-section">
                    <h3>Set New Availability</h3>
                    <form action="../actions/set_availability.php" method="post" class="row g-3">
                        <div class="col-12">
                            <label for="dates" class="form-label">Select Dates</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="text" class="form-control date-picker" id="dates" name="dates" placeholder="Select dates" required>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-clock me-2"></i>Set Availability
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <script>
        // Initialize date picker
        flatpickr(".date-picker", {
            mode: "multiple",
            dateFormat: "Y-m-d",
            minDate: "today",
            altInput: true,
            altFormat: "F j, Y",
            conjunction: ", ",
            disable: []  // You can add dates to disable here
        });

        // Add time slot functionality
        document.getElementById('add-time-slot').addEventListener('click', function() {
            const timeSlotContainer = document.getElementById('time-slots');
            const timeSlotPairs = timeSlotContainer.querySelectorAll('.time-slot-pair');
            const lastTimeSlotPair = timeSlotPairs[timeSlotPairs.length - 1];
            
            // Enable the remove button on the last time slot
            const lastRemoveButton = lastTimeSlotPair.querySelector('.remove-time-slot');
            lastRemoveButton.disabled = false;
            
            // Clone the last time slot pair
            const newTimeSlotPair = lastTimeSlotPair.cloneNode(true);
            
            // Clear the inputs
            newTimeSlotPair.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
            
            // Add event listener to remove button
            const removeButton = newTimeSlotPair.querySelector('.remove-time-slot');
            removeButton.addEventListener('click', function() {
                this.closest('.time-slot-pair').remove();
                
                // If only one time slot is left, disable its remove button
                const timeSlotPairs = timeSlotContainer.querySelectorAll('.time-slot-pair');
                if (timeSlotPairs.length === 1) {
                    timeSlotPairs[0].querySelector('.remove-time-slot').disabled = true;
                }
            });
            
            // Add the new time slot pair to the container
            timeSlotContainer.appendChild(newTimeSlotPair);
        });
        
        // Add specialty functionality
        document.getElementById('add-specialty').addEventListener('click', function() {
            const specialtyInput = document.getElementById('specialty-input');
            const specialty = specialtyInput.value.trim();
            
            if (specialty) {
                const specialtiesContainer = document.getElementById('specialties-container');
                
                // Create specialty chip
                const specialtyChip = document.createElement('div');
                specialtyChip.className = 'specialty-chip';
                specialtyChip.innerHTML = `
                    ${specialty}
                    <input type="hidden" name="specialties[]" value="${specialty}">
                    <button type="button" class="btn-remove-specialty"><i class="fas fa-times"></i></button>
                `;
                
                // Add event listener to remove button
                const removeButton = specialtyChip.querySelector('.btn-remove-specialty');
                removeButton.addEventListener('click', function() {
                    this.closest('.specialty-chip').remove();
                });
                
                // Add the chip to the container
                specialtiesContainer.appendChild(specialtyChip);
                
                // Clear the input
                specialtyInput.value = '';
            }
        });
        
        // Add event listeners to existing remove specialty buttons
        document.querySelectorAll('.btn-remove-specialty').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.specialty-chip').remove();
            });
        });
        
        // Calculate discount for package
        document.getElementById('sessions_count').addEventListener('input', calculateDiscount);
        document.getElementById('package_price').addEventListener('input', calculateDiscount);
        
        function calculateDiscount() {
            const sessionsCount = document.getElementById('sessions_count').value;
            const packagePrice = document.getElementById('package_price').value;
            const hourlyRate = <?php echo $profile['hourly_rate'] ?? 0; ?>;
            
            if (sessionsCount && packagePrice && hourlyRate) {
                const regularPrice = hourlyRate * sessionsCount;
                const discountPercentage = (regularPrice - packagePrice) / regularPrice * 100;
                
                document.getElementById('discount_preview').value = discountPercentage.toFixed(2) + '%';
            } else {
                document.getElementById('discount_preview').value = '';
            }
        }
    </script>

    <style>
    /* Enhanced Profile Container */
    .profile-container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        overflow: hidden;
        position: relative;
    }

    /* Enhanced Profile Header */
    .profile-header {
        display: flex;
        align-items: center;
        gap: 2.5rem;
        margin-bottom: 0;
        padding: 3rem;
        background: linear-gradient(135deg, #1e40af, #3b82f6);
        color: white;
        position: relative;
        overflow: hidden;
        border-bottom: 4px solid rgba(255, 255, 255, 0.1);
    }

    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h100v100H0z" fill="none"/><path d="M0 0h100v100H0z" fill="rgba(255,255,255,0.05)"/></svg>') repeat;
        opacity: 0.1;
        animation: backgroundMove 20s linear infinite;
    }

    @keyframes backgroundMove {
        0% {
            background-position: 0 0;
        }
        100% {
            background-position: 100px 100px;
        }
    }

    /* Enhanced Profile Photo */
    .profile-photo {
        width: 180px;
        height: 180px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #3b82f6;
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
        z-index: 2;
        transition: transform 0.3s ease, border-color 0.3s ease;
        position: relative;
        animation: photoGlow 3s infinite;
    }

    @keyframes photoGlow {
        0% {
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
        }
        50% {
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.5);
        }
        100% {
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
        }
    }

    .profile-info {
        flex: 1;
        z-index: 2;
    }

    .profile-info h1 {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        color: white;
        font-weight: 700;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .profile-info .lead {
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.95);
        margin-bottom: 1.2rem;
        font-weight: 500;
    }

    .profile-info p {
        margin-bottom: 0.8rem;
        color: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Status badges */
    .status-badge {
        display: inline-block;
        padding: 0.3rem 1rem;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        position: relative;
        padding-left: 2rem;
    }

    .status-badge::before {
        content: '';
        position: absolute;
        left: 0.8rem;
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: currentColor;
        box-shadow: 0 0 8px currentColor;
    }

    .status-active {
        background-color: rgba(34, 197, 94, 0.2);
        color: #16a34a;
        border: 1px solid rgba(34, 197, 94, 0.3);
        animation: pulse 2s infinite;
    }

    .status-inactive {
        background-color: rgba(239, 68, 68, 0.2);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(34, 197, 94, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
        }
    }

    .badge-specialty {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        color: #1e40af;
        padding: 0.8rem 1.2rem;
        border-radius: 30px;
        margin-right: 0.8rem;
        margin-bottom: 0.8rem;
        font-size: 0.95rem;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        transition: all 0.3s ease;
        border: 1px solid rgba(59, 130, 246, 0.1);
        position: relative;
        overflow: hidden;
    }

    .badge-specialty::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .badge-specialty:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }

    .badge-specialty:hover::before {
        opacity: 1;
    }

    /* Profile tabs */
    .profile-tabs {
        padding: 0 2.5rem;
        margin-bottom: 0;
        background-color: #fff;
        border-bottom: 1px solid #e5e7eb;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        list-style: none;
    }

    .profile-tabs .nav-link {
        color: #64748b;
        border: none;
        padding: 1.4rem 1.8rem;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        font-size: 1rem;
    }

    .profile-tabs .nav-link:hover {
        color: #1e40af;
        background: none;
    }

    .profile-tabs .nav-link.active {
        color: #1e40af;
        background: none;
        font-weight: 700;
    }

    .profile-tabs .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 4px;
        background: #1e40af;
        border-radius: 4px 4px 0 0;
    }

    /* Enhanced Profile Sections */
    .profile-section {
        background: #fff;
        padding: 2.5rem;
        border-radius: 16px;
        margin: 2rem;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(59, 130, 246, 0.1);
    }

    .profile-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(to bottom, #1e40af, #3b82f6);
        opacity: 0.8;
    }

    .profile-section:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .profile-section h3 {
        color: #1e293b;
        margin-bottom: 2rem;
        font-size: 1.5rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .profile-section h3::after {
        content: '';
        height: 2px;
        background: linear-gradient(to right, #e5e7eb, transparent);
        flex-grow: 1;
    }

    /* Enhanced Form Controls */
    .form-label {
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.8rem;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-label::before {
        content: '';
        display: inline-block;
        width: 4px;
        height: 16px;
        background: linear-gradient(to bottom, #1e40af, #3b82f6);
        border-radius: 2px;
    }

    .form-control {
        border: 2px solid #e2e8f0;
        padding: 1rem 1.2rem;
        border-radius: 12px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        font-size: 1rem;
        background-color: #f8fafc;
        position: relative;
        overflow: hidden;
    }

    .form-control:focus {
        border-color: #1e40af;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        background-color: #fff;
        transform: translateY(-1px);
    }

    .input-group {
        position: relative;
        display: flex;
        align-items: stretch;
        width: 100%;
    }

    .input-group-text {
        background-color: #f8fafc;
        border: 2px solid #e2e8f0;
        border-right: none;
        color: #64748b;
        padding: 1rem 1.2rem;
        border-radius: 12px 0 0 12px;
    }

    .input-group .form-control {
        border-radius: 0 12px 12px 0;
    }

    /* Enhanced Bio Textarea */
    textarea.form-control {
        min-height: 120px;
        resize: vertical;
        line-height: 1.6;
    }

    /* Enhanced Hourly Rate Input */
    input[type="number"].form-control {
        font-family: monospace;
        font-size: 1.1rem;
        text-align: right;
        padding-right: 2rem;
    }

    /* Enhanced File Upload Preview */
    .file-upload-preview {
        margin-top: 1rem;
        padding: 1rem;
        background-color: #f8fafc;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        display: none;
    }

    .file-upload-preview.active {
        display: block;
        animation: fadeIn 0.3s ease-out;
    }

    /* Enhanced Availability Section */
    .availability-slot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        background-color: #f8fafc;
        border-radius: 12px;
        border-left: 4px solid #1e40af;
        transition: all 0.3s ease;
        margin-bottom: 1rem;
        position: relative;
        overflow: hidden;
    }

    .availability-slot::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .availability-slot:hover::before {
        opacity: 1;
    }

    /* Enhanced Date Picker */
    .flatpickr-calendar {
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        border: none;
    }

    .flatpickr-day.selected {
        background: #1e40af !important;
        border-color: #1e40af !important;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }

    /* Enhanced Alert Messages */
    .alert {
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }

    .alert::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: currentColor;
        opacity: 0.2;
    }

    /* Responsive Enhancements */
    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
            padding: 2rem 1.5rem;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            margin-bottom: 1.5rem;
        }

        .profile-section {
            padding: 1.5rem;
            margin: 1rem;
        }

        .form-control {
            padding: 0.8rem 1rem;
        }

        .btn-primary {
            padding: 0.8rem 1.5rem;
        }
    }

    /* Animation Effects */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .profile-section {
        animation: fadeIn 0.5s ease-out;
    }

    /* Specialty chips */
    .specialty-input-container {
        display: flex;
        gap: 0.8rem;
        margin-bottom: 1.2rem;
    }

    .btn-remove-specialty {
        background: none;
        border: none;
        color: #1e40af;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        font-size: 0.8rem;
        margin-left: 0.6rem;
        padding: 0;
        cursor: pointer;
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .btn-remove-specialty:hover {
        background-color: rgba(59, 130, 246, 0.1);
        transform: rotate(90deg);
    }

    /* Availability */
    .time-range {
        font-weight: 600;
        color: #334155;
        font-size: 1.1rem;
    }

    .badge {
        padding: 0.4rem 0.8rem;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .bg-success {
        background: linear-gradient(135deg, #1e40af, #3b82f6) !important;
    }

    .bg-secondary {
        background: linear-gradient(135deg, #64748b, #475569) !important;
    }

    /* Consultant Info Styling */
    .consultant-info {
        background: linear-gradient(135deg, #1e40af, #3b82f6);
        padding: 1.5rem;
        border-radius: 12px;
        margin: 1rem 2.5rem;
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
    }

    .consultant-id, .user-id {
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .consultant-id span, .user-id span {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-weight: 600;
        backdrop-filter: blur(4px);
    }
    </style>
</body>
</html>
    