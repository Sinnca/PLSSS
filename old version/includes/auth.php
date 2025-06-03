<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set your project base path here
$base_path = '/hahaha11';

// Function to check if user is logged in
function checkUserLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /hahaha11/user/login.php?error=Please login to access this page");
        exit;
    }
}

// Function to check if user is admin
function checkAdminLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: /hahaha11/user/login.php?error=Please login as admin to access this page");
        exit;
    }
}

// Function to check if user is consultant
function checkConsultantLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'consultant') {
        header("Location: /hahaha11/user/login.php?error=Please login as consultant to access this page");
        exit;
    }
}

// Function to check if user is regular user
function checkRegularUserLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
        header("Location: /hahaha11/user/login.php?error=Please login as user to access this page");
        exit;
    }
}

// Function to get user role
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Function to check if user has specific role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current user name
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? null;
}

// Function to get current user email
function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

// Function to get consultant ID if user is consultant
function getConsultantId() {
    return $_SESSION['consultant_id'] ?? null;
}
?>
