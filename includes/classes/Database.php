<?php
/**
 * Database Singleton Class
 * /includes/classes/Database.php
 * 
 * Gère la connexion PDO à MySQL
 * Utilisation: $db = Database::getInstance();
 */

class Database {
    private static $instance = null;
    private $connection = null;
    
    /**
     * Database credentials - À PERSONNALISER
     */
    private $host = 'localhost';
    private $dbname = 'mahe6420_cms-site-ed-bordeaux';
    private $user = 'mahe6420_edbordeaux';
    private $password = '1KX(M3wwBbbW';
    private $charset = 'utf8mb4';
    
    /**
     * Private constructor (singleton pattern)
     */
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $this->connection = new PDO(
                $dsn,
                $this->user,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            
            // Set timezone
            $this->connection->exec("SET time_zone = '+00:00'");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection error: " . $e->getMessage());
        }
    }
    
    /**
     * Get singleton instance (returns PDO connection)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }
    
    /**
     * Get the Database object itself
     */
    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Execute a prepared statement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Insert record
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        
        return $this->connection->lastInsertId();
    }
    
    /**
     * Update records
     */
    public function update($table, $data, $where = '', $whereParams = []) {
        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = "{$key} = ?";
        }
        $setString = implode(', ', $sets);
        
        $whereString = '';
        if (!empty($where)) {
            $whereString = ' WHERE ' . $where;
        }
        
        $sql = "UPDATE {$table} SET {$setString}{$whereString}";
        $params = array_merge(array_values($data), $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Delete records
     */
    public function delete($table, $where = '', $params = []) {
        $whereString = '';
        if (!empty($where)) {
            $whereString = ' WHERE ' . $where;
        }
        
        $sql = "DELETE FROM {$table}{$whereString}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Test connection
     */
    public static function testConnection() {
        try {
            $db = self::getInstance();
            $result = $db->query("SELECT 1");
            return $result->fetch() ? true : false;
        } catch (Exception $e) {
            error_log("Connection test failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing
     */
    private function __wakeup() {}
}

?>