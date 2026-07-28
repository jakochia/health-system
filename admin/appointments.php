<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) header("Location: ../index.php");

$page_title = 'Manage Appointments';
require_once '../includes/functions.php';
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Update status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE appointments SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $success = "Status updated.";
}

$appointments = $conn->query("SELECT a.*, s.full_name FROM appointments a JOIN students s ON a.student_id = s.id ORDER BY a.appointment_date DESC");
?>
<?php include 'includes/header.php'; ?>
<div class="card">
    <div class="card-header bg-black text-white">Appointments</div>
    <div class="card-body">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <table class="table table-bordered">
            <thead><tr><th>Student</th><th>Date & Time</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php while($a = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($a['full_name']) ?></td>
                    <td><?= $a['appointment_date'] ?></td>
                    <td><?= htmlspecialchars($a['reason']) ?></td>
                    <td><?= $a['status'] ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <select name="status" class="form-select form-select-sm d-inline-block w-auto">
                                <option value="pending" <?= $a['status']=='pending'?'selected':'' ?>>Pending</option>
                                <option value="completed" <?= $a['status']=='completed'?'selected':'' ?>>Completed</option>
                                <option value="cancelled" <?= $a['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'includes/footer.php'; ?>