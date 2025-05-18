<?php
// Include database connection
require_once __DIR__ . '/../config/db.php';

// Email addresses to reset (add more if needed)
$emails = [
    'alejandro.santos28@gmail.com' => 'santos2025',
    // Add other emails and passwords here if needed
];

// Counter for successful updates
$successCount = 0;

echo "<h2>Password Reset Tool</h2>";
echo "<p>Attempting to fix consultant account passwords...</p>";

foreach ($emails as $email => $password) {
    // Hash the password using PHP's password_hash
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Update the password for this email in the users table
    $sql = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p style='color: green;'>✓ Password reset successful for: $email</p>";
        $successCount++;
    } else {
        echo "<p style='color: red;'>✗ Error resetting password for $email: " . mysqli_error($conn) . "</p>";
    }
    
    mysqli_stmt_close($stmt);
}

echo "<p><strong>Summary:</strong> Successfully reset $successCount passwords.</p>";
echo "<p>You can now try to log in using the updated password.</p>";
echo "<p><a href='../user/login.php'>Go to Login Page</a></p>";
?>