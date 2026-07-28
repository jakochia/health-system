<?php
session_start();
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                session_regenerate_id(true);
                $this->logAction($row['id'], "Login");
                return true;
            }
        }
        return false;
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logAction($_SESSION['user_id'], "Logout");
        }
        session_destroy();
        header("Location: index.php");
        exit();
    }

    private function logAction($userId, $action) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->conn->prepare("INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $action, $ip);
        $stmt->execute();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] == $role;
    }

    public function redirectBasedOnRole() {
        if (!$this->isLoggedIn()) {
            header("Location: index.php");
            exit();
        }
        switch($_SESSION['role']) {
            case 'admin':
                header("Location: admin/");
                break;
            case 'teacher':
                header("Location: teacher/");
                break;
            case 'student':
                header("Location: student/");
                break;
        }
        exit();
    }
}
?>