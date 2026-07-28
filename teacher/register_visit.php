<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('teacher')) header("Location: ../index.php");

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get clinician ID from staff table based on logged-in user
$userId = $_SESSION['user_id'];
$clinician = $conn->query("SELECT id FROM staff WHERE user_id = $userId")->fetch_assoc();
$clinicianId = $clinician ? $clinician['id'] : null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $symptoms = $_POST['symptoms'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $referred = isset($_POST['referred']) ? 1 : 0;
    $referral_hospital = $_POST['referral_hospital'] ?? '';

    $stmt = $conn->prepare("INSERT INTO visits (student_id, clinician_id, symptoms, diagnosis, treatment, referred, referral_hospital) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssis", $student_id, $clinicianId, $symptoms, $diagnosis, $treatment, $referred, $referral_hospital);
    if ($stmt->execute()) {
        $visit_id = $stmt->insert_id;
        // Prescriptions
        if(!empty($_POST['drug_name'])) {
            foreach($_POST['drug_name'] as $index => $drug) {
                if(!empty($drug)) {
                    $dosage = $_POST['dosage'][$index];
                    $duration = $_POST['duration'][$index];
                    $notes = $_POST['notes'][$index] ?? '';
                    $stmt2 = $conn->prepare("INSERT INTO prescriptions (visit_id, drug_name, dosage, duration, notes) VALUES (?, ?, ?, ?, ?)");
                    $stmt2->bind_param("issss", $visit_id, $drug, $dosage, $duration, $notes);
                    $stmt2->execute();
                }
            }
        }
        $success = "Visit recorded successfully!";
    } else {
        $error = "Error: " . $conn->error;
    }
}

$students = $conn->query("SELECT id, full_name, admission_no FROM students ORDER BY full_name");

