<?php
require_once '../includes/header.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Notifications (same as before)
$notifications = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id AND is_read = 0 ORDER BY created_at DESC");
if ($notifications && $notifications->num_rows > 0) {
    echo '<div class="alert alert-kenya alert-dismissible fade show shadow-lg rounded-4" role="alert">';
    echo '<div class="d-flex align-items-start">';
    echo '<i class="fas fa-bell me-3 fs-4"></i>';
    echo '<div class="flex-grow-1">';
    echo '<strong class="fs-6">Notifications</strong><br>';
    while ($notif = $notifications->fetch_assoc()) {
        echo '<p class="mb-1 small">' . htmlspecialchars($notif['message']) . ' <span class="text-muted">(' . date('M d, H:i', strtotime($notif['created_at'])) . ')</span></p>';
        $conn->query("UPDATE notifications SET is_read = 1 WHERE id = " . $notif['id']);
    }
    echo '</div>';
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div></div>';
}

// Stats Queries
$totalVisitsToday = $conn->query("SELECT COUNT(*) FROM visits WHERE DATE(visit_date) = CURDATE()")->fetch_row()[0];
$totalStudents = $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$lowStock = $conn->query("SELECT COUNT(*) FROM drugs WHERE quantity <= reorder_level")->fetch_row()[0];
$recentVisits = $conn->query("SELECT v.*, s.full_name FROM visits v JOIN students s ON v.student_id = s.id ORDER BY v.visit_date DESC LIMIT 5");

// Chart data
$visitsChart = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = $conn->query("SELECT COUNT(*) FROM visits WHERE DATE(visit_date) = '$date'")->fetch_row()[0];
    $visitsChart['labels'][] = date('D', strtotime($date));
    $visitsChart['data'][] = $count;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | MOHI Namarei Health System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --kenya-black: #000000;
            --kenya-red: #BB0000;
            --kenya-green: #00843D;
            --kenya-white: #FFFFFF;
            --gradient-primary: linear-gradient(135deg, var(--kenya-black) 0%, var(--kenya-red) 100%);
            --gradient-secondary: linear-gradient(135deg, var(--kenya-green) 0%, var(--kenya-black) 100%);
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.2s ease;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #fef9f4 0%, #fff5f0 100%);
            position: relative;
        }
        /* Kenyan flag stripe at top */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, var(--kenya-black) 0%, var(--kenya-black) 33%, var(--kenya-red) 33%, var(--kenya-red) 66%, var(--kenya-green) 66%, var(--kenya-green) 100%);
            z-index: 1000;
        }
        .dashboard-card {
            border: none;
            border-radius: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(2px);
        }
        .dashboard-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .stat-card {
            background: white;
            border-radius: 1.25rem;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--kenya-red);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -10px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
            transition: var(--transition);
        }
        .stat-card:hover .stat-icon {
            transform: scale(1.05);
            opacity: 1;
        }
        .alert-kenya {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe6e6 100%);
            border-left: 5px solid var(--kenya-red);
            border-radius: 1rem;
            color: #1e293b;
        }
        .btn-modern {
            border-radius: 2rem;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: var(--transition);
        }
        .btn-modern:hover {
            transform: translateY(-2px);
        }
        .btn-kenya-primary {
            background: var(--kenya-black);
            color: white;
            border: none;
        }
        .btn-kenya-primary:hover {
            background: #333;
        }
        .btn-kenya-secondary {
            background: var(--kenya-green);
            color: white;
            border: none;
        }
        .quick-action-btn {
            border-radius: 2rem;
            padding: 0.6rem 1rem;
            font-weight: 500;
            background: white;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .quick-action-btn:hover {
            background: #f1f5f9;
            border-color: var(--kenya-red);
            transform: translateY(-2px);
            color: var(--kenya-red);
        }
        .table-modern {
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .table-modern tbody tr {
            background-color: white;
            border-radius: 1rem;
            transition: var(--transition);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .table-modern tbody tr:hover {
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .table-modern td, .table-modern th {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }
        .table-modern th {
            background: transparent;
            font-weight: 600;
            color: #475569;
        }
        .badge-diagnosis {
            background: #eef2ff;
            color: #4338ca;
            border-radius: 100px;
            padding: 0.35rem 0.85rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .section-title {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
            padding-bottom: 0.5rem;
        }
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--kenya-red), var(--kenya-green));
            border-radius: 3px;
        }
        .chart-container {
            background: white;
            border-radius: 1.25rem;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }
        .chart-container:hover {
            transform: translateY(-4px);
        }
        .kenya-badge {
            background: linear-gradient(135deg, var(--kenya-black), var(--kenya-red));
            color: white;
            border-radius: 100px;
            padding: 0.2rem 0.8rem;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .flag-stripe {
            background: linear-gradient(90deg, var(--kenya-black), var(--kenya-red), var(--kenya-green));
            height: 4px;
            width: 100%;
        }
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 1rem;
            }
            .table-modern td, .table-modern th {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid px-4 py-4">
    <!-- Header with welcome and date -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="display-6 fw-bold mb-0">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h1>
            <p class="text-muted mt-1">Here's what's happening with your health center today.</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <div class="kenya-badge"><i class="fas fa-flag-checkered me-1"></i> MOHI Namarei</div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-4 col-xl-3">
            <div class="stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-uppercase small fw-semibold text-muted">Today's Visits</span>
                        <h2 class="mb-0 fw-bold mt-2"><?= $totalVisitsToday ?></h2>
                    </div>
                    <div class="stat-icon text-danger">
                        <i class="fas fa-calendar-check fa-fw"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <i class="fas fa-chart-line me-1"></i> <?= date('l') ?>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl-3">
            <div class="stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-uppercase small fw-semibold text-muted">Total Students</span>
                        <h2 class="mb-0 fw-bold mt-2"><?= $totalStudents ?></h2>
                    </div>
                    <div class="stat-icon text-success">
                        <i class="fas fa-users fa-fw"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <i class="fas fa-graduation-cap me-1"></i> Enrolled
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl-3">
            <div class="stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-uppercase small fw-semibold text-muted">Low Stock Alerts</span>
                        <h2 class="mb-0 fw-bold mt-2 <?= $lowStock > 0 ? 'text-danger' : '' ?>"><?= $lowStock ?></h2>
                    </div>
                    <div class="stat-icon text-dark">
                        <i class="fas fa-pills fa-fw"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <i class="fas fa-exclamation-triangle me-1"></i> Medications below reorder level
                </div>
            </div>
        </div>
        <div class="col-md-12 col-xl-3">
            <div class="stat-card p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <span class="text-uppercase small fw-semibold text-muted">Quick Actions</span>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <a href="register_visit.php" class="quick-action-btn"><i class="fas fa-plus-circle"></i> Register Visit</a>
                        <a href="health_history.php" class="quick-action-btn"><i class="fas fa-history"></i> Health History</a>
                        <a href="drug_inventory.php" class="quick-action-btn"><i class="fas fa-capsules"></i> Inventory</a>
                        <a href="reports.php" class="quick-action-btn"><i class="fas fa-chart-bar"></i> Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart + Recent Visits Row -->
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="chart-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="section-title mb-0">Weekly Visit Trend</h5>
                    <span class="badge bg-light text-dark rounded-pill">Last 7 days</span>
                </div>
                <canvas id="visitsChart" height="250" style="max-height: 250px; width: 100%;"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="dashboard-card p-4 h-100">
                <h5 class="section-title">Recent Clinic Visits</h5>
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr><th>Student</th><th>Date</th><th>Symptoms</th><th>Diagnosis</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($recentVisits && $recentVisits->num_rows > 0): ?>
                                <?php while($v = $recentVisits->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($v['full_name']) ?></td>
                                    <td><span class="text-muted small"><?= date('M d, Y', strtotime($v['visit_date'])) ?></span></td>
                                    <td class="text-truncate" style="max-width: 150px;"><?= htmlspecialchars($v['symptoms']) ?></td>
                                    <td><span class="badge-diagnosis"><?= htmlspecialchars($v['diagnosis'] ?: 'N/A') ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No recent visits recorded</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end mt-3">
                    <a href="visits_list.php" class="text-decoration-none fw-semibold small">View all <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Low stock warning (Kenya colors) -->
    <?php if ($lowStock > 0): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-warning d-flex align-items-center shadow-sm border-0 rounded-4" style="background: #fff3e0; border-left: 5px solid var(--kenya-red);">
                <i class="fas fa-exclamation-triangle me-3 fs-4 text-danger"></i>
                <div class="flex-grow-1">
                    <strong class="fw-bold">Inventory Alert</strong><br>
                    <?= $lowStock ?> medication(s) are running low. Please check the drug inventory and reorder soon.
                </div>
                <a href="drug_inventory.php" class="btn btn-sm ms-auto px-3 rounded-pill" style="background: var(--kenya-green); color: white;">Review Stock</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    const ctx = document.getElementById('visitsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($visitsChart['labels']) ?>,
            datasets: [{
                label: 'Number of Visits',
                data: <?= json_encode($visitsChart['data']) ?>,
                backgroundColor: 'rgba(187, 0, 0, 0.7)',
                borderColor: '#BB0000',
                borderWidth: 1,
                borderRadius: 8,
                barPercentage: 0.6,
                categoryPercentage: 0.8
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
                    grid: { display: false },
                    ticks: { font: { weight: 500 } }
                }
            },
            layout: { padding: { top: 10, bottom: 5 } }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>