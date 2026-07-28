<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$username = 'admin';
$password = 'admin123';  // Change to your desired password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // Update existing admin
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashed, $username);
    if ($stmt->execute()) {
        echo "Admin password updated successfully. Username: admin, Password: admin123";
    } else {
        echo "Error updating: " . $conn->error;
    }
} else {
    // Insert new admin
    $role = 'admin';
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed, $role);
    if ($stmt->execute()) {
        echo "Admin user created. Username: admin, Password: admin123";
    } else {
        echo "Error creating: " . $conn->error;
    }
}
?>