<?php
/**
 * Demo Script - Create Test Students
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Models/Student.php';

use Database\Database;
use Models\Student;
use Exception;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Demo - Create Test Students</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <h1>🎓 Demo - Create Test Students</h1>
    
    <?php
    try {
        // Test database connection
        echo "<p class='success'>✅ Connecting to database...</p>";
        $db = Database::getInstance();
        $studentModel = new Student();
        echo "<p class='success'>✅ Database connected successfully</p>";
        
        // Test students data
        $testStudents = [
            [
                'student_id' => 'STU2025001',
                'name' => 'Rahul Sharma',
                'email' => 'rahul.sharma@example.com',
                'phone' => '9876543210',
                'course' => 'Computer Science Engineering',
                'total_fees' => 150000.00
            ],
            [
                'student_id' => 'STU2025002',
                'name' => 'Priya Patel',
                'email' => 'priya.patel@example.com',
                'phone' => '9876543211',
                'course' => 'Business Administration',
                'total_fees' => 120000.00
            ],
            [
                'student_id' => 'STU2025003',
                'name' => 'Amit Kumar',
                'email' => 'amit.kumar@example.com',
                'phone' => '9876543212',
                'course' => 'Mechanical Engineering',
                'total_fees' => 140000.00
            ]
        ];
        
        echo "<h2>Creating Test Students...</h2>";
        
        $created = 0;
        $existing = 0;
        
        foreach ($testStudents as $studentData) {
            try {
                $existingStudent = $studentModel->getByStudentId($studentData['student_id']);
                if ($existingStudent) {
                    echo "<p>ℹ️ Student {$studentData['student_id']} already exists - skipping</p>";
                    $existing++;
                } else {
                    $studentModel->create($studentData);
                    echo "<p class='success'>✅ Created: {$studentData['name']} ({$studentData['student_id']})</p>";
                    $created++;
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error creating {$studentData['name']}: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
        echo "<div class='info'>";
        echo "<strong>Summary:</strong><br>";
        echo "Created: $created students<br>";
        echo "Already existed: $existing students<br>";
        echo "Total: " . ($created + $existing) . " students available";
        echo "</div>";
        
        // Display all students
        echo "<h2>All Students in Database:</h2>";
        
        $allStudents = $studentModel->getAll(1, 100);
        
        if (count($allStudents) > 0) {
            echo "<table>";
            echo "<thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Course</th><th>Total Fees</th><th>Balance</th><th>Actions</th></tr></thead>";
            echo "<tbody>";
            
            foreach ($allStudents as $student) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($student['student_id']) . "</td>";
                echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                echo "<td>" . htmlspecialchars($student['email']) . "</td>";
                echo "<td>" . htmlspecialchars($student['course'] ?? 'N/A') . "</td>";
                echo "<td>₹" . number_format($student['total_fees'], 2) . "</td>";
                echo "<td>₹" . number_format($student['balance_amount'], 2) . "</td>";
                echo "<td>";
                echo "<a href='student-dashboard.php?student_id=" . urlencode($student['student_id']) . "' class='btn'>View</a>";
                echo "<a href='create-payment.php?student_id=" . urlencode($student['student_id']) . "' class='btn'>Pay</a>";
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</tbody></table>";
        } else {
            echo "<p>No students found in database.</p>";
        }
        
        echo "<h2>🎉 Demo Complete!</h2>";
        echo "<div class='info'>";
        echo "<strong>Next Steps:</strong><br>";
        echo "1. Click 'View' to see a student's dashboard<br>";
        echo "2. Click 'Pay' to create a payment link<br>";
        echo "3. Configure payment gateway in resources/config.json<br>";
        echo "4. Test the complete payment flow";
        echo "</div>";
        
        echo "<p style='margin-top: 30px;'>";
        echo "<a href='index.html' class='btn'>← Back to Home</a> ";
        echo "<a href='test-db.php' class='btn'>Test Database</a>";
        echo "</p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<div class='info'>";
        echo "<strong>Troubleshooting:</strong><br>";
        echo "1. Check database configuration in resources/db_config.json<br>";
        echo "2. Ensure database tables are created (run schema.sql)<br>";
        echo "3. Verify database connection at test-db.php<br>";
        echo "</div>";
    }
    ?>
    
</body>
</html>