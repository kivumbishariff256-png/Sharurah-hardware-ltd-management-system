<?php
/**
 * Payment Processing Class for Sharurah Hardware Ltd Management System
 * File: classes/Payment.php
 */

require_once __DIR__ . '/../config/database.php';

class Payment {
    private $db;
    private $mtn_phone = '0773586844';
    private $airtel_phone = '0704467880';
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Create payment
    public function createPayment($data) {
        try {
            $this->db->beginTransaction();
            
            // Generate payment number
            $payment_number = $this->generatePaymentNumber();
            
            // Insert payment
            $query = "INSERT INTO payments (payment_number, order_id, customer_id, amount, 
                                          payment_method, payment_status, transaction_reference, 
                                          phone_number, processed_by, notes) 
                      VALUES (:payment_number, :order_id, :customer_id, :amount, 
                              :payment_method, :payment_status, :transaction_reference, 
                              :phone_number, :processed_by, :notes)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":payment_number", $payment_number);
            $stmt->bindParam(":order_id", $data['order_id']);
            $stmt->bindParam(":customer_id", $data['customer_id']);
            $stmt->bindParam(":amount", $data['amount']);
            $stmt->bindParam(":payment_method", $data['payment_method']);
            $stmt->bindParam(":payment_status", $data['payment_status']);
            $stmt->bindParam(":transaction_reference", $data['transaction_reference']);
            $stmt->bindParam(":phone_number", $data['phone_number']);
            $stmt->bindParam(":processed_by", $data['processed_by']);
            $stmt->bindParam(":notes", $data['notes']);
            
            $stmt->execute();
            $payment_id = $this->db->lastInsertId();
            
