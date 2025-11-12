<?php
namespace Services;

use PaymentHandler\PaymentHandler;
use PaymentHandler\APIException;
use Models\Student;
use Models\Payment;
use Models\PaymentSession;
use Models\PaymentLog;
use Database\Database;
use Exception;

class PaymentService {
    private $paymentHandler;
    private $studentModel;
    private $paymentModel;
    private $sessionModel;
    private $logModel;
    private $db;
    
    public function __construct() {
        $this->paymentHandler = new PaymentHandler("resources/config.json");
        $this->studentModel = new Student();
        $this->paymentModel = new Payment();
        $this->sessionModel = new PaymentSession();
        $this->logModel = new PaymentLog();
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a payment link for a student
     */
    public function createPaymentLink($studentId, $amount, $description = null, $returnUrl = null) {
        try {
            // Validate student exists
            $student = $this->studentModel->getByStudentId($studentId);
            if (!$student) {
                throw new Exception("Student not found");
            }
            
            // Generate unique IDs
            $orderId = "order_" . $studentId . "_" . time() . "_" . uniqid();
            $paymentId = "pay_" . uniqid();
            
            // Set default return URL
            if ($returnUrl === null) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost:5000';
                $returnUrl = "$protocol://$host/handlePaymentResponse.php";
            }
            
            // Prepare session parameters
            $sessionParams = [
                'amount' => number_format($amount, 2, '.', ''),
                'order_id' => $orderId,
                'customer_id' => $studentId,
                'action' => 'paymentPage',
                'return_url' => $returnUrl,
                'currency' => 'INR'
            ];
            
            // Add customer details
            if (!empty($student['email'])) {
                $sessionParams['customer_email'] = $student['email'];
            }
            if (!empty($student['phone'])) {
                $sessionParams['customer_phone'] = $student['phone'];
            }
            
            $this->db->beginTransaction();
            
            try {
                // Create payment record
                $this->paymentModel->create([
                    'payment_id' => $paymentId,
                    'order_id' => $orderId,
                    'student_id' => $studentId,
                    'amount' => $amount,
                    'currency' => 'INR',
                    'payment_status' => 'pending',
                    'description' => $description
                ]);
                
                // Call payment gateway to create session
                $session = $this->paymentHandler->orderSession($sessionParams);
                
                // Store session information
                $this->sessionModel->create([
                    'session_id' => $session['id'] ?? uniqid(),
                    'order_id' => $orderId,
                    'student_id' => $studentId,
                    'amount' => $amount,
                    'payment_link' => $session['payment_links']['web'] ?? null,
                    'session_data' => $session
                ]);
                
                // Log the action
                $this->logModel->log($orderId, $studentId, 'CREATE_PAYMENT_LINK', $sessionParams, $session);
                
                $this->db->commit();
                
                return [
                    'success' => true,
                    'order_id' => $orderId,
                    'payment_id' => $paymentId,
                    'payment_link' => $session['payment_links']['web'] ?? null,
                    'qr_code' => $session['payment_links']['upi'] ?? null,
                    'session' => $session
                ];
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (APIException $e) {
            $this->logModel->log($orderId ?? null, $studentId, 'CREATE_PAYMENT_LINK_ERROR', $sessionParams ?? [], [
                'error' => $e->getErrorMessage(),
                'error_code' => $e->getErrorCode()
            ]);
            
            throw new Exception("Payment gateway error: " . $e->getErrorMessage());
        }
    }
    
    /**
     * Handle payment response/callback
     */
    public function handlePaymentResponse($params) {
        try {
            $orderId = $params['order_id'] ?? null;
            
            if (!$orderId) {
                throw new Exception("Order ID is required");
            }
            
            // Get payment record
            $payment = $this->paymentModel->getByOrderId($orderId);
            if (!$payment) {
                throw new Exception("Payment not found");
            }
            
            $studentId = $payment['student_id'];
            
            // Validate HMAC signature
            if ($params['status'] != 'NEW' && !$this->paymentHandler->validateHMAC_SHA256($params)) {
                $this->logModel->log($orderId, $studentId, 'SIGNATURE_VERIFICATION_FAILED', $params, null);
                throw new Exception("Signature verification failed");
            }
            
            // Get order status from payment gateway
            $orderStatus = $this->paymentHandler->orderStatus($orderId);
            
            $this->logModel->log($orderId, $studentId, 'PAYMENT_RESPONSE_RECEIVED', $params, $orderStatus);
            
            $this->db->beginTransaction();
            
            try {
                // Update payment status
                $paymentStatus = $this->mapPaymentStatus($orderStatus['status']);
                $this->paymentModel->updateStatus(
                    $orderId, 
                    $paymentStatus,
                    $orderStatus,
                    $orderStatus['transaction_id'] ?? null
                );
                
                // If payment is successful, update student balance
                if ($paymentStatus === 'charged') {
                    $this->studentModel->updatePaymentAmounts($studentId, $payment['amount']);
                    
                    // Mark session as used
                    $session = $this->sessionModel->getByOrderId($orderId);
                    if ($session) {
                        $this->sessionModel->markAsUsed($session['session_id']);
                    }
                }
                
                $this->db->commit();
                
                return [
                    'success' => true,
                    'order_id' => $orderId,
                    'payment_status' => $paymentStatus,
                    'order_details' => $orderStatus,
                    'student_id' => $studentId
                ];
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (APIException $e) {
            $this->logModel->log($orderId ?? null, $studentId ?? null, 'PAYMENT_RESPONSE_ERROR', $params, [
                'error' => $e->getErrorMessage()
            ]);
            throw new Exception("Payment gateway error: " . $e->getErrorMessage());
        }
    }
    
    /**
     * Process refund
     */
    public function processRefund($orderId, $amount, $reason = null) {
        try {
            // Get payment record
            $payment = $this->paymentModel->getByOrderId($orderId);
            if (!$payment) {
                throw new Exception("Payment not found");
            }
            
            if ($payment['payment_status'] !== 'charged') {
                throw new Exception("Only successful payments can be refunded");
            }
            
            $studentId = $payment['student_id'];
            $refundId = "ref_" . time() . "_" . uniqid();
            
            // Prepare refund parameters
            $refundParams = [
                'order_id' => $orderId,
                'amount' => number_format($amount, 2, '.', ''),
                'unique_request_id' => $refundId
            ];
            
            $this->db->beginTransaction();
            
            try {
                // Call payment gateway for refund
                $refundResponse = $this->paymentHandler->refund($refundParams);
                
                // Store refund information
                $this->db->insert('refunds', [
                    'refund_id' => $refundId,
                    'payment_id' => $payment['payment_id'],
                    'order_id' => $orderId,
                    'student_id' => $studentId,
                    'amount' => $amount,
                    'refund_status' => $refundResponse['status'] ?? 'pending',
                    'refund_reason' => $reason,
                    'gateway_response' => json_encode($refundResponse),
                    'refund_date' => date('Y-m-d H:i:s')
                ]);
                
                // Update payment status
                $this->paymentModel->updateStatus($orderId, 'refunded', $refundResponse);
                
                // Update student balance
                $student = $this->studentModel->getByStudentId($studentId);
                $newPaidAmount = floatval($student['paid_amount']) - floatval($amount);
                $newBalanceAmount = floatval($student['total_fees']) - $newPaidAmount;
                
                $this->studentModel->update($studentId, [
                    'paid_amount' => $newPaidAmount,
                    'balance_amount' => $newBalanceAmount,
                    'status' => $newBalanceAmount > 0 ? 'active' : 'completed'
                ]);
                
                // Log the action
                $this->logModel->log($orderId, $studentId, 'REFUND_PROCESSED', $refundParams, $refundResponse);
                
                $this->db->commit();
                
                return [
                    'success' => true,
                    'refund_id' => $refundId,
                    'order_id' => $orderId,
                    'refund_status' => $refundResponse['status'] ?? 'pending',
                    'refund_details' => $refundResponse
                ];
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (APIException $e) {
            $this->logModel->log($orderId ?? null, $studentId ?? null, 'REFUND_ERROR', $refundParams ?? [], [
                'error' => $e->getErrorMessage()
            ]);
            throw new Exception("Refund processing error: " . $e->getErrorMessage());
        }
    }
    
    /**
     * Get payment details
     */
    public function getPaymentDetails($orderId) {
        $payment = $this->paymentModel->getByOrderId($orderId);
        if (!$payment) {
            throw new Exception("Payment not found");
        }
        
        $student = $this->studentModel->getByStudentId($payment['student_id']);
        $logs = $this->logModel->getByOrderId($orderId);
        
        return [
            'payment' => $payment,
            'student' => $student,
            'logs' => $logs
        ];
    }
    
    /**
     * Map payment gateway status to internal status
     */
    private function mapPaymentStatus($gatewayStatus) {
        $statusMap = [
            'CHARGED' => 'charged',
            'PENDING' => 'pending',
            'PENDING_VBV' => 'pending',
            'AUTHORIZATION_FAILED' => 'authorization_failed',
            'AUTHENTICATION_FAILED' => 'authentication_failed',
            'FAILED' => 'failed'
        ];
        
        return $statusMap[$gatewayStatus] ?? 'pending';
    }
}