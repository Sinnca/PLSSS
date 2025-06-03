<?php
// Include database connection
require_once __DIR__ . '/../config/db.php';

// The email to debug
$email = 'alejandro.santos28@gmail.com';

echo "<h2>Account Debug Tool</h2>";

// Get user account details
$sql = "SELECT id, name, email, role, password FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo "<h3>Account Found:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Password Hash (first 20 chars)</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['password'], 0, 20)) . "...</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Test if this password would work
    echo "<h3>Password Test:</h3>";
    
    // Reset the result pointer
    mysqli_data_seek($result, 0);
    $user = mysqli_fetch_assoc($result);
    
    $test_password = 'santos2025';
    $password_match = password_verify($test_password, $user['password']);
    
    echo "<p>Testing if password '<strong>$test_password</strong>' matches the stored hash:</p>";
    if ($password_match) {
        echo "<p style='color: green;'>✓ Password is correct! This should work for login.</p>";
    } else {
        echo "<p style='color: red;'>✗ Password does not match! The stored hash is invalid or the password was not updated correctly.</p>";
        
        // Get info about the hash
        $hash_info = password_get_info($user['password']);
        echo "<p>Hash algorithm: " . ($hash_info['algo'] ?: "Not a valid PHP hash") . "</p>";
        
        // Fix it
        echo "<h3>Fix Password:</h3>";
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $new_hash, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<p style='color: green;'>✓ Password has been properly updated with a valid hash!</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to update password: " . mysqli_error($conn) . "</p>";
        }
    }
} else {
    echo "<p style='color: red;'>No account found with email: $email</p>";
}

echo "<p><a href='../user/login.php'>Go to Login Page</a></p>";
?>
