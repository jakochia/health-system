<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) header("Location: ../index.php");

$page_title = 'All Visits';
require_once '../includes/functions.php';
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$filter_student = isset($_GET['student']) ? intval($_GET['student']) : 0;
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

$sql = "SELECT v.*, s.full_name as student_name, st.full_name as clinician_name 
        FROM visits v 
        JOIN students s ON v.student_id = s.id 
        LEFT JOIN staff st ON v.clinician_id = st.id 
        WHERE 1=1";
$params = [];
$types = "";

if ($filter_student) {
    $sql .= " AND v.student_id = ?";
    $params[] = $filter_student;
    $types .= "i";
}
if ($filter_date) {
    $sql .= " AND DATE(v.visit_date) = ?";
    $params[] = $filter_date;
    $types .= "s";
}
$sql .= " ORDER BY v.visit_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$visits = $stmt->get_result();

$students = $conn->query("SELECT id, full_name FROM students ORDER BY full_name");
?>
<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Visits | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .btn-kenya-secondary {
            background: var(--kenya-green);
            color: white;
        }
        .btn-kenya-secondary:hover {
            background: #006b31;
            box-shadow: 0 4px 12px rgba(0,132,61,0.3);
        }
        .table-modern {
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .table-modern thead th {
            background: transparent;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 0.75rem;
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
            padding: 0.75rem;
            vertical-align: middle;
        }
        .badge-referred {
            background-color: #fef3c7;
            color: #92400e;
            border-radius: 100px;
            padding: 0.25rem 0.75rem;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-not-referred {
            background-color: #e2e8f0;
            color: #475569;
            border-radius: 100px;
            padding: 0.25rem 0.75rem;
            font-size: 0.7rem;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .table-modern thead {
                display: none;
            }
            .table-modern, .table-modern tbody, .table-modern tr, .table-modern td {
                display: block;
                width: 100%;
            }
            .table-modern tr {
                margin-bottom: 1rem;
                background: white;
                border-radius: 1rem;
                padding: 0.5rem;
            }
            .table-modern td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem;
                border-bottom: 1px solid #eef2f7;
            }
            .table-modern td:before {
                content: attr(data-label);
                font-weight: 600;
                color: #475569;
                margin-right: 1rem;
            }
            .table-modern td:last-child {
                border-bottom: none;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid px-4 py-4">
    <div class="modern-card">
        <div class="card-header-modern">
            <i class="fas fa-notes-medical"></i> Visit Records
        </div>
        <div class="card-body p-4">
            <!-- Filter Form -->
            <form method="GET" class="form-modern row g-3 mb-5">
                <div class="col-md-4">
                    <label class="form-label">Filter by Student</label>
                    <select name="student" class="form-select">
                        <option value="0">All Students</option>
                        <?php while($s = $students->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>" <?= ($filter_student == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['full_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter by Date</label>
                    <input type="date" name="date" class="form-control" value="<?= $filter_date ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-kenya-primary btn-modern">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <a href="visits.php" class="btn btn-kenya-secondary btn-modern">
                        <i class="fas fa-undo-alt me-1"></i> Reset
                    </a>
                </div>
            </form>

            <!-- Visits Table -->
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Date</th><th>Student</th><th>Clinician</th><th>Symptoms</th><th>Diagnosis</th><th>Treatment</th><th>Referral</th>
                        </thead>
                    <tbody>
                        <?php if ($visits->num_rows > 0): ?>
                            <?php while($v = $visits->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Date"><?= date('M d, Y H:i', strtotime($v['visit_date'])) ?></td>
                                <td data-label="Student"><?= htmlspecialchars($v['student_name']) ?></td>
                                <td data-label="Clinician"><?= htmlspecialchars($v['clinician_name'] ?? 'N/A') ?></td>
                                <td data-label="Symptoms"><?= htmlspecialchars($v['symptoms']) ?></td>
                                <td data-label="Diagnosis"><?= htmlspecialchars($v['diagnosis']) ?></td>
                                <td data-label="Treatment"><?= htmlspecialchars($v['treatment']) ?></td>
                                <td data-label="Referral">
                                    <?php if ($v['referred']): ?>
                                        <span class="badge-referred">
                                            <i class="fas fa-ambulance me-1"></i> <?= htmlspecialchars($v['referral_hospital']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-not-referred">
                                            <i class="fas fa-check-circle me-1"></i> No
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle me-2"></i> No visits found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>