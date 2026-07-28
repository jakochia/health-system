<?php
$title = "NHIF Report";
require_once 'includes/header.php';
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Fetch students with NHIF numbers
$students = $conn->query("SELECT admission_no, full_name, class, nhif_number, nhif_member_type, nhif_valid_until FROM students WHERE nhif_number IS NOT NULL AND nhif_number != '' ORDER BY full_name");
// Fetch staff with NHIF numbers
$staff = $conn->query("SELECT staff_id, full_name, role, nhif_number, nhif_member_type, nhif_valid_until FROM staff WHERE nhif_number IS NOT NULL AND nhif_number != '' ORDER BY full_name");
?>
<div class="card">
    <div class="card-header bg-black text-white">NHIF Coverage Report</div>
    <div class="card-body">
        <h5>Students with NHIF</h5>
        <table class="table table-bordered">
            <thead>
                <tr><th>Admission No</th><th>Full Name</th><th>Class</th><th>NHIF No</th><th>Member Type</th><th>Valid Until</th> </tr>
            </thead>
            <tbody>
                <?php while($row = $students->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['admission_no'] ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= $row['class'] ?></td>
                    <td><?= $row['nhif_number'] ?></td>
                    <td><?= ucfirst($row['nhif_member_type']) ?></td>
                    <td><?= $row['nhif_valid_until'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h5 class="mt-4">Staff with NHIF</h5>
        <table class="table table-bordered">
            <thead>
                <tr><th>Staff ID</th><th>Full Name</th><th>Role</th><th>NHIF No</th><th>Member Type</th><th>Valid Until</th> </tr>
            </thead>
            <tbody>
                <?php while($row = $staff->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['staff_id'] ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= ucfirst($row['role']) ?></td>
                    <td><?= $row['nhif_number'] ?></td>
                    <td><?= ucfirst($row['nhif_member_type']) ?></td>
                    <td><?= $row['nhif_valid_until'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="mt-3">
            <a href="export_nhif.php" class="btn btn-primary">Export to Excel</a>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>