<?php
/**
 * Create Payment Page
 * Allow students to create payment links
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files directly
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Models/Student.php';

use Models\Student;
use Exception;

$studentId = $_GET['student_id'] ?? null;
$student = null;
$error = null;

if ($studentId) {
    try {
        $studentModel = new Student();
        $student = $studentModel->getByStudentId($studentId);
        
        if (!$student) {
            $error = "Student not found";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Payment</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        
        .student-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-weight: 600;
        }
        
        .info-value {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .quick-amounts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .quick-amount-btn {
            padding: 10px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .quick-amount-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .payment-link-section {
            display: none;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .payment-link {
            background: white;
            padding: 15px;
            border-radius: 8px;
            word-break: break-all;
            margin: 15px 0;
            border: 2px solid #667eea;
        }
        
        .copy-btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .copy-btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Create Payment</h1>
            <p>Generate payment link for your fees</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="error-message">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="success-message" id="successMessage">
                Payment link created successfully!
            </div>
            
            <?php if ($student): ?>
                <div class="student-info">
                    <div class="info-row">
                        <span class="info-label">Student Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Student ID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['student_id']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Fees:</span>
                        <span class="info-value">₹<?php echo number_format($student['total_fees'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Balance Due:</span>
                        <span class="info-value">₹<?php echo number_format($student['balance_amount'], 2); ?></span>
                    </div>
                </div>
                
                <form id="paymentForm">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($studentId); ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Payment Amount (₹) *</label>
                        <input type="number" 
                               class="form-input" 
                               name="amount" 
                               id="amount" 
                               step="0.01" 
                               min="1" 
                               max="<?php echo $student['balance_amount']; ?>"
                               required>
                        <div class="quick-amounts">
                            <?php
                            $balanceAmount = floatval($student['balance_amount']);
                            $quickAmounts = [];
                            
                            if ($balanceAmount >= 1000) {
                                $quickAmounts = [1000, 5000, 10000, $balanceAmount];
                            } elseif ($balanceAmount >= 100) {
                                $quickAmounts = [100, 500, $balanceAmount];
                            } else {
                                $quickAmounts = [10, 50, $balanceAmount];
                            }
                            
                            $quickAmounts = array_unique($quickAmounts);
                            foreach ($quickAmounts as $quickAmount):
                            ?>
                                <button type="button" 
                                        class="quick-amount-btn" 
                                        onclick="setAmount(<?php echo $quickAmount; ?>)">
                                    ₹<?php echo number_format($quickAmount, 0); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description (Optional)</label>
                        <textarea class="form-textarea" 
                                  name="description" 
                                  placeholder="e.g., Course fees payment for semester 1">Payment for course fees</textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        Generate Payment Link
                    </button>
                </form>
                
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Creating payment link...</p>
                </div>
                
                <div class="payment-link-section" id="paymentLinkSection">
                    <h3>Payment Link Generated!</h3>
                    <div class="payment-link" id="paymentLinkDisplay"></div>
                    <button class="copy-btn" onclick="copyLink()">Copy Link</button>
                    <button class="btn btn-primary" style="margin-top: 15px;" onclick="openPaymentLink()">
                        Proceed to Payment
                    </button>
                </div>
                
            <?php else: ?>
                <p>Please provide a valid student ID to create a payment.</p>
                <div class="form-group" style="margin-top: 20px;">
                    <label class="form-label">Student ID</label>
                    <input type="text" class="form-input" id="studentIdInput" placeholder="Enter your student ID">
                    <button class="btn btn-primary" style="margin-top: 15px;" onclick="redirectWithStudentId()">
                        Continue
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let generatedPaymentLink = '';
        
        function setAmount(amount) {
            document.getElementById('amount').value = amount;
        }
        
        function redirectWithStudentId() {
            const studentId = document.getElementById('studentIdInput').value;
            if (studentId) {
                window.location.href = 'create-payment.php?student_id=' + encodeURIComponent(studentId);
            }
        }
        
        document.getElementById('paymentForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                student_id: formData.get('student_id'),
                amount: parseFloat(formData.get('amount')),
                description: formData.get('description')
            };
            
            // Show loading
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('loading').style.display = 'block';
            document.getElementById('paymentForm').style.display = 'none';
            
            try {
                const response = await fetch('api/create-payment-link.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    generatedPaymentLink = result.data.payment_link;
                    document.getElementById('paymentLinkDisplay').textContent = generatedPaymentLink;
                    document.getElementById('successMessage').style.display = 'block';
                    document.getElementById('paymentLinkSection').style.display = 'block';
                } else {
                    alert('Error: ' + result.error);
                    document.getElementById('paymentForm').style.display = 'block';
                    document.getElementById('submitBtn').disabled = false;
                }
            } catch (error) {
                alert('Error creating payment link: ' + error.message);
                document.getElementById('paymentForm').style.display = 'block';
                document.getElementById('submitBtn').disabled = false;
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        });
        
        function copyLink() {
            navigator.clipboard.writeText(generatedPaymentLink).then(function() {
                alert('Payment link copied to clipboard!');
            }, function(err) {
                alert('Failed to copy link: ' + err);
            });
        }
        
        function openPaymentLink() {
            if (generatedPaymentLink) {
                window.location.href = generatedPaymentLink;
            }
        }
    </script>
</body>
</html>