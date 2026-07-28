<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) header("Location: ../index.php");

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8);
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $settingKey);
            $stmt->execute();
        }
    }
    $success = "Settings updated!";
}

// Fetch settings
$settings = [];
$result = $conn->query("SELECT * FROM settings");
while($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Admin</title>
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
        .form-modern .form-control {
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            padding: 0.6rem 1rem;
            transition: var(--transition);
        }
        .form-modern .form-control:focus {
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
        @media (max-width: 768px) {
            .form-modern .row {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid px-4 py-4">
    <div class="modern-card">
        <div class="card-header-modern">
            <i class="fas fa-sliders-h"></i> System Settings
        </div>
        <div class="card-body p-4">
            <?php if(isset($success)): ?>
                <div class="alert-modern alert-modern-success">
                    <i class="fas fa-check-circle fs-5"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" class="form-modern">
                <div class="mb-4">
                    <label class="form-label"><i class="fas fa-hospital me-2 text-danger"></i> Clinic Name</label>
                    <input type="text" name="setting_clinic_name" class="form-control" value="<?= htmlspecialchars($settings['clinic_name']) ?>" placeholder="e.g., MOHI Namarei Health Center">
                </div>
                <div class="mb-4">
                    <label class="form-label"><i class="fas fa-image me-2 text-success"></i> Clinic Logo Path</label>
                    <input type="text" name="setting_clinic_logo" class="form-control" value="<?= htmlspecialchars($settings['clinic_logo']) ?>" placeholder="assets/images/logo.png">
                    <small class="text-muted">Relative path from the root directory</small>
                </div>
                <div class="mb-4">
                    <label class="form-label"><i class="fas fa-phone-alt me-2 text-primary"></i> Contact Phone</label>
                    <input type="text" name="setting_contact_phone" class="form-control" value="<?= htmlspecialchars($settings['contact_phone']) ?>" placeholder="+254 712 345 678">
                </div>
                <div class="mb-4">
                    <label class="form-label"><i class="fas fa-envelope me-2 text-info"></i> Contact Email</label>
                    <input type="email" name="setting_contact_email" class="form-control" value="<?= htmlspecialchars($settings['contact_email']) ?>" placeholder="info@mohi.ac.ke">
                </div>
                <div class="mb-4">
                    <label class="form-label"><i class="fas fa-network-wired me-2 text-secondary"></i> Allowed IP Addresses</label>
                    <input type="text" name="setting_allowed_ips" class="form-control" value="<?= htmlspecialchars($settings['allowed_ips'] ?? '') ?>" placeholder="192.168.1.100, 192.168.1.101">
                    <small class="text-muted">Comma-separated IPs allowed to access the system. Leave empty to allow all.</small>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-kenya-primary btn-modern">
                        <i class="fas fa-save me-1"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>