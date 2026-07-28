<?php
session_start();
// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/");
            break;
        case 'teacher':
            header("Location: teacher/");
            break;
        case 'student':
            header("Location: student/");
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>MOHI Namarei – School Health Management System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 (free) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Bootstrap 5 CSS (minimal for grid and utilities) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ---------- GLOBAL ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            background: #f8fafc;
        }
        h1, h2, h3, h4, h5, h6, .navbar-brand, .btn {
            font-family: 'Poppins', sans-serif;
        }
        /* ---------- ANIMATIONS ---------- */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .animate-fade-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        /* ---------- NAVBAR (GLASS) ---------- */
        .navbar {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            padding: 1rem 0;
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: -0.5px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }
        .navbar-brand i {
            font-size: 1.8rem;
            background: linear-gradient(135deg, #fff, #22c55e);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-right: 10px;
        }
        .nav-link {
            font-weight: 500;
            transition: color 0.3s;
            margin: 0 0.5rem;
        }
        .nav-link:hover {
            color: #22c55e !important;
        }
        .btn-glow {
            background: linear-gradient(45deg, #15803d, #22c55e);
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.8rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 0 10px rgba(34,197,94,0.3);
        }
        .btn-glow:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(34,197,94,0.5);
            background: linear-gradient(45deg, #22c55e, #15803d);
        }
        /* ---------- HERO SECTION ---------- */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: linear-gradient(135deg, #0f0f1f 0%, #0a0a1a 100%);
            color: white;
            overflow: hidden;
        }
        /* Particle canvas */
        #particle-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            padding: 0 1rem;
        }
        .hero h1 {
            font-size: 3.8rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff, #22c55e);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.02em;
        }
        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        .btn-hero {
            background: linear-gradient(45deg, #15803d, #22c55e);
            border: none;
            padding: 12px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 30px rgba(34,197,94,0.3);
        }
        /* ---------- FEATURES SECTION ---------- */
        .features-section {
            padding: 100px 0;
            background: #ffffff;
        }
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 3rem;
            text-align: center;
            background: linear-gradient(135deg, #1e293b, #15803d);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .feature-card {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 24px;
            padding: 2rem 1.5rem;
            text-align: center;
            transition: all 0.4s;
            height: 100%;
            box-shadow: 0 20px 35px -15px rgba(0,0,0,0.1);
        }
        .feature-card:hover {
            transform: translateY(-10px);
            border-color: #22c55e;
            box-shadow: 0 30px 40px -20px rgba(34,197,94,0.3);
        }
        .feature-icon {
            font-size: 3rem;
            color: #22c55e;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        .feature-card h5 {
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .feature-card p {
            color: #475569;
            line-height: 1.5;
        }
        /* ---------- STATS SECTION ---------- */
        .stats-section {
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 80px 0;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #fff, #22c55e);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .stat-label {
            font-size: 1rem;
            opacity: 0.8;
            margin-top: 0.5rem;
        }
        /* ---------- CONTACT SECTION ---------- */
        .contact-section {
            padding: 80px 0;
            background: #ffffff;
        }
        .contact-card {
            background: #f9fafb;
            border-radius: 28px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        }
        .contact-icon {
            width: 45px;
            height: 45px;
            background: #22c55e20;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: #15803d;
        }
        /* ---------- FOOTER ---------- */
        footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 2rem 0;
            text-align: center;
        }
        footer a {
            color: #22c55e;
            text-decoration: none;
        }
        footer a:hover {
            text-decoration: underline;
        }
        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.3rem;
            }
            .hero p {
                font-size: 1rem;
            }
            .section-title {
                font-size: 2rem;
            }
            .stat-number {
                font-size: 2.2rem;
            }
            .feature-card {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Navigation with Hospital Icon Logo -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-hospital-user"></i> <!-- Hospital icon as logo -->
            MOHI Namarei
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#stats">Statistics</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                <li class="nav-item"><a class="btn btn-glow text-white ms-3" href="login.php">Login</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section with Particle Background (unchanged) -->
<div class="hero">
    <canvas id="particle-canvas"></canvas>
    <div class="hero-content">
        <h1 class="animate-fade-up">MOHI Namarei<br>School Health Management</h1>
        <p class="animate-fade-up" style="animation-delay: 0.1s;">Streamlining student health records, clinic operations, and emergency services for a healthier school community.</p>
        <a href="login.php" class="btn btn-hero btn-lg text-white animate-fade-up" style="animation-delay: 0.2s;">Get Started <i class="fas fa-arrow-right ms-2"></i></a>
    </div>
</div>

<!-- Features Section -->
<div id="features" class="features-section">
    <div class="container">
        <h2 class="section-title">Our Features</h2>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <h5>Patient Management</h5>
                    <p>Comprehensive records for all students, including medical history, allergies, and NHIF details.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-clinic-medical"></i></div>
                    <h5>Clinic Visits & Prescriptions</h5>
                    <p>Log symptoms, diagnoses, treatments, and track prescriptions with stock alerts.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-ambulance"></i></div>
                    <h5>Ambulance Service</h5>
                    <p>Request emergency transport with real‑time tracking and notifications.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                    <h5>Appointment System</h5>
                    <p>Book clinic appointments and manage queues efficiently.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h5>Reports & Analytics</h5>
                    <p>Generate health trends, disease analysis, and export data to PDF/Excel.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h5>Secure & Role‑Based Access</h5>
                    <p>Data protection with encryption, audit logs, and multi‑role permissions.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Section (unchanged) -->
<div id="stats" class="stats-section">
    <div class="container">
        <h2 class="text-center text-white mb-5 fw-bold">System Impact</h2>
        <div class="row text-center">
            <div class="col-md-3 mb-4">
                <div class="stat-number" data-target="500">0</div>
                <div class="stat-label">Students Served</div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-number" data-target="2000">0</div>
                <div class="stat-label">Clinic Visits</div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-number" data-target="50">0</div>
                <div class="stat-label">Staff Members</div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-number" data-target="24">0</div>
                <div class="stat-label">Ambulance Availability <small>(hrs)</small></div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Section (unchanged) -->
<div id="contact" class="contact-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="contact-card">
                    <div class="row">
                        <div class="col-md-6">
                            <h3 class="fw-bold mb-3">Contact Us</h3>
                            <p><i class="fas fa-map-marker-alt contact-icon me-2"></i> MOHI Namarei School, Nairobi, Kenya</p>
                            <p><i class="fas fa-phone-alt contact-icon me-2"></i> +254 712 345 678</p>
                            <p><i class="fas fa-envelope contact-icon me-2"></i> clinic@mohinamarei.ac.ke</p>
                        </div>
                        <div class="col-md-6">
                            <h3 class="fw-bold mb-3">Quick Links</h3>
                            <ul class="list-unstyled">
                                <li class="mb-2"><a href="login.php" class="text-decoration-none text-dark"><i class="fas fa-chevron-right text-success me-2"></i>Staff/Student Login</a></li>
                                <li class="mb-2"><a href="#" class="text-decoration-none text-dark"><i class="fas fa-chevron-right text-success me-2"></i>Privacy Policy</a></li>
                                <li class="mb-2"><a href="#" class="text-decoration-none text-dark"><i class="fas fa-chevron-right text-success me-2"></i>Support</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; <?= date('Y') ?> MOHI Namarei School. All rights reserved.</p>
        <p>Built with <i class="fas fa-heart text-danger"></i> for the school community</p>
    </div>
</footer>

<!-- Scripts (unchanged) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Particle background (unchanged)
    const canvas = document.getElementById('particle-canvas');
    const ctx = canvas.getContext('2d');
    let particles = [];
    let width, height;

    function resizeCanvas() {
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.width = width;
        canvas.height = height;
        initParticles();
    }

    function initParticles() {
        particles = [];
        const particleCount = Math.min(100, Math.floor(width * height / 10000));
        for (let i = 0; i < particleCount; i++) {
            particles.push({
                x: Math.random() * width,
                y: Math.random() * height,
                radius: Math.random() * 2 + 1,
                vx: (Math.random() - 0.5) * 0.5,
                vy: (Math.random() - 0.5) * 0.3,
                opacity: Math.random() * 0.5 + 0.2
            });
        }
    }

    function drawParticles() {
        ctx.clearRect(0, 0, width, height);
        ctx.fillStyle = '#0f0f1f';
        ctx.fillRect(0, 0, width, height);
        particles.forEach(p => {
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(34, 197, 94, ${p.opacity})`;
            ctx.fill();
            p.x += p.vx;
            p.y += p.vy;
            if (p.x < 0) p.x = width;
            if (p.x > width) p.x = 0;
            if (p.y < 0) p.y = height;
            if (p.y > height) p.y = 0;
        });
        requestAnimationFrame(drawParticles);
    }

    window.addEventListener('resize', () => {
        resizeCanvas();
    });
    resizeCanvas();
    drawParticles();

    // Animated counter (unchanged)
    const counters = document.querySelectorAll('.stat-number');
    let animated = false;

    function startCounters() {
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            let current = 0;
            const increment = target / 50;
            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    counter.innerText = Math.ceil(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.innerText = target;
                }
            };
            updateCounter();
        });
    }

    function isInViewport(el) {
        const rect = el.getBoundingClientRect();
        return rect.top < window.innerHeight && rect.bottom > 0;
    }

    function checkCounters() {
        if (!animated && isInViewport(document.querySelector('.stats-section'))) {
            animated = true;
            startCounters();
        }
    }

    window.addEventListener('scroll', checkCounters);
    checkCounters();

    // Smooth scroll (unchanged)
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
</script>
</body>
</html>