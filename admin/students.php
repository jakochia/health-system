<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) header("Location: ../index.php");

$page_title = 'Manage Students';
require_once '../includes/functions.php';
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_student'])) {
        $admission_no = $_POST['admission_no'];
        $full_name = $_POST['full_name'];
        $class = $_POST['class'];
        $parent_phone = $_POST['parent_phone'];
        $dob = $_POST['date_of_birth'];
        $allergies = $_POST['allergies'];

        $stmt = $conn->prepare("INSERT INTO students (admission_no, full_name, class, parent_phone, date_of_birth, allergies) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $admission_no, $full_name, $class, $parent_phone, $dob, $allergies);
        if ($stmt->execute()) {
            $success = "Student added.";
        } else {
            $error = "Error: " . $conn->error;
        }
    } elseif (isset($_POST['edit_student'])) {
        $id = $_POST['id'];
        $admission_no = $_POST['admission_no'];
        $full_name = $_POST['full_name'];
        $class = $_POST['class'];
        $parent_phone = $_POST['parent_phone'];
        $dob = $_POST['date_of_birth'];
        $allergies = $_POST['allergies'];

        $stmt = $conn->prepare("UPDATE students SET admission_no=?, full_name=?, class=?, parent_phone=?, date_of_birth=?, allergies=? WHERE id=?");
        $stmt->bind_param("ssssssi", $admission_no, $full_name, $class, $parent_phone, $dob, $allergies, $id);
        if ($stmt->execute()) {
            $success = "Student updated.";
        } else {
            $error = "Error: " . $conn->error;
        }
    } elseif (isset($_POST['delete_student'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Student deleted.";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

$students = $conn->query("SELECT * FROM students ORDER BY full_name");
?>

<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | Admin</title>
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
        .btn-kenya-success {
            background: var(--kenya-green);
            color: white;
        }
        .btn-kenya-success:hover {
            background: #006b31;
            box-shadow: 0 4px 12px rgba(0,132,61,0.3);
        }
        .btn-kenya-warning {
            background: #f59e0b;
            color: white;
        }
        .btn-kenya-warning:hover {
            background: #d97706;
            box-shadow: 0 4px 12px rgba(245,158,11,0.3);
        }
        .alert-modern {
            border-radius: 1rem;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-modern-success {
            background: #f0fdf4;
            border-left: 4px solid var(--kenya-green);
            color: #166534;
        }
        .alert-modern-danger {
            background: #fef2f2;
            border-left: 4px solid var(--kenya-red);
            color: #991b1b;
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
        hr {
            background: linear-gradient(90deg, var(--kenya-red), var(--kenya-green), var(--kenya-black));
            height: 2px;
            border: none;
            margin: 1.5rem 0;
        }
        .modal-modern .modal-content {
            border-radius: 1.25rem;
            border: none;
            box-shadow: var(--card-shadow-hover);
        }
        .modal-modern .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-bottom: none;
            border-radius: 1.25rem 1.25rem 0 0;
        }
        .modal-modern .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .modal-modern .modal-footer {
            border-top: none;
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
            <i class="fas fa-user-graduate"></i> Student Management
        </div>
        <div class="card-body p-4">
            <?php if (isset($success)): ?>
                <div class="alert-modern alert-modern-success">
                    <i class="fas fa-check-circle fs-5"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert-modern alert-modern-danger">
                    <i class="fas fa-exclamation-triangle fs-5"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- Add Student Form -->
            <h5 class="mb-3"><i class="fas fa-user-plus me-2 text-danger"></i> Add New Student</h5>
            <form method="POST" class="form-modern row g-3 mb-5">
                <div class="col-md-3">
                    <label class="form-label">Admission No</label>
                    <input type="text" name="admission_no" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Class</label>
                    <input type="text" name="class" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Parent Phone</label>
                    <input type="text" name="parent_phone" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Allergies</label>
                    <input type="text" name="allergies" class="form-control">
                </div>
                <div class="col-md-12">
                    <button type="submit" name="add_student" class="btn btn-kenya-success btn-modern">
                        <i class="fas fa-save me-1"></i> Add Student
                    </button>
                </div>
            </form>

            <hr>

            <!-- Existing Students Table -->
            <h5 class="mb-3"><i class="fas fa-list me-2 text-success"></i> Existing Students</h5>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>ID</th><th>Admission No</th><th>Full Name</th><th>Class</th><th>Parent Phone</th><th>DOB</th><th>Allergies</th><th>Actions</th>
                        </thead>
                    <tbody>
                        <?php while($row = $students->fetch_assoc()): ?>
                         <tr>
                            <td data-label="ID"><?= $row['id'] ?></td>
                            <td data-label="Admission No"><?= htmlspecialchars($row['admission_no']) ?></td>
                            <td data-label="Full Name"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td data-label="Class"><?= $row['class'] ?></td>
                            <td data-label="Parent Phone"><?= htmlspecialchars($row['parent_phone']) ?></td>
                            <td data-label="DOB"><?= $row['date_of_birth'] ?></td>
                            <td data-label="Allergies"><?= htmlspecialchars($row['allergies']) ?></td>
                            <td data-label="Actions">
                                <button class="btn btn-sm btn-kenya-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this student?');">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="delete_student" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </td>
                         </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade modal-modern" id="editModal<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Student</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Admission No</label>
                                                <input type="text" name="admission_no" class="form-control" value="<?= htmlspecialchars($row['admission_no']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($row['full_name']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Class</label>
                                                <input type="text" name="class" class="form-control" value="<?= $row['class'] ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Parent Phone</label>
                                                <input type="text" name="parent_phone" class="form-control" value="<?= htmlspecialchars($row['parent_phone']) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Date of Birth</label>
                                                <input type="date" name="date_of_birth" class="form-control" value="<?= $row['date_of_birth'] ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Allergies</label>
                                                <input type="text" name="allergies" class="form-control" value="<?= htmlspecialchars($row['allergies']) ?>">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="edit_student" class="btn btn-kenya-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>