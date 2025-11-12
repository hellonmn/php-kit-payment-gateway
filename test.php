<?php
/**
 * Simple Test Page
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test</title></head><body>";
echo "<h1>Testing System Components</h1>";

// Test 1: Basic PHP
echo "<h2>Test 1: PHP is working</h2>";
echo "PHP Version: " . phpversion() . " ✓<br><br>";

// Test 2: File system
echo "<h2>Test 2: File System</h2>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Files in current directory:<br><ul>";
$files = scandir(__DIR__);
foreach (array_slice($files, 0, 10) as $file) {
    echo "<li>$file</li>";
}
echo "</ul><br>";

// Test 3: Check if Database.php exists
echo "<h2>Test 3: Check Database Class</h2>";
$db_file = __DIR__ . '/Database/Database.php';
if (file_exists($db_file)) {
    echo "Database.php exists ✓<br>";
    echo "Path: $db_file<br>";
    
    // Try to include it
    try {
        require_once $db_file;
        echo "Database.php included ✓<br>";
        
        // Check if class exists
        if (class_exists('Database\Database')) {
            echo "Database\\Database class exists ✓<br>";
        } else {
            echo "Database\\Database class NOT found ✗<br>";
        }
    } catch (Exception $e) {
        echo "Error including Database.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Database.php NOT found ✗<br>";
}
echo "<br>";

// Test 4: Check config files
echo "<h2>Test 4: Configuration Files</h2>";
$config_files = [
    'resources/db_config.json',
    'resources/config.json'
];

foreach ($config_files as $config_file) {
    $full_path = __DIR__ . '/' . $config_file;
    if (file_exists($full_path)) {
        echo "$config_file exists ✓<br>";
        $content = file_get_contents($full_path);
        $json = json_decode($content, true);
        if ($json) {
            echo "Valid JSON ✓<br>";
            echo "Keys: " . implode(', ', array_keys($json)) . "<br>";
        } else {
            echo "Invalid JSON ✗<br>";
        }
    } else {
        echo "$config_file NOT found ✗<br>";
    }
    echo "<br>";
}

// Test 5: Database connection
echo "<h2>Test 5: Database Connection</h2>";
$db_config_path = __DIR__ . '/resources/db_config.json';
if (file_exists($db_config_path)) {
    $db_config = json_decode(file_get_contents($db_config_path), true);
    
    echo "Host: " . $db_config['DB_HOST'] . "<br>";
    echo "Database: " . $db_config['DB_NAME'] . "<br>";
    echo "Username: " . $db_config['DB_USERNAME'] . "<br>";
    
    try {
        $dsn = "mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_config['DB_USERNAME'], $db_config['DB_PASSWORD']);
        echo "<strong style='color: green;'>Database connection successful ✓</strong><br>";
        
        // Try to query students table
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Students table has {$result['count']} records<br>";
        
    } catch (PDOException $e) {
        echo "<strong style='color: red;'>Database connection failed ✗</strong><br>";
        echo "Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Config file not found<br>";
}

echo "<br>";
echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ul>";
echo "<li><a href='debug.php'>Run Full Debug</a></li>";
echo "<li><a href='index.html'>Go to Home</a></li>";
echo "<li><a href='student-dashboard.php?student_id=STU2025001'>Try Dashboard</a></li>";
echo "</ul>";

echo "</body></html>";