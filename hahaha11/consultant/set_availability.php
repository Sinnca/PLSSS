<?php
require_once '../config/db.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Check if consultant is logged in
checkConsultantLogin();

$consultant_id = $_SESSION['consultant_id'];

// Get current availability
$sql = "SELECT * FROM availability 
        WHERE consultant_id = $consultant_id 
        AND slot_date >= CURDATE()
        ORDER BY slot_date, slot_time";
$availability = mysqli_query($conn, $sql);

// Get upcoming appointments
$sql = "SELECT a.*, app.id as appointment_id, app.status, app.purpose, u.name as client_name, u.email as client_email
        FROM availability a
        JOIN appointments app ON a.id = app.availability_id
        JOIN users u ON app.user_id = u.id
        WHERE a.consultant_id = $consultant_id 
        AND a.slot_date >= CURDATE()
        AND a.is_booked = 1
        ORDER BY a.slot_date, a.slot_time";
$appointments = mysqli_query($conn, $sql);

// Group availability by date for calendar view
$availability_by_date = [];
$dates_with_slots = [];
mysqli_data_seek($availability, 0);
while ($slot = mysqli_fetch_assoc($availability)) {
    $date = $slot['slot_date'];
    if (!isset($availability_by_date[$date])) {
        $availability_by_date[$date] = [];
        $dates_with_slots[] = $date;
    }
    $availability_by_date[$date][] = $slot;
}

