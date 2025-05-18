<?php
/**
 * Process Availability Handler
 * 
 * Handles both creating new availability slots and deleting existing ones
 * for consultant availability management.
 */

// Include necessary files
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if consultant is logged in
checkConsultantLogin();

// Get consultant ID from session
$consultant_id = $_SESSION['consultant_id'];

// Initialize errors array
$errors = [];

// HANDLE DELETE REQUEST
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $slot_id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Check if the slot exists and belongs to this consultant
    $check_sql = "SELECT id, is_booked FROM availability 
                 WHERE id = $slot_id AND consultant_id = $consultant_id";
    $result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($result) > 0) {
        $slot = mysqli_fetch_assoc($result);
        
        // Check if the slot is booked
        if ($slot['is_booked'] == 1) {
            $_SESSION['availability_errors'] = ["You cannot delete a time slot that is already booked by a client."];
        } else {
            // Delete the availability slot
            $delete_sql = "DELETE FROM availability WHERE id = $slot_id";
            if (mysqli_query($conn, $delete_sql)) {
                $_SESSION['success_message'] = "Time slot deleted successfully.";
            } else {
                $_SESSION['availability_errors'] = ["Error deleting time slot: " . mysqli_error($conn)];
            }
        }
    } else {
        $_SESSION['availability_errors'] = ["Invalid time slot or you don't have permission to delete it."];
    }
    
    // Redirect back to the availability page
    header("Location: /consultant/availability.php");
    exit();
}

// HANDLE CREATE REQUEST (FORM SUBMISSION)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate form data
    if (empty($_POST['dates'])) {
        $errors[] = "Please select at least one date.";
    }
    
    if (empty($_POST['start_times']) || empty($_POST['end_times'])) {
        $errors[] = "Please provide at least one time slot with start and end times.";
    }
    
    // Get slot duration (in minutes)
    $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 60;
    
    // Valid durations: 30, 60, 90, 120 minutes
    if (!in_array($duration, [30, 60, 90, 120])) {
        $duration = 60; // Default to 1 hour if invalid
    }
    
    // If no errors, process the form
    if (empty($errors)) {
        $dates = explode(',', $_POST['dates']);
        $start_times = $_POST['start_times'];
        $end_times = $_POST['end_times'];
        
        $slots_created = 0;
        $slots_failed = 0;
        
        // Loop through each date
        foreach ($dates as $date) {
            $date = trim($date);
            
            // Validate date format (YYYY-MM-DD)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $errors[] = "Invalid date format: $date";
                continue;
            }
            
            // Ensure date is not in the past
            $current_date = date('Y-m-d');
            if ($date < $current_date) {
                $errors[] = "Cannot create availability for past dates: $date";
                continue;
            }
            
            // Loop through each time slot range
            for ($i = 0; $i < count($start_times); $i++) {
                if (empty($start_times[$i]) || empty($end_times[$i])) {
                    continue; // Skip empty time slots
                }
                
                $start_time = $start_times[$i];
                $end_time = $end_times[$i];
                
                // Validate time format and range
                if ($start_time >= $end_time) {
                    $errors[] = "End time must be after start time for slot #" . ($i + 1);
                    continue;
                }
                
                // Calculate time slots based on duration
                $current_time = strtotime($start_time);
                $end_timestamp = strtotime($end_time);
                
                while ($current_time + ($duration * 60) <= $end_timestamp) {
                    $slot_time = date('H:i:s', $current_time);
                    
                    // Check if this slot already exists
                    $check_sql = "SELECT id FROM availability 
                                WHERE consultant_id = $consultant_id 
                                AND slot_date = '$date' 
                                AND slot_time = '$slot_time'";
                    $result = mysqli_query($conn, $check_sql);
                    
                    if (mysqli_num_rows($result) == 0) {
                        // Insert new availability slot
                        $insert_sql = "INSERT INTO availability (consultant_id, slot_date, slot_time) 
                                      VALUES ($consultant_id, '$date', '$slot_time')";
                        
                        if (mysqli_query($conn, $insert_sql)) {
                            $slots_created++;
                        } else {
                            $slots_failed++;
                            $errors[] = "Error creating slot for $date at " . date('g:i A', $current_time) . ": " . mysqli_error($conn);
                        }
                    } else {
                        // Slot already exists, skip it
                        $slots_failed++;
                    }
                    
                    // Move to next time slot
                    $current_time += ($duration * 60);
                }
            }
        }
        
        // Set success message if any slots were created
        if ($slots_created > 0) {
            $_SESSION['success_message'] = "$slots_created time slot(s) created successfully.";
            
            // Add warning about skipped slots if any failed
            if ($slots_failed > 0) {
                $_SESSION['success_message'] .= " $slots_failed slot(s) were skipped (already exist or error).";
            }
        } elseif ($slots_failed > 0) {
            $errors[] = "No time slots were created. $slots_failed slot(s) were skipped (already exist or error).";
        } else {
            $errors[] = "No time slots were created. Please check your selection.";
        }
    }
    
    // Store errors in session if any
    if (!empty($errors)) {
        $_SESSION['availability_errors'] = $errors;
    }
    
    // Redirect back to the availability page
    header("Location: /consultant/dashboard.php");
    exit();
}

// If neither POST nor GET['delete'], redirect back
header("Location: /consultant/dashboard.php");
exit();
?>