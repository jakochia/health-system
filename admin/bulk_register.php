<?php
$title = "Bulk Registration";
require_once 'includes/header.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$message = '';
$error = '';

// Handle file upload and import (original logic unchanged)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['import_type']) && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $import_type = $_POST['import_type']; // 'students' or 'staff'
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle); // first row headers

        // Expected columns
        if ($import_type == 'students') {
            $expected = ['admission_no', 'full_name', 'class', 'parent_phone', 'date_of_birth', 'blood_group', 'allergies'];
        } else {
            $expected = ['staff_id', 'full_name', 'role', 'phone', 'email'];
        }

        // Validate headers
        if ($headers !== $expected) {
            $error = "CSV headers do not match expected columns: " . implode(', ', $expected);
        } else {
            $success_count = 0;
            $error_rows = [];
            $row_num = 1; // after header

            while (($data = fgetcsv($handle)) !== FALSE) {
                $row_num++;
                $row = array_combine($headers, $data);

                if ($import_type == 'students') {
                    // Check if admission_no already exists
                    $check = $conn->prepare("SELECT id FROM students WHERE admission_no = ?");
                    $check->bind_param("s", $row['admission_no']);
                    $check->execute();
                    $check->store_result();
                    if ($check->num_rows > 0) {
                        $error_rows[] = "Row $row_num: Admission number {$row['admission_no']} already exists.";
                        continue;
                    }
                    // Check if username exists
                    $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
                    $check_user->bind_param("s", $row['admission_no']);
                    $check_user->execute();
                    $check_user->store_result();
                    if ($check_user->num_rows > 0) {
                        $error_rows[] = "Row $row_num: Username {$row['admission_no']} already exists.";
                        continue;
                    }

                    // Insert student
                    $stmt = $conn->prepare("INSERT INTO students (admission_no, full_name, class, parent_phone, date_of_birth, blood_group, allergies) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", 
                        $row['admission_no'], $row['full_name'], $row['class'], 
                        $row['parent_phone'], $row['date_of_birth'], $row['blood_group'], $row['allergies']
                    );
                    if ($stmt->execute()) {
                        $student_id = $stmt->insert_id;
                        // Create user account
                        $username = $row['admission_no'];
                        $default_password = $row['admission_no']; // or something else
                        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
                        $role = 'student';
                        $user_stmt = $conn->prepare("INSERT INTO users (username, password, role, student_id) VALUES (?, ?, ?, ?)");
                        $user_stmt->bind_param("sssi", $username, $hashed_password, $role, $student_id);
                        if ($user_stmt->execute()) {
                            $success_count++;
                        } else {
                            $error_rows[] = "Row $row_num: Failed to create user account for {$row['admission_no']}.";
                        }
                    } else {
                        $error_rows[] = "Row $row_num: Failed to insert student.";
                    }
                } 
                else if ($import_type == 'staff') {
                    // Check if staff_id exists
                    $check = $conn->prepare("SELECT id FROM staff WHERE staff_id = ?");
                    $check->bind_param("s", $row['staff_id']);
                    $check->execute();
                    $check->store_result();
                    if ($check->num_rows > 0) {
                        $error_rows[] = "Row $row_num: Staff ID {$row['staff_id']} already exists.";
                        continue;
                    }
                    // Check if username exists
                    $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
                    $check_user->bind_param("s", $row['staff_id']);
                    $check_user->execute();
                    $check_user->store_result();
                    if ($check_user->num_rows > 0) {
                        $error_rows[] = "Row $row_num: Username {$row['staff_id']} already exists.";
                        continue;
                    }

                    // Insert user first
                    $username = $row['staff_id'];
                    $default_password = $row['staff_id'];
                    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
                    $role = 'teacher'; // staff can be teacher, clinician, etc. We'll map role from CSV
                    // Map role from CSV: teacher, clinician, pharmacist
                    $staff_role = $row['role'];
                    $valid_roles = ['teacher', 'clinician', 'pharmacist'];
                    if (!in_array($staff_role, $valid_roles)) {
                        $error_rows[] = "Row $row_num: Invalid role '$staff_role'. Must be teacher, clinician, or pharmacist.";
                        continue;
                    }

                    $user_stmt = $conn->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
                    $user_stmt->bind_param("ssss", $username, $hashed_password, $role, $row['email']);
                    if ($user_stmt->execute()) {
                        $user_id = $user_stmt->insert_id;
                        // Insert staff
                        $staff_stmt = $conn->prepare("INSERT INTO staff (user_id, staff_id, full_name, role, phone) VALUES (?, ?, ?, ?, ?)");
                        $staff_stmt->bind_param("issss", $user_id, $row['staff_id'], $row['full_name'], $staff_role, $row['phone']);
                        if ($staff_stmt->execute()) {
                            $success_count++;
                        } else {
                            $error_rows[] = "Row $row_num: Failed to insert staff record.";
                        }
                    } else {
                        $error_rows[] = "Row $row_num: Failed to create user account.";
                    }
                }
            }
            fclose($handle);
            $message = "Import completed. $success_count records imported successfully.";
            if (!empty($error_rows)) {
                $error .= "<br>Errors:<br>" . implode("<br>", $error_rows);
            }
        }
    } else {
        $error = "Please select a file to upload.";
    }
}
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
    .nav-tabs-modern {
        border-bottom: 1px solid #e2e8f0;
        gap: 0.5rem;
        padding: 0 1rem;
        background: #fafcff;
    }
    .nav-tabs-modern .nav-link {
        border: none;
        color: #64748b;
        font-weight: 500;
        padding: 0.8rem 1.25rem;
        margin-bottom: -1px;
        transition: var(--transition);
        border-radius: 0;
        position: relative;
    }
    .nav-tabs-modern .nav-link i {
        margin-right: 0.5rem;
    }
    .nav-tabs-modern .nav-link:hover {
        color: var(--kenya-red);
        border-bottom: 2px solid #cbd5e1;
    }
    .nav-tabs-modern .nav-link.active {
        color: var(--kenya-red);
        border-bottom: 2px solid var(--kenya-red);
        background: transparent;
    }
    .tab-content {
        padding: 1.5rem;
    }
    .form-modern .form-control, .form-modern .form-select {
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        padding: 0.75rem 1rem;
        transition: var(--transition);
    }
    .form-modern .form-control:focus, .form-modern .form-select:focus {
        border-color: var(--kenya-red);
        box-shadow: 0 0 0 3px rgba(187, 0, 0, 0.2);
        outline: none;
    }
    .form-modern .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
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
    .btn-kenya-secondary {
        background: var(--kenya-green);
        color: white;
    }
    .btn-kenya-secondary:hover {
        background: #006b31;
        box-shadow: 0 4px 12px rgba(0,132,61,0.3);
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
    .file-upload-wrapper {
        position: relative;
        margin-bottom: 1rem;
    }
    .file-upload-label {
        display: inline-block;
        background: #f1f5f9;
        border: 1px dashed #cbd5e1;
        border-radius: 0.75rem;
        padding: 2rem;
        text-align: center;
        width: 100%;
        cursor: pointer;
        transition: var(--transition);
    }
    .file-upload-label:hover {
        border-color: var(--kenya-red);
        background: #fef2f2;
    }
    .file-upload-label i {
        font-size: 2rem;
        color: var(--kenya-red);
        margin-bottom: 0.5rem;
    }
    .file-upload-input {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        cursor: pointer;
    }
    .file-name {
        font-size: 0.85rem;
        color: #475569;
        margin-top: 0.5rem;
    }
    @media (max-width: 768px) {
        .tab-content {
            padding: 1rem;
        }
        .nav-tabs-modern .nav-link {
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
        }
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="modern-card">
        <div class="card-header-modern">
            <i class="fas fa-file-upload"></i> Bulk Registration
        </div>
        <div class="card-body p-0">
            <!-- Alerts -->
            <?php if ($message): ?>
                <div class="alert-modern alert-modern-success m-3">
                    <i class="fas fa-check-circle fs-5"></i>
                    <div><?= nl2br(htmlspecialchars($message)) ?></div>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-modern alert-modern-danger m-3">
                    <i class="fas fa-exclamation-triangle fs-5"></i>
                    <div><?= nl2br(htmlspecialchars($error)) ?></div>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <ul class="nav nav-tabs-modern" id="importTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab">
                        <i class="fas fa-user-graduate"></i> Students
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff" type="button" role="tab">
                        <i class="fas fa-user-md"></i> Staff
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Students Tab -->
                <div class="tab-pane fade show active" id="students" role="tabpanel">
                    <form method="POST" enctype="multipart/form-data" class="form-modern">
                        <input type="hidden" name="import_type" value="students">
                        <div class="mb-4">
                            <label class="form-label">CSV File (Students)</label>
                            <div class="file-upload-wrapper">
                                <div class="file-upload-label" id="studentFileLabel">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p class="mb-0">Click or drag to upload CSV file</p>
                                </div>
                                <input type="file" name="csv_file" class="file-upload-input" accept=".csv" required>
                            </div>
                            <div id="studentFileName" class="file-name"></div>
                            <small class="text-muted">Columns: admission_no, full_name, class, parent_phone, date_of_birth, blood_group, allergies</small>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-kenya-primary btn-modern">
                                <i class="fas fa-upload me-1"></i> Import Students
                            </button>
                            <button type="button" onclick="downloadTemplate('students')" class="btn btn-kenya-secondary btn-modern">
                                <i class="fas fa-download me-1"></i> Download Template
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Staff Tab -->
                <div class="tab-pane fade" id="staff" role="tabpanel">
                    <form method="POST" enctype="multipart/form-data" class="form-modern">
                        <input type="hidden" name="import_type" value="staff">
                        <div class="mb-4">
                            <label class="form-label">CSV File (Staff)</label>
                            <div class="file-upload-wrapper">
                                <div class="file-upload-label" id="staffFileLabel">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p class="mb-0">Click or drag to upload CSV file</p>
                                </div>
                                <input type="file" name="csv_file" class="file-upload-input" accept=".csv" required>
                            </div>
                            <div id="staffFileName" class="file-name"></div>
                            <small class="text-muted">Columns: staff_id, full_name, role, phone, email</small>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-kenya-primary btn-modern">
                                <i class="fas fa-upload me-1"></i> Import Staff
                            </button>
                            <button type="button" onclick="downloadTemplate('staff')" class="btn btn-kenya-secondary btn-modern">
                                <i class="fas fa-download me-1"></i> Download Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Display file name when selected
document.querySelectorAll('.file-upload-input').forEach(input => {
    input.addEventListener('change', function(e) {
        const file = this.files[0];
        const fileNameSpan = this.closest('.file-upload-wrapper').nextElementSibling;
        if (file) {
            fileNameSpan.textContent = `Selected: ${file.name}`;
        } else {
            fileNameSpan.textContent = '';
        }
    });
});

function downloadTemplate(type) {
    let headers = '';
    let content = '';
    if (type === 'students') {
        headers = 'admission_no,full_name,class,parent_phone,date_of_birth,blood_group,allergies';
        content = 'STU001,John Doe,Form 1,0712345678,2008-05-15,A+,None';
    } else {
        headers = 'staff_id,full_name,role,phone,email';
        content = 'TCH001,Jane Smith,teacher,0722000000,jane@mohi.ac.ke';
    }
    const blob = new Blob([headers + "\n" + content], {type: 'text/csv'});
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.href = url;
    link.download = `${type}_template.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}
</script>

<?php require_once 'includes/footer.php'; ?>