// Fetch consultant name for navbar
$sql = "SELECT u.name FROM consultants c JOIN users u ON c.user_id = u.id WHERE c.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $consultant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$consultant = mysqli_fetch_assoc($result);
$consultant_name = $consultant['name'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Availability | Consultant Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #eef2ff;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-light: #e5e7eb;
            --bg-light: #f9fafb;
            --bg-gray: #f3f4f6;
            --white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 0.5rem;
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --text-color: #1e293b;
            --light-text: #64748b;
            --background: #f0f7ff;
            --hover-bg: #e6f0ff;
            --border-color: #dbeafe;
            --gradient-start: #2563eb;
            --gradient-end: #1d4ed8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.5;
            color: var(--text-dark);
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        
        .dashboard-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem 1rem;
            background: rgba(255,255,255,0.95);
            border-radius: 2rem;
            box-shadow: 0 8px 32px rgba(67,97,238,0.10);
            padding: 2.5rem 2rem;
            margin-top: 2rem;
        }
        
        .header-section {
            background: linear-gradient(90deg, #2563eb 60%, #3b82f6 100%);
            color: #fff;
            border-radius: 1.5rem 1.5rem 0 0;
            box-shadow: 0 4px 24px rgba(67,97,238,0.10);
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
            text-shadow: 0 2px 8px rgba(37,99,235,0.10);
        }
        
        .header-section .page-subtitle {
            color: #e0e7ff;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0;
        }
        
        .header-section .btn-primary {
            box-shadow: 0 2px 8px rgba(67,97,238,0.15);
            font-size: 1.1rem;
            padding: 0.8rem 2rem;
        }
        
        @media (max-width: 700px) {
            .header-section {
                padding: 1.2rem 0.7rem 1.2rem 0.7rem;
            }
            .header-section .page-title {
                font-size: 1.3rem;
            }
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.5;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: var(--radius);
            transition: all 0.3s ease;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .btn::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: -100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        
        .btn:hover::after {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #4361ee, #2563eb);
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 2rem;
            box-shadow: 0 4px 15px rgba(67,97,238,0.2);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(90deg, #2563eb, #4361ee);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67,97,238,0.3);
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        @media (min-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 2fr 1fr;
            }
        }
        
        .card {
            background-color: var(--white);
            border-radius: 1.2rem;
            box-shadow: 0 6px 32px rgba(67,97,238,0.10);
            overflow: hidden;
            margin-bottom: 2rem;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(67,97,238,0.15);
        }
        
        .card-header {
            background: linear-gradient(90deg, #2563eb 60%, #3b82f6 100%);
            color: #fff;
            padding: 1.5rem 1.5rem 1rem 1.5rem;
            border-bottom: none;
            font-weight: 700;
            font-size: 1.2rem;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(67,97,238,0.08);
        }
        
        .card-title {
            color: #fff;
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0;
        }
        
        .card-body {
            padding: 2rem 1.5rem;
        }
        
        .card-footer {
            padding: 1rem 1.5rem;
            background-color: var(--bg-light);
            border-top: 1px solid var(--border-light);
        }
        
        .appointments-container {
            max-height: 400px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--bg-light);
        }
        
        .appointments-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .appointments-container::-webkit-scrollbar-track {
            background: var(--bg-light);
            border-radius: 10px;
        }
        
        .appointments-container::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 10px;
        }
        
        /* Calendar Styles */
        .calendar-header {
            background: linear-gradient(90deg, #2563eb 60%, #3b82f6 100%);
            color: #fff;
            border-radius: 1rem 1rem 0 0;
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        
        .calendar-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .calendar-nav {
            display: flex;
            gap: 0.5rem;
        }
        
        .calendar-nav-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background-color: var(--bg-light);
            border: 1px solid var(--border-light);
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .calendar-nav-btn:hover {
            background-color: var(--primary-light);
            color: var(--primary);
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }
        
        .calendar-day-header {
            text-align: center;
            font-weight: 500;
            font-size: 0.85rem;
            padding: 0.5rem 0;
            color: var(--text-muted);
        }
        
        .calendar-day {
            aspect-ratio: 1/1;
            border-radius: 0.5rem;
            border: 1px solid var(--border-light);
            padding: 0.5rem;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            z-index: 1;
        }
        
        .calendar-day:hover {
            background: linear-gradient(135deg, #e0e7ff 0%, #f0f7ff 100%) !important;
            transform: translateY(-3px) scale(1.05);
            z-index: 2;
            box-shadow: 0 4px 15px rgba(67,97,238,0.2);
        }
        
        .calendar-day.other-month {
            opacity: 0.5;
            background-color: var(--bg-light);
        }
        
        .calendar-day.today {
            background-color: var(--primary-light);
            border-color: var(--primary);
        }
        
        .calendar-day-number {
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .calendar-day-indicator {
            position: absolute;
            bottom: 0.5rem;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 0.25rem;
        }
        
        .calendar-day-indicator span {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
        }
        
        .indicator-available {
            background-color: var(--success);
        }
        
        .indicator-booked {
            background-color: var(--danger);
        }
        
        .calendar-day.selected, .calendar-day.has-availability {
            background: linear-gradient(135deg, #2563eb 0%, #4361ee 100%) !important;
            color: white !important;
            border: none;
            box-shadow: 0 4px 15px rgba(67,97,238,0.3);
        }
        
        /* Time Slots List */
        .time-slots-container {
            padding: 1rem 0;
            max-height: 600px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--bg-light);
        }
        
        .time-slots-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .time-slots-container::-webkit-scrollbar-track {
            background: var(--bg-light);
            border-radius: 10px;
        }
        
        .time-slots-container::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 10px;
        }
        
        .time-slots-container.date-filtered {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
            border-radius: 0.5rem;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--bg-light);
        }
        
        .time-slots-container.date-filtered::-webkit-scrollbar {
            width: 8px;
        }
        
        .time-slots-container.date-filtered::-webkit-scrollbar-track {
            background: var(--bg-light);
            border-radius: 10px;
        }
        
        .time-slots-container.date-filtered::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 10px;
        }
        
        .date-header {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 1.5rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-light);
            transition: all 0.3s ease;
            scroll-margin-top: 20px;
        }
        
        .highlight-date {
            background-color: var(--primary-light);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67,97,238,0.2);
        }
        
        .date-header:first-child {
            margin-top: 0;
        }
        
        .time-slot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.75rem;
        }
        
        .time-slot-card {
            padding: 1.25rem;
            border-radius: 1rem;
            background-color: var(--bg-light);
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .time-slot-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .time-slot-card.available::before {
            background-color: var(--success);
        }
        
        .time-slot-card.booked::before {
            background-color: var(--danger);
        }
        
        .time-slot-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67,97,238,0.1);
        }
        
        .time-slot-time {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .time-slot-status {
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .status-available {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .status-booked {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .time-slot-action {
            margin-top: auto;
        }
        
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 1rem;
            transition: all 0.3s ease;
        }
        
        .empty-state:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(67,97,238,0.1);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        .empty-state:hover .empty-state-icon {
            transform: scale(1.1);
            opacity: 1;
        }
        
        .empty-state-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .empty-state-text {
            color: var(--text-muted);
            max-width: 300px;
            margin: 0 auto;
        }
        
        /* Appointments List */
        .appointment-card {
            padding: 1rem;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            gap: 1rem;
        }
        
        .appointment-card:last-child {
            border-bottom: none;
        }
        
        .appointment-icon {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .appointment-details {
            flex-grow: 1;
        }
        
        .appointment-time {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }
        
        .appointment-client {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .appointment-status {
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 1rem;
            display: inline-block;
        }
        
        .status-pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .status-approved {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .status-cancelled {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        /* Tips List */
        .tips-list {
            list-style: none;
        }
        
        .tips-list li {
            display: flex;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px dashed var(--border-light);
        }
        
        .tips-list li:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .tip-icon {
            color: var(--primary);
            margin-right: 0.75rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background-color: var(--white);
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 600px;
            max-height: 85vh;
            position: relative;
            animation: modalSlideUp 0.4s ease-out forwards;
            display: flex;
            flex-direction: column;
        }
        
        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.25rem;
            color: var(--text-muted);
        }
        
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
            max-height: calc(85vh - 140px); /* Subtract header and footer height */
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 2px solid var(--border-light);
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67,97,238,0.1);
            transform: translateY(-1px);
        }
        
        .date-picker {
            border: 2px solid var(--border-light);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: white;
            box-shadow: 0 4px 15px rgba(67,97,238,0.05);
            max-height: 400px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--bg-light);
        }
        
        .date-picker::-webkit-scrollbar {
            width: 8px;
        }
        
        .date-picker::-webkit-scrollbar-track {
            background: var(--bg-light);
            border-radius: 10px;
        }
        
        .date-picker::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 10px;
        }
        
        .date-picker-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }
        
        .date-picker-day {
            aspect-ratio: 1/1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .date-picker-day:hover {
            background-color: var(--primary-light);
            transform: scale(1.1);
        }
        
        .date-picker-day.selected {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            box-shadow: 0 4px 15px rgba(67,97,238,0.2);
        }
        
        .time-slot-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: flex-end;
        }
        
        .time-slot-form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .time-slot-action {
            padding-bottom: 0.625rem;
        }
        
        .selected-dates-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .selected-date-badge {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            font-weight: 500;
            gap: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67,97,238,0.1);
        }
        
        .selected-date-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67,97,238,0.2);
        }
        
        .remove-date-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .remove-date-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            border-left: 5px solid #2563eb;
            background: #eef2ff;
            color: #1e293b;
            box-shadow: 0 2px 8px rgba(67,97,238,0.08);
            font-size: 0.95rem;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        /* Utility Classes */
        .text-center {
            text-align: center;
        }
        
        .mb-0 {
            margin-bottom: 0 !important;
        }

        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }
        
        .align-items-center {
            align-items: center;
        }
        
        /* Filter Buttons */
        .filter-container {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .filter-btn {
            padding: 0.5rem 1.25rem;
            border-radius: 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid var(--border-light);
            background: white;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67,97,238,0.1);
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(67,97,238,0.2);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .calendar-grid {
                gap: 0.25rem;
            }
            
            .calendar-day {
                padding: 0.25rem;
            }
            
            .time-slot-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
            
            .time-slot-row {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .time-slot-action {
                padding-bottom: 0;
                width: 100%;
            }
            
            .time-slot-action button {
                width: 100%;
            }
            
            .date-btn-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .date-btn-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* 4 buttons per row */
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .date-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid #2563eb;
            border-radius: 12px;
            background: #fff;
            color: #2563eb;
            font-weight: 500;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, border 0.2s;
            min-width: 140px;
            text-align: center;
        }
        
        .date-btn.active,
        .date-btn:focus {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
            outline: none;
        }
        
        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .status-badge.approved {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .status-badge.cancelled {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .status-badge.pending {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 1rem 0;
        }
        
        .navbar-brand, .nav-link {
            color: white !important;
            transition: all 0.3s ease;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
        }
        
        .nav-link:hover {
            transform: translateY(-2px);
            color: rgba(255, 255, 255, 0.95) !important;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: white;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .nav-link:hover::after {
            width: 80%;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.15);
            border-radius: 1rem;
            padding: 0.75rem;
            animation: dropdownFade 0.3s ease-out;
        }
        
        .date-dropdown {
            max-height: 300px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--bg-light);
        }
        
        .date-dropdown::-webkit-scrollbar {
            width: 6px;
        }
        
        .date-dropdown::-webkit-scrollbar-track {
            background: var(--bg-light);
            border-radius: 10px;
        }
        
        .date-dropdown::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 10px;
        }
        
        @keyframes dropdownFade {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            padding: 0.8rem 1.2rem;
            border-radius: 0.7rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .dropdown-item:hover {
            background-color: var(--hover-bg);
            color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .dropdown-item i {
            color: var(--primary-color);
            transition: transform 0.3s ease;
        }
        
        .dropdown-item:hover i {
            transform: scale(1.2);
        }
    </style>
</head>
<body>
<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fa-solid fa-user-md me-2"></i>
            Consultant Availability
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fa-solid fa-gauge-high me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" href="messages.php">
                        <i class="fa-solid fa-envelope me-1"></i> Messages
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="consultantDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-user-circle me-1"></i> 
                        <?php echo htmlspecialchars($consultant_name); ?>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<div class="dashboard-container">
    <div class="header-section d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Manage Your Availability</h1>
            <p class="page-subtitle">Set your available time slots for client appointments</p>
        </div>
        <button class="btn btn-primary" id="openAddModal">
            <i class="fas fa-plus"></i> Add Availability
        </button>
    </div>
    
    <?php
    // Display success message if available
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">
                <i class="fas fa-check-circle"></i> ' . $_SESSION['success_message'] . '
              </div>';
        unset($_SESSION['success_message']);
    }

    // Display errors if available
    if (isset($_SESSION['availability_errors']) && is_array($_SESSION['availability_errors'])) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ($_SESSION['availability_errors'] as $error) {
            echo '<li>' . $error . '</li>';
        }
        echo '</ul></div>';
        unset($_SESSION['availability_errors']);
    }
    ?>
    
    <div class="dashboard-grid">
        <div class="main-content">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Availability Calendar</h2>
                    <div class="calendar-nav">
                        <button class="calendar-nav-btn" id="prevMonth">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="calendar-nav-btn" id="nextMonth">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="calendar-header">
                        <h3 class="calendar-title" id="currentMonth">May 2025</h3>
                    </div>
                    
                    <div class="calendar-grid" id="calendarDays">
                        <!-- Days of week headers -->
                        <div class="calendar-day-header">Sun</div>
                        <div class="calendar-day-header">Mon</div>
                        <div class="calendar-day-header">Tue</div>
                        <div class="calendar-day-header">Wed</div>
                        <div class="calendar-day-header">Thu</div>
                        <div class="calendar-day-header">Fri</div>
                        <div class="calendar-day-header">Sat</div>
                        
                        <!-- Calendar days will be generated by JavaScript -->
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h2 class="card-title">Available Time Slots</h2>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="dateNavigationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-calendar-day me-2"></i>Jump to Date
                                </button>
                                <ul class="dropdown-menu date-dropdown" aria-labelledby="dateNavigationDropdown" id="dateDropdownMenu">
                                    <?php 
                                    // Add dates with availability to dropdown
                                    foreach($dates_with_slots as $date) {
                                        $formatted_date = date('l, F j, Y', strtotime($date));
                                        echo "<li><a class='dropdown-item date-nav-item' href='#date-$date' data-date='$date'>$formatted_date</a></li>";
                                    }
                                    if (empty($dates_with_slots)) {
                                        echo "<li><span class='dropdown-item disabled'>No dates available</span></li>";
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex mt-3 justify-content-between align-items-center">
                        <div class="filter-container">
                            <button class="filter-btn active" data-filter="all">All</button>
                            <button class="filter-btn" data-filter="available">Available</button>
                            <button class="filter-btn" data-filter="booked">Booked</button>
                            <button class="filter-btn d-none" id="showAllDates">
                                <i class="fas fa-list me-1"></i>Show All Dates
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="time-slots-container" id="timeSlotsContainer">
                        <?php 
                        mysqli_data_seek($availability, 0);
                        if (mysqli_num_rows($availability) > 0): 
                            $current_date = '';
                            while ($slot = mysqli_fetch_assoc($availability)):
                                $date = $slot['slot_date'];
                                $formatted_date = date('l, F j, Y', strtotime($date));
                                
                                if ($current_date != $date): 
                                    if ($current_date != '') {
                                        echo '</div>'; // Close previous time-slot-grid
                                    }
                                    $current_date = $date;
                                    echo '<h3 class="date-header" id="date-' . $date . '">' . $formatted_date . '</h3>';
                                    echo '<div class="time-slot-grid">';
                                endif;
                                
                                $start_time = date('g:i A', strtotime($slot['slot_time']));
                                $duration = isset($slot['duration']) ? $slot['duration'] : 60;
                                $end_time = date('g:i A', strtotime($slot['slot_time']) + ($duration * 60));
                                $is_booked = $slot['is_booked'] == 1;
                                $status_class = $is_booked ? 'booked' : 'available';
                                $status_text = $is_booked ? 'Booked' : 'Available';
                                $status_badge_class = $is_booked ? 'status-booked' : 'status-available';
                        ?>
                            <div class="time-slot-card <?php echo $status_class; ?>" data-status="<?php echo $status_class; ?>">
                                <div class="time-slot-time">
                                    <?php echo $start_time . ' - ' . $end_time; ?>
                                </div>
                                <span class="time-slot-status <?php echo $status_badge_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                                <div class="time-slot-action">
                                    <?php if (!$is_booked): ?>
                                        <button class="btn btn-sm btn-primary delete-slot" data-id="<?php echo $slot['id']; ?>">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-primary" disabled>
                                            <i class="fas fa-lock"></i> Locked
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                            echo '</div>'; // Close last time-slot-grid
                        else: 
                        ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-calendar-times"></i>
                                </div>
                                <h3 class="empty-state-title">No availability set</h3>
                                <p class="empty-state-text">Add your available time slots using the button above.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="sidebar">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Upcoming Appointments</h2>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($appointments) > 0): ?>
                        <div class="appointments-container">
                        <?php while ($appointment = mysqli_fetch_assoc($appointments)): 
                            $appointment_date = date('l, F j', strtotime($appointment['slot_date']));
                            $start_time = strtotime($appointment['slot_time']);
                            $end_time = strtotime($appointment['slot_time'] . ' +1 hour');
                            $appointment_time = date('g:i A', $start_time) . ' - ' . date('g:i A', $end_time);
                            $status_class = '';
                            
                            switch($appointment['status']) {
                                case 'approved':
                                    $status_class = 'status-approved';
                                    break;
                                case 'cancelled':
                                    $status_class = 'status-cancelled';
                                    break;
                                default:
                                    $status_class = 'status-pending';
                            }
                        ?>
                            <div class="appointment-card">
                                <div class="appointment -icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="appointment-details">
                                    <div class="appointment-time"><?php echo $appointment_date; ?> at <?php echo $appointment_time; ?></div>
                                    <div class="appointment-client"><?php echo $appointment['client_name']; ?></div>
                                    <div>
                                        <span class="appointment-status <?php echo $status_class; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted"><?php echo $appointment['purpose']; ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h3 class="empty-state-title">No appointments</h3>
                            <p class="empty-state-text">Your upcoming appointments will appear here once clients book your available slots.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Quick Tips</h2>
                </div>
                <div class="card-body">
                    <ul class="tips-list">
                        <li>
                            <div class="tip-icon"><i class="fas fa-lightbulb"></i></div>
                            <div>Add multiple time slots at once to save time.</div>
                        </li>
                        <li>
                            <div class="tip-icon"><i class="fas fa-lightbulb"></i></div>
                            <div>You cannot delete time slots that are already booked.</div>
                        </li>
                        <li>
                            <div class="tip-icon"><i class="fas fa-lightbulb"></i></div>
                            <div>Regular availability patterns help clients find suitable slots more easily.</div>
                        </li>
                        <li>
                            <div class="tip-icon"><i class="fas fa-lightbulb"></i></div>
                            <div>Set your availability at least 2-3 weeks in advance to maximize bookings.</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Availability Modal -->
<div class="modal" id="addAvailabilityModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add Availability</h3>
            <button class="modal-close" id="closeAddModal">&times;</button>
        </div>
        <div class="modal-body">

        
        <form id="availabilityForm" action="../actions/set_availability.php" method="POST">

                <div class="form-group">
                    <label class="form-label">Select Dates</label>
                    <div class="date-picker" id="datePicker">
                        <div class="calendar-header">
                            <h3 class="calendar-title" id="datePickerMonth">May 2025</h3>
                            <div class="calendar-nav">
                                <button type="button" class="calendar-nav-btn" id="prevPickerMonth">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button type="button" class="calendar-nav-btn" id="nextPickerMonth">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                        <div class="date-picker-grid" id="datePickerGrid">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <div class="selected-dates-container" id="selectedDates">
                        <!-- Selected dates will appear here -->
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Duration</label>
                    <select class="form-control" name="duration" id="slotDuration">
                        <option value="30">30 minutes</option>
                        <option value="60" selected>1 hour</option>
                        <option value="90">1.5 hours</option>
                        <option value="120">2 hours</option>
                    </select>
                </div>
                
                <hr>
                <h4>Time Slots</h4>
                <p class="page-subtitle">Add start and end times for your availability on the selected dates</p>
                
                <div id="timeSlotsInput">
                    <div class="time-slot-row">
                        <div class="time-slot-form-group">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_times[]" class="form-control" required>
                        </div>
                        <div class="time-slot-form-group">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_times[]" class="form-control" required>
                        </div>
                        <div class="time-slot-action">
                            <button type="button" class="btn btn-primary remove-time-slot" disabled>
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-3">
                    <button type="button" id="addTimeSlot" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Add Another Time Slot
                    </button>
                </div>
                
                <input type="hidden" name="dates" id="selectedDatesInput">
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" id="cancelAddModal">Cancel</button>
            <button type="submit" form="availabilityForm" class="btn btn-primary">Save Availability</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteConfirmModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Deletion</h3>
            <button class="modal-close" id="closeDeleteModal">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this availability slot?</p>
            <p>This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" id="cancelDeleteModal">Cancel</button>
            <button type="button" class="btn btn-primary" id="confirmDelete">Delete</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentDate = new Date();
        let currentYear = currentDate.getFullYear();
        let currentMonth = currentDate.getMonth();
        let selectedDates = new Set();
        let slotToDelete = null;
        
        // Initialize calendar
        updateCalendar(currentMonth, currentYear);
        updateDatePickerCalendar(currentMonth, currentYear);
        
        // Initialize filter buttons
        initializeFilterButtons();
        
        // Initialize Show All Dates button
        const showAllButton = document.getElementById('showAllDates');
        showAllButton.addEventListener('click', showAllDatesFunction);
        
        // Date navigation dropdown
        document.querySelectorAll('.date-nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const dateId = this.getAttribute('href');
                const selectedDate = this.getAttribute('data-date');
                const targetElement = document.querySelector(dateId);
                
                if (targetElement) {
                    // Update dropdown button text to show selected date
                    const dropdownButton = document.getElementById('dateNavigationDropdown');
                    const formattedDate = this.textContent;
                    dropdownButton.innerHTML = `<i class="fas fa-calendar-day me-2"></i>${formattedDate}`;
                    
                    // Update filter buttons to show active state for 'All'
                    document.querySelectorAll('.filter-btn').forEach(btn => {
                        btn.classList.remove('active');
                        if (btn.getAttribute('data-filter') === 'all') {
                            btn.classList.add('active');
                        }
                    });
                    
                    // Hide all date sections and time slot grids
                    const allDateHeaders = document.querySelectorAll('.date-header');
                    allDateHeaders.forEach(header => {
                        header.style.display = 'none';
                        
                        // Find the next time-slot-grid and hide it
                        let nextElement = header.nextElementSibling;
                        if (nextElement && nextElement.classList.contains('time-slot-grid')) {
                            nextElement.style.display = 'none';
                        }
                    });
                    
                    // Show only the selected date section and its time slot grid
                    targetElement.style.display = 'block';
                    let nextElement = targetElement.nextElementSibling;
                    if (nextElement && nextElement.classList.contains('time-slot-grid')) {
                        nextElement.style.display = 'grid';
                    }
                    
                    // Add scrollbar to time slots container
                    const timeSlotsContainer = document.getElementById('timeSlotsContainer');
                    timeSlotsContainer.classList.add('date-filtered');
                    
                    // Scroll to the selected date
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Highlight the date header temporarily
                    targetElement.classList.add('highlight-date');
                    setTimeout(() => {
                        targetElement.classList.remove('highlight-date');
                    }, 2000);
                    
                    // Show the 'Show All Dates' button
                    showAllButton.classList.remove('d-none');
                    
                    // Apply the current filter
                    applyCurrentFilter();
                }
            });
        });
        
        // Function to initialize filter buttons
        function initializeFilterButtons() {
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all filter buttons
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Apply the filter
                    applyCurrentFilter();
                });
            });
        }
        
        // Function to apply the current filter
        function applyCurrentFilter() {
            const activeFilter = document.querySelector('.filter-btn.active');
            if (!activeFilter) return;
            
            const filter = activeFilter.getAttribute('data-filter');
            const allTimeSlots = document.querySelectorAll('.time-slot-card');
            
            allTimeSlots.forEach(slot => {
                if (filter === 'all') {
                    slot.style.display = 'block';
                } else if (filter === 'available' && slot.classList.contains('available')) {
                    slot.style.display = 'block';
                } else if (filter === 'booked' && slot.classList.contains('booked')) {
                    slot.style.display = 'block';
                } else {
                    slot.style.display = 'none';
                }
            });
        }
        
        // Function to show all dates
        function showAllDatesFunction() {
            // Show all date headers and time slot grids
            const allDateHeaders = document.querySelectorAll('.date-header');
            allDateHeaders.forEach(header => {
                header.style.display = 'block';
                
                // Find the next time-slot-grid and show it
                let nextElement = header.nextElementSibling;
                if (nextElement && nextElement.classList.contains('time-slot-grid')) {
                    nextElement.style.display = 'grid';
                }
            });
            
            // Reset dropdown button text
            const dropdownButton = document.getElementById('dateNavigationDropdown');
            dropdownButton.innerHTML = '<i class="fas fa-calendar-day me-2"></i>Jump to Date';
            
            // Remove scrollbar from time slots container
            const timeSlotsContainer = document.getElementById('timeSlotsContainer');
            timeSlotsContainer.classList.remove('date-filtered');
            
            // Hide the Show All Dates button
            this.classList.add('d-none');
            
            // Apply the current filter
            applyCurrentFilter();
        }
        
        // Modal controls
        const addAvailabilityModal = document.getElementById('addAvailabilityModal');
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');
        
        document.getElementById('openAddModal').addEventListener('click', function() {
            addAvailabilityModal.classList.add('show');
        });
        
        document.getElementById('closeAddModal').addEventListener('click', function() {
            addAvailabilityModal.classList.remove('show');
        });
        
        document.getElementById('cancelAddModal').addEventListener('click', function() {
            addAvailabilityModal.classList.remove('show');
        });
        
        document.getElementById('closeDeleteModal').addEventListener('click', function() {
            deleteConfirmModal.classList.remove('show');
        });
        
        document.getElementById('cancelDeleteModal').addEventListener('click', function() {
            deleteConfirmModal.classList.remove('show');
        });
        
        // Calendar navigation
        document.getElementById('prevMonth').addEventListener('click', function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            updateCalendar(currentMonth, currentYear);
        });
        
        document.getElementById('nextMonth').addEventListener('click', function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            updateCalendar(currentMonth, currentYear);
        });
        
        // Date picker calendar navigation
        document.getElementById('prevPickerMonth').addEventListener('click', function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            updateDatePickerCalendar(currentMonth, currentYear);
        });
        
        document.getElementById('nextPickerMonth').addEventListener('click', function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            updateDatePickerCalendar(currentMonth, currentYear);
        });
        
        // Filter time slots by status
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Update active state
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Check if we're in filtered date mode
                const showAllButton = document.getElementById('showAllDates');
                const isDateFiltered = showAllButton !== null;
                
                if (isDateFiltered) {
                    // Get the visible date header
                    const visibleHeader = document.querySelector('.date-header[style="display: block;"]');
                    if (visibleHeader) {
                        // Get the time slots grid for this date
                        const timeSlotGrid = visibleHeader.nextElementSibling;
                        if (timeSlotGrid && timeSlotGrid.classList.contains('time-slot-grid')) {
                            // Filter only the time slots for this date
                            const timeSlots = timeSlotGrid.querySelectorAll('.time-slot-card');
                            timeSlots.forEach(slot => {
                                if (filter === 'all' || slot.getAttribute('data-status') === filter) {
                                    slot.style.display = 'block';
                                } else {
                                    slot.style.display = 'none';
                                }
                            });
                        }
                    }
                } else {
                    // Filter all time slots
                    const timeSlots = document.querySelectorAll('.time-slot-card');
                    timeSlots.forEach(slot => {
                        if (filter === 'all' || slot.getAttribute('data-status') === filter) {
                            slot.style.display = 'block';
                        } else {
                            slot.style.display = 'none';
                        }
                    });
                }
            });
        });
        
        // Delete time slot
        const deleteButtons = document.querySelectorAll('.delete-slot');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                slotToDelete = this.getAttribute('data-id');
                deleteConfirmModal.classList.add('show');
            });
        });
        
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (slotToDelete) {
                window.location.href = '../actions/set_availability.php?delete=' + slotToDelete;
            }

            deleteConfirmModal.classList.remove('show');
        });
        
        // Add time slot in modal
        document.getElementById('addTimeSlot').addEventListener('click', function() {
            const timeSlotsContainer = document.getElementById('timeSlotsInput');
            const timeSlotTemplate = timeSlotsContainer.querySelector('.time-slot-row').cloneNode(true);
            
            // Clear inputs
            timeSlotTemplate.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
            
            // Enable remove button
            const removeButton = timeSlotTemplate.querySelector('.remove-time-slot');
            removeButton.disabled = false;
            removeButton.addEventListener('click', function() {
                timeSlotsContainer.removeChild(timeSlotTemplate);
                
                // If only one time slot remains, disable its remove button
                if (timeSlotsContainer.querySelectorAll('.time-slot-row').length === 1) {
                    timeSlotsContainer.querySelector('.remove-time-slot').disabled = true;
                }
            });
            
            timeSlotsContainer.appendChild(timeSlotTemplate);
            
            // Enable all remove buttons when there are multiple slots
            if (timeSlotsContainer.querySelectorAll('.time-slot-row').length > 1) {
                timeSlotsContainer.querySelectorAll('.remove-time-slot').forEach(btn => {
                    btn.disabled = false;
                });
            }
        });
        
        // Calendar functions
        function updateCalendar(month, year) {
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;
            
            const calendarDays = document.getElementById('calendarDays');
            
            // Clear existing days except headers
            while (calendarDays.childElementCount > 7) {
                calendarDays.removeChild(calendarDays.lastChild);
            }
            
            // Get first day of month and total days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            // Previous month days
            const prevMonthDays = new Date(year, month, 0).getDate();
            for (let i = 0; i < firstDay; i++) {
                const day = document.createElement('div');
                day.className = 'calendar-day other-month';
                day.innerHTML = `<div class="calendar-day-number">${prevMonthDays - firstDay + i + 1}</div>`;
                calendarDays.appendChild(day);
            }
            
            // Current month days
            for (let i = 1; i <= daysInMonth; i++) {
                const day = document.createElement('div');
                day.className = 'calendar-day';
                
                // Check if it's today
                const today = new Date();
                if (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                    day.classList.add('today');
                }
                
                // Format date for comparison
                const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                
                // Check if this date has available or booked slots
                const hasSlots = <?php echo json_encode($dates_with_slots); ?>.includes(dateString);
                
                let dayHtml = `<div class="calendar-day-number">${i}</div>`;
                
                if (hasSlots) {
                    dayHtml += `<div class="calendar-day-indicator">
                                    <span class="indicator-available"></span>
                                </div>`;
                }
                
                day.innerHTML = dayHtml;
                calendarDays.appendChild(day);
            }
            
            // Next month days to complete the grid
            const totalCells = 42; // 6 rows * 7 days
            const remainingCells = totalCells - (firstDay + daysInMonth);
            
            for (let i = 1; i <= remainingCells; i++) {
                const day = document.createElement('div');
                day.className = 'calendar-day other-month';
                day.innerHTML = `<div class="calendar-day-number">${i}</div>`;
                calendarDays.appendChild(day);
            }
        }
        
        function updateDatePickerCalendar(month, year) {
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            document.getElementById('datePickerMonth').textContent = `${monthNames[month]} ${year}`;
            
            const datePickerGrid = document.getElementById('datePickerGrid');
            datePickerGrid.innerHTML = '';
            
            // Add day headers
            const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            daysOfWeek.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'calendar-day-header';
                dayHeader.textContent = day;
                datePickerGrid.appendChild(dayHeader);
            });
            
            // Get first day of month and total days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            // Previous month days
            const prevMonthDays = new Date(year, month, 0).getDate();
            for (let i = 0; i < firstDay; i++) {
                const day = document.createElement('div');
                day.className = 'date-picker-day other-month';
                day.textContent = prevMonthDays - firstDay + i + 1;
                datePickerGrid.appendChild(day);
            }
            
            // Current month days
            const today = new Date();
            const todayString = today.toISOString().split('T')[0];
            
            for (let i = 1; i <= daysInMonth; i++) {
                const day = document.createElement('div');
                day.className = 'date-picker-day';
                
                // Format date string
                const dateObj = new Date(year, month, i);
                const dateString = [
                    dateObj.getFullYear(),
                    String(dateObj.getMonth() + 1).padStart(2, '0'),
                    String(dateObj.getDate()).padStart(2, '0')
                ].join('-');
                
                // Disable past dates
                if (dateObj < today && dateString !== todayString) {
                    day.classList.add('disabled');
                } else {
                    day.setAttribute('data-date', dateString);
                    
                    // Check if this date is already selected
                    if (selectedDates.has(dateString)) {
                        day.classList.add('selected');
                    }
                    
                    day.addEventListener('click', function() {
                        const date = this.getAttribute('data-date');
                        
                        if (selectedDates.has(date)) {
                            selectedDates.delete(date);
                            this.classList.remove('selected');
                        } else {
                            selectedDates.add(date);
                            this.classList.add('selected');
                        }
                        
                        updateSelectedDatesDisplay();
                    });
                }
                
                day.textContent = i;
                datePickerGrid.appendChild(day);
            }
            
            // Next month days
            const totalCells = 42; // 6 rows * 7 days
            const remainingCells = totalCells - (firstDay + daysInMonth);
            
            for (let i = 1; i <= remainingCells; i++) {
                const day = document.createElement('div');
                day.className = 'date-picker-day other-month';
                day.textContent = i;
                datePickerGrid.appendChild(day);
            }
        }
        
        function updateSelectedDatesDisplay() {
            const selectedDatesContainer = document.getElementById('selectedDates');
            const selectedDatesInput = document.getElementById('selectedDatesInput');
            
            selectedDatesContainer.innerHTML = '';
            
            if (selectedDates.size > 0) {
                const datesArray = Array.from(selectedDates);
                selectedDatesInput.value = datesArray.join(',');
                
                datesArray.forEach(date => {
                    const formattedDate = new Date(date).toLocaleDateString('en-US', {
                        weekday: 'short',
                        month: 'short',
                        day: 'numeric'
                    });
                    
                    const badge = document.createElement('div');
                    badge.className = 'selected-date-badge';
                    badge.innerHTML = `${formattedDate} <button type="button" class="remove-date-btn" data-date="${date}"><i class="fas fa-times"></i></button>`;
                    
                    selectedDatesContainer.appendChild(badge);
                    
                    badge.querySelector('.remove-date-btn').addEventListener('click', function() {
                        const dateToRemove = this.getAttribute('data-date');
                        selectedDates.delete(dateToRemove);
                        
                        // Also update the visual state in the calendar
                        const calendarDay = document.querySelector(`.date-picker-day[data-date="${dateToRemove}"]`);
                        if (calendarDay) {
                            calendarDay.classList.remove('selected');
                        }
                        
                        updateSelectedDatesDisplay();
                    });
                });
            } else {
                selectedDatesInput.value = '';
            }
        }
    });
</script>
</body>
</html>