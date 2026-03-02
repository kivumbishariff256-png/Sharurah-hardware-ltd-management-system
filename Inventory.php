<?php
/**
 * Inventory Management Class for Sharurah Hardware Ltd Management System
 * File: classes/Inventory.php
 */

require_once __DIR__ . '/../config/database.php';

class Inventory {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Get all products
    public function getAllProducts($limit = 50, $offset = 0) {
        $query = "SELECT p.*, c.category_name, s.supplier_name 
                  FROM products p 
                  LEFT JOIN product_categories c ON p.category_id = c.category_id 
                  LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
                  WHERE p.is_active = TRUE 
                  ORDER BY p.product_name ASC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get product by ID
    public function getProductById($product_id) {
        $query = "SELECT p.*, c.category_name, s.supplier_name 
                  FROM products p 
                  LEFT JOIN product_categories c ON p.category_id = c.category_id 
                  LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
                  WHERE p.product_id = :product_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":product_id", $product_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Search products
    public function searchProducts($search_term) {
        $query = "SELECT p.*, c.category_name 
                  FROM products p 
                  LEFT JOIN product_categories c ON p.category_id = c.category_id 
                  WHERE p.is_active = TRUE 
                  AND (p.product_name LIKE :search_term 
                       OR p.product_code LIKE :search_term 
                       OR p.barcode LIKE :search_term)
                  ORDER BY p.product_name ASC 
                  LIMIT 50";
        
        $search_param = "%$search_term%";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":search_term", $search_param);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add new product
    public function addProduct($data) {
        $query = "INSERT INTO products (product_code, product_name, category_id, description, unit_price, 
                                        cost_price, quantity_in_stock, reorder_level, unit_of_measure, 
                                        brand, supplier_id, barcode, image_url) 
                  VALUES (:product_code, :product_name, :category_id, :description, :unit_price, 
                          :cost_price, :quantity_in_stock, :reorder_level, :unit_of_measure, 
                          :brand, :supplier_id, :barcode, :image_url)";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":product_code", $data['product_code']);
            $stmt->bindParam(":product_name", $data['product_name']);
            $stmt->bindParam(":category_id", $data['category_id']);
            $stmt->bindParam(":description", $data['description']);
            $stmt->bindParam(":unit_price", $data['unit_price']);
            $stmt->bindParam(":cost_price", $data['cost_price']);
            $stmt->bindParam(":quantity_in_stock", $data['quantity_in_stock']);
            $stmt->bindParam(":reorder_level", $data['reorder_level']);
            $stmt->bindParam(":unit_of_measure", $data['unit_of_measure']);
            $stmt->bindParam(":brand", $data['brand']);
            $stmt->bindParam(":supplier_id", $data['supplier_id']);
            $stmt->bindParam(":barcode", $data['barcode']);
            $stmt->bindParam(":image_url", $data['image_url']);
            
            if ($stmt->execute()) {
                $product_id = $this->db->lastInsertId();
                
                // Record initial stock movement
                $this->recordStockMovement($product_id, 'purchase', $data['quantity_in_stock'], 
                                          'Initial stock', $data['created_by']);
                
                return ['success' => true, 'product_id' => $product_id];
            }
            
            return ['success' => false, 'message' => 'Failed to add product'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Update product
    public function updateProduct($product_id, $data) {
        $query = "UPDATE products 
                  SET product_name = :product_name, 
                      category_id = :category_id, 
                      description = :description, 
                      unit_price = :unit_price, 
                      cost_price = :cost_price, 
                      reorder_level = :reorder_level, 
                      unit_of_measure = :unit_of_measure, 
                      brand = :brand, 
                      supplier_id = :supplier_id, 
                      barcode = :barcode, 
                      image_url = :image_url,
                      updated_at = NOW()
                  WHERE product_id = :product_id";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":product_name", $data['product_name']);
            $stmt->bindParam(":category_id", $data['category_id']);
            $stmt->bindParam(":description", $data['description']);
            $stmt->bindParam(":unit_price", $data['unit_price']);
            $stmt->bindParam(":cost_price", $data['cost_price']);
            $stmt->bindParam(":reorder_level", $data['reorder_level']);
            $stmt->bindParam(":unit_of_measure", $data['unit_of_measure']);
            $stmt->bindParam(":brand", $data['brand']);
            $stmt->bindParam(":supplier_id", $data['supplier_id']);
            $stmt->bindParam(":barcode", $data['barcode']);
            $stmt->bindParam(":image_url", $data['image_url']);
            $stmt->bindParam(":product_id", $product_id);
            
            return ['success' => $stmt->execute()];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Delete product (soft delete)
    public function deleteProduct($product_id) {
        $query = "UPDATE products SET is_active = FALSE WHERE product_id = :product_id";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":product_id", $product_id);
            return ['success' => $stmt->execute()];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Update stock quantity
    public function updateStock($product_id, $quantity, $movement_type, $reference, $user_id) {
        $query = "SELECT quantity_in_stock FROM products WHERE product_id = :product_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":product_id", $product_id);
        $stmt->execute();
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        $new_quantity = $product['quantity_in_stock'];
        
        if (in_array($movement_type, ['purchase', 'return'])) {
            $new_quantity += $quantity;
        } elseif (in_array($movement_type, ['sale', 'adjustment'])) {
            $new_quantity -= $quantity;
            
            if ($new_quantity < 0) {
                return ['success' => false, 'message' => 'Insufficient stock'];
            }
        }
        
        $update_query = "UPDATE products SET quantity_in_stock = :new_quantity WHERE product_id = :product_id";
        $update_stmt = $this->db->prepare($update_query);
        $update_stmt->bindParam(":new_quantity", $new_quantity);
        $update_stmt->bindParam(":product_id", $product_id);
        
        if ($update_stmt->execute()) {
            $this->recordStockMovement($product_id, $movement_type, $quantity, $reference, $user_id);
            return ['success' => true, 'new_quantity' => $new_quantity];
        }
        
        return ['success' => false, 'message' => 'Failed to update stock'];
    }
    
    // Record stock movement
    private function recordStockMovement($product_id, $movement_type, $quantity, $reference, $user_id) {
        $query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_number, created_by) 
                  VALUES (:product_id, :movement_type, :quantity, :reference_number, :created_by)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":product_id", $product_id);
        $stmt->bindParam(":movement_type", $movement_type);
        $stmt->bindParam(":quantity", $quantity);
        $stmt->bindParam(":reference_number", $reference);
        $stmt->bindParam(":created_by", $user_id);
        
        $stmt->execute();
    }
    
    // Get low stock products
    public function getLowStockProducts() {
        $query = "SELECT p.*, c.category_name 
                  FROM products p 
                  LEFT JOIN product_categories c ON p.category_id = c.category_id 
                  WHERE p.is_active = TRUE 
                  AND p.quantity_in_stock <= p.reorder_level 
                  ORDER BY p.quantity_in_stock ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get out of stock products
    public function getOutOfStockProducts() {
        $query = "SELECT p.*, c.category_name 
                  FROM products p 
                  LEFT JOIN product_categories c ON p.category_id = c.category_id 
                  WHERE p.is_active = TRUE 
                  AND p.quantity_in_stock = 0 
                  ORDER BY p.product_name ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get product categories
    public function getCategories() {
        $query = "SELECT * FROM product_categories ORDER BY category_name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get suppliers
    public function getSuppliers() {
        $query = "SELECT * FROM suppliers WHERE is_active = TRUE ORDER BY supplier_name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get stock movements
    public function getStockMovements($product_id = null, $limit = 100) {
        if ($product_id) {
            $query = "SELECT sm.*, p.product_name, u.full_name 
                      FROM stock_movements sm 
                      JOIN products p ON sm.product_id = p.product_id 
                      JOIN users u ON sm.created_by = u.user_id 
                      WHERE sm.product_id = :product_id 
                      ORDER BY sm.created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":product_id", $product_id);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        } else {
            $query = "SELECT sm.*, p.product_name, u.full_name 
                      FROM stock_movements sm 
                      JOIN products p ON sm.product_id = p.product_id 
                      JOIN users u ON sm.created_by = u.user_id 
                      ORDER BY sm.created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get inventory summary
    public function getInventorySummary() {
        $query = "SELECT 
                    COUNT(*) as total_products,
                    SUM(quantity_in_stock) as total_items,
                    SUM(quantity_in_stock * cost_price) as total_cost_value,
                    SUM(quantity_in_stock * unit_price) as total_retail_value,
                    COUNT(CASE WHEN quantity_in_stock = 0 THEN 1 END) as out_of_stock,
                    COUNT(CASE WHEN quantity_in_stock <= reorder_level THEN 1 END) as low_stock
                  FROM products 
                  WHERE is_active = TRUE";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>