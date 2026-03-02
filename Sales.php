<?php
/**
 * Sales Management Class for Sharurah Hardware Ltd Management System
 * File: classes/Sales.php
 */

require_once __DIR__ . '/../config/database.php';

class Sales {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Create new sales order
    public function createOrder($data) {
        try {
            $this->db->beginTransaction();
            
            // Generate order number
            $order_number = $this->generateOrderNumber();
            
            // Insert sales order
            $query = "INSERT INTO sales_orders (order_number, customer_id, order_date, subtotal, 
                                                discount_amount, tax_amount, total_amount, 
                                                payment_status, order_status, payment_method, 
                                                notes, created_by) 
                      VALUES (:order_number, :customer_id, NOW(), :subtotal, 
                              :discount_amount, :tax_amount, :total_amount, 
                              :payment_status, :order_status, :payment_method, 
                              :notes, :created_by)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":order_number", $order_number);
            $stmt->bindParam(":customer_id", $data['customer_id']);
            $stmt->bindParam(":subtotal", $data['subtotal']);
            $stmt->bindParam(":discount_amount", $data['discount_amount']);
            $stmt->bindParam(":tax_amount", $data['tax_amount']);
            $stmt->bindParam(":total_amount", $data['total_amount']);
            $stmt->bindParam(":payment_status", $data['payment_status']);
            $stmt->bindParam(":order_status", $data['order_status']);
            $stmt->bindParam(":payment_method", $data['payment_method']);
            $stmt->bindParam(":notes", $data['notes']);
            $stmt->bindParam(":created_by", $data['created_by']);
            
            $stmt->execute();
            $order_id = $this->db->lastInsertId();
            
            // Insert order items
            foreach ($data['items'] as $item) {
                $item_query = "INSERT INTO sales_order_items (order_id, product_id, quantity, 
                                                              unit_price, discount_percent, total_price) 
                              VALUES (:order_id, :product_id, :quantity, 
                                      :unit_price, :discount_percent, :total_price)";
                
                $item_stmt = $this->db->prepare($item_query);
                $item_stmt->bindParam(":order_id", $order_id);
                $item_stmt->bindParam(":product_id", $item['product_id']);
                $item_stmt->bindParam(":quantity", $item['quantity']);
                $item_stmt->bindParam(":unit_price", $item['unit_price']);
                $item_stmt->bindParam(":discount_percent", $item['discount_percent']);
                $item_stmt->bindParam(":total_price", $item['total_price']);
                $item_stmt->execute();
                
