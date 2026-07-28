<?php
$title = "Ambulance Requests";
require_once 'includes/header.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$message = '';
$error = '';

// Update status
if (isset($_POST['update_status'])) {
    $req_id = $_POST['request_id'];
    $status = $_POST['status'];

    // Get user_id for this request
    $user_result = $conn->query("SELECT user_id FROM ambulance_requests WHERE id = $req_id");
    if ($user_row = $user_result->fetch_assoc()) {
        $user_id = $user_row['user_id'];
    } else {
        $error = "Request not found.";
    }

    $dispatched_at = ($status == 'dispatched') ? date('Y-m-d H:i:s') : null;
    $stmt = $conn->prepare("UPDATE ambulance_requests SET status = ?, dispatched_at = COALESCE(?, dispatched_at) WHERE id = ?");
    $stmt->bind_param("ssi", $status, $dispatched_at, $req_id);
    if ($stmt->execute()) {
        // Send notification for cancellation
        if ($status == 'cancelled' && isset($user_id)) {
            $notif_msg = "Your ambulance request #$req_id has been cancelled.";
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notif_stmt->bind_param("is", $user_id, $notif_msg);
            $notif_stmt->execute();
        }
        $message = "Status updated.";
    } else {
        $error = "Error updating status.";
    }
}

// Fetch all requests
$requests = $conn->query("SELECT ar.*, u.username FROM ambulance_requests ar JOIN users u ON ar.user_id = u.id ORDER BY ar.request_time DESC");
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
    .badge-urgency {
        border-radius: 100px;
        padding: 0.25rem 0.75rem;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-urgency-emergency {
        background: #fee2e2;
        color: #991b1b;
    }
    .badge-urgency-high {
        background: #fff3e0;
        color: #b45309;
    }
    .badge-urgency-medium {
        background: #e0f2fe;
        color: #0c4a6e;
    }
    .badge-urgency-low {
        background: #e2e8f0;
        color: #475569;
    }
    .badge-status {
        border-radius: 100px;
        padding: 0.25rem 0.75rem;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-status-pending { background: #fef3c7; color: #92400e; }
    .badge-status-dispatched { background: #dbeafe; color: #1e40af; }
    .badge-status-arrived { background: #cffafe; color: #0e7490; }
    .badge-status-completed { background: #d1fae5; color: #065f46; }
    .badge-status-cancelled { background: #fee2e2; color: #991b1b; }
    .btn-modern {
        border-radius: 2rem;
        padding: 0.25rem 1rem;
        font-weight: 500;
        transition: var(--transition);
        border: none;
    }
    .btn-modern:hover {
        transform: translateY(-1px);
    }
    .btn-kenya-primary {
        background: var(--kenya-black);
        color: white;
    }
    .btn-kenya-primary:hover {
        background: #1a1a1a;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
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
        .form-select-sm {
            width: auto;
        }
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="modern-card">
        <div class="card-header-modern">
            <i class="fas fa-ambulance"></i> Ambulance Requests
        </div>
        <div class="card-body p-4">
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

            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>ID</th><th>Request Time</th><th>User</th><th>Location</th><th>Phone</th>
                            <th>Urgency</th><th>Condition</th><th>Status</th><th>Dispatched</th><th>Action</th>
                        </thead>
                    <tbody>
                        <?php while ($row = $requests->fetch_assoc()): ?>
                        <tr>
                            <td data-label="ID"><?= $row['id'] ?></td>
                            <td data-label="Request Time"><?= date('M d, Y H:i', strtotime($row['request_time'])) ?></td>
                            <td data-label="User"><?= htmlspecialchars($row['username']) ?></td>
                            <td data-label="Location"><?= htmlspecialchars($row['location']) ?></td>
                            <td data-label="Phone"><?= $row['contact_phone'] ?></td>
                            <td data-label="Urgency">
                                <?php
                                $urgencyClass = '';
                                switch($row['urgency']) {
                                    case 'emergency': $urgencyClass = 'badge-urgency-emergency'; break;
                                    case 'high': $urgencyClass = 'badge-urgency-high'; break;
                                    case 'medium': $urgencyClass = 'badge-urgency-medium'; break;
                                    default: $urgencyClass = 'badge-urgency-low';
                                }
                                ?>
                                <span class="badge-urgency <?= $urgencyClass ?>"><?= ucfirst($row['urgency']) ?></span>
                            </td>
                            <td data-label="Condition"><?= htmlspecialchars($row['medical_condition']) ?></td>
                            <td data-label="Status">
                                <?php
                                $statusClass = 'badge-status-' . $row['status'];
                                ?>
                                <span class="badge-status <?= $statusClass ?>"><?= ucfirst($row['status']) ?></span>
                            </td>
                            <td data-label="Dispatched"><?= $row['dispatched_at'] ? date('M d, Y H:i', strtotime($row['dispatched_at'])) : '-' ?></td>
                            <td data-label="Action">
                                <form method="POST" class="d-flex flex-wrap gap-1">
                                    <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                    <select name="status" class="form-select form-select-sm" style="width: auto;">
                                        <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="dispatched" <?= $row['status'] == 'dispatched' ? 'selected' : '' ?>>Dispatched</option>
                                        <option value="arrived" <?= $row['status'] == 'arrived' ? 'selected' : '' ?>>Arrived</option>
                                        <option value="completed" <?= $row['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $row['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-kenya-primary btn-modern btn-sm">Update</button>
                                </form>
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