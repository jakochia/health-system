<?php
// Get clinic settings for logo/name
require_once __DIR__ . '/../../config/database.php';
$db = new Database();
$conn = $db->getConnection();
$clinic_name = getSetting('clinic_name', $conn);
$clinic_logo = getSetting('clinic_logo', $conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo $clinic_name ?? 'MOHI Namarei'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark text-white vh-100 position-fixed">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <img src="../<?php echo $clinic_logo; ?>" alt="Logo" height="60" class="rounded-circle bg-light p-1">
                    <h5 class="mt-2"><?php echo $clinic_name; ?></h5>
                    <hr class="bg-white">
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="users.php">
                            <i class="bi bi-people"></i> User Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="staff.php">
                            <i class="bi bi-person-badge"></i> Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="students.php">
                            <i class="bi bi-mortarboard"></i> Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="visits.php">
                            <i class="bi bi-calendar-check"></i> Visits
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="drugs.php">
                            <i class="bi bi-capsule"></i> Pharmacy
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="appointments.php">
                            <i class="bi bi-calendar-event"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="reports.php">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'ambulance.php' ? 'active' : '' ?>" href="ambulance.php">
        <i class="bi bi-truck"></i> Ambulance
    </a>
</li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="audit_logs.php">
                            <i class="bi bi-journal-text"></i> Audit Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="backup.php">
                            <i class="bi bi-database"></i> Backup
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="settings.php">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $page_title ?? 'Admin Panel'; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button class="btn btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-expanded="false" aria-controls="sidebar">
                        <i class="bi bi-list"></i> Menu
                    </button>
                </div>
            </div>