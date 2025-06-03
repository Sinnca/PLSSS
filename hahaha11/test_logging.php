<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define log file path
$logFile = __DIR__ . '/logs/test_debug.log';
$logDir = __DIR__ . '/logs';

// Create logs directory if it doesn't exist
if (!file_exists($logDir)) {
    if (!mkdir($logDir, 0777, true)) {
        die("<h1>Error: Failed to create logs directory</h1>");
    }
}

// Test writing to log file
$testMessage = "Testing log file write at " . date('Y-m-d H:i:s') . "\n";
$writeResult = file_put_contents($logFile, $testMessage, FILE_APPEND);

// Output results
echo "<h1>Logging Test</h1>";
echo "<p>Log file path: " . htmlspecialchars($logFile) . "</p>";

echo "<h2>Test Results:</h2>";
echo "<p>Directory exists: " . (file_exists($logDir) ? 'Yes' : 'No') . "</p>";
echo "<p>Directory writable: " . (is_writable($logDir) ? 'Yes' : 'No') . "</p>";
echo "<p>Log file exists: " . (file_exists($logFile) ? 'Yes' : 'No') . "</p>";
echo "<p>Log file writable: " . (is_writable($logFile) ? 'Yes' : 'No') . "</p>";
echo "<p>Write test: " . ($writeResult !== false ? 'Success' : 'Failed') . "</p>";

echo "<h2>Log file contents:</h2>";
echo "<pre>";
if (file_exists($logFile)) {
    echo htmlspecialchars(file_get_contents($logFile));
} else {
    echo "Log file not found or not readable.";
}
echo "</pre>";

// Try to write to Apache error log as well
error_log("Test message to Apache error log at " . date('Y-m-d H:i:s'));
?>