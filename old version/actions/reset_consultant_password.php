<?php
// Run this script ONCE to reset the consultant password for alejandro.santos28@gmail.com
require_once __DIR__ . '/../config/db.php';

$email = 'alejandro.santos28@gmail.com';
$newPassword = 'santos2025';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$sql = "UPDATE users SET password = '$hashedPassword' WHERE email = '$email' AND role = 'consultant'";

if (mysqli_query($conn, $sql)) {
    echo "Password reset successful for $email (consultant).";
} else {
    echo "Error updating password: " . mysqli_error($conn);
}
?>