            // If mobile money, create mobile money transaction record
            if (in_array($data['payment_method'], ['mtn_mobile_money', 'airtel_money'])) {
                $provider = $data['payment_method'] === 'mtn_mobile_money' ? 'mtn' : 'airtel';
                $this->createMobileMoneyTransaction($payment_id, $provider, $data);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'payment_id' => $payment_id,
                'payment_number' => $payment_number
            ];
            
        } catch(PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Generate unique payment number
    private function generatePaymentNumber() {
        $prefix = 'PAY';
        $date = date('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }
    
    // Create mobile money transaction
    private function createMobileMoneyTransaction($payment_id, $provider, $data) {
        $query = "INSERT INTO mobile_money_transactions (payment_id, provider, transaction_reference, 
                                                          phone_number, amount, transaction_status) 
                  VALUES (:payment_id, :provider, :transaction_reference, 
                          :phone_number, :amount, 'pending')";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":payment_id", $payment_id);
        $stmt->bindParam(":provider", $provider);
        $stmt->bindParam(":transaction_reference", $data['transaction_reference']);
        $stmt->bindParam(":phone_number", $data['phone_number']);
        $stmt->bindParam(":amount", $data['amount']);
        
        $stmt->execute();
    }
    
    // Process MTN Mobile Money payment
    public function processMTNPayment($phone_number, $amount, $reference) {
        // This would integrate with MTN Mobile Money API
        // For now, simulating the process
        
        $response = [
            'success' => true,
            'transaction_id' => 'MTN' . time() . rand(1000, 9999),
            'message' => 'Payment initiated successfully',
            'status' => 'pending'
        ];
        
        return $response;
    }
    
    // Process Airtel Money payment
    public function processAirtelPayment($phone_number, $amount, $reference) {
        // This would integrate with Airtel Money API
        // For now, simulating the process
        
        $response = [
            'success' => true,
            'transaction_id' => 'AIRTEL' . time() . rand(1000, 9999),
            'message' => 'Payment initiated successfully',
            'status' => 'pending'
        ];
        
        return $response;
    }
    
    // Update payment status
    public function updatePaymentStatus($payment_id, $status, $response_code = null, $response_message = null) {
        try {
            $this->db->beginTransaction();
            
            // Update payment status
            $query = "UPDATE payments 
                      SET payment_status = :status, updated_at = NOW() 
                      WHERE payment_id = :payment_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":payment_id", $payment_id);
            $stmt->execute();
            
            // Update mobile money transaction if applicable
            $mm_query = "UPDATE mobile_money_transactions 
                        SET transaction_status = :status,
                            response_code = :response_code,
                            response_message = :response_message,
                            completed_at = NOW()
                        WHERE payment_id = :payment_id";
            
            $mm_stmt = $this->db->prepare($mm_query);
            $mm_stmt->bindParam(":status", $status);
            $mm_stmt->bindParam(":response_code", $response_code);
            $mm_stmt->bindParam(":response_message", $response_message);
            $mm_stmt->bindParam(":payment_id", $payment_id);
            $mm_stmt->execute();
            
            // If payment is completed, update order payment status
            if ($status === 'completed') {
                $this->updateOrderPaymentStatus($payment_id);
            }
            
            $this->db->commit();
            
            return ['success' => true];
            
        } catch(PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Update order payment status
    private function updateOrderPaymentStatus($payment_id) {
        // Get payment details
        $query = "SELECT order_id, amount FROM payments WHERE payment_id = :payment_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":payment_id", $payment_id);
        $stmt->execute();
        
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment && $payment['order_id']) {
            // Calculate total paid for this order
            $total_query = "SELECT SUM(amount) as total_paid 
                           FROM payments 
                           WHERE order_id = :order_id AND payment_status = 'completed'";
            
            $total_stmt = $this->db->prepare($total_query);
            $total_stmt->bindParam(":order_id", $payment['order_id']);
            $total_stmt->execute();
            
            $total_paid = $total_stmt->fetch(PDO::FETCH_ASSOC)['total_paid'];
            
            // Get order total
            $order_query = "SELECT total_amount FROM sales_orders WHERE order_id = :order_id";
            $order_stmt = $this->db->prepare($order_query);
            $order_stmt->bindParam(":order_id", $payment['order_id']);
            $order_stmt->execute();
            
            $order_total = $order_stmt->fetch(PDO::FETCH_ASSOC)['total_amount'];
            
            // Update order payment status
            $payment_status = 'partial';
            if ($total_paid >= $order_total) {
                $payment_status = 'paid';
            }
            
            $update_query = "UPDATE sales_orders SET payment_status = :payment_status WHERE order_id = :order_id";
            $update_stmt = $this->db->prepare($update_query);
            $update_stmt->bindParam(":payment_status", $payment_status);
            $update_stmt->bindParam(":order_id", $payment['order_id']);
            $update_stmt->execute();
        }
    }
    
    // Get payment by ID
    public function getPaymentById($payment_id) {
        $query = "SELECT p.*, 
                         CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                         c.phone_number as customer_phone,
                         u.full_name as processed_by_name,
                         so.order_number
                  FROM payments p
                  JOIN customers c ON p.customer_id = c.customer_id
                  JOIN users u ON p.processed_by = u.user_id
                  LEFT JOIN sales_orders so ON p.order_id = so.order_id
                  WHERE p.payment_id = :payment_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":payment_id", $payment_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get all payments
    public function getAllPayments($limit = 50, $offset = 0, $status = null) {
        $query = "SELECT p.*, 
                         CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                         c.phone_number as customer_phone,
                         u.full_name as processed_by_name
                  FROM payments p
                  JOIN customers c ON p.customer_id = c.customer_id
                  JOIN users u ON p.processed_by = u.user_id";
        
        if ($status) {
            $query .= " WHERE p.payment_status = :status";
        }
        
        $query .= " ORDER BY p.payment_date DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        
        if ($status) {
            $stmt->bindParam(":status", $status);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get payments by order
    public function getPaymentsByOrder($order_id) {
        $query = "SELECT p.*, 
                         CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                         u.full_name as processed_by_name
                  FROM payments p
                  JOIN customers c ON p.customer_id = c.customer_id
                  JOIN users u ON p.processed_by = u.user_id
                  WHERE p.order_id = :order_id
                  ORDER BY p.payment_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":order_id", $order_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get payment summary
    public function getPaymentSummary($start_date = null, $end_date = null) {
        $query = "SELECT 
                    COUNT(*) as total_payments,
                    SUM(amount) as total_amount,
                    SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as completed_amount,
                    SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END) as cash_amount,
                    SUM(CASE WHEN payment_method = 'mtn_mobile_money' THEN amount ELSE 0 END) as mtn_amount,
                    SUM(CASE WHEN payment_method = 'airtel_money' THEN amount ELSE 0 END) as airtel_amount,
                    SUM(CASE WHEN payment_method = 'bank_transfer' THEN amount ELSE 0 END) as bank_amount
                  FROM payments";
        
        if ($start_date && $end_date) {
            $query .= " WHERE DATE(payment_date) BETWEEN :start_date AND :end_date";
        }
        
        $stmt = $this->db->prepare($query);
        
        if ($start_date && $end_date) {
            $stmt->bindParam(":start_date", $start_date);
            $stmt->bindParam(":end_date", $end_date);
        }
        
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get mobile money transactions
    public function getMobileMoneyTransactions($provider = null, $limit = 50) {
        $query = "SELECT mmt.*, 
                         p.payment_number,
                         p.amount,
                         p.payment_status,
                         CONCAT(c.first_name, ' ', c.last_name) as customer_name
                  FROM mobile_money_transactions mmt
                  JOIN payments p ON mmt.payment_id = p.payment_id
                  JOIN customers c ON p.customer_id = c.customer_id";
        
        if ($provider) {
            $query .= " WHERE mmt.provider = :provider";
        }
        
        $query .= " ORDER BY mmt.initiated_at DESC LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        
        if ($provider) {
            $stmt->bindParam(":provider", $provider);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get company payment numbers
    public function getCompanyPaymentNumbers() {
        return [
            'mtn' => $this->mtn_phone,
            'airtel' => $this->airtel_phone
        ];
    }
}
?>