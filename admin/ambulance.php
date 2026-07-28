<?php
$title = "Ambulance Requests";
require_once 'includes/header.php';
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Update status
if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];
    $allowed = ['dispatched', 'arrived', 'completed', 'cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE ambulance_requests SET status = ?, dispatched_at = IF(?='dispatched', NOW(), dispatched_at) WHERE id = ?");
        $stmt->bind_param("ssi", $status, $status, $id);
        $stmt->execute();
        header("Location: ambulance.php");
        exit;
    }
}

// Fetch all requests
$requests = $conn->query("SELECT ar.*, u.username FROM ambulance_requests ar JOIN users u ON ar.user_id = u.id ORDER BY ar.request_time DESC");
?>
<div class="card">
    <div class="card-header bg-black text-white">Ambulance Requests</div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th><th>User</th><th>Request Time</th><th>Location</th><th>Contact</th><th>Urgency</th><th>Condition</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $requests->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= $row['request_time'] ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td><?= $row['contact_phone'] ?></td>
                    <td>
                        <span class="badge <?= $row['urgency']=='emergency'?'bg-danger':($row['urgency']=='high'?'bg-warning':'bg-secondary') ?>">
                            <?= ucfirst($row['urgency']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['condition']) ?></td>
                    <td><?= $row['status'] ?></td>
                    <td>
                        <?php if ($row['status'] == 'pending'): ?>
                            <a href="?update_status=dispatched&id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Dispatch</a>
                        <?php elseif ($row['status'] == 'dispatched'): ?>
                            <a href="?update_status=arrived&id=<?= $row['id'] ?>" class="btn btn-sm btn-info">Arrived</a>
                        <?php elseif ($row['status'] == 'arrived'): ?>
                            <a href="?update_status=completed&id=<?= $row['id'] ?>" class="btn btn-sm btn-success">Complete</a>
                        <?php endif; ?>
                        <a href="?update_status=cancelled&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this request?')">Cancel</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>