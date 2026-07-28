<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) header("Location: ../index.php");

$page_title = 'Dashboard';
require_once '../includes/functions.php';
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Stats
$totalPatients = $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$totalVisits = $conn->query("SELECT COUNT(*) FROM visits")->fetch_row()[0];
$lowStock = $conn->query("SELECT COUNT(*) FROM drugs WHERE quantity <= reorder_level")->fetch_row()[0];
$totalStaff = $conn->query("SELECT COUNT(*) FROM staff")->fetch_row()[0];

// Chart data: visits per day for the last 7 days
$visitsChart = ['labels' => [], 'data' => []];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = $conn->query("SELECT COUNT(*) FROM visits WHERE DATE(visit_date) = '$date'")->fetch_row()[0];
    $visitsChart['labels'][] = date('D', strtotime($date));
    $visitsChart['data'][] = $count;
}
?>
<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | MOHI Namarei</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0f4c5c;
            --primary-dark: #0a3a46;
            --secondary: #e56b6f;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
            --card-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #f1f5f9 0%, #eef2ff 100%);
        }
        /* Stats cards */
        .stat-card {
            background: white;
            border-radius: 1.25rem;
            border: none;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            overflow: hidden;
            position: relative;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 1rem;
            background: rgba(15, 76, 92, 0.1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
        }
        /* Modern card */
        .modern-card {
            background: white;
            border-radius: 1.25rem;
            border: none;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            overflow: hidden;
        }
        .modern-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-shadow-hover);
        }
        .card-header-modern {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-bottom: none;
            font-weight: 600;
        }
        .card-header-modern i {
            margin-right: 0.5rem;
        }
        /* Table styling */
        .table-modern {
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .table-modern tbody tr {
            background-color: #f9fafb;
            border-radius: 0.75rem;
            transition: var(--transition);
        }
        .table-modern tbody tr:hover {
            background-color: #f1f5f9;
            transform: scale(1.01);
        }
        .table-modern td, .table-modern th {
            border: none;
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }
        .table-modern th {
            font-weight: 600;
            color: #475569;
        }
        .badge-diagnosis {
            background: #eef2ff;
            color: #4338ca;
            border-radius: 100px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        /* Quick action buttons */
        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            font-weight: 500;
            transition: var(--transition);
            background: white;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            text-decoration: none;
        }
        .quick-action-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(15, 76, 92, 0.2);
        }
        .quick-action-btn i {
            font-size: 1rem;
        }
        /* Chart container */
        .chart-container {
            padding: 1rem;
        }
        canvas {
            max-height: 250px;
            width: 100%;
        }
        /* Responsive */
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 1rem;
            }
            .quick-action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid px-4 py-4">
    <!-- Welcome row -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h1 class="display-6 fw-bold">Admin Dashboard</h1>
            <p class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?> 👋</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                <i class="fas fa-calendar-alt me-1"></i> <?= date('l, F j, Y') ?>
            </span>
        </div>
    </div>

    <!-- Stats row -->
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-xl-3">
            <div class="stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-uppercase small fw-semibold text-muted">Total Students</span>
                        <h2 class="mb-0 fw-bold mt-2"><?= $totalPatients ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <i class="fas fa-chart-line me-1"></i> Enrolled in system
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-uppercase small fw-semibold text-muted">Total Visits</span>
                        <h2 class="mb-0 fw-bold mt-2"><?= $totalVisits ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <i class="fas fa-calendar-check me-1"></i> All-time clinic visits
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-uppercase small fw-semibold text-muted">Low Stock Alerts</span>
                        <h2 class="mb-0 fw-bold mt-2 <?= $lowStock > 0 ? 'text-danger' : '' ?>"><?= $lowStock ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-pills"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <i class="fas fa-exclamation-triangle me-1"></i> Medications below reorder level
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-uppercase small fw-semibold text-muted">Staff Members</span>
                        <h2 class="mb-0 fw-bold mt-2"><?= $totalStaff ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <i class="fas fa-briefcase me-1"></i> Active staff
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <!-- Chart Column -->
        <div class="col-lg-6">
            <div class="modern-card">
                <div class="card-header-modern">
                    <i class="fas fa-chart-line"></i> Weekly Visit Trend
                </div>
                <div class="chart-container">
                    <canvas id="visitsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Visits Column -->
        <div class="col-lg-6">
            <div class="modern-card">
                <div class="card-header-modern">
                    <i class="fas fa-clock"></i> Recent Visits
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr><th>Student</th><th>Date</th><th>Diagnosis</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $conn->query("SELECT s.full_name, v.visit_date, v.diagnosis FROM visits v JOIN students s ON v.student_id = s.id ORDER BY v.visit_date DESC LIMIT 5");
                                while($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><span class="text-muted small"><?= date('M d, Y', strtotime($row['visit_date'])) ?></span></td>
                                    <td><span class="badge-diagnosis"><?= htmlspecialchars($row['diagnosis'] ?: 'N/A') ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($result->num_rows == 0): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">No visits recorded yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-2">
                        <a href="visits.php" class="text-decoration-none small fw-semibold">View all <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="modern-card">
        <div class="card-header-modern">
            <i class="fas fa-bolt"></i> Quick Actions
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <a href="users.php" class="quick-action-btn d-flex align-items-center justify-content-center">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="reports.php" class="quick-action-btn d-flex align-items-center justify-content-center">
                        <i class="fas fa-chart-bar"></i> Generate Reports
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="backup.php" class="quick-action-btn d-flex align-items-center justify-content-center">
                        <i class="fas fa-database"></i> Backup DB
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="settings.php" class="quick-action-btn d-flex align-items-center justify-content-center">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('visitsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($visitsChart['labels']) ?>,
            datasets: [{
                label: 'Visits',
                data: <?= json_encode($visitsChart['data']) ?>,
                borderColor: '#0f4c5c',
                backgroundColor: 'rgba(15, 76, 92, 0.1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#e56b6f',
                pointBorderColor: '#fff',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#1e293b', titleColor: '#f1f5f9', bodyColor: '#cbd5e1', padding: 10, cornerRadius: 8 }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#e2e8f0', drawBorder: false },
                    ticks: { stepSize: 1, precision: 0 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>