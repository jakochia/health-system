<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('student')) header("Location: ../index.php");

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$userId = $_SESSION['user_id'];

// Fetch student record
$student = $conn->query("SELECT s.* FROM students s 
                         JOIN users u ON u.student_id = s.id 
                         WHERE u.id = $userId")->fetch_assoc();

if (!$student) {
    die("Student record not found. Please contact the administrator.");
}

$studentId = $student['id'];
$visits = $conn->query("SELECT * FROM visits WHERE student_id = $studentId ORDER BY visit_date DESC");
$totalVisits = $visits->num_rows;

// Last visit date
$lastVisit = $conn->query("SELECT MAX(visit_date) as last_date FROM visits WHERE student_id = $studentId")->fetch_assoc();
$lastVisitDate = $lastVisit['last_date'] ? date('M d, Y', strtotime($lastVisit['last_date'])) : 'No visits yet';

// Monthly chart data
$monthlyData = [];
if ($totalVisits > 0) {
    $monthlyQuery = $conn->query("SELECT DATE_FORMAT(visit_date, '%Y-%m') as month, COUNT(*) as count FROM visits WHERE student_id = $studentId GROUP BY month ORDER BY month DESC LIMIT 6");
    while ($row = $monthlyQuery->fetch_assoc()) {
        $monthlyData[$row['month']] = $row['count'];
    }
    $months = array_keys($monthlyData);
    $counts = array_values($monthlyData);
}
?>
<?php include '../includes/header.php'; ?>

<!-- Modern styling with Kenyan flag colors -->
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
        --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --transition: all 0.2s ease;
    }
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
    }
    .dashboard-card {
        border: none;
        border-radius: 1.25rem;
        box-shadow: var(--card-shadow);
        transition: var(--transition);
        overflow: hidden;
    }
    .student-header {
        background: var(--gradient-primary);
        color: white;
        border-radius: 1.5rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--card-shadow);
    }
    .stat-badge {
        background: rgba(255,255,255,0.2);
        border-radius: 2rem;
        padding: 0.35rem 1rem;
        font-size: 0.85rem;
        font-weight: 500;
        backdrop-filter: blur(4px);
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
        border-bottom: 2px solid #e2e8f0;
    }
    .badge-diagnosis {
        background: #eef2ff;
        color: #4338ca;
        border-radius: 100px;
        padding: 0.35rem 0.85rem;
        font-size: 0.75rem;
        font-weight: 500;
        display: inline-block;
    }
    .prescription-item {
        background: #f8fafc;
        border-radius: 0.5rem;
        padding: 0.3rem 0.6rem;
        margin-bottom: 0.25rem;
        font-size: 0.8rem;
        display: inline-block;
        margin-right: 0.3rem;
    }
    .referral-badge {
        background: #fff3e0;
        color: #c2410c;
        border-radius: 100px;
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .empty-state {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 1.25rem;
    }
    .section-title {
        font-weight: 700;
        font-size: 1.25rem;
        margin-bottom: 1rem;
        position: relative;
        display: inline-block;
    }
    .section-title:after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 40px;
        height: 3px;
        background: var(--gradient-primary);
        border-radius: 3px;
    }
    .btn-back {
        border-radius: 2rem;
        padding: 0.5rem 1.25rem;
        font-weight: 500;
        background: white;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    .btn-back:hover {
        background: #f1f5f9;
        transform: translateY(-2px);
    }
    .chart-container {
        background: white;
        border-radius: 1.25rem;
        padding: 1rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 1.5rem;
    }
</style>

<div class="container-fluid px-4 py-4">
    <!-- Back button and header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="dashboard.php" class="btn-back btn btn-sm"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
        <div class="text-muted small"><i class="fas fa-clock me-1"></i> Last updated: <?= date('M d, Y') ?></div>
    </div>

    <!-- Student Profile Card with Kenyan gradient -->
    <div class="student-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold mb-1"><?= htmlspecialchars($student['full_name']) ?></h2>
                <p class="mb-2 opacity-75">Admission No: <?= htmlspecialchars($student['admission_no']) ?> | Class: <?= htmlspecialchars($student['class'] ?? 'Not set') ?></p>
                <div class="d-flex gap-3 mt-2">
                    <span class="stat-badge"><i class="fas fa-stethoscope me-1"></i> Total Visits: <?= $totalVisits ?></span>
                    <span class="stat-badge"><i class="fas fa-calendar-alt me-1"></i> Last Visit: <?= $lastVisitDate ?></span>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <i class="fas fa-user-md fa-3x opacity-50"></i>
            </div>
        </div>
    </div>

    <?php if ($totalVisits > 0): ?>
        <!-- Optional chart -->
        <?php if (!empty($months)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="section-title mb-0">Visit Trend (Last 6 Months)</h5>
                        <span class="badge bg-light text-dark rounded-pill">Monthly count</span>
                    </div>
                    <canvas id="visitsChart" height="200" style="max-height: 200px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Health History Table -->
        <div class="dashboard-card p-3">
            <h5 class="section-title">Complete Medical Record</h5>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr><th>Date</th><th>Symptoms</th><th>Diagnosis</th><th>Treatment</th><th>Prescriptions</th><th>Referral</th></tr>
                    </thead>
                    <tbody>
                        <?php while($v = $visits->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-semibold"><?= date('M d, Y', strtotime($v['visit_date'])) ?></td>
                            <td><?= htmlspecialchars($v['symptoms']) ?></td>
                            <td><span class="badge-diagnosis"><?= htmlspecialchars($v['diagnosis'] ?: 'Not specified') ?></span></td>
                            <td><?= htmlspecialchars($v['treatment'] ?: 'None') ?></td>
                            <td>
                                <?php
                                $pres = $conn->query("SELECT drug_name, dosage, duration FROM prescriptions WHERE visit_id = {$v['id']}");
                                if ($pres && $pres->num_rows > 0) {
                                    while($p = $pres->fetch_assoc()) {
                                        echo '<div class="prescription-item">' . htmlspecialchars($p['drug_name']) . ' (' . htmlspecialchars($p['dosage']) . ') - ' . htmlspecialchars($p['duration']) . '</div>';
                                    }
                                } else {
                                    echo '<span class="text-muted">—</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($v['referred']): ?>
                                    <span class="referral-badge"><i class="fas fa-hospital-user me-1"></i> <?= htmlspecialchars($v['referral_hospital']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <!-- Empty state -->
        <div class="empty-state">
            <i class="fas fa-notes-medical fa-4x text-muted mb-3"></i>
            <h4 class="fw-semibold">No Health Records Yet</h4>
            <p class="text-muted">Your clinic visits will appear here once you visit the school clinic.</p>
            <a href="dashboard.php" class="btn btn-primary rounded-pill px-4 mt-2" style="background: var(--kenya-green); border: none;">Go to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($totalVisits > 0 && !empty($months)): ?>
<script>
    const ctx = document.getElementById('visitsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_reverse($months)) ?>,
            datasets: [{
                label: 'Visits',
                data: <?= json_encode(array_reverse($counts)) ?>,
                borderColor: '#BB0000',
                backgroundColor: 'rgba(187, 0, 0, 0.1)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#BB0000',
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
                tooltip: { backgroundColor: '#1e293b', titleColor: '#f1f5f9', bodyColor: '#cbd5e1' }
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
<?php endif; ?>

<?php include '../includes/footer.php'; ?>