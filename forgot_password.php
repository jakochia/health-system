<?php
session_start();
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 1) {
        $username = trim($_POST['username']);
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Check if security question exists
            $qStmt = $conn->prepare("SELECT question FROM user_security WHERE user_id = ?");
            $qStmt->bind_param("i", $row['id']);
            $qStmt->execute();
            $qResult = $qStmt->get_result();
            if ($qRow = $qResult->fetch_assoc()) {
                $_SESSION['reset_user_id'] = $row['id'];
                $_SESSION['reset_username'] = $row['username'];
                $_SESSION['reset_question'] = $qRow['question'];
                header("Location: forgot_password.php?step=2");
                exit;
            } else {
                $error = "No security question set for this user. Please contact administrator.";
            }
        } else {
            $error = "Username not found.";
        }
    } elseif ($step == 2) {
        if (!isset($_SESSION['reset_user_id'])) {
            header("Location: forgot_password.php");
            exit;
        }
        $answer = trim($_POST['answer']);
        $userId = $_SESSION['reset_user_id'];
        $stmt = $conn->prepare("SELECT answer_hash FROM user_security WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($answer, $row['answer_hash'])) {
                header("Location: forgot_password.php?step=3");
                exit;
            } else {
                $error = "Incorrect answer.";
            }
        } else {
            $error = "Security data missing.";
        }
    } elseif ($step == 3) {
        if (!isset($_SESSION['reset_user_id'])) {
            header("Location: forgot_password.php");
            exit;
        }
        $new_password = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        if ($new_password !== $confirm) {
            $error = "Passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $userId = $_SESSION['reset_user_id'];
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $userId);
            if ($stmt->execute()) {
                $success = "Password reset successfully. You can now login.";
                // Clear session data
                unset($_SESSION['reset_user_id'], $_SESSION['reset_username'], $_SESSION['reset_question']);
                // Redirect after a few seconds or show login link
                header("refresh:3;url=index.php");
            } else {
                $error = "Database error. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MOHI Namarei</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-top: 100px; }
        .btn-primary { background-color: #000; border-color: #000; }
        .btn-primary:hover { background-color: #333; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-black text-white text-center">
                    <h4>Reset Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?> <a href="index.php">Login</a></div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if (!$success && $step == 1): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Next</button>
                        </form>
                    <?php elseif ($step == 2 && isset($_SESSION['reset_username'])): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label>Username: <strong><?= htmlspecialchars($_SESSION['reset_username']) ?></strong></label>
                            </div>
                            <div class="mb-3">
                                <label>Security Question: <?= htmlspecialchars($_SESSION['reset_question']) ?></label>
                                <input type="text" name="answer" class="form-control" placeholder="Your answer" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Verify</button>
                        </form>
                    <?php elseif ($step == 3): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label>New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="index.php">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>