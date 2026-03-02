<?php
/**
 * Customer Management Class for Sharurah Hardware Ltd Management System
 * File: classes/Customer.php
 */

require_once __DIR__ . '/../config/database.php';

class Customer {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Get all customers
    public function getAllCustomers($limit = 50, $offset = 0) {
        $query = "SELECT * FROM customers 
                  WHERE is_active = TRUE 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get customer by ID
    public function getCustomerById($customer_id) {
        $query = "SELECT * FROM customers WHERE customer_id = :customer_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":customer_id", $customer_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get customer by phone number
    public function getCustomerByPhone($phone_number) {
        $query = "SELECT * FROM customers WHERE phone_number = :phone_number AND is_active = TRUE";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":phone_number", $phone_number);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Add new customer
    public function addCustomer($data) {
        // Generate customer code
        $customer_code = $this->generateCustomerCode();
        
        $query = "INSERT INTO customers (customer_code, first_name, last_name, phone_number, 
                                        email, address, city, country, customer_type, 
                                        credit_limit, current_balance) 
                  VALUES (:customer_code, :first_name, :last_name, :phone_number, 
                          :email, :address, :city, :country, :customer_type, 
                          :credit_limit, 0)";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":customer_code", $customer_code);
            $stmt->bindParam(":first_name", $data['first_name']);
            $stmt->bindParam(":last_name", $data['last_name']);
            $stmt->bindParam(":phone_number", $data['phone_number']);
            $stmt->bindParam(":email", $data['email']);
            $stmt->bindParam(":address", $data['address']);
            $stmt->bindParam(":city", $data['city']);
            $stmt->bindParam(":country", $data['country']);
            $stmt->bindParam(":customer_type", $data['customer_type']);
            $stmt->bindParam(":credit_limit", $data['credit_limit']);
            
            if ($stmt->execute()) {
                $customer_id = $this->db->lastInsertId();
                return ['success' => true, 'customer_id' => $customer_id, 'customer_code' => $customer_code];
            }
            
            return ['success' => false, 'message' => 'Failed to add customer'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Generate unique customer code
    private function generateCustomerCode() {
        $prefix = 'CUST';
        $date = date('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }
    
    // Update customer
    public function updateCustomer($customer_id, $data) {
        $query = "UPDATE customers 
                  SET first_name = :first_name, 
                      last_name = :last_name, 
                      phone_number = :phone_number, 
                      email = :email, 
                      address = :address, 
                      city = :city, 
                      country = :country, 
                      customer_type = :customer_type, 
                      credit_limit = :credit_limit,
                      updated_at = NOW()
                  WHERE customer_id = :customer_id";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":first_name", $data['first_name']);
            $stmt->bindParam(":last_name", $data['last_name']);
            $stmt->bindParam(":phone_number", $data['phone_number']);
            $stmt->bindParam(":email", $data['email']);
            $stmt->bindParam(":address", $data['address']);
            $stmt->bindParam(":city", $data['city']);
            $stmt->bindParam(":country", $data['country']);
            $stmt->bindParam(":customer_type", $data['customer_type']);
            $stmt->bindParam(":credit_limit", $data['credit_limit']);
            $stmt->bindParam(":customer_id", $customer_id);
            
            return ['success' => $stmt->execute()];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Delete customer (soft delete)
    public function deleteCustomer($customer_id) {
        $query = "UPDATE customers SET is_active = FALSE WHERE customer_id = :customer_id";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":customer_id", $customer_id);
            return ['success' => $stmt->execute()];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Search customers
    public function searchCustomers($search_term) {
        $query = "SELECT * FROM customers 
                  WHERE is_active = TRUE 
                  AND (first_name LIKE :search_term 
                       OR last_name LIKE :search_term 
                       OR phone_number LIKE :search_term 
                       OR customer_code LIKE :search_term)
                  ORDER BY created_at DESC 
                  LIMIT 50";
        
        $search_param = "%$search_term%";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":search_term", $search_param);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Update customer balance
    public function updateBalance($customer_id, $amount) {
        $query = "UPDATE customers SET current_balance = current_balance + :amount WHERE customer_id = :customer_id";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":amount", $amount);
            $stmt->bindParam(":customer_id", $customer_id);
            return ['success' => $stmt->execute()];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Get customers with outstanding balance
    public function getCustomersWithBalance() {
        $query = "SELECT * FROM customers 
                  WHERE is_active = TRUE 
                  AND current_balance > 0 
                  ORDER BY current_balance DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get customer summary
    public function getCustomerSummary() {
        $query = "SELECT 
                    COUNT(*) as total_customers,
                    SUM(current_balance) as total_outstanding,
                    COUNT(CASE WHEN customer_type = 'retail' THEN 1 END) as retail_customers,
                    COUNT(CASE WHEN customer_type = 'wholesale' THEN 1 END) as wholesale_customers,
                    COUNT(CASE WHEN customer_type = 'corporate' THEN 1 END) as corporate_customers
                  FROM customers 
                  WHERE is_active = TRUE";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>