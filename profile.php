<?php
require_once 'includes/header.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$error = '';

// Fetch current user data
$user = $conn->query("SELECT username, email FROM users WHERE id = $user_id")->fetch_assoc();
$student = null;
$staff = null;
if ($role == 'student') {
    $student = $conn->query("SELECT * FROM students WHERE id = (SELECT student_id FROM users WHERE id = $user_id)")->fetch_assoc();
} elseif ($role == 'teacher') {
    $staff = $conn->query("SELECT * FROM staff WHERE user_id = $user_id")->fetch_assoc();
}

// Handle form submissions (same logic as before)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($role == 'student' && $student) {
            $stmt = $conn->prepare("UPDATE students SET parent_phone = ?, full_name = ? WHERE id = ?");
            $stmt->bind_param("ssi", $phone, $full_name, $student['id']);
            $stmt->execute();
        } elseif ($role == 'teacher' && $staff) {
            $stmt = $conn->prepare("UPDATE staff SET phone = ?, full_name = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $phone, $full_name, $user_id);
            $stmt->execute();
        }
        $message = "Profile updated successfully!";
        $user['email'] = $email;
        if ($student) $student['parent_phone'] = $phone;
        if ($staff) $staff['phone'] = $phone;
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $passCheck = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $passCheck->bind_param("i", $user_id);
        $passCheck->execute();
        $passRow = $passCheck->get_result()->fetch_assoc();
        if (password_verify($current, $passRow['password'])) {
            if ($new === $confirm && strlen($new) >= 6) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->bind_param("si", $hashed, $user_id);
                if ($update->execute()) {
                    $message = "Password changed successfully!";
                } else {
                    $error = "Error updating password.";
                }
            } else {
                $error = "New password must be at least 6 characters and match confirmation.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }

    if (isset($_POST['update_security'])) {
        $question = trim($_POST['security_question']);
        $answer = trim($_POST['security_answer']);
        $hashed_answer = password_hash($answer, PASSWORD_DEFAULT);

        $check = $conn->prepare("SELECT id FROM user_security WHERE user_id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE user_security SET question = ?, answer_hash = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $question, $hashed_answer, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO user_security (user_id, question, answer_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $question, $hashed_answer);
        }
        if ($stmt->execute()) {
            $message = "Security question updated!";
        } else {
            $error = "Error saving security question.";
        }
    }
}

// Fetch existing security question
$secQ = $conn->query("SELECT question FROM user_security WHERE user_id = $user_id")->fetch_assoc();
$existing_question = $secQ['question'] ?? '';

$roleDisplay = ($role == 'student') ? 'Student' : 'Teacher';
$roleBadgeClass = ($role == 'student') ? 'bg-primary' : 'bg-success';
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
        --gradient-primary: linear-gradient(135deg, var(--kenya-black) 0%, var(--kenya-red) 100%);
        --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --card-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(145deg, #f1f5f9 0%, #eef2ff 100%);
        min-height: 100vh;
    }
    .profile-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .profile-card {
        border: none;
        border-radius: 1.5rem;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        background: white;
        transition: var(--transition);
    }
    .profile-card:hover {
        box-shadow: var(--card-shadow-hover);
    }
    .profile-header {
        background: var(--gradient-primary);
        color: white;
        padding: 2rem;
        text-align: center;
        position: relative;
    }
    .profile-avatar {
        width: 90px;
        height: 90px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        margin-bottom: 1rem;
        backdrop-filter: blur(4px);
        transition: var(--transition);
    }
    .profile-card:hover .profile-avatar {
        transform: scale(1.05);
    }
    .profile-role {
        background: rgba(255,255,255,0.2);
        border-radius: 2rem;
        padding: 0.3rem 1.2rem;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-block;
    }
    .nav-tabs-modern {
        border-bottom: 1px solid #e2e8f0;
        gap: 0.5rem;
        padding: 0 1rem;
        background: #fafcff;
    }
    .nav-tabs-modern .nav-link {
        border: none;
        color: #64748b;
        font-weight: 500;
        padding: 1rem 1.25rem;
        margin-bottom: -1px;
        transition: var(--transition);
        border-radius: 0;
        position: relative;
    }
    .nav-tabs-modern .nav-link i {
        margin-right: 0.5rem;
    }
    .nav-tabs-modern .nav-link:hover {
        color: var(--kenya-red);
        border-bottom: 2px solid #cbd5e1;
    }
    .nav-tabs-modern .nav-link.active {
        color: var(--kenya-red);
        border-bottom: 2px solid var(--kenya-red);
        background: transparent;
    }
    .tab-content {
        padding: 1.75rem;
    }
    .form-modern .form-control {
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        padding: 0.75rem 1rem;
        transition: var(--transition);
        background-color: #ffffff;
    }
    .form-modern .form-control:focus {
        border-color: var(--kenya-red);
        box-shadow: 0 0 0 3px rgba(187, 0, 0, 0.2);
        outline: none;
    }
    .form-modern .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
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
        margin: 1rem 1rem 0 1rem;
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
    .form-text {
        font-size: 0.75rem;
        margin-top: 0.25rem;
        color: #6c757d;
    }
    @media (max-width: 576px) {
        .profile-header {
            padding: 1.5rem;
        }
        .profile-avatar {
            width: 70px;
            height: 70px;
            font-size: 2rem;
        }
        .nav-tabs-modern .nav-link {
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
        }
        .tab-content {
            padding: 1.25rem;
        }
    }
</style>

<div class="profile-container">
    <div class="profile-card">
        <!-- Profile Header with Kenyan gradient -->
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <h3 class="mb-1 fw-bold"><?= htmlspecialchars($user['username']) ?></h3>
            <p class="mb-2"><?= htmlspecialchars($user['email']) ?></p>
            <span class="profile-role"><i class="fas fa-<?= $role == 'student' ? 'graduation-cap' : 'chalkboard-user' ?> me-1"></i> <?= $roleDisplay ?></span>
        </div>

        <!-- Alert Messages -->
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

        <!-- Tabs -->
        <ul class="nav nav-tabs-modern" id="profileTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                    <i class="fas fa-user-edit"></i> Personal Details
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                    <i class="fas fa-shield-alt"></i> Security Question
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Personal Details Tab -->
            <div class="tab-pane fade show active" id="details" role="tabpanel">
                <form method="POST" class="form-modern">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <?php if ($role == 'student' && $student): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($student['full_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parent/Guardian Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['parent_phone']) ?>">
                    </div>
                    <?php elseif ($role == 'teacher' && $staff): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($staff['full_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($staff['phone']) ?>">
                    </div>
                    <?php endif; ?>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" name="update_profile" class="btn btn-kenya-primary btn-modern">
                            <i class="fas fa-save me-1"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password Tab -->
            <div class="tab-pane fade" id="password" role="tabpanel">
                <form method="POST" class="form-modern">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                        <div class="form-text">At least 6 characters</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" name="change_password" class="btn btn-kenya-warning btn-modern">
                            <i class="fas fa-exchange-alt me-1"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Question Tab -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <form method="POST" class="form-modern">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Security Question</label>
                        <input type="text" name="security_question" class="form-control" value="<?= htmlspecialchars($existing_question) ?>" placeholder="e.g., What is your mother's maiden name?" required>
                        <div class="form-text">Choose a question only you would know.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Answer</label>
                        <input type="text" name="security_answer" class="form-control" required>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" name="update_security" class="btn btn-kenya-secondary btn-modern">
                            <i class="fas fa-lock me-1"></i> Save Security Question
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>