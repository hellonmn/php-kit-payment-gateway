<?php
/**
 * Simple Database Connection Test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Database Connection Test</h1>
    
    <?php
    // Check if config file exists
    $config_file = __DIR__ . '/resources/db_config.json';
    
    if (!file_exists($config_file)) {
        echo "<p class='error'>❌ Configuration file not found: resources/db_config.json</p>";
        echo "<div class='info'>";
        echo "<strong>Please create the file with this content:</strong>";
        echo "<pre>" . htmlspecialchars('{
    "DB_HOST": "localhost",
    "DB_NAME": "payment_system",
    "DB_USERNAME": "your_username",
    "DB_PASSWORD": "your_password",
    "DB_CHARSET": "utf8mb4"
}') . "</pre>";
        echo "</div>";
        exit;
    }
    
    // Load configuration
    $config_content = file_get_contents($config_file);
    $config = json_decode($config_content, true);
    
    if (!$config) {
        echo "<p class='error'>❌ Invalid JSON in configuration file</p>";
        echo "<p>File content:</p>";
        echo "<pre>" . htmlspecialchars($config_content) . "</pre>";
        exit;
    }
    
    echo "<p class='success'>✅ Configuration file loaded</p>";
    
    // Display configuration (without password)
    echo "<div class='info'>";
    echo "<strong>Database Configuration:</strong><br>";
    echo "Host: " . ($config['DB_HOST'] ?? 'NOT SET') . "<br>";
    echo "Database: " . ($config['DB_NAME'] ?? 'NOT SET') . "<br>";
    echo "Username: " . ($config['DB_USERNAME'] ?? 'NOT SET') . "<br>";
    echo "Password: " . (isset($config['DB_PASSWORD']) && !empty($config['DB_PASSWORD']) ? '***SET***' : 'NOT SET') . "<br>";
    echo "</div>";
    
    // Check required fields
    $required = ['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD'];
    $missing = [];
    foreach ($required as $field) {
        if (!isset($config[$field]) || empty($config[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        echo "<p class='error'>❌ Missing required fields: " . implode(', ', $missing) . "</p>";
        exit;
    }
    
    // Try to connect
    echo "<h2>Testing Database Connection...</h2>";
    
    try {
        $dsn = "mysql:host={$config['DB_HOST']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['DB_USERNAME'], $config['DB_PASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        echo "<p class='success'>✅ Connected to MySQL server</p>";
        
        // Check if database exists
        $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['DB_NAME']}'");
        $db_exists = $stmt->rowCount() > 0;
        
        if ($db_exists) {
            echo "<p class='success'>✅ Database '{$config['DB_NAME']}' exists</p>";
            
            // Select database
            $pdo->exec("USE `{$config['DB_NAME']}`");
            
            // Check tables
            echo "<h3>Checking Tables...</h3>";
            $required_tables = ['students', 'payments', 'payment_sessions', 'refunds', 'payment_logs'];
            $stmt = $pdo->query("SHOW TABLES");
            $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $all_exist = true;
            foreach ($required_tables as $table) {
                if (in_array($table, $existing_tables)) {
                    // Get row count
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                    $count = $stmt->fetch()['count'];
                    echo "<p class='success'>✅ Table '$table' exists ($count rows)</p>";
                } else {
                    echo "<p class='error'>❌ Table '$table' is missing</p>";
                    $all_exist = false;
                }
            }
            
            if (!$all_exist) {
                echo "<div class='info'>";
                echo "<strong>To create missing tables, run:</strong><br>";
                echo "<pre>mysql -u {$config['DB_USERNAME']} -p {$config['DB_NAME']} &lt; database/schema.sql</pre>";
                echo "</div>";
            } else {
                echo "<h2 class='success'>🎉 All checks passed! System is ready.</h2>";
                echo "<div class='info'>";
                echo "<strong>Next steps:</strong><br>";
                echo "1. <a href='demo.php'>Run demo script</a> to create test students<br>";
                echo "2. <a href='index.html'>Visit home page</a><br>";
                echo "3. <a href='student-dashboard.php?student_id=STU2025001'>Test dashboard</a><br>";
                echo "</div>";
            }
            
        } else {
            echo "<p class='error'>❌ Database '{$config['DB_NAME']}' does not exist</p>";
            echo "<div class='info'>";
            echo "<strong>To create the database, run:</strong><br>";
            echo "<pre>mysql -u {$config['DB_USERNAME']} -p -e \"CREATE DATABASE {$config['DB_NAME']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"</pre>";
            echo "<strong>Then import the schema:</strong><br>";
            echo "<pre>mysql -u {$config['DB_USERNAME']} -p {$config['DB_NAME']} &lt; database/schema.sql</pre>";
            echo "</div>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Database connection failed</p>";
        echo "<div class='info'>";
        echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>";
        echo "<strong>Common solutions:</strong><br>";
        echo "1. Check that MySQL is running<br>";
        echo "2. Verify username and password are correct<br>";
        echo "3. Ensure the user has permission to connect<br>";
        echo "4. Check if the host is correct (usually 'localhost')<br>";
        echo "</div>";
    }
    ?>
    
    <hr>
    <p><a href="test.php">← Back to System Test</a> | <a href="index.html">Home</a></p>
</body>
</html>