<?php
// This file should be placed in ../actions/set_availability.php relative to your dashboard

require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if consultant is logged in
checkConsultantLogin();

$consultant_id = $_SESSION['consultant_id'];

// Handle deletion of availability slot
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $slot_id = $_GET['delete'];
    
    // Check if slot belongs to this consultant and is not booked
    $sql = "SELECT * FROM availability WHERE id = ? AND consultant_id = ? AND is_booked = 0";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $slot_id, $consultant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Delete the slot
        $sql = "DELETE FROM availability WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $slot_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Availability slot deleted successfully.";
        } else {
            $_SESSION['availability_errors'] = ["Failed to delete availability slot. Please try again."];
        }
    } else {
        $_SESSION['availability_errors'] = ["Unable to delete availability slot. It may be booked or not found."];
    }
    
    header("Location: ../consultant/set_availability.php");
    exit();
}

// Handle adding new availability
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate inputs
    if (empty($_POST['dates'])) {
        $errors[] = "Please select at least one date.";
    }
    
    if (empty($_POST['start_times']) || empty($_POST['end_times'])) {
        $errors[] = "Please provide start and end times.";
    }
    
    if (!isset($_POST['duration']) || !in_array($_POST['duration'], [30, 60, 90, 120])) {
        $errors[] = "Invalid session duration.";
    }
    
    if (empty($errors)) {
        $dates = explode(',', $_POST['dates']);
        $start_times = $_POST['start_times'];
        $end_times = $_POST['end_times'];
        $duration = $_POST['duration'];
        
        $slots_added = 0;
        
        // For each date and time range
        foreach ($dates as $date) {
            foreach ($start_times as $key => $start_time) {
                if (isset($end_times[$key]) && !empty($start_time) && !empty($end_times[$key])) {
                    $start = $start_time;
                    $end = $end_times[$key];
                    
                    // Skip if end time is before or equal to start time
                    if ($end <= $start) {
                        $errors[] = "End time must be after start time.";
                        continue;
                    }
                    
                    // Generate time slots based on duration
                    $current_time = strtotime($start);
                    $end_time = strtotime($end);
                    
                    while ($current_time + ($duration * 60) <= $end_time) {
                        $slot_time = date('H:i:s', $current_time);
                        
                        // Check if slot already exists
                        $sql = "SELECT * FROM availability 
                                WHERE consultant_id = ? 
                                AND slot_date = ? 
                                AND slot_time = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "iss", $consultant_id, $date, $slot_time);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($result) == 0) {
                            // Insert new slot
                            $sql = "INSERT INTO availability (consultant_id, slot_date, slot_time, duration, is_booked) 
                                    VALUES (?, ?, ?, ?, 0)";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "issi", $consultant_id, $date, $slot_time, $duration);
                            
                            if (mysqli_stmt_execute($stmt)) {
                                $slots_added++;
                            } else {
                                $errors[] = "Error adding slot at " . date('h:i A', $current_time) . ": " . mysqli_error($conn);
                            }
                        }
                        
                        // Move to next slot
                        $current_time += ($duration * 60);
                    }
                }
            }
        }
        
        if ($slots_added > 0) {
            $_SESSION['success_message'] = "$slots_added availability slots added successfully.";
        } else {
            $errors[] = "No new availability slots were added. They may already exist or there was an error.";
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['availability_errors'] = $errors;
    }
    
    header("Location: ../consultant/set_availability.php");
    exit();
}

// If neither POST nor GET[delete], redirect back to availability page
header("Location: ../dashboard/availability.php");
exit();
?>