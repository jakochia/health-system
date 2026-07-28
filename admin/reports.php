<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) header("Location: ../index.php");

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Fetch monthly visits
$monthly = $conn->query("SELECT DATE_FORMAT(visit_date, '%Y-%m') as month, COUNT(*) as total FROM visits GROUP BY month ORDER BY month DESC LIMIT 6");
$months = []; $counts = [];
while($row = $monthly->fetch_assoc()) {
    $months[] = $row['month'];
    $counts[] = $row['total'];
}
$months = array_reverse($months);
$counts = array_reverse($counts);

// Common diseases
$diseases = $conn->query("SELECT diagnosis, COUNT(*) as total FROM visits GROUP BY diagnosis ORDER BY total DESC LIMIT 5");
$diagNames = []; $diagCounts = [];
while($row = $diseases->fetch_assoc()) {
    $diagNames[] = $row['diagnosis'];
    $diagCounts[] = $row['total'];
}
?>
<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Reports | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --kenya-black: #000000;
            --kenya-red: #BB0000;
            --kenya-green: #00843D;
            --kenya-white: #FFFFFF;
            --primary-gradient: linear-gradient(135deg, var(--kenya-black) 0%, var(--kenya-red) 100%);
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --card-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #f1f5f9 0%, #eef2ff 100%);
        }
        .modern-card {
            background: white;
            border-radius: 1.5rem;
            border: none;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: var(--transition);
        }
        .modern-card:hover {
            box-shadow: var(--card-shadow-hover);
        }
        .card-header-modern {
            background: var(--primary-gradient);
            color: white;
            padding: 1.2rem 1.5rem;
            border-bottom: none;
            font-weight: 600;
        }
        .card-header-modern i {
            margin-right: 0.5rem;
        }
        .chart-container {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: var(--transition);
        }
        .chart-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        .form-modern .form-control, .form-modern .form-select {
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            padding: 0.6rem 1rem;
            transition: var(--transition);
        }
        .form-modern .form-control:focus, .form-modern .form-select:focus {
            border-color: var(--kenya-red);
            box-shadow: 0 0 0 3px rgba(187, 0, 0, 0.2);
            outline: none;
        }
        .form-modern .form-label {
            font-weight: 500;
            margin-bottom: 0.4rem;
            color: #1e293b;
        }
        .btn-modern {
            border-radius: 2rem;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: var(--transition);
            border: none;
        }
        .btn-modern:hover {
            transform: translateY(-2px);
        }
        .btn-kenya-primary {
            background: var(--kenya-black);
            color: white;
        }
        .btn-kenya-primary:hover {
            background: #1a1a1a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        hr {
            background: linear-gradient(90deg, var(--kenya-red), var(--kenya-green), var(--kenya-black));
            height: 2px;
            border: none;
            margin: 2rem 0;
        }
        @media (max-width: 768px) {
            .chart-container {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid px-4 py-4">
    <div class="modern-card">
        <div class="card-header-modern">
            <i class="fas fa-chart-line"></i> Health Reports
        </div>
        <div class="card-body p-4">
            <!-- Charts Row -->
            <div class="row g-4 mb-5">
                <div class="col-lg-6">
                    <div class="chart-container">
                        <h5 class="mb-3 fw-semibold"><i class="fas fa-calendar-alt me-2 text-danger"></i> Monthly Visit Trend</h5>
                        <canvas id="visitChart" height="250" style="max-height: 250px; width: 100%;"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="chart-container">
                        <h5 class="mb-3 fw-semibold"><i class="fas fa-stethoscope me-2 text-success"></i> Top 5 Diagnoses</h5>
                        <canvas id="diseaseChart" height="250" style="max-height: 250px; width: 100%;"></canvas>
                    </div>
                </div>
            </div>

            <hr>

            <!-- Export Form -->
            <h5 class="mb-3"><i class="fas fa-file-export me-2 text-primary"></i> Export Reports</h5>
            <form method="POST" action="export.php" class="form-modern row g-3">
                <div class="col-md-4">
                    <label class="form-label">Report Type</label>
                    <select name="type" class="form-select">
                        <option value="visits">Visits Report</option>
                        <option value="students">Students List</option>
                        <option value="drugs">Drug Inventory</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Format</label>
                    <select name="format" class="form-select">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-kenya-primary btn-modern w-100">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Visit trend chart (line with Kenyan green)
    new Chart(document.getElementById('visitChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: 'Visits',
                data: <?= json_encode($counts) ?>,
                borderColor: '#00843D',
                backgroundColor: 'rgba(0, 132, 61, 0.05)',
                borderWidth: 2,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#00843D',
                pointBorderColor: '#fff',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: { backgroundColor: '#1e293b', titleColor: '#f1f5f9', bodyColor: '#cbd5e1', padding: 10, cornerRadius: 8 }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#e2e8f0' },
                    ticks: { stepSize: 1, precision: 0 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Disease chart (bar with Kenyan red)
    new Chart(document.getElementById('diseaseChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($diagNames) ?>,
            datasets: [{
                label: 'Cases',
                data: <?= json_encode($diagCounts) ?>,
                backgroundColor: '#BB0000',
                borderRadius: 8,
                barPercentage: 0.7,
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
                    grid: { color: '#e2e8f0' },
                    ticks: { stepSize: 1, precision: 0 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>