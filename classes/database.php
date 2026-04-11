<?php
require_once __DIR__ . '/../configs/config.php';

class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            die("Connessione fallita: " . $this->connection->connect_error);
        }
        $this->connection->set_charset("utf8");
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }
}
?>
