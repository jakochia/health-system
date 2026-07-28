<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'mohi_namarei';
    private $username = 'root';
    private $password = '';  // if you set a MySQL password, update it here
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
        } catch(Exception $e) {
            die("Database error: " . $e->getMessage());
        }
        return $this->conn;
    }
}
?>