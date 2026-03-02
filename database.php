<?php
/**
 * Database Configuration for Sharurah Hardware Ltd Management System
 * File: config/database.php
 */

class Database {
    // Database credentials
    private $host = 'localhost';
    private $db_name = 'sharurah_hardware_db';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    
    // PDO instance
    public $conn;
    
    // Get database connection
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        
        return $this->conn;
    }
    
    // Execute query
    public function executeQuery($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            echo "Query Error: " . $e->getMessage();
            return false;
        }
    }
    
    // Fetch all records
    public function fetchAll($query, $params = []) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Fetch single record
    public function fetchOne($query, $params = []) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt ? $stmt->fetch() : null;
    }
    
    // Insert record
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        try {
            $stmt = $this->conn->prepare($query);
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute();
            return $this->conn->lastInsertId();
        } catch(PDOException $e) {
            echo "Insert Error: " . $e->getMessage();
            return false;
        }
    }
    
    // Update record
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $key) {
            $setClause[] = "$key = :$key";
        }
        $setClause = implode(', ', $setClause);
        
        $query = "UPDATE $table SET $setClause WHERE $where";
        
        try {
            $stmt = $this->conn->prepare($query);
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            foreach ($whereParams as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            return $stmt->execute();
        } catch(PDOException $e) {
            echo "Update Error: " . $e->getMessage();
            return false;
        }
    }
    
    // Delete record
    public function delete($table, $where, $params = []) {
        $query = "DELETE FROM $table WHERE $where";
        
        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            return $stmt->execute();
        } catch(PDOException $e) {
            echo "Delete Error: " . $e->getMessage();
            return false;
        }
    }
}
?>