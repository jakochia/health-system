<?php
require_once 'includes/auth.php';
$auth = new Auth();
if ($auth->isLoggedIn()) {
    $auth->redirectBasedOnRole();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    if ($auth->login($username, $password)) {
        $auth->redirectBasedOnRole();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | MOHI Namarei Health Portal</title>
    <!-- Bootstrap 5 + Icons + Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #000000 0%, #1a1a2e 100%);
            position: relative;
            overflow: hidden;
        }

        /* Animated geometric pattern */
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            top: -50%;
            left: -50%;
            background-image: radial-gradient(circle at 30% 40%, rgba(0, 132, 61, 0.1) 2%, transparent 2.5%);
            background-size: 40px 40px;
            animation: movePattern 30s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes movePattern {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(10%, 10%) rotate(5deg); }
        }

        .login-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 460px;
            margin: 1.5rem;
        }

        /* Glassmorphic card with Kenyan colors */
        .card-modern {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(15px);
            border-radius: 2rem;
            border: 1px solid rgba(0, 132, 61, 0.3);
            box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.5), 0 8px 18px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 55px -12px rgba(0, 132, 61, 0.2);
            border-color: rgba(0, 132, 61, 0.6);
        }

        /* Header with red accent */
        .card-header-glow {
            background: linear-gradient(135deg, #000000 0%, #8B0000 100%);
            padding: 1.8rem 1.5rem 1.5rem;
            text-align: center;
            border-bottom: none;
            color: white;
            position: relative;
        }

        .card-header-glow::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #00843D, #FFD700, #00843D);
        }

        .logo-wrapper {
            background: rgba(0, 132, 61, 0.2);
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            backdrop-filter: blur(4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            border: 2px solid #00843D;
        }

        .logo-wrapper i {
            font-size: 3rem;
            color: #FFD700;
            text-shadow: 0 0 10px rgba(0,132,61,0.5);
        }

        .card-header-glow h3 {
            font-weight: 700;
            letter-spacing: -0.3px;
            margin-bottom: 0.25rem;
            color: white;
        }

        .card-header-glow p {
            opacity: 0.9;
            font-size: 0.9rem;
            margin-bottom: 0;
            color: #ddd;
        }

        /* Form styling */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 1.1rem;
            transition: color 0.2s;
        }

        .form-control-icon {
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            border-radius: 3rem;
            border: 1px solid rgba(255,255,255,0.2);
            background-color: rgba(255,255,255,0.1);
            color: white;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            backdrop-filter: blur(5px);
        }

        .form-control-icon:focus {
            border-color: #00843D;
            background-color: rgba(0,0,0,0.5);
            box-shadow: 0 0 0 4px rgba(0, 132, 61, 0.3);
            outline: none;
            color: white;
        }

        .form-control-icon:focus + i {
            color: #FFD700;
        }

        .form-control-icon::placeholder {
            color: rgba(255,255,255,0.6);
        }

        /* Button with green gradient */
        .btn-login {
            background: linear-gradient(95deg, #00843D, #FFD700);
            border: none;
            padding: 0.85rem;
            border-radius: 3rem;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.5px;
            color: #000;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 132, 61, 0.3);
            width: 100%;
        }

        .btn-login:hover {
            transform: scale(1.02);
            box-shadow: 0 12px 28px rgba(0, 132, 61, 0.5);
            filter: brightness(1.05);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        /* Links & extras */
        .forgot-link {
            color: #FFD700;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #00843D;
            text-decoration: underline;
        }

        .form-check-label {
            color: #ddd;
        }

        .form-check-input:checked {
            background-color: #00843D;
            border-color: #00843D;
        }

        .footer-note {
            font-size: 0.75rem;
            color: #aaa;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 1.25rem;
            margin-top: 0.5rem;
            text-align: center;
        }

        .alert-modern {
            border-radius: 2rem;
            background: rgba(255, 245, 245, 0.2);
            backdrop-filter: blur(5px);
            border-left: 4px solid #FFD700;
            color: #FFD700;
            font-size: 0.9rem;
            padding: 0.75rem 1.2rem;
            margin-bottom: 1.5rem;
        }

        /* responsiveness */
        @media (max-width: 480px) {
            .card-header-glow {
                padding: 1.2rem;
            }
            .logo-wrapper {
                width: 70px;
                height: 70px;
            }
            .logo-wrapper i {
                font-size: 2.5rem;
            }
            .card-header-glow h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card card-modern">
            <div class="card-header-glow">
                <div class="logo-wrapper">
                    <i class="fas fa-hospital-user"></i>
                </div>
                <h3>Welcome back</h3>
                <p>Your health, our priority ✨</p>
            </div>
            <div class="card-body p-4 p-md-5">
                <?php if (isset($error)): ?>
                    <div class="alert-modern">
                        <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" class="form-control form-control-icon" placeholder="Username or email" required autofocus>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="form-control form-control-icon" placeholder="Password" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label small" for="remember">Keep me logged in</label>
                        </div>
                        <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Sign In
                    </button>
                </form>

                <div class="footer-note">
                    <i class="fas fa-shield-alt me-1"></i> Secure & encrypted · 24/7 health support
                </div>
            </div>
        </div>
    </div>
</body>
</html>