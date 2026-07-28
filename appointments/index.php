<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn()) header("Location: ../index.php");

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Book appointment (for students)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book'])) {
    $student_id = $_POST['student_id'];
    $app_date = $_POST['appointment_date'];
    $reason = $_POST['reason'];
    $stmt = $conn->prepare("INSERT INTO appointments (student_id, appointment_date, reason, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("iss", $student_id, $app_date, $reason);
    $stmt->execute();
    $success = "Appointment booked!";
}

// Fetch queue for clinicians (if role teacher)
$queue = [];
if ($auth->hasRole('teacher') || $auth->hasRole('admin')) {
    $queue = $conn->query("SELECT a.*, s.full_name FROM appointments a JOIN students s ON a.student_id = s.id WHERE a.status = 'pending' ORDER BY a.appointment_date ASC");
}

$students = [];
if ($auth->hasRole('student')) {
    $studentId = $conn->query("SELECT student_id FROM users WHERE id = {$_SESSION['user_id']}")->fetch_assoc()['student_id'];
    $appointments = $conn->query("SELECT * FROM appointments WHERE student_id = $studentId ORDER BY appointment_date DESC");
}
?>
<?php include '../includes/header.php'; ?>
<div class="row">
    <?php if($auth->hasRole('student')): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Book Appointment</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Date & Time</label>
                        <input type="datetime-local" name="appointment_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Reason</label>
                        <textarea name="reason" class="form-control" required></textarea>
                    </div>
                    <input type="hidden" name="student_id" value="<?= $studentId ?>">
                    <button type="submit" name="book" class="btn btn-primary">Book</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">My Appointments</div>
            <div class="card-body">
                <ul>
                    <?php while($a = $appointments->fetch_assoc()): ?>
                    <li><?= $a['appointment_date'] ?> - <?= htmlspecialchars($a['reason']) ?> (<?= $a['status'] ?>)</li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($auth->hasRole('teacher') || $auth->hasRole('admin')): ?>
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">Appointment Queue</div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>Student</th><th>Date & Time</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($a = $queue->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                            <td><?= $a['appointment_date'] ?></td>
                            <td><?= htmlspecialchars($a['reason']) ?></td>
                            <td><?= $a['status'] ?></td>
                            <td>
                                <a href="update_status.php?id=<?= $a['id'] ?>&status=completed" class="btn btn-sm btn-success">Complete</a>
                                <a href="update_status.php?id=<?= $a['id'] ?>&status=cancelled" class="btn btn-sm btn-danger">Cancel</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>