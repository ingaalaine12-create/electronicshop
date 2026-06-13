<?php
// login.php
// Premium admin login screen with secure verification and modern responsive design

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to admin dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php");
    exit();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/utils.php';

$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username) || empty($password)) {
        $errorMsg = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE `username` = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['username'];
                header("Location: admin.php");
                exit();
            } else {
                $errorMsg = 'Invalid administrative credentials.';
            }
        } catch (PDOException $e) {
            $errorMsg = 'Database Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kigali TechHub | Administrative Login</title>
    
    <!-- CSS & Typography -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0a0d16 0%, #121824 100%); position: relative; overflow: hidden; padding: 24px;">

    <!-- Optional subtle background graphic circles for premium feel -->
    <div style="position: absolute; top: -10%; left: -10%; width: 40%; height: 40%; background: radial-gradient(circle, rgba(0, 242, 254, 0.05) 0%, transparent 70%); z-index: 1;"></div>
    <div style="position: absolute; bottom: -10%; right: -10%; width: 40%; height: 40%; background: radial-gradient(circle, rgba(79, 172, 254, 0.05) 0%, transparent 70%); z-index: 1;"></div>

    <!-- Login Card Container -->
    <div class="success-card" style="margin: 0; width: 100%; max-width: 440px; padding: 40px; background: rgba(18, 24, 38, 0.75); border: 1px solid var(--border-color); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); z-index: 10; display: flex; flex-direction: column; text-align: left;">
        
        <!-- Brand Logo Header -->
        <div style="text-align: center; margin-bottom: 30px;">
            <a href="index.php" class="logo" style="display: inline-flex; font-size: 28px; justify-content: center; width: auto; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                <i class="fa-solid fa-gauge-high"></i> Kigali<span>TechHub</span>
            </a>
            <p style="color: var(--text-muted); font-size: 13.5px; margin-top: 8px; font-weight: 500; text-transform: uppercase; letter-spacing: 1.5px;">Dashboard Access Gate</p>
        </div>

        <!-- Alert Notification for Errors -->
        <?php if (!empty($errorMsg)): ?>
            <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: var(--border-radius-md); padding: 12px 16px; color: var(--danger); margin-bottom: 24px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?php echo htmlspecialchars($errorMsg); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form Elements -->
        <form action="login.php" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
            
            <div class="form-group" style="margin-bottom: 0;">
                <label for="username" class="form-label">Admin Username</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-user" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px;"></i>
                    <input type="text" id="username" name="username" class="form-input" placeholder="e.g. admin" style="padding-left: 44px;" required autocomplete="username">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="password" class="form-label">Security Password</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-lock" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px;"></i>
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" style="padding-left: 44px;" required autocomplete="current-password">
                </div>
            </div>

            <!-- Login Submission Button -->
            <button type="submit" class="checkout-btn" style="margin-top: 10px; padding: 14px 18px;">
                <i class="fa-solid fa-right-to-bracket"></i> Authenticate & Enter
            </button>
        </form>

        <!-- Back to Storefront footer helper -->
        <div style="text-align: center; margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 20px;">
            <a href="index.php" style="font-size: 13.5px; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 6px; transition: var(--transition-snappy);" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='var(--text-secondary)'">
                <i class="fa-solid fa-arrow-left"></i> Return to Kigali TechHub Store
            </a>
        </div>
    </div>

</body>
</html>
