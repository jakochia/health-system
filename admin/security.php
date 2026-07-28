<?php
$title = "Security Question";
require_once 'includes/header.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

$message = '';
$error = '';

// Fetch existing security question
$secQ = $conn->query("SELECT question FROM user_security WHERE user_id = $user_id")->fetch_assoc();
$existing_question = $secQ['question'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question = trim($_POST['security_question']);
    $answer = trim($_POST['security_answer']);
    $hashed_answer = password_hash($answer, PASSWORD_DEFAULT);

    $check = $conn->prepare("SELECT id FROM user_security WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE user_security SET question = ?, answer_hash = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $question, $hashed_answer, $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO user_security (user_id, question, answer_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $question, $hashed_answer);
    }
    if ($stmt->execute()) {
        $message = "Security question updated successfully!";
        $existing_question = $question; // refresh displayed question
    } else {
        $error = "Error saving security question. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Question | MOHI Namarei</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .security-card {
            max-width: 550px;
            width: 100%;
            background: white;
            border-radius: 1.5rem;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: var(--transition);
        }
        .security-card:hover {
            box-shadow: var(--card-shadow-hover);
        }
        .card-header-modern {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        .card-header-modern i {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .card-header-modern h3 {
            margin: 0;
            font-weight: 600;
        }
        .card-body {
            padding: 2rem;
        }
        .form-modern .form-control {
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }
        .form-modern .form-control:focus {
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
            width: 100%;
        }
        .btn-kenya-primary {
            background: var(--kenya-black);
            color: white;
        }
        .btn-kenya-primary:hover {
            background: #1a1a1a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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
        .question-suggestions {
            margin-top: 0.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .suggestion-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
        }
        .suggestion-badge:hover {
            background: var(--kenya-red);
            color: white;
        }
        .info-text {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 1rem;
            text-align: center;
        }
        @media (max-width: 576px) {
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="security-card">
        <div class="card-header-modern">
            <i class="fas fa-shield-alt"></i>
            <h3>Security Question</h3>
            <p class="mb-0 opacity-75">Protect your account with an extra layer</p>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert-modern alert-modern-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?= htmlspecialchars($message) ?></div>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-modern alert-modern-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" class="form-modern">
                <div class="mb-4">
                    <label class="form-label">Security Question</label>
                    <input type="text" name="security_question" class="form-control" 
                           value="<?= htmlspecialchars($existing_question) ?>" 
                           placeholder="e.g., What is your mother's maiden name?" required>
                    <div class="question-suggestions">
                        <span class="suggestion-badge">What is your mother's maiden name?</span>
                        <span class="suggestion-badge">What was the name of your first pet?</span>
                        <span class="suggestion-badge">What city were you born in?</span>
                        <span class="suggestion-badge">What was your first school?</span>
                        <span class="suggestion-badge">What is your favorite book?</span>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Answer</label>
                    <input type="text" name="security_answer" class="form-control" 
                           placeholder="Your answer (case-sensitive)" required>
                    <small class="text-muted">Store this answer safely – you'll need it to recover your password.</small>
                </div>
                <button type="submit" class="btn btn-kenya-primary btn-modern">
                    <i class="fas fa-save me-1"></i> Save Security Question
                </button>
            </form>

            <div class="info-text">
                <i class="fas fa-lock"></i> Your answer is encrypted and will never be shared.
            </div>
        </div>
    </div>

    <script>
        // Click on suggestion to fill the question field
        document.querySelectorAll('.suggestion-badge').forEach(badge => {
            badge.addEventListener('click', function() {
                document.querySelector('input[name="security_question"]').value = this.innerText;
            });
        });
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>