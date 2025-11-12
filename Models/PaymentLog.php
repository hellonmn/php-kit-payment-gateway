<?php
namespace Models;

use Database\Database;

class PaymentLog {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a log entry
     */
    public function log($orderId, $studentId, $action, $requestData = null, $responseData = null) {
        $logData = [
            'order_id' => $orderId,
            'student_id' => $studentId,
            'action' => $action,
            'request_data' => $requestData ? json_encode($requestData) : null,
            'response_data' => $responseData ? json_encode($responseData) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        return $this->db->insert('payment_logs', $logData);
    }
    
    /**
     * Get logs for an order
     */
    public function getByOrderId($orderId) {
        $sql = "SELECT * FROM payment_logs WHERE order_id = :order_id ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, ['order_id' => $orderId]);
    }
    
    /**
     * Get logs for a student
     */
    public function getByStudentId($studentId) {
        $sql = "SELECT * FROM payment_logs WHERE student_id = :student_id ORDER BY created_at DESC LIMIT 100";
        return $this->db->fetchAll($sql, ['student_id' => $studentId]);
    }
}