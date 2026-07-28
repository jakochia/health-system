<?php
require_once 'includes/header.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Pre-fill phone from student/staff record
$phone = '';
if ($role == 'student') {
    $stu = $conn->query("SELECT parent_phone FROM students WHERE id = (SELECT student_id FROM users WHERE id = $user_id)")->fetch_assoc();
    $phone = $stu['parent_phone'] ?? '';
} elseif ($role == 'teacher') {
    $staff = $conn->query("SELECT phone FROM staff WHERE user_id = $user_id")->fetch_assoc();
    $phone = $staff['phone'] ?? '';
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $location = trim($_POST['location']);
    $contact_phone = trim($_POST['contact_phone']);
    $urgency = $_POST['urgency'];
    $medical_condition = trim($_POST['medical_condition']);
    $notes = trim($_POST['notes']);

    $stmt = $conn->prepare("INSERT INTO ambulance_requests (user_id, location, contact_phone, urgency, medical_condition, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $location, $contact_phone, $urgency, $medical_condition, $notes);
    if ($stmt->execute()) {
        $message = "Ambulance request submitted. Our team will contact you shortly.";
    } else {
        $error = "Error submitting request. Please try again.";
    }
}
?>

<!-- Modern Font & Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #dc3545, #b91c1c);
        --secondary-gradient: linear-gradient(135deg, #f8f9fa, #e9ecef);
        --shadow-sm: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
        --shadow-md: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.02);
        --border-radius: 1rem;
    }

    body {
        background: linear-gradient(145deg, #f5f7fc 0%, #eef2f7 100%);
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    .ambulance-card {
        border: none;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--shadow-md);
        backdrop-filter: blur(2px);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .ambulance-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 25px 30px -12px rgba(0,0,0,0.2);
    }

    .card-header-modern {
        background: var(--primary-gradient);
        padding: 1.25rem 1.5rem;
        border-bottom: none;
        position: relative;
        overflow: hidden;
    }

    .card-header-modern h4 {
        font-weight: 600;
        letter-spacing: -0.3px;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .card-header-modern h4 i {
        font-size: 1.8rem;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    }

    .form-label {
        font-weight: 500;
        color: #1e293b;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-label i {
        color: #dc3545;
        width: 1.25rem;
        font-size: 1rem;
    }

    .form-control, .form-select {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        transition: all 0.2s ease;
        background-color: #ffffff;
        box-shadow: var(--shadow-sm);
    }

    .form-control:focus, .form-select:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.2);
        outline: none;
    }

    textarea.form-control {
        resize: vertical;
    }

    .btn-ambulance {
        background: var(--primary-gradient);
        border: none;
        padding: 0.85rem 1.5rem;
        font-weight: 600;
        border-radius: 2rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        letter-spacing: 0.3px;
    }

    .btn-ambulance:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(220, 53, 69, 0.4);
        filter: brightness(1.02);
    }

    .btn-ambulance:active {
        transform: translateY(1px);
    }

    .alert-modern {
        border-radius: 1rem;
        border-left: 5px solid;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .alert-success-modern {
        background-color: #ecfdf5;
        border-left-color: #10b981;
        color: #065f46;
    }

    .alert-danger-modern {
        background-color: #fef2f2;
        border-left-color: #dc2626;
        color: #991b1b;
    }

    .alert-modern i {
        font-size: 1.25rem;
    }

    /* Responsive tweaks */
    @media (max-width: 768px) {
        .card-header-modern h4 {
            font-size: 1.4rem;
        }
        .btn-ambulance {
            width: 100%;
        }
    }

    /* subtle animation for inputs */
    .form-control, .form-select {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card ambulance-card">
                <div class="card-header-modern text-white">
                    <h4>
                        <i class="fas fa-ambulance"></i> 
                        Emergency Ambulance Request
                    </h4>
                    <p class="mb-0 mt-2 opacity-75 small">Immediate assistance – fill the details below</p>
                </div>
                <div class="card-body p-4 p-lg-5">
                    <!-- Dynamic alerts with icons -->
                    <?php if ($message): ?>
                        <div class="alert-modern alert-success-modern">
                            <i class="fas fa-check-circle"></i>
                            <span><?= htmlspecialchars($message) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert-modern alert-danger-modern">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="ambulanceForm">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-map-marker-alt"></i> Current Location
                                    </label>
                                    <input type="text" name="location" class="form-control" placeholder="e.g., Building A, Room 204 / Sports Complex" required>
                                    <small class="text-muted">Specify exact location for quick response</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-phone-alt"></i> Contact Phone
                                    </label>
                                    <input type="tel" name="contact_phone" class="form-control" value="<?= htmlspecialchars($phone) ?>" placeholder="Phone number for updates" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-exclamation-triangle"></i> Urgency Level
                                    </label>
                                    <select name="urgency" class="form-select" required>
                                        <option value="low">🟢 Low (non-emergency)</option>
                                        <option value="medium">🟡 Medium (needs attention)</option>
                                        <option value="high">🟠 High (urgent)</option>
                                        <option value="emergency">🔴 Emergency (life-threatening)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-heartbeat"></i> Medical Condition / Symptoms
                                    </label>
                                    <textarea name="medical_condition" class="form-control" rows="3" placeholder="Describe symptoms, known allergies, or current condition..." required></textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-pen-alt"></i> Additional Notes
                                    </label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Any extra details that might help the medical team"></textarea>
                                </div>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-ambulance text-white w-100 w-md-auto" id="submitBtn">
                                    <i class="fas fa-paper-plane me-2"></i> Submit Request
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Extra info / modern touch -->
            <div class="text-center mt-4 text-muted small">
                <i class="fas fa-clock"></i> Average response time: &lt; 5 minutes &nbsp;|&nbsp;
                <i class="fas fa-shield-alt"></i> Your data is secure
            </div>
        </div>
    </div>
</div>

<!-- Simple JS to disable button on submit & loading state (improves UX) -->
<script>
    document.getElementById('ambulanceForm')?.addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Submitting...';
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>