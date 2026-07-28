<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) header("Location: ../index.php");

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$logs = $conn->query("SELECT l.*, u.username FROM audit_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.timestamp DESC LIMIT 100");
?>
<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | Admin</title>
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
        .search-box {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .search-box input {
            border-radius: 2rem;
            padding: 0.6rem 1rem 0.6rem 2.5rem;
            border: 1px solid #e2e8f0;
            width: 100%;
            transition: var(--transition);
        }
        .search-box input:focus {
            border-color: var(--kenya-red);
            box-shadow: 0 0 0 3px rgba(187, 0, 0, 0.2);
            outline: none;
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
        .badge-action {
            border-radius: 100px;
            padding: 0.25rem 0.75rem;
            font-size: 0.7rem;
            font-weight: 600;
            background: #eef2ff;
            color: #4338ca;
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
            <i class="fas fa-history"></i> Audit Logs
        </div>
        <div class="card-body p-4">
            <!-- Search Box -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by user, action, or IP..." autocomplete="off">
            </div>

            <!-- Logs Table -->
            <div class="table-responsive">
                <table class="table table-modern" id="logsTable">
                    <thead>
                        <tr>
                            <th>Time</th><th>User</th><th>Action</th><th>IP Address</th>
                        </thead>
                    <tbody>
                        <?php while($row = $logs->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Time"><?= date('M d, Y H:i:s', strtotime($row['timestamp'])) ?> </td>
                            <td data-label="User"><?= htmlspecialchars($row['username'] ?: 'System') ?> </td>
                            <td data-label="Action"><span class="badge-action"><?= htmlspecialchars($row['action']) ?></span></td>
                            <td data-label="IP Address"><?= $row['ip_address'] ?> </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#logsTable tbody tr');
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include '../includes/footer.php'; ?>