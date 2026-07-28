<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Set base URL (adjust if your project is in a different folder)
$base_url = '/mohi_namarei/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'MOHI Namarei' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/custom.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-black">
    <div class="container">
        <a class="navbar-brand" href="<?= $base_url ?>">MOHI Namarei</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if ($role == 'teacher'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>teacher/">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>teacher/register_visit.php">Register Visit</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>teacher/health_history.php">Health History</a></li>
                <?php elseif ($role == 'student'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>student/">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>student/history.php">My Health Records</a></li>
                <?php elseif ($role == 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url ?>admin/">Admin Panel</a></li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?>profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?>request_ambulance.php"><i class="bi bi-truck"></i> Ambulance</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?>logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container mt-4">