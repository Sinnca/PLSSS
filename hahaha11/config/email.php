<?php
// SMTP Configuration
// Note: Update these values with your actual SMTP server details

define('SMTP_HOST', 'smtp.example.com'); // Your SMTP server
// define('SMTP_HOST', 'smtp.gmail.com'); // Example for Gmail
define('SMTP_PORT', 587); // Port for TLS/STARTTLS
define('SMTP_USERNAME', 'your-email@example.com'); // Your SMTP username (email)
define('SMTP_PASSWORD', 'your-smtp-password'); // Your SMTP password or app password
define('SMTP_FROM_EMAIL', 'noreply@example.com'); // Sender email address
define('SMTP_FROM_NAME', 'Consultation System'); // Sender name

// Email settings
define('MAIL_ENABLED', true); // Set to false to disable all email sending

// Debug mode (for development)
define('MAIL_DEBUG', 0); // 0 = off, 1 = client messages, 2 = client and server messages
