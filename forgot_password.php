
<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/mail_helpers.php';

function app_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if ($dir === '.') {
        $dir = '';
    }

    return $scheme . '://' . $host . $dir;
}

$authLogoPath = '612401184_4348220988792023_5812589285034246497_n.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Please enter a valid email address.');
        header('Location: forgot_password.php');
        exit();
    }

    $stmt = $conn->prepare('SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    unset($_SESSION['password_reset_link']);


    if ($row) {
        $now = app_now();
        $expiresAt = $now->modify('+1 hour');

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $nowSql = $now->format('Y-m-d H:i:s');
        $expireSql = $expiresAt->format('Y-m-d H:i:s');

        $invalidate = $conn->prepare('UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL');
        $invalidate->bind_param('si', $nowSql, $row['id']);
        $invalidate->execute();
        $invalidate->close();

        $ins = $conn->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
        $ins->bind_param('iss', $row['id'], $tokenHash, $expireSql);
        $ins->execute();
        $ins->close();

        $base = app_base_url();
        $resetUrl = rtrim($base, '/') . '/reset_password.php?token=' . urlencode($token);
        $mailError = null;
        $sent = send_password_reset_email($row['email'], (string)($row['full_name'] ?? $row['email']), $resetUrl, $expiresAt, $mailError);
        if (!$sent) {
            // SMTP not configured; show link on-screen for local testing.
            $_SESSION['password_reset_link'] = $resetUrl;
        }
    }

    set_flash('success', 'If that email exists, you can reset your password.');
    header('Location: forgot_password.php');
    exit();
}

$resetLink = $_SESSION['password_reset_link'] ?? null;

render_header('Forgot Password', '');
?>
<div class="auth-shell auth-shell-login-left">
    <div class="auth-card auth-card-login card border-0">
        <div class="card-body p-4 p-md-5">
            <div class="auth-login-brand text-center mb-4">
                <img class="auth-login-logo mb-3" src="<?php echo e($authLogoPath); ?>" alt="Minor Basilica Logo">
                <h5 class="auth-login-title mb-2">Forgot Password</h5>
                <p class="auth-login-subtitle mb-0">Enter your email and we will send you a reset link.</p>
            </div>

            <?php if ($resetLink): ?>
                <div class="alert alert-info">
                    <div class="fw-semibold mb-1">Reset link (copy and open in browser):</div>
                    <div class="text-break"><a class="link-light" href="<?php echo e($resetLink); ?>"><?php echo e($resetLink); ?></a></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php" class="auth-login-form">
                <label class="form-label">Email</label>
                <input class="form-control mb-4" type="email" name="email" placeholder="Enter your email" required>
                <button class="btn auth-login-btn w-100 mb-3" type="submit">Send Reset Link</button>
                <div class="d-flex gap-2 auth-actions auth-login-links">
                    <a class="btn btn-outline-light" href="account_management.php?auth=login">Back to Login</a>
                    <a class="btn btn-outline-light" href="index.php">Back to Homepage</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
render_footer();
?>