// Common symptoms and diagnosis for suggestions
$commonSymptoms = [
    "Fever", "Headache", "Cough", "Sore throat", "Runny nose", "Fatigue", 
    "Nausea", "Vomiting", "Diarrhea", "Abdominal pain", "Chest pain", 
    "Shortness of breath", "Dizziness", "Rash", "Muscle aches", "Sneezing"
];
$commonDiagnoses = [
    "Upper Respiratory Infection", "Influenza", "Gastroenteritis", "Allergic Reaction", 
    "Tonsillitis", "Bronchitis", "Asthma exacerbation", "Urinary Tract Infection", 
    "Conjunctivitis", "Headache - Tension", "Migraine", "Sprain/Strain", 
    "Dermatitis", "Anxiety", "Dehydration"
];
?>
<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Medical Visit | MOHI Namarei</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #0f4c5c;
            --primary-light: #1e6f82;
            --accent: #e56b6f;
            --gray-light: #f8f9fa;
        }
        body {
            background: linear-gradient(145deg, #eef2f7 0%, #d9e2ec 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, 'Inter', sans-serif;
        }
        .card-modern {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .card-modern:hover {
            transform: translateY(-3px);
        }
        .card-header-modern {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            padding: 1.2rem 1.5rem;
            color: white;
            border-bottom: none;
        }
        .card-header-modern h4 {
            margin: 0;
            font-weight: 600;
            letter-spacing: -0.3px;
        }
        .card-header-modern h4 i {
            margin-right: 8px;
        }
        .form-label {
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            padding: 0.6rem 1rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(229, 107, 111, 0.2);
        }
        .btn-modern {
            background: linear-gradient(95deg, var(--primary), var(--accent));
            border: none;
            border-radius: 2rem;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(229,107,111,0.3);
        }
        .prescription-row {
            background: var(--gray-light);
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        .remove-prescription {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: none;
            border: none;
            color: #dc3545;
            font-size: 1.2rem;
            cursor: pointer;
        }
        .alert-modern {
            border-radius: 1rem;
            border-left: 5px solid;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .badge-suggestion {
            background-color: #e2e8f0;
            color: #1e293b;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            margin-right: 0.3rem;
            margin-bottom: 0.3rem;
            display: inline-block;
            cursor: pointer;
            transition: background 0.2s;
        }
        .badge-suggestion:hover {
            background-color: var(--accent);
            color: white;
        }
        @media (max-width: 768px) {
            .prescription-row .row > div {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card card-modern">
                <div class="card-header-modern">
                    <h4><i class="fas fa-stethoscope"></i> Register New Medical Visit</h4>
                </div>
                <div class="card-body p-4 p-lg-5">
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success alert-modern">
                            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger alert-modern">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="visitForm">
                        <!-- Student Selection -->
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-user-graduate me-1"></i> Student</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">-- Select Student --</option>
                                <?php while($row = $students->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['full_name']) ?> (<?= $row['admission_no'] ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Symptoms with suggestions -->
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-head-side-medical me-1"></i> Symptoms</label>
                            <textarea name="symptoms" class="form-control" rows="3" required placeholder="Describe symptoms..."></textarea>
                            <div class="mt-2">
                                <small class="text-muted">Common symptoms:</small>
                                <div id="symptomSuggestions">
                                    <?php foreach($commonSymptoms as $symptom): ?>
                                        <span class="badge-suggestion" data-field="symptoms"><?= htmlspecialchars($symptom) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Diagnosis with dropdown suggestions -->
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-clinic-medical me-1"></i> Diagnosis</label>
                            <input type="text" name="diagnosis" class="form-control" list="diagnosisList" required placeholder="e.g., Upper Respiratory Infection">
                            <datalist id="diagnosisList">
                                <?php foreach($commonDiagnoses as $diag): ?>
                                    <option value="<?= htmlspecialchars($diag) ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <div class="mt-2">
                                <small class="text-muted">Common diagnoses:</small>
                                <div id="diagnosisSuggestions">
                                    <?php foreach($commonDiagnoses as $diag): ?>
                                        <span class="badge-suggestion" data-field="diagnosis"><?= htmlspecialchars($diag) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Treatment -->
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-notes-medical me-1"></i> Treatment / Notes</label>
                            <textarea name="treatment" class="form-control" rows="3" placeholder="Treatment plan, advice, etc."></textarea>
                        </div>

                        <!-- Prescriptions (dynamic) -->
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-prescription-bottle me-1"></i> Prescriptions</label>
                            <div id="prescriptionsContainer">
                                <!-- Initial prescription row -->
                                <div class="prescription-row" id="prescriptionRowTemplate" style="display: none;">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <input type="text" name="drug_name[]" class="form-control" placeholder="Drug name">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="dosage[]" class="form-control" placeholder="Dosage (e.g., 500mg)">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="duration[]" class="form-control" placeholder="Duration (e.g., 5 days)">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="notes[]" class="form-control" placeholder="Notes">
                                        </div>
                                    </div>
                                    <button type="button" class="remove-prescription" title="Remove drug"><i class="fas fa-trash-alt"></i></button>
                                </div>
                                <!-- Actual rows will be added here -->
                                <div id="prescriptionsList">
                                    <div class="prescription-row">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <input type="text" name="drug_name[]" class="form-control" placeholder="Drug name">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="dosage[]" class="form-control" placeholder="Dosage">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="duration[]" class="form-control" placeholder="Duration">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="notes[]" class="form-control" placeholder="Notes">
                                            </div>
                                        </div>
                                        <button type="button" class="remove-prescription" title="Remove drug"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addPrescriptionBtn">
                                <i class="fas fa-plus-circle"></i> Add another drug
                            </button>
                        </div>

                        <!-- Referral -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="referred" class="form-check-input" id="referCheck">
                                <label class="form-check-label" for="referCheck">Refer to hospital</label>
                            </div>
                            <div id="referralDiv" style="display:none;" class="mt-2">
                                <label class="form-label">Hospital Name</label>
                                <input type="text" name="referral_hospital" class="form-control" placeholder="Enter hospital name">
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-modern px-5">
                                <i class="fas fa-save me-2"></i> Save Visit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to add new prescription row
    function addPrescriptionRow() {
        const template = document.getElementById('prescriptionRowTemplate');
        const newRow = template.cloneNode(true);
        newRow.removeAttribute('id');
        newRow.style.display = 'block';
        // Clear input values in the cloned row
        const inputs = newRow.querySelectorAll('input');
        inputs.forEach(input => input.value = '');
        // Append to list
        document.getElementById('prescriptionsList').appendChild(newRow);
        // Attach remove event to the new button
        attachRemoveEvent(newRow.querySelector('.remove-prescription'));
    }

    function attachRemoveEvent(btn) {
        btn.addEventListener('click', function() {
            this.closest('.prescription-row').remove();
        });
    }

    // Attach remove events to existing remove buttons
    document.querySelectorAll('.remove-prescription').forEach(btn => attachRemoveEvent(btn));

    // Add button click
    document.getElementById('addPrescriptionBtn').addEventListener('click', addPrescriptionRow);

    // Referral checkbox toggle
    document.getElementById('referCheck').addEventListener('change', function() {
        const referralDiv = document.getElementById('referralDiv');
        referralDiv.style.display = this.checked ? 'block' : 'none';
    });

    // Click on suggestion badges to fill corresponding textarea/input
    document.querySelectorAll('.badge-suggestion').forEach(badge => {
        badge.addEventListener('click', function() {
            const field = this.getAttribute('data-field');
            const value = this.innerText;
            if (field === 'symptoms') {
                const textarea = document.querySelector('textarea[name="symptoms"]');
                let current = textarea.value;
                textarea.value = current ? current + ', ' + value : value;
            } else if (field === 'diagnosis') {
                const input = document.querySelector('input[name="diagnosis"]');
                input.value = value;
            }
        });
    });
</script>
</body>
</html>
<?php include '../includes/footer.php'; ?>