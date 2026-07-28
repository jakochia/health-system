<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !in_array($_SESSION['role'], ['admin','teacher'])) header("Location: ../index.php");

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Add/update drugs
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_drug'])) {
    $name = $_POST['name'];
    $qty = $_POST['quantity'];
    $reorder = $_POST['reorder_level'];
    $price = $_POST['price'];
    $desc = $_POST['description'];
    $stmt = $conn->prepare("INSERT INTO drugs (name, quantity, reorder_level, unit_price, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("siids", $name, $qty, $reorder, $price, $desc);
    $stmt->execute();
    $success = "Drug added!";
}

// Update quantity
if (isset($_POST['update_qty'])) {
    $id = $_POST['drug_id'];
    $new_qty = $_POST['new_qty'];
    $conn->query("UPDATE drugs SET quantity = $new_qty WHERE id = $id");
    $success = "Quantity updated.";
}

$drugs = $conn->query("SELECT * FROM drugs ORDER BY name");
?>
<?php include '../includes/header.php'; ?>
<div class="card">
    <div class="card-header bg-black text-white">Pharmacy Inventory</div>
    <div class="card-body">
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <h5>Add New Drug</h5>
        <form method="POST" class="row g-3 mb-4">
            <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Drug name" required></div>
            <div class="col-md-2"><input type="number" name="quantity" class="form-control" placeholder="Quantity" required></div>
            <div class="col-md-2"><input type="number" name="reorder_level" class="form-control" placeholder="Reorder level" value="10"></div>
            <div class="col-md-2"><input type="number" step="0.01" name="price" class="form-control" placeholder="Unit price"></div>
            <div class="col-md-3"><input type="text" name="description" class="form-control" placeholder="Description"></div>
            <div class="col-md-12"><button type="submit" name="add_drug" class="btn btn-success">Add Drug</button></div>
        </form>

        <h5>Current Stock</h5>
        <table class="table table-bordered">
            <thead><tr><th>Name</th><th>Quantity</th><th>Reorder Level</th><th>Unit Price</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php while($row = $drugs->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td><?= $row['reorder_level'] ?></td>
                    <td><?= $row['unit_price'] ?></td>
                    <td><?= ($row['quantity'] <= $row['reorder_level']) ? '<span class="badge bg-danger">Low Stock</span>' : '<span class="badge bg-success">OK</span>' ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="drug_id" value="<?= $row['id'] ?>">
                            <input type="number" name="new_qty" style="width:70px" placeholder="New Qty">
                            <button type="submit" name="update_qty" class="btn btn-sm btn-primary">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>