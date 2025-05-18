<?php
require_once '../config/db.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Check if consultant is logged in
checkConsultantLogin();
$consultant_id = $_SESSION['consultant_id'];

// Date filter
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$filter_name = isset($_GET['filter_name']) ? trim($_GET['filter_name']) : '';
$sql = "SELECT a.*, u.name as client_name, u.email as client_email, av.slot_date, av.slot_time, av.duration
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN availability av ON a.availability_id = av.id
        WHERE a.consultant_id = ? AND a.status = 'approved' 
        AND (av.slot_date < CURDATE() OR (av.slot_date = CURDATE() AND av.slot_time < CURTIME()))";
$params = [$consultant_id];
$types = "i";
if ($filter_date) {
    $sql .= " AND av.slot_date = ?";
    $params[] = $filter_date;
    $types .= "s";
}
if ($filter_name) {
    $sql .= " AND u.name LIKE ?";
    $params[] = '%' . $filter_name . '%';
    $types .= "s";
}
$sql .= " ORDER BY av.slot_date DESC, av.slot_time DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$past_appointments = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Past Appointments - Consultant Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(120deg, #e0e7ff 0%, #f0f7ff 100%);
            min-height: 100vh;
        }
        .modern-container {
            max-width: 1000px;
            margin: 3rem auto 2rem auto;
            background: linear-gradient(120deg, #f7faff 60%, #e0e7ff 100%);
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px rgba(67,97,238,0.13);
            padding: 2.8rem 2.5rem 2.2rem 2.5rem;
            border: 1px solid #dbeafe;
            transition: box-shadow 0.25s;
        }
        .modern-header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 1.2rem;
            margin-bottom: 2.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .modern-title {
            font-weight: 800;
            font-size: 2.2rem;
            color: #2563eb;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            text-shadow: 0 2px 8px #e0e7ff;
        }
        .modern-header .text-muted {
            font-size: 1.10rem;
            margin-top: 0.2rem;
            color: #64748b;
        }
        .back-btn {
            border-radius: 0.6rem;
            font-weight: 600;
            font-size: 1.05rem;
            margin-bottom: 1.8rem;
            background: linear-gradient(90deg, #a5b4fc 0%, #60a5fa 100%);
            color: #fff;
            border: none;
            box-shadow: 0 2px 8px rgba(67,97,238,0.10);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .back-btn:hover {
            background: linear-gradient(90deg, #60a5fa 0%, #2563eb 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(67,97,238,0.17);
        }
        .table-modern {
            border-radius: 1.2rem;
            overflow: hidden;
            background: #f8fafc;
            box-shadow: 0 2px 16px rgba(67,97,238,0.08);
        }
        .table-modern th {
            background: linear-gradient(90deg, #e0e7ff 0%, #f1f5ff 100%);
            color: #2563eb;
            font-weight: 700;
            border: none;
            font-size: 1.09rem;
            letter-spacing: 0.01em;
        }
        .table-modern td {
            border: none;
            vertical-align: middle;
            font-size: 1.05rem;
            background: #f8fafc;
            transition: background 0.18s;
        }
        .table-modern tr {
            transition: background 0.18s, box-shadow 0.18s;
        }
        .table-modern tbody tr:hover {
            background: #dbeafe;
            box-shadow: 0 2px 12px #a5b4fc33;
        }
        .btn-view-details {
            background: linear-gradient(90deg, #60a5fa 0%, #2563eb 100%);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(67,97,238,0.07);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-view-details:hover {
            background: linear-gradient(90deg, #2563eb 0%, #60a5fa 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(67,97,238,0.14);
        }
        /* Modal Styling */
        .modal-content {
            border-radius: 1.2rem;
            background: linear-gradient(120deg, #f8fafc 60%, #e0e7ff 100%);
            box-shadow: 0 8px 32px rgba(67,97,238,0.13);
            border: 1px solid #dbeafe;
        }
        .modal-header {
            border-bottom: 1px solid #c7d2fe;
            background: linear-gradient(90deg, #e0e7ff 0%, #f1f5ff 100%);
            border-top-left-radius: 1.2rem;
            border-top-right-radius: 1.2rem;
        }
        .modal-title {
            color: #2563eb;
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: 0.01em;
        }
        .modal-body {
            background: #f8fafc;
            border-bottom-left-radius: 1.2rem;
            border-bottom-right-radius: 1.2rem;
        }
        .modal-footer {
            border-top: 1px solid #c7d2fe;
            background: #f1f5ff;
            border-bottom-left-radius: 1.2rem;
            border-bottom-right-radius: 1.2rem;
        }
        .modal .fw-bold {
            color: #2563eb;
        }
        .spinner-border.text-primary {
            color: #2563eb !important;
        }
        @media (max-width: 700px) {
            .modern-container {
                padding: 1rem 0.5rem 1.5rem 0.5rem;
            }
            .modal-content {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
<div class="modern-container">
    <div class="modern-header">
        <span class="modern-title"><i class="fas fa-history me-2"></i>Past Appointments</span>
        <span class="text-muted">View all your completed appointments with clients.</span>
    </div>
    <a href="appointments.php" class="btn btn-lg btn-back mb-4" style="background:linear-gradient(90deg,#2563eb 0%,#60a5fa 100%);color:#fff;border:none;border-radius:0.7rem;padding:.7rem 2.2rem;font-weight:600;box-shadow:0 2px 8px #2563eb22;transition:background 0.18s,box-shadow 0.18s;">
        <i class="fas fa-arrow-left me-2"></i>Back to Appointments
    </a>
    <form method="get" class="row g-3 align-items-end mb-4" style="max-width: 700px;">
        <div class="col-auto">
            <label for="filter_date" class="form-label mb-1 fw-bold text-primary">Filter by Date</label>
            <input type="date" class="form-control form-control-lg border-0 shadow-sm" style="background:#e0e7ff;color:#2563eb;" id="filter_date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
        </div>
        <div class="col-auto">
            <label for="filter_name" class="form-label mb-1 fw-bold text-primary">Filter by Name</label>
            <input type="text" class="form-control form-control-lg border-0 shadow-sm" style="background:#e0e7ff;color:#2563eb;min-width:170px;" id="filter_name" name="filter_name" placeholder="Enter client name" value="<?php echo htmlspecialchars($filter_name); ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-lg px-4" style="border-radius:0.7rem;background:linear-gradient(90deg,#2563eb 0%,#60a5fa 100%);border:none;">
                <i class="fa-solid fa-filter me-1"></i> Filter
            </button>
        </div>
        <?php if ($filter_date || $filter_name): ?>
        <div class="col-auto">
            <a href="past_appointments.php" class="btn btn-outline-secondary btn-lg px-4" style="border-radius:0.7rem;">
                <i class="fa-solid fa-times"></i> Clear
            </a>
        </div>
        <?php endif; ?>
    </form>
    <style>
        .btn-back:hover {
            background:linear-gradient(90deg,#60a5fa 0%,#2563eb 100%);
            color:#fff;
            box-shadow:0 4px 16px #60a5fa33;
        }
    </style>
    <div class="table-responsive">
        <table class="table table-modern table-striped align-middle">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Duration</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($appt = mysqli_fetch_assoc($past_appointments)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($appt['client_name']); ?></td>
                    <td><?php echo htmlspecialchars($appt['client_email']); ?></td>
                    <td><?php echo date('F j, Y', strtotime($appt['slot_date'])); ?></td>
                    <td><?php 
                        $start_time = date('g:i A', strtotime($appt['slot_time']));
                        $end_time = date('g:i A', strtotime($appt['slot_time']) + ($appt['duration'] * 60));
                        echo $start_time . ' - ' . $end_time;
                    ?></td>
                    <td><?php echo $appt['duration']; ?> min</td>
                    <td>
                        <button class="btn btn-primary btn-sm btn-view-details" 
                                data-id="<?php echo $appt['id']; ?>">
                            <i class="fa fa-eye me-1"></i> View
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    </div>

    <!-- Appointment Details Modal -->
    <div class="modal fade" id="appointmentDetailsModal" tabindex="-1" aria-labelledby="appointmentDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appointmentDetailsModalLabel">
                        <i class="fas fa-calendar-check me-2"></i>Appointment Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modal-loading" class="text-center my-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading appointment details...</p>
                    </div>
                    <div id="modal-details" style="display:none;">
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="fw-bold mb-2"><i class="fa-solid fa-user me-2"></i>Client</h6>
                                <p id="modal-client" class="mb-1"></p>
                                <p id="modal-client-email" class="text-muted small mb-2"></p>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6">
                                <h6 class="fw-bold mb-2"><i class="fa-solid fa-calendar-day me-2"></i>Date</h6>
                                <p id="modal-date"></p>
                            </div>
                            <div class="col-6">
                                <h6 class="fw-bold mb-2"><i class="fa-solid fa-clock me-2"></i>Time</h6>
                                <p id="modal-time"></p>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6">
                                <h6 class="fw-bold mb-2"><i class="fa-solid fa-hourglass-half me-2"></i>Duration</h6>
                                <p id="modal-duration"></p>
                            </div>
                            <div class="col-6">
                                <h6 class="fw-bold mb-2"><i class="fa-solid fa-info-circle me-2"></i>Status</h6>
                                <p id="modal-status"></p>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="fw-bold mb-2"><i class="fa-solid fa-briefcase me-2"></i>Service</h6>
                                <p id="modal-service"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('.btn-view-details').on('click', function() {
        var apptId = $(this).data('id');
        $('#appointmentDetailsModal').modal('show');
        $('#modal-details').hide();
        $('#modal-loading').show();
        $.ajax({
            url: '../actions/get_appointment_details.php',
            method: 'GET',
            data: { id: apptId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var appt = response.appointment;
                    $('#modal-client').text(appt.client_name);
                    $('#modal-client-email').text(appt.client_email);
                    $('#modal-date').text(appt.date);
                    $('#modal-time').text(appt.start_time + ' - ' + appt.end_time);
                    $('#modal-duration').text(appt.duration + ' min');
                    $('#modal-status').text(appt.status.charAt(0).toUpperCase() + appt.status.slice(1));
                    $('#modal-service').text(appt.service ? appt.service : '-');
                    $('#modal-loading').hide();
                    $('#modal-details').show();
                } else {
                    $('#modal-loading').html('<p class="text-danger">' + response.message + '</p>');
                }
            },
            error: function() {
                $('#modal-loading').html('<p class="text-danger">Failed to load appointment details.</p>');
            }
        });
    });
    // Reset modal when closed
    $('#appointmentDetailsModal').on('hidden.bs.modal', function () {
        $('#modal-details').hide();
        $('#modal-loading').show().html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Loading appointment details...</p>');
    });
});
</script>
</body>
</html>
