<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include authentication functions
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultant Booking System</title>
</head>
<body>
    <header>
        <nav>
            <!-- Navigation links will go here -->
            <!-- You'll implement the HTML/CSS -->
        </nav>
    </header>
    <main>