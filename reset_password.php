
<?php
require_once __DIR__ . '/layout.php';

$authLogoPath = '612401184_4348220988792023_5812589285034246497_n.jpg';

$token = trim((string)($_GET['token'] ?? ($_POST['token'] ?? '')));
if ($token !== '' && !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $token = '';
}

$now = app_now();
$nowSql = $now->format('Y-m-d H:i:s');
$tokenHash = $token !== '' ? hash('sha256', $token) : '';

$resetRow = null;
if ($tokenHash !== '') {
    $stmt = $conn->prepare('SELECT pr.id AS reset_id, pr.user_id, pr.expires_at, u.email FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > ? LIMIT 1');
    $stmt->bind_param('ss', $tokenHash, $nowSql);
    $stmt->execute();
    $resetRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$resetRow) {
        set_flash('danger', 'That password reset link is invalid or has expired. Please request a new one.');
        header('Location: forgot_password.php');
        exit();
    }

    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($password === '' || $confirm === '') {
        set_flash('danger', 'Please enter and confirm your new password.');
        header('Location: reset_password.php?token=' . urlencode($token));
        exit();
    }

    if ($password !== $confirm) {
        set_flash('danger', 'Passwords do not match.');
        header('Location: reset_password.php?token=' . urlencode($token));
        exit();
    }

    $strongPasswordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z\\d]).{8,}$/';
    if (!preg_match($strongPasswordPattern, $password)) {
        set_flash('danger', 'Password must be strong: at least 8 chars, with uppercase, lowercase, number, and special character.');
        header('Location: reset_password.php?token=' . urlencode($token));
        exit();
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $usedAt = app_now()->format('Y-m-d H:i:s');

    $conn->begin_transaction();
    try {
        $up = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $up->bind_param('si', $passwordHash, $resetRow['user_id']);
        $up->execute();
        $up->close();

        $mark = $conn->prepare('UPDATE password_resets SET used_at = ? WHERE id = ?');
        $mark->bind_param('si', $usedAt, $resetRow['reset_id']);
        $mark->execute();
        $mark->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        set_flash('danger', 'Unable to reset password right now. Please try again.');
        header('Location: reset_password.php?token=' . urlencode($token));
        exit();
    }

    set_flash('success', 'Password updated. You can now login.');
    header('Location: account_management.php?auth=login');
    exit();
}

render_header('Reset Password', '');
?>
<div class="auth-shell auth-shell-login-left">
    <div class="auth-card auth-card-login card border-0">
        <div class="card-body p-4 p-md-5">
            <div class="auth-login-brand text-center mb-4">
                <img class="auth-login-logo mb-3" src="<?php echo e($authLogoPath); ?>" alt="Minor Basilica Logo">
                <h5 class="auth-login-title mb-2">Reset Password</h5>
                <p class="auth-login-subtitle mb-0">Set a new password for your account.</p>
            </div>

            <?php if (!$resetRow): ?>
                <div class="alert alert-danger">This reset link is invalid or has expired.</div>
                <div class="d-flex gap-2 auth-actions auth-login-links">
                    <a class="btn btn-outline-light" href="forgot_password.php">Request a New Link</a>
                    <a class="btn btn-outline-light" href="account_management.php?auth=login">Back to Login</a>
                </div>
            <?php else: ?>
                <form method="POST" action="reset_password.php" class="auth-login-form">
                    <input type="hidden" name="token" value="<?php echo e($token); ?>">
                    <label class="form-label">New Password</label>
                    <input id="resetPassword" class="form-control mb-3" type="password" name="password" placeholder="Enter a new password" required>
                    <label class="form-label">Confirm Password</label>
                    <input id="resetConfirm" class="form-control mb-3" type="password" name="confirm_password" placeholder="Confirm new password" required>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="showResetPassword">
                        <label class="form-check-label" for="showResetPassword">Show password</label>
                    </div>
                    <button class="btn auth-login-btn w-100 mb-3" type="submit">Update Password</button>
                    <div class="d-flex gap-2 auth-actions auth-login-links">
                        <a class="btn btn-outline-light" href="account_management.php?auth=login">Back to Login</a>
                        <a class="btn btn-outline-light" href="index.php">Back to Homepage</a>
                    </div>
                </form>
                <script>
                    (function () {
                        var checkbox = document.getElementById('showResetPassword');
                        var p1 = document.getElementById('resetPassword');
                        var p2 = document.getElementById('resetConfirm');
                        if (!checkbox) return;
                        checkbox.addEventListener('change', function () {
                            var type = checkbox.checked ? 'text' : 'password';
                            if (p1) p1.type = type;
                            if (p2) p2.type = type;
                        });
                    })();
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
render_footer();
?>
