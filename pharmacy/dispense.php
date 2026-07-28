<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !in_array($_SESSION['role'], ['admin','teacher'])) header("Location: ../index.php");

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get pending prescriptions (prescriptions not yet dispensed)
$pending = $conn->query("SELECT p.*, v.student_id, s.full_name FROM prescriptions p JOIN visits v ON p.visit_id = v.id JOIN students s ON v.student_id = s.id WHERE p.id NOT IN (SELECT prescription_id FROM dispensing) ORDER BY v.visit_date DESC");

// Dispense
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dispense'])) {
    $pres_id = $_POST['prescription_id'];
    $drug_id = $_POST['drug_id'];
    $qty = $_POST['quantity'];
    $dispensed_by = $auth->hasRole('admin') ? 1 : 0; // simplify

    // Check stock
    $stock = $conn->query("SELECT quantity FROM drugs WHERE id = $drug_id")->fetch_assoc();
    if ($stock['quantity'] >= $qty) {
        $conn->query("UPDATE drugs SET quantity = quantity - $qty WHERE id = $drug_id");
        $stmt = $conn->prepare("INSERT INTO dispensing (prescription_id, drug_id, quantity_dispensed, dispensed_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $pres_id, $drug_id, $qty, $dispensed_by);
        $stmt->execute();
        $success = "Dispensed successfully.";
    } else {
        $error = "Insufficient stock!";
    }
}
?>
<?php include '../includes/header.php'; ?>
<div class="card">
    <div class="card-header bg-black text-white">Dispense Medications</div>
    <div class="card-body">
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <table class="table table-bordered">
            <thead><tr><th>Student</th><th>Drug</th><th>Dosage</th><th>Duration</th><th>Action</th></tr></thead>
            <tbody>
                <?php while($row = $pending->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= $row['drug_name'] ?></td>
                    <td><?= $row['dosage'] ?></td>
                    <td><?= $row['duration'] ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="prescription_id" value="<?= $row['id'] ?>">
                            <select name="drug_id" class="form-select form-select-sm d-inline-block w-auto">
                                <?php
                                $drugs = $conn->query("SELECT id, name, quantity FROM drugs");
                                while($d = $drugs->fetch_assoc()) {
                                    echo "<option value='{$d['id']}'>{$d['name']} (Stock: {$d['quantity']})</option>";
                                }
                                ?>
                            </select>
                            <input type="number" name="quantity" placeholder="Qty" class="form-control d-inline-block w-auto" required>
                            <button type="submit" name="dispense" class="btn btn-sm btn-primary">Dispense</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>