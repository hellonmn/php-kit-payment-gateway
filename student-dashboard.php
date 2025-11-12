<?php
/**
 * Student Dashboard
 * Display student information and payment status
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files directly
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Models/Student.php';
require_once __DIR__ . '/Models/Payment.php';

use Database\Database;
use Models\Student;
use Models\Payment;
use Exception;

try {
    $studentId = $_GET['student_id'] ?? null;
    
    if (!$studentId) {
        throw new Exception("Student ID is required");
    }
    
    $studentModel = new Student();
    $paymentModel = new Payment();
    
    $student = $studentModel->getByStudentId($studentId);
    if (!$student) {
        throw new Exception("Student not found");
    }
    
    $payments = $paymentModel->getByStudentId($studentId);
    $statistics = $studentModel->getStatistics($studentId);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo htmlspecialchars($student['name'] ?? 'Error'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .container {
            max-width: 1200px;
            margin: -50px auto 30px;
            padding: 0 20px;
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .card-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .card-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .card-value.success {
            color: #28a745;
        }
        
        .card-value.warning {
            color: #ffc107;
        }
        
        .card-value.danger {
            color: #dc3545;
        }
        
        .card-info {
            font-size: 13px;
            color: #999;
        }
        
        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-title {
            font-size: 24px;
            color: #333;
            font-weight: 600;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .student-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }
        
        .payments-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .payments-table th,
        .payments-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .payments-table th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .payments-table td {
            color: #666;
            font-size: 14px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-charged {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
            
            .student-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo isset($student) ? htmlspecialchars($student['name']) : 'Error'; ?></h1>
            <p><?php echo isset($student) ? htmlspecialchars($student['student_id']) : ''; ?></p>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            
            <!-- Summary Cards -->
            <div class="cards-grid">
                <div class="card">
                    <div class="card-label">Total Fees</div>
                    <div class="card-value">₹<?php echo number_format($student['total_fees'], 2); ?></div>
                    <div class="card-info">Course fees</div>
                </div>
                
                <div class="card">
                    <div class="card-label">Paid Amount</div>
                    <div class="card-value success">₹<?php echo number_format($student['paid_amount'], 2); ?></div>
                    <div class="card-info"><?php echo $statistics['total_payments']; ?> payments</div>
                </div>
                
                <div class="card">
                    <div class="card-label">Balance Due</div>
                    <div class="card-value <?php echo $student['balance_amount'] > 0 ? 'warning' : 'success'; ?>">
                        ₹<?php echo number_format($student['balance_amount'], 2); ?>
                    </div>
                    <div class="card-info">
                        <?php echo $student['balance_amount'] > 0 ? 'Payment pending' : 'Fully paid'; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-label">Status</div>
                    <div class="card-value">
                        <?php echo strtoupper($student['status']); ?>
                    </div>
                    <div class="card-info">Account status</div>
                </div>
            </div>
            
            <!-- Student Information -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Student Information</h2>
                    <?php if ($student['balance_amount'] > 0): ?>
                    <a href="create-payment.php?student_id=<?php echo urlencode($studentId); ?>" class="btn btn-primary">
                        Make Payment
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="student-info">
                    <div class="info-item">
                        <div class="info-label">Student ID</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['student_id']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Course</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Payment History -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Payment History</h2>
                </div>
                
                <?php if (count($payments) > 0): ?>
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['order_id']); ?></td>
                            <td><?php echo date('d M Y, h:i A', strtotime($payment['created_at'])); ?></td>
                            <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                    <?php echo strtoupper($payment['payment_status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($payment['transaction_id'] ?? 'Pending'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <p>No payment history available</p>
                </div>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>
    </div>
</body>
</html>