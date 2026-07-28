<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_ambulance'])) {
    $location = trim($_POST['location']);
    $contact = trim($_POST['contact']);
    $urgency = $_POST['urgency'];
    $condition = trim($_POST['condition']);

    $stmt = $conn->prepare("INSERT INTO ambulance_requests (user_id, location, contact_phone, urgency, condition) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $location, $contact, $urgency, $condition);
    if ($stmt->execute()) {
        $message = "Ambulance request submitted successfully. Our team will contact you shortly.";
    } else {
        $error = "Error submitting request.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambulance Service - MOHI Namarei</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4><i class="bi bi-truck"></i> Request Ambulance</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label>Your Location (e.g., Admin Block, Dormitory, Classroom)</label>
                            <input type="text" name="location" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Contact Phone Number</label>
                            <input type="tel" name="contact" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Urgency Level</label>
                            <select name="urgency" class="form-control" required>
                                <option value="low">Low – Not urgent, can wait</option>
                                <option value="medium">Medium – Need assistance soon</option>
                                <option value="high">High – Urgent, requires quick response</option>
                                <option value="emergency">Emergency – Life-threatening</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Brief Description of Condition</label>
                            <textarea name="condition" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="request_ambulance" class="btn btn-danger w-100">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>