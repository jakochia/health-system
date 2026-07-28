<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) header("Location: ../index.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Backup using mysqldump (adjust paths as needed)
    $backupFile = '../backups/mohi_namarei_' . date('Y-m-d_H-i-s') . '.sql';
    $command = "mysqldump --user=root --password= --host=localhost mohi_namarei > $backupFile";
    system($command, $output);
    if (file_exists($backupFile)) {
        $success = "Backup created: " . basename($backupFile);
    } else {
        $error = "Backup failed. Check mysqldump path and permissions.";
    }
}
?>
<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup | Admin</title>
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
        .backup-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .backup-item {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }
        .backup-item:hover {
            background: #f1f5f9;
            transform: translateX(5px);
        }
        .backup-name {
            font-family: monospace;
            font-size: 0.85rem;
            color: #1e293b;
        }
        .backup-meta {
            font-size: 0.7rem;
            color: #64748b;
        }
        .backup-download {
            color: var(--kenya-green);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        .backup-download:hover {
            color: #006b31;
            transform: scale(1.05);
        }
        hr {
            background: linear-gradient(90deg, var(--kenya-red), var(--kenya-green), var(--kenya-black));
            height: 2px;
            border: none;
            margin: 1.5rem 0;
        }
        .info-box {
            background: #eef2ff;
            border-radius: 1rem;
            padding: 1rem;
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }
        .info-box i {
            font-size: 1.5rem;
            color: var(--kenya-red);
        }
        @media (max-width: 768px) {
            .backup-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid px-4 py-4">
    <div class="modern-card">
        <div class="card-header-modern">
            <i class="fas fa-database"></i> Database Backup
        </div>
        <div class="card-body p-4">
            <?php if(isset($success)): ?>
                <div class="alert-modern alert-modern-success">
                    <i class="fas fa-check-circle fs-5"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>
            <?php if(isset($error)): ?>
                <div class="alert-modern alert-modern-danger">
                    <i class="fas fa-exclamation-triangle fs-5"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" class="mb-4" onsubmit="return confirm('Create a new database backup? This may take a moment.');">
                <button type="submit" class="btn btn-kenya-primary btn-modern">
                    <i class="fas fa-plus-circle me-1"></i> Create Backup Now
                </button>
            </form>

            <hr>

            <h5 class="mb-3"><i class="fas fa-list me-2 text-success"></i> Existing Backups</h5>
            <?php
            $backupDir = '../backups/';
            if (is_dir($backupDir)) {
                $files = scandir($backupDir);
                $backups = array_filter($files, function($file) {
                    return $file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql';
                });
                if (count($backups) > 0) {
                    echo '<ul class="backup-list">';
                    foreach ($backups as $file) {
                        $filepath = $backupDir . $file;
                        $size = filesize($filepath);
                        $sizeFormatted = $size < 1024 ? $size . ' B' : ($size < 1048576 ? round($size / 1024, 1) . ' KB' : round($size / 1048576, 1) . ' MB');
                        $date = date('M d, Y H:i:s', filemtime($filepath));
                        echo '<li class="backup-item">';
                        echo '<div>';
                        echo '<div class="backup-name"><i class="fas fa-file-archive me-2"></i>' . htmlspecialchars($file) . '</div>';
                        echo '<div class="backup-meta">' . $sizeFormatted . ' · ' . $date . '</div>';
                        echo '</div>';
                        echo '<a href="../backups/' . urlencode($file) . '" download class="backup-download"><i class="fas fa-download me-1"></i> Download</a>';
                        echo '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p class="text-muted text-center py-4"><i class="fas fa-folder-open me-2"></i>No backups found.</p>';
                }
            } else {
                echo '<p class="text-danger">Backup directory does not exist. Please create it with proper permissions.</p>';
            }
            ?>

            <div class="info-box">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <strong>Security Notice</strong><br>
                    Backup files are stored in a web-accessible folder. For better security, move the <code>backups</code> folder outside the public web root, or protect it with an .htaccess file.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>