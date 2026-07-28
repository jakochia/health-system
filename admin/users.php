<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $email = $_POST['email'] ?? '';

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $password, $role, $email);
        $stmt->execute();
        $userId = $stmt->insert_id;

        if ($role == 'teacher') {
            $staff_id = $_POST['staff_id'];
            $full_name = $_POST['full_name'];
            $phone = $_POST['phone'];
            $stmt2 = $conn->prepare("INSERT INTO staff (user_id, staff_id, full_name, role, phone) VALUES (?, ?, ?, 'teacher', ?)");
            $stmt2->bind_param("isss", $userId, $staff_id, $full_name, $phone);
            $stmt2->execute();
        } elseif ($role == 'student') {
            $admission_no = $_POST['admission_no'];
            $full_name = $_POST['full_name'];
            $class = $_POST['class'];
            $parent_phone = $_POST['parent_phone'];
            $dob = $_POST['dob'];
            $stmt2 = $conn->prepare("INSERT INTO students (admission_no, full_name, class, parent_phone, date_of_birth) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("sssss", $admission_no, $full_name, $class, $parent_phone, $dob);
            $stmt2->execute();
            $studentId = $stmt2->insert_id;
            // Link user to student
            $conn->query("UPDATE users SET student_id = $studentId WHERE id = $userId");
        }
        $conn->commit();
        $success = "User created successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch existing users
$users = $conn->query("SELECT u.id, u.username, u.role, u.email, s.full_name as student_name, st.full_name as staff_name FROM users u LEFT JOIN students s ON u.student_id = s.id LEFT JOIN staff st ON u.id = st.user_id");
?>
<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Admin</title>
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
            background: var(--kenya-red);
            color: white;
        }
        .btn-kenya-warning:hover {
            background: #990000;
            box-shadow: 0 4px 12px rgba(187,0,0,0.3);
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
        .badge-role {
            border-radius: 100px;
            padding: 0.25rem 0.75rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-role.admin {
            background: #1e293b;
            color: white;
        }
        .badge-role.teacher {
            background: #0f4c5c;
            color: white;
        }
        .badge-role.student {
            background: var(--kenya-green);
            color: white;
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
        hr {
            background: linear-gradient(90deg, var(--kenya-red), var(--kenya-green), var(--kenya-black));
            height: 2px;
            border: none;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
<div class="container-fluid px-4 py-4">
    <div class="modern-card">
        <div class="card-header-modern">
            <i class="fas fa-users-cog"></i> User Management
        </div>
        <div class="card-body p-4">
            <!-- Alerts -->
            <?php if(isset($success)): ?>
                <div class="alert-modern alert-modern-success">
                    <i class="fas fa-check-circle fs-5"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>
            <?php if(isset($error)): ?>
                <div class="alert-modern alert-modern-danger">
                    <i class="fas fa-exclamation-triangle fs-5"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- Create User Section -->
            <h5 class="mb-3"><i class="fas fa-user-plus me-2 text-danger"></i> Create New User</h5>
            <form method="POST" class="form-modern row g-3 mb-5">
                <div class="col-md-4">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher (Staff)</option>
                        <option value="student">Student</option>
                    </select>
                </div>

                <!-- Teacher Fields -->
                <div id="teacherFields" style="display:none;" class="col-md-8">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Staff ID</label>
                            <input type="text" name="staff_id" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Student Fields -->
                <div id="studentFields" style="display:none;" class="col-md-8">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Admission No</label>
                            <input type="text" name="admission_no" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Class</label>
                            <input type="text" name="class" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Parent Phone</label>
                            <input type="text" name="parent_phone" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <button type="submit" name="create_user" class="btn btn-kenya-success btn-modern">
                        <i class="fas fa-save me-1"></i> Create User
                    </button>
                </div>
            </form>

            <hr>

            <!-- Existing Users Section -->
            <h5 class="mb-3"><i class="fas fa-list me-2 text-success"></i> Existing Users</h5>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>ID</th><th>Username</th><th>Role</th><th>Email</th><th>Details</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td data-label="ID"><?= $row['id'] ?></td>
                            <td data-label="Username"><?= htmlspecialchars($row['username']) ?></td>
                            <td data-label="Role">
                                <span class="badge-role <?= $row['role'] ?>">
                                    <?= ucfirst($row['role']) ?>
                                </span>
                            </td>
                            <td data-label="Email"><?= htmlspecialchars($row['email'] ?: '—') ?></td>
                            <td data-label="Details">
                                <?php if($row['role'] == 'student'): ?>
                                    <i class="fas fa-user-graduate me-1 text-primary"></i> <?= htmlspecialchars($row['student_name'] ?: 'N/A') ?>
                                <?php elseif($row['role'] == 'teacher'): ?>
                                    <i class="fas fa-chalkboard-user me-1 text-info"></i> <?= htmlspecialchars($row['staff_name'] ?: 'N/A') ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td data-label="Actions">
                                <a href="reset_password.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-kenya-warning me-1">
                                    <i class="fas fa-key"></i> Reset
                                </a>
                                <a href="delete_user.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete this user?')" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('role').addEventListener('change', function() {
    var teacherDiv = document.getElementById('teacherFields');
    var studentDiv = document.getElementById('studentFields');
    teacherDiv.style.display = 'none';
    studentDiv.style.display = 'none';
    if (this.value == 'teacher') {
        teacherDiv.style.display = 'block';
    } else if (this.value == 'student') {
        studentDiv.style.display = 'block';
    }
});
</script>

<?php include '../includes/footer.php'; ?>