                // Update stock
                $this->updateProductStock($item['product_id'], $item['quantity'], $order_number, $data['created_by']);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'order_id' => $order_id,
                'order_number' => $order_number
            ];
            
        } catch(PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Generate unique order number
    private function generateOrderNumber() {
        $prefix = 'SHH'; // Sharurah Hardware
        $date = date('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }
    
    // Update product stock after sale
    private function updateProductStock($product_id, $quantity, $reference, $user_id) {
        $query = "UPDATE products SET quantity_in_stock = quantity_in_stock - :quantity 
                  WHERE product_id = :product_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":quantity", $quantity);
        $stmt->bindParam(":product_id", $product_id);
        $stmt->execute();
        
        // Record stock movement
        $movement_query = "INSERT INTO stock_movements (product_id, movement_type, quantity, 
                                                        reference_number, created_by) 
                          VALUES (:product_id, 'sale', :quantity, :reference_number, :created_by)";
        
        $movement_stmt = $this->db->prepare($movement_query);
        $movement_stmt->bindParam(":product_id", $product_id);
        $movement_stmt->bindParam(":quantity", $quantity);
        $movement_stmt->bindParam(":reference_number", $reference);
        $movement_stmt->bindParam(":created_by", $user_id);
        $movement_stmt->execute();
    }
    
    // Get order by ID
    public function getOrderById($order_id) {
        $query = "SELECT so.*, 
                         CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                         c.phone_number as customer_phone,
                         c.address as customer_address,
                         u.full_name as created_by_name
                  FROM sales_orders so
                  JOIN customers c ON so.customer_id = c.customer_id
                  JOIN users u ON so.created_by = u.user_id
                  WHERE so.order_id = :order_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":order_id", $order_id);
        $stmt->execute();
        
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Get order items
            $items_query = "SELECT soi.*, p.product_name, p.product_code, p.unit_of_measure
                           FROM sales_order_items soi
                           JOIN products p ON soi.product_id = p.product_id
                           WHERE soi.order_id = :order_id";
            
            $items_stmt = $this->db->prepare($items_query);
            $items_stmt->bindParam(":order_id", $order_id);
            $items_stmt->execute();
            
            $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $order;
    }
    
    // Get all orders
    public function getAllOrders($limit = 50, $offset = 0, $status = null) {
        $query = "SELECT so.*, 
                         CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                         c.phone_number as customer_phone,
                         u.full_name as created_by_name
                  FROM sales_orders so
                  JOIN customers c ON so.customer_id = c.customer_id
                  JOIN users u ON so.created_by = u.user_id";
        
        if ($status) {
            $query .= " WHERE so.order_status = :status";
        }
        
        $query .= " ORDER BY so.order_date DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        
        if ($status) {
            $stmt->bindParam(":status", $status);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Update order status
    public function updateOrderStatus($order_id, $status) {
        $query = "UPDATE sales_orders SET order_status = :status, updated_at = NOW() 
                  WHERE order_id = :order_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":order_id", $order_id);
        
        return ['success' => $stmt->execute()];
    }
    
    // Get sales summary
    public function getSalesSummary($start_date = null, $end_date = null) {
        $query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_sales,
                    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN payment_status = 'pending' THEN total_amount ELSE 0 END) as pending_amount,
                    AVG(total_amount) as average_order_value
                  FROM sales_orders";
        
        if ($start_date && $end_date) {
            $query .= " WHERE DATE(order_date) BETWEEN :start_date AND :end_date";
        }
        
        $stmt = $this->db->prepare($query);
        
        if ($start_date && $end_date) {
            $stmt->bindParam(":start_date", $start_date);
            $stmt->bindParam(":end_date", $end_date);
        }
        
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get daily sales
    public function getDailySales($days = 30) {
        $query = "SELECT 
                    DATE(order_date) as sale_date,
                    COUNT(*) as orders,
                    SUM(total_amount) as total_sales
                  FROM sales_orders
                  WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                  GROUP BY DATE(order_date)
                  ORDER BY sale_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":days", $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get top selling products
    public function getTopSellingProducts($limit = 10, $start_date = null, $end_date = null) {
        $query = "SELECT 
                    p.product_id,
                    p.product_name,
                    p.product_code,
                    SUM(soi.quantity) as total_sold,
                    SUM(soi.total_price) as total_revenue
                  FROM sales_order_items soi
                  JOIN products p ON soi.product_id = p.product_id
                  JOIN sales_orders so ON soi.order_id = so.order_id";
        
        if ($start_date && $end_date) {
            $query .= " WHERE DATE(so.order_date) BETWEEN :start_date AND :end_date";
        }
        
        $query .= " GROUP BY p.product_id, p.product_name, p.product_code
                   ORDER BY total_sold DESC
                   LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        
        if ($start_date && $end_date) {
            $stmt->bindParam(":start_date", $start_date);
            $stmt->bindParam(":end_date", $end_date);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get customer orders
    public function getCustomerOrders($customer_id, $limit = 20) {
        $query = "SELECT so.*, 
                         COUNT(soi.item_id) as total_items
                  FROM sales_orders so
                  LEFT JOIN sales_order_items soi ON so.order_id = soi.order_id
                  WHERE so.customer_id = :customer_id
                  GROUP BY so.order_id
                  ORDER BY so.order_date DESC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":customer_id", $customer_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>