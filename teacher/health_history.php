<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('teacher')) header("Location: ../index.php");

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$search = $_GET['search'] ?? '';
$student_id = $_GET['student_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $search = $_POST['search'];
    $students = $conn->query("SELECT id, full_name, admission_no FROM students WHERE full_name LIKE '%$search%' OR admission_no LIKE '%$search%'");
} else {
    $students = $conn->query("SELECT id, full_name, admission_no FROM students ORDER BY full_name");
}

$visits = null;
if ($student_id) {
    $visits = $conn->query("SELECT v.*, s.full_name FROM visits v JOIN students s ON v.student_id = s.id WHERE v.student_id = $student_id ORDER BY v.visit_date DESC");
}
?>
<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Health History | MOHI Namarei</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #0f4c5c;
            --primary-light: #1e6f82;
            --accent: #e56b6f;
            --gray-light: #f8f9fa;
        }
        body {
            background: linear-gradient(145deg, #eef2f7 0%, #d9e2ec 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, 'Inter', sans-serif;
        }
        .card-modern {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        .card-header-modern {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            padding: 1.2rem 1.5rem;
            color: white;
            border-bottom: none;
        }
        .card-header-modern h4 {
            margin: 0;
            font-weight: 600;
            letter-spacing: -0.3px;
        }
        .card-header-modern h4 i {
            margin-right: 8px;
        }
        .search-container {
            background: white;
            border-radius: 2rem;
            padding: 0.25rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .search-container input {
            border: none;
            border-radius: 2rem 0 0 2rem;
            padding: 0.7rem 1.2rem;
            font-size: 0.95rem;
        }
        .search-container input:focus {
            box-shadow: none;
            outline: none;
        }
        .search-container button {
            border-radius: 0 2rem 2rem 0;
            padding: 0.7rem 1.5rem;
            background: var(--primary);
            border: none;
            color: white;
            transition: background 0.2s;
        }
        .search-container button:hover {
            background: var(--accent);
        }
        .student-list {
            max-height: 70vh;
            overflow-y: auto;
            border-radius: 1rem;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .student-item {
            transition: all 0.2s;
            border-left: 4px solid transparent;
            padding: 0.8rem 1rem;
            cursor: pointer;
        }
        .student-item:hover {
            background-color: var(--gray-light);
            transform: translateX(3px);
        }
        .student-item.active {
            background: linear-gradient(95deg, rgba(15,76,92,0.1), rgba(229,107,111,0.1));
            border-left-color: var(--accent);
        }
        .student-name {
            font-weight: 600;
            color: var(--primary);
        }
        .student-admission {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .visit-card {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
            border: 1px solid #eef2f7;
        }
        .visit-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .visit-date {
            font-size: 0.85rem;
            color: var(--accent);
            font-weight: 500;
        }
        .badge-referred {
            background-color: #ffc107;
            color: #856404;
        }
        .badge-not-referred {
            background-color: #e9ecef;
            color: #495057;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 1rem;
        }
        @media (max-width: 768px) {
            .student-list {
                max-height: 40vh;
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="card card-modern">
        <div class="card-header-modern">
            <h4><i class="fas fa-notes-medical"></i> Student Health History</h4>
        </div>
        <div class="card-body p-4 p-lg-5">
            <!-- Search Form -->
            <form method="POST" class="mb-4">
                <div class="search-container d-flex">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or admission number..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn"><i class="fas fa-search me-1"></i> Search</button>
                </div>
            </form>

            <div class="row g-4">
                <!-- Students List Column -->
                <div class="col-md-4">
                    <div class="student-list">
                        <?php if($students->num_rows > 0): ?>
                            <?php while($row = $students->fetch_assoc()): ?>
                                <a href="?student_id=<?= $row['id'] ?>" class="student-item <?= ($student_id == $row['id']) ? 'active' : '' ?> d-block text-decoration-none">
                                    <div class="student-name">
                                        <i class="fas fa-user-circle me-2 text-secondary"></i>
                                        <?= htmlspecialchars($row['full_name']) ?>
                                    </div>
                                    <div class="student-admission">
                                        <i class="fas fa-id-card me-1"></i> <?= $row['admission_no'] ?>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center p-4 text-muted">
                                <i class="fas fa-user-slash fa-2x mb-2"></i>
                                <p>No students found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Visits Column -->
                <div class="col-md-8">
                    <?php if($student_id): ?>
                        <?php if($visits && $visits->num_rows > 0): ?>
                            <h5 class="mb-3">
                                <i class="fas fa-calendar-alt me-2 text-accent"></i> Visit Records
                                <span class="badge bg-primary rounded-pill ms-2"><?= $visits->num_rows ?> visits</span>
                            </h5>
                            <?php while($v = $visits->fetch_assoc()): ?>
                                <div class="visit-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="visit-date">
                                            <i class="far fa-calendar-alt me-1"></i> <?= date('d M Y, H:i', strtotime($v['visit_date'])) ?>
                                        </div>
                                        <div>
                                            <?php if($v['referred']): ?>
                                                <span class="badge badge-referred"><i class="fas fa-ambulance me-1"></i> Referred: <?= htmlspecialchars($v['referral_hospital']) ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-not-referred"><i class="fas fa-check-circle me-1"></i> Treated on site</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <strong><i class="fas fa-head-side-medical me-1 text-muted"></i> Symptoms:</strong>
                                            <p class="mb-1"><?= nl2br(htmlspecialchars($v['symptoms'])) ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <strong><i class="fas fa-stethoscope me-1 text-muted"></i> Diagnosis:</strong>
                                            <p class="mb-1"><?= htmlspecialchars($v['diagnosis']) ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <strong><i class="fas fa-prescription-bottle me-1 text-muted"></i> Treatment:</strong>
                                            <p class="mb-1"><?= nl2br(htmlspecialchars($v['treatment'])) ?></p>
                                        </div>
                                    </div>
                                    <!-- Optional: show prescriptions if you have them in the query? -->
                                    <!-- For now, we show just the basic info -->
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-notes-medical fa-3x text-muted mb-3"></i>
                                <h5>No visits recorded</h5>
                                <p class="text-muted">This student hasn't had any medical visits yet.</p>
                                <a href="new_visit.php?student_id=<?= $student_id ?>" class="btn btn-modern mt-2">Record a visit</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                            <h5>Select a student</h5>
                            <p class="text-muted">Choose a student from the list to view their health history.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-modern {
        background: linear-gradient(95deg, var(--primary), var(--accent));
        border: none;
        border-radius: 2rem;
        padding: 0.6rem 1.5rem;
        font-weight: 500;
        color: white;
        transition: all 0.3s;
    }
    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(229,107,111,0.3);
        color: white;
    }
    .text-accent {
        color: var(--accent);
    }
</style>

<script>
    // Optional: add a smooth scroll to the visits section when a student is selected
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.search.includes('student_id')) {
            document.querySelector('.col-md-8').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
</script>
</body>
</html>
<?php include '../includes/footer.php'; ?>