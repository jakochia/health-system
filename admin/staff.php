<?php
$title = "Manage Staff";
require_once 'includes/header.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();
$message = '';
$error = '';

// ----- Handle deletion -----
if (isset($_GET['delete'])) {
    $staff_id = (int)$_GET['delete'];
    $conn->begin_transaction();
    try {
        // Get the associated user_id before deleting staff
        $user_result = $conn->query("SELECT user_id FROM staff WHERE id = $staff_id");
        if ($user_row = $user_result->fetch_assoc()) {
            $user_id = $user_row['user_id'];

            // 1. Remove references in visits (set clinician_id to NULL)
            $update_visits = $conn->prepare("UPDATE visits SET clinician_id = NULL WHERE clinician_id = ?");
            $update_visits->bind_param("i", $staff_id);
            $update_visits->execute();

            // 2. Delete the staff record
            $delete_staff = $conn->prepare("DELETE FROM staff WHERE id = ?");
            $delete_staff->bind_param("i", $staff_id);
            $delete_staff->execute();

            // 3. Delete audit logs for this user
            $delete_audit = $conn->prepare("DELETE FROM audit_logs WHERE user_id = ?");
            $delete_audit->bind_param("i", $user_id);
            $delete_audit->execute();

            // 4. Delete the user account (the login)
            $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
            $delete_user->bind_param("i", $user_id);
            $delete_user->execute();

            $conn->commit();
            $message = "Staff member and associated user deleted successfully.";
        } else {
            throw new Exception("Staff record not found.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error deleting staff: " . $e->getMessage();
    }
}

// ----- Handle add/edit (simplified) -----
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_staff'])) {
        $staff_id = trim($_POST['staff_id']);
        $full_name = trim($_POST['full_name']);
        $role = $_POST['role'];
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);

        // Check if staff_id already exists
        $check = $conn->prepare("SELECT id FROM staff WHERE staff_id = ?");
        $check->bind_param("s", $staff_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "Staff ID already exists.";
        } else {
            // Create user account first
            $username = $staff_id;
            $default_password = password_hash($staff_id, PASSWORD_DEFAULT);
            $user_role = 'teacher'; // all staff are teachers role in users table

            $insert_user = $conn->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
            $insert_user->bind_param("ssss", $username, $default_password, $user_role, $email);
            if ($insert_user->execute()) {
                $user_id = $insert_user->insert_id;
                // Insert staff record
                $insert_staff = $conn->prepare("INSERT INTO staff (user_id, staff_id, full_name, role, phone) VALUES (?, ?, ?, ?, ?)");
                $insert_staff->bind_param("issss", $user_id, $staff_id, $full_name, $role, $phone);
                if ($insert_staff->execute()) {
                    $message = "Staff added successfully. Default password is the staff ID.";
                } else {
                    // rollback user creation
                    $conn->query("DELETE FROM users WHERE id = $user_id");
                    $error = "Error adding staff: " . $conn->error;
                }
            } else {
                $error = "Error creating user: " . $conn->error;
            }
        }
    }
}

// ----- Fetch staff list -----
$staff = $conn->query("SELECT s.*, u.username, u.email FROM staff s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.id DESC");
?>

<!-- Modern styling with Kenyan flag colors -->
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
    .badge-role {
        border-radius: 100px;
        padding: 0.25rem 0.75rem;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .badge-role.teacher {
        background: #0f4c5c;
        color: white;
    }
    .badge-role.clinician {
        background: #1e6f82;
        color: white;
    }
    .badge-role.pharmacist {
        background: var(--kenya-green);
        color: white;
    }
    hr {
        background: linear-gradient(90deg, var(--kenya-red), var(--kenya-green), var(--kenya-black));
        height: 2px;
        border: none;
        margin: 1.5rem 0;
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

<div class="container-fluid px-4 py-4">
    <div class="modern-card">
        <div class="card-header-modern">
            <i class="fas fa-user-md"></i> Staff Management
        </div>
        <div class="card-body p-4">
            <!-- Alerts -->
            <?php if ($message): ?>
                <div class="alert-modern alert-modern-success">
                    <i class="fas fa-check-circle fs-5"></i>
                    <div><?= htmlspecialchars($message) ?></div>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-modern alert-modern-danger">
                    <i class="fas fa-exclamation-triangle fs-5"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- Add Staff Form -->
            <h5 class="mb-3"><i class="fas fa-user-plus me-2 text-danger"></i> Add New Staff</h5>
            <form method="POST" class="form-modern row g-3 mb-5">
                <div class="col-md-3">
                    <label class="form-label">Staff ID</label>
                    <input type="text" name="staff_id" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="teacher">Teacher</option>
                        <option value="clinician">Clinician</option>
                        <option value="pharmacist">Pharmacist</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-md-12">
                    <button type="submit" name="add_staff" class="btn btn-kenya-success btn-modern">
                        <i class="fas fa-save me-1"></i> Add Staff
                    </button>
                </div>
            </form>

            <hr>

            <!-- Staff List -->
            <h5 class="mb-3"><i class="fas fa-list me-2 text-success"></i> Existing Staff</h5>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Actions</th>
                        </thead>
                    <tbody>
                        <?php while ($row = $staff->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Staff ID"><?= htmlspecialchars($row['staff_id']) ?></td>
                            <td data-label="Full Name"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td data-label="Role">
                                <span class="badge-role <?= $row['role'] ?>">
                                    <?= ucfirst($row['role']) ?>
                                </span>
                            </td>
                            <td data-label="Phone"><?= htmlspecialchars($row['phone']) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
                            <td data-label="Username"><?= htmlspecialchars($row['username']) ?></td>
                            <td data-label="Actions">
                                <a href="staff_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-kenya-warning me-1">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="staff.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete staff? This will also remove the user account and all associated records (audit logs).')">
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

<?php require_once 'includes/footer.php'; ?>