<?php
/**
 * Debug Script - Check System Status
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>System Debug Report</h1>";

// Check PHP version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Required: 7.4+<br>";
echo "Status: " . (version_compare(phpversion(), '7.4.0', '>=') ? '✓ OK' : '✗ FAIL') . "<br><br>";

// Check required extensions
echo "<h2>2. PHP Extensions</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'curl', 'mbstring'];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "$ext: " . ($loaded ? '✓ Loaded' : '✗ Not Loaded') . "<br>";
}
echo "<br>";

// Check file structure
echo "<h2>3. File Structure</h2>";
$required_files = [
    'Database/Database.php',
    'Models/Student.php',
    'Models/Payment.php',
    'PaymentHandler.php',
    'resources/config.json',
    'resources/db_config.json'
];

foreach ($required_files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "$file: " . ($exists ? '✓ Exists' : '✗ Missing') . "<br>";
}
echo "<br>";

// Check database config
echo "<h2>4. Database Configuration</h2>";
$db_config_file = __DIR__ . '/resources/db_config.json';
if (file_exists($db_config_file)) {
    $db_config = json_decode(file_get_contents($db_config_file), true);
    echo "Config file: ✓ Found<br>";
    echo "DB_HOST: " . ($db_config['DB_HOST'] ?? 'Not set') . "<br>";
    echo "DB_NAME: " . ($db_config['DB_NAME'] ?? 'Not set') . "<br>";
    echo "DB_USERNAME: " . ($db_config['DB_USERNAME'] ?? 'Not set') . "<br>";
    
    // Try database connection
    echo "<br><strong>Testing Database Connection...</strong><br>";
    try {
        $dsn = "mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_config['DB_USERNAME'], $db_config['DB_PASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "Database Connection: ✓ Success<br>";
        
        // Check tables
        echo "<br><strong>Checking Tables...</strong><br>";
        $tables = ['students', 'payments', 'payment_sessions', 'refunds', 'payment_logs'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
                $count = $stmt->fetchColumn();
                echo "$table: ✓ Exists ($count rows)<br>";
            } catch (PDOException $e) {
                echo "$table: ✗ " . $e->getMessage() . "<br>";
            }
        }
    } catch (PDOException $e) {
        echo "Database Connection: ✗ Failed<br>";
        echo "Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Config file: ✗ Not found<br>";
}
echo "<br>";

// Check payment gateway config
echo "<h2>5. Payment Gateway Configuration</h2>";
$pg_config_file = __DIR__ . '/resources/config.json';
if (file_exists($pg_config_file)) {
    $pg_config = json_decode(file_get_contents($pg_config_file), true);
    echo "Config file: ✓ Found<br>";
    echo "MERCHANT_ID: " . (isset($pg_config['MERCHANT_ID']) && !empty($pg_config['MERCHANT_ID']) ? '✓ Set' : '✗ Not set') . "<br>";
    echo "API_KEY: " . (isset($pg_config['API_KEY']) && !empty($pg_config['API_KEY']) ? '✓ Set' : '✗ Not set') . "<br>";
    echo "BASE_URL: " . ($pg_config['BASE_URL'] ?? 'Not set') . "<br>";
} else {
    echo "Config file: ✗ Not found<br>";
}
echo "<br>";

// Test autoloader
echo "<h2>6. Testing Autoloader</h2>";
spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . '/' . $classPath . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});

$test_classes = [
    'Database\Database',
    'Models\Student',
    'Models\Payment',
    'Services\PaymentService'
];

foreach ($test_classes as $class) {
    try {
        if (class_exists($class)) {
            echo "$class: ✓ Can load<br>";
        } else {
            echo "$class: ✗ Cannot load<br>";
        }
    } catch (Exception $e) {
        echo "$class: ✗ Error - " . $e->getMessage() . "<br>";
    }
}
echo "<br>";

// Test student page
echo "<h2>7. Testing Student Dashboard</h2>";
try {
    // Simple test
    if (class_exists('Database\Database') && class_exists('Models\Student')) {
        echo "Classes loaded: ✓ OK<br>";
        
        // Try to get Database instance
        $db = Database\Database::getInstance();
        echo "Database instance: ✓ OK<br>";
        
        // Try to get a student
        $studentModel = new Models\Student();
        echo "Student model: ✓ OK<br>";
        
    } else {
        echo "Classes not loaded: ✗ FAIL<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
echo "<br>";

// Check permissions
echo "<h2>8. Directory Permissions</h2>";
$dirs = ['logs', 'resources'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path);
        echo "$dir: Permissions $perms " . ($writable ? '✓ Writable' : '✗ Not writable') . "<br>";
    } else {
        echo "$dir: ✗ Does not exist<br>";
    }
}

echo "<br><hr>";
echo "<h2>Summary</h2>";
echo "If all checks pass above, the system should work correctly.<br>";
echo "If there are errors, please address them before proceeding.<br><br>";
echo "<a href='index.html'>Go to Home</a> | ";
echo "<a href='student-dashboard.php?student_id=STU2025001'>Test Dashboard</a>";