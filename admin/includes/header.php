<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database configuration and functions
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';   // if you have helper functions

$db = new Database();
$conn = $db->getConnection();

// Optionally restrict access by IP (school network only)
function isIpAllowed($conn) {
    // Example: allow only IPs in 192.168.1.0/24
    $ip = $_SERVER['REMOTE_ADDR'];
    if (strpos($ip, '192.168.1.') === 0) {
        return true;
    }
    // You could also check a database table for allowed IPs
    return false;
}

// Uncomment the next line to enforce IP restriction
// if (!isIpAllowed($conn)) { die("Access denied. This system is only accessible from the school network."); }

// Get admin details
$admin_id = $_SESSION['user_id'];
$admin = $conn->query("SELECT username, email FROM users WHERE id = $admin_id")->fetch_assoc();
$admin_name = $admin['username'] ?? 'Admin';
$admin_email = $admin['email'] ?? 'admin@mohi.ac.ke';

// Get recent staff for the "Members" section (optional)
$staff_list = $conn->query("SELECT full_name, role FROM staff ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'MOHI Namarei - Admin Panel' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body>

<!-- Offcanvas Sidebar (for mobile) -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0"></div>
</div>

<!-- Desktop Sidebar -->
<div class="sidebar d-none d-md-flex">
    <div class="sidebar-top">
        <div class="sidebar-user">
            <div class="avatar-lg"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
            <div class="user-info">
                <strong><?= htmlspecialchars($admin_name) ?></strong>
                <small>Administrator</small>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>" href="users.php">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'bulk_register.php' ? 'active' : '' ?>" href="bulk_register.php">
                    <i class="bi bi-shield-lock"></i> Bulk Register
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'security.php' ? 'active' : '' ?>" href="security.php">
                    <i class="bi bi-shield-lock"></i> Security Questions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'staff.php' ? 'active' : '' ?>" href="staff.php">
                    <i class="bi bi-person-badge"></i> Staff
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : '' ?>" href="students.php">
                    <i class="bi bi-mortarboard"></i> Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'visits.php' ? 'active' : '' ?>" href="visits.php">
                    <i class="bi bi-clipboard2-pulse"></i> Visits
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : '' ?>" href="appointments.php">
                    <i class="bi bi-calendar-check"></i> Appointments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'drugs.php' ? 'active' : '' ?>" href="drugs.php">
                    <i class="bi bi-capsule"></i> Pharmacy
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>" href="reports.php">
                    <i class="bi bi-bar-chart"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'nhif_report.php' ? 'active' : '' ?>" href="nhif_report.php">
                    <i class="bi bi-credit-card"></i> NHIF Report
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'audit_logs.php' ? 'active' : '' ?>" href="audit_logs.php">
                    <i class="bi bi-journal-text"></i> Audit Logs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : '' ?>" href="backup.php">
                    <i class="bi bi-database"></i> Backup
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>" href="settings.php">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'ambulance_requests.php' ? 'active' : '' ?>" href="ambulance_requests.php">
                    <i class="bi bi-truck"></i> Ambulance Requests
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-mini">
            <div class="avatar-sm"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
            <div class="user-mini-info">
                <strong><?= htmlspecialchars($admin_name) ?></strong>
                <small>Administrator</small>
            </div>
        </div>
        <a href="../logout.php" class="btn btn-outline-light btn-sm w-100 mt-2">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <nav class="navbar navbar-dark bg-black d-md-none">
        <div class="container-fluid">
            <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                <i class="bi bi-list"></i>
            </button>
            <span class="navbar-brand mb-0 h1">MOHI Namarei</span>
        </div>
    </nav>
    <div class="container-fluid p-3">