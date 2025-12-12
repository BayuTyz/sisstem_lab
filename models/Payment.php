<?php
// models/Payment.php

class Payment {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance();
    }
    
    public function getAllPayments($startDate = null, $endDate = null) {
        $sql = "SELECT p.*, e.examination_code, e.examination_type, pt.full_name as patient_name
                FROM payments p
                JOIN examinations e ON p.examination_id = e.id
                JOIN patients pt ON e.patient_id = pt.id";
        
        $params = [];
        if ($startDate && $endDate) {
            $sql .= " WHERE p.payment_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY p.payment_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getPaymentStats($startDate = null, $endDate = null) {
        $sql = "SELECT 
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue,
                COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN payment_status = 'unpaid' THEN 1 END) as unpaid_count,
                AVG(total_amount) as avg_transaction
                FROM payments";
        
        $params = [];
        if ($startDate && $endDate) {
            $sql .= " WHERE payment_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function createPayment($data) {
        $sql = "INSERT INTO payments 
                (examination_id, total_amount, payment_method, payment_date, payment_status, notes) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['examination_id'],
            $data['total_amount'],
            $data['payment_method'],
            $data['payment_date'],
            $data['payment_status'] ?? 'paid',
            $data['notes'] ?? ''
        ]);
    }
}
?>