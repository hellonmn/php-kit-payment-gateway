<?php
/**
 * Demo Script - Test the Payment System
 * 
 * Run this script to create test data and verify setup
 */

require_once __DIR__ . '/vendor/autoload.php';

use Database\Database;
use Models\Student;
use Services\PaymentService;

echo "=== Payment System Demo Script ===\n\n";

// Test database connection
echo "1. Testing database connection...\n";
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "   ✓ Database connected successfully\n\n";
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "   Please check resources/db_config.json\n\n";
    exit(1);
}

// Create test students
echo "2. Creating test students...\n";
$studentModel = new Student();

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

foreach ($testStudents as $studentData) {
    try {
        // Check if student already exists
        $existing = $studentModel->getByStudentId($studentData['student_id']);
        if ($existing) {
            echo "   - {$studentData['name']} ({$studentData['student_id']}) already exists\n";
        } else {
            $studentModel->create($studentData);
            echo "   ✓ Created: {$studentData['name']} ({$studentData['student_id']})\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Failed to create {$studentData['name']}: " . $e->getMessage() . "\n";
    }
}

echo "\n3. Student Summary:\n";
echo "   ----------------------------------------------------\n";
foreach ($testStudents as $studentData) {
    $student = $studentModel->getByStudentId($studentData['student_id']);
    if ($student) {
        echo "   {$student['name']} ({$student['student_id']})\n";
        echo "   Email: {$student['email']}\n";
        echo "   Course: {$student['course']}\n";
        echo "   Total Fees: ₹" . number_format($student['total_fees'], 2) . "\n";
        echo "   Balance: ₹" . number_format($student['balance_amount'], 2) . "\n";
        echo "   Status: {$student['status']}\n";
        echo "   ----------------------------------------------------\n";
    }
}

echo "\n4. Access URLs:\n";
echo "   ----------------------------------------------------\n";
foreach ($testStudents as $studentData) {
    echo "   Dashboard: http://localhost:5000/student-dashboard.php?student_id={$studentData['student_id']}\n";
    echo "   Create Payment: http://localhost:5000/create-payment.php?student_id={$studentData['student_id']}\n";
    echo "   ----------------------------------------------------\n";
}

echo "\n5. API Examples:\n";
echo "   ----------------------------------------------------\n";
echo "   Get Student:\n";
echo "   curl http://localhost:5000/api/get-student.php?student_id=STU2025001\n\n";

echo "   Create Payment Link:\n";
echo "   curl -X POST http://localhost:5000/api/create-payment-link.php \\\n";
echo "     -H 'Content-Type: application/json' \\\n";
echo "     -d '{\"student_id\":\"STU2025001\",\"amount\":10000.00}'\n\n";
echo "   ----------------------------------------------------\n";

echo "\n✓ Demo script completed successfully!\n";
echo "\nNext Steps:\n";
echo "1. Configure payment gateway credentials in resources/config.json\n";
echo "2. Visit student dashboard URLs above\n";
echo "3. Test payment link creation\n";
echo "4. Monitor logs/PaymentHandler.log for debugging\n\n";