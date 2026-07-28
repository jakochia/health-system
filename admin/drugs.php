<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) header("Location: ../index.php");

$page_title = 'Pharmacy Inventory';
require_once '../includes/functions.php';
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Add/update drugs
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_drug'])) {
        $name = $_POST['name'];
        $qty = $_POST['quantity'];
        $reorder = $_POST['reorder_level'];
        $price = $_POST['price'];
        $desc = $_POST['description'];
        $stmt = $conn->prepare("INSERT INTO drugs (name, quantity, reorder_level, unit_price, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siids", $name, $qty, $reorder, $price, $desc);
        if ($stmt->execute()) {
            $success = "Drug added.";
        } else {
            $error = "Error: " . $conn->error;
        }
    } elseif (isset($_POST['update_qty'])) {
        $id = $_POST['drug_id'];
        $new_qty = $_POST['new_qty'];
        $stmt = $conn->prepare("UPDATE drugs SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_qty, $id);
        $stmt->execute();
        $success = "Quantity updated.";
    } elseif (isset($_POST['edit_drug'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $reorder = $_POST['reorder_level'];
        $price = $_POST['price'];
        $desc = $_POST['description'];
        $stmt = $conn->prepare("UPDATE drugs SET name=?, reorder_level=?, unit_price=?, description=? WHERE id=?");
        $stmt->bind_param("sidsi", $name, $reorder, $price, $desc, $id);
        $stmt->execute();
        $success = "Drug updated.";
    } elseif (isset($_POST['delete_drug'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM drugs WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "Drug deleted.";
    }
}

$drugs = $conn->query("SELECT * FROM drugs ORDER BY name");
?>
<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Inventory | Admin</title>
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
        .badge-stock {
            border-radius: 100px;
            padding: 0.25rem 0.75rem;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-low {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-ok {
            background: #e0f2fe;
            color: #0c4a6e;
        }
        hr {
            background: linear-gradient(90deg, var(--kenya-red), var(--kenya-green), var(--kenya-black));
            height: 2px;
            border: none;
            margin: 1.5rem 0;
        }
        .modal-modern .modal-content {
            border-radius: 1.25rem;
            border: none;
            box-shadow: var(--card-shadow-hover);
        }
        .modal-modern .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-bottom: none;
            border-radius: 1.25rem 1.25rem 0 0;
        }
        .modal-modern .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .modal-modern .modal-footer {
            border-top: none;
        }
        .qty-update-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .qty-update-form input {
            width: 80px;
            border-radius: 2rem;
            padding: 0.25rem 0.5rem;
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
            .qty-update-form {
                flex-direction: column;
                align-items: stretch;
            }
            .qty-update-form input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid px-4 py-4">
    <div class="modern-card">
        <div class="card-header-modern">
            <i class="fas fa-capsules"></i> Pharmacy Inventory
        </div>
        <div class="card-body p-4">
            <?php if (isset($success)): ?>
                <div class="alert-modern alert-modern-success">
                    <i class="fas fa-check-circle fs-5"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert-modern alert-modern-danger">
                    <i class="fas fa-exclamation-triangle fs-5"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- Add New Drug Form -->
            <h5 class="mb-3"><i class="fas fa-plus-circle me-2 text-danger"></i> Add New Drug</h5>
            <form method="POST" class="form-modern row g-3 mb-5">
                <div class="col-md-3">
                    <label class="form-label">Drug Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g., Paracetamol" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-control" placeholder="Qty" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Reorder Level</label>
                    <input type="number" name="reorder_level" class="form-control" placeholder="Reorder level" value="10">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Unit Price (KSh)</label>
                    <input type="number" step="0.01" name="price" class="form-control" placeholder="Price">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Description">
                </div>
                <div class="col-md-12">
                    <button type="submit" name="add_drug" class="btn btn-kenya-success btn-modern">
                        <i class="fas fa-save me-1"></i> Add Drug
                    </button>
                </div>
            </form>

            <hr>

            <!-- Current Stock Table -->
            <h5 class="mb-3"><i class="fas fa-list me-2 text-success"></i> Current Stock</h5>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Name</th><th>Quantity</th><th>Reorder Level</th><th>Unit Price</th><th>Description</th><th>Status</th><th>Actions</th>
                        </thead>
                    <tbody>
                        <?php while($row = $drugs->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Name"><?= htmlspecialchars($row['name']) ?> </td>
                            <td data-label="Quantity"><?= $row['quantity'] ?></td>
                            <td data-label="Reorder Level"><?= $row['reorder_level'] ?></td>
                            <td data-label="Unit Price">KSh <?= number_format($row['unit_price'], 2) ?></td>
                            <td data-label="Description"><?= htmlspecialchars($row['description'] ?: '—') ?></td>
                            <td data-label="Status">
                                <?php if ($row['quantity'] <= $row['reorder_level']): ?>
                                    <span class="badge-stock badge-low"><i class="fas fa-exclamation-triangle me-1"></i> Low Stock</span>
                                <?php else: ?>
                                    <span class="badge-stock badge-ok"><i class="fas fa-check-circle me-1"></i> OK</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Actions">
                                <div class="d-flex flex-wrap gap-1">
                                    <button class="btn btn-sm btn-kenya-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this drug?');">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="delete_drug" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                    <form method="POST" class="qty-update-form d-inline-flex">
                                        <input type="hidden" name="drug_id" value="<?= $row['id'] ?>">
                                        <input type="number" name="new_qty" class="form-control form-control-sm" placeholder="New Qty" style="width: 80px;">
                                        <button type="submit" name="update_qty" class="btn btn-sm btn-kenya-primary">
                                            <i class="fas fa-sync-alt"></i> Update
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade modal-modern" id="editModal<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Drug</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Name</label>
                                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Reorder Level</label>
                                                <input type="number" name="reorder_level" class="form-control" value="<?= $row['reorder_level'] ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Unit Price (KSh)</label>
                                                <input type="number" step="0.01" name="price" class="form-control" value="<?= $row['unit_price'] ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($row['description']) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="edit_drug" class="btn btn-kenya-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>