<?php
/**
 * API Route: Get Student Details
 * GET /api/get-student.php?student_id=XXX
 */

header('Content-Type: application/json');

// Autoload classes
spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . '/../' . $classPath . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use Models\Student;
use Exception;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Invalid request method. Use GET.");
    }
    
    $studentId = $_GET['student_id'] ?? null;
    
    if (!$studentId) {
        throw new Exception("student_id parameter is required");
    }
    
    $studentModel = new Student();
    $student = $studentModel->getByStudentId($studentId);
    
    if (!$student) {
        throw new Exception("Student not found");
    }
    
    // Get payment history
    $payments = $studentModel->getPaymentHistory($studentId);
    
    // Get statistics
    $statistics = $studentModel->getStatistics($studentId);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'student' => $student,
            'payments' => $payments,
            'statistics' => $statistics
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}