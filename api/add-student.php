<?php
/**
 * API Route: Add Student
 * POST /api/add-student.php
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method. Use POST.");
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['student_id', 'name', 'email', 'total_fees'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("$field is required");
        }
    }
    
    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }
    
    $studentModel = new Student();
    
    // Check if student already exists
    if ($studentModel->getByStudentId($input['student_id'])) {
        throw new Exception("Student with this ID already exists");
    }
    
    if ($studentModel->getByEmail($input['email'])) {
        throw new Exception("Student with this email already exists");
    }
    
    // Create student
    $studentId = $studentModel->create([
        'student_id' => $input['student_id'],
        'name' => $input['name'],
        'email' => $input['email'],
        'phone' => $input['phone'] ?? null,
        'course' => $input['course'] ?? null,
        'total_fees' => floatval($input['total_fees']),
        'status' => 'active'
    ]);
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Student added successfully',
        'data' => [
            'id' => $studentId,
            'student_id' => $input['student_id']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}