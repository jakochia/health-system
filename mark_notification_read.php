<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$id = $_GET['id'] ?? 0;
$redirect = $_GET['redirect'] ?? '../index.php';

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();

header("Location: $redirect");
exit;