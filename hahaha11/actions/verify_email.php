<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!$conn) {
    die('DB connection failed.');
}

// Check if token exists
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $_SESSION['verification_error'] = "Invalid verification link.";
    header("Location: /hahaha11/user/login.php");
    exit;
}

$token = mysqli_real_escape_string($conn, $_GET['token']);

// Find user with this token
$sql = "SELECT id, name, email, verification_token_expires_at FROM users 
        WHERE verification_token = ? AND is_verified = 0";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    // Check if token is expired
    $now = new DateTime();
    $expiresAt = new DateTime($user['verification_token_expires_at']);
    
    if ($now > $expiresAt) {
        $_SESSION['verification_error'] = "Verification link has expired. Please request a new one.";
        header("Location: /hahaha11/user/login.php");
        exit;
    }
    
    // Mark user as verified
    $updateSql = "UPDATE users SET is_verified = 1, verification_token = NULL, 
                  verification_token_expires_at = NULL 
                  WHERE id = ?";
    
    $updateStmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($updateStmt, "i", $user['id']);
    
    if (mysqli_stmt_execute($updateStmt)) {
        $_SESSION['verification_success'] = "Your email has been verified successfully! You can now log in.";
    } else {
        $_SESSION['verification_error'] = "Failed to verify email. Please try again.";
    }
    
    mysqli_stmt_close($updateStmt);
} else {
    $_SESSION['verification_error'] = "Invalid or expired verification link.";
}

mysqli_stmt_close($stmt);
header("Location: /hahaha11/user/login.php");
exit;
?>
