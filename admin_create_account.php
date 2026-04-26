<?php
require_once __DIR__ . '/layout.php';
$admin = require_admin_only();


function admin_mail_config(): array
{
    $config = [
        'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
        'port' => (int)(getenv('SMTP_PORT') ?: 587),
        'username' => getenv('SMTP_USER') ?: '',
        'password' => getenv('SMTP_PASS') ?: '',
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: '',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'Minor Basilica',
        'security' => getenv('SMTP_SECURITY') ?: 'tls',
    ];

    $file = __DIR__ . '/mail_config.php';
    if (is_file($file)) {
        $local = require $file;
        if (is_array($local)) {
            $config = array_merge($config, $local);
        }
    }

    return $config;
}

function send_created_account_email(string $toEmail, string $fullName, string $role, string $accountEmail, string $rawPassword, string $creatorEmail, ?string &$error = null): bool
{
    $error = null;
    $mailConfig = admin_mail_config();
    $phpMailerBase = __DIR__ . '/PHPMailer-master/src/';
    $exceptionFile = $phpMailerBase . 'Exception.php';
    $phpMailerFile = $phpMailerBase . 'PHPMailer.php';
    $smtpFile = $phpMailerBase . 'SMTP.php';

    $subject = 'Your Minor Basilica Account Has Been Created';
    $message = "Hello {$fullName},\n\n"
        . "An account has been created for you in the Minor Basilica Information System.\n\n"
        . "Role: {$role}\n"
        . "Account Email: {$accountEmail}\n"
        . "Temporary Password: {$rawPassword}\n"
        . "Created by: {$creatorEmail}\n\n"
        . "Please login and change your password as soon as possible.";

    if (is_file($exceptionFile) && is_file($phpMailerFile) && is_file($smtpFile)) {
        require_once $exceptionFile;
        require_once $phpMailerFile;
        require_once $smtpFile;

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = (string)($mailConfig['host'] ?? 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = (string)($mailConfig['username'] ?? '');
            $mail->Password = (string)($mailConfig['password'] ?? '');
            if ($mail->Username === '' || $mail->Password === '') {
                $error = 'SMTP credentials missing in mail_config.php';
                return false;
            }
            $security = strtolower((string)($mailConfig['security'] ?? 'tls'));
            $mail->SMTPSecure = $security === 'ssl'
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)($mailConfig['port'] ?? 587);
            $fromEmail = (string)($mailConfig['from_email'] ?? '');
            if ($fromEmail === '') {
                $fromEmail = $mail->Username;
            }
            $fromName = (string)($mailConfig['from_name'] ?? 'Minor Basilica');
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $message;
            return $mail->send();
        } catch (Throwable $e) {
            $error = $e->getMessage();
            return false;
        }
    }

    $headers = "From: no-reply@basilica.local\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $ok = mail($toEmail, $subject, $message, $headers);
    if (!$ok) {
        $error = 'PHPMailer not installed and mail() failed.';
    }
    return $ok;
}

function role_email_domain(string $role): string
{
    return match ($role) {
        'priest' => 'priest.basilica',
        'minister' => 'minister.basilica',
        'staff' => 'cstaff.basilica',
        default => 'basilica.local',
    };
}

function role_email_local_part(string $lastName): string
{
    $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $lastName));
    return $base !== '' ? $base : 'user';
}

function generate_role_based_email_preview(string $lastName, string $role): string
{
    return role_email_local_part($lastName) . '@' . role_email_domain($role);
}

function save_admin_account_form_input(array $source): void
{
    $_SESSION['admin_create_account_form'] = [
        'first_name' => trim($source['first_name'] ?? ''),
        'middle_name' => trim($source['middle_name'] ?? ''),
        'last_name' => trim($source['last_name'] ?? ''),
        'suffix' => trim($source['suffix'] ?? ''),
        'notify_email' => trim($source['notify_email'] ?? ''),
        'role' => trim($source['role'] ?? 'staff'),
    ];
}

function clear_admin_account_form_input(): void
{
    unset($_SESSION['admin_create_account_form']);
}

function remember_admin_created_account(array $account): void
{
    $_SESSION['admin_create_account_result'] = $account;
}

function generate_role_based_email(mysqli $conn, string $lastName, string $role): string
{
    $domain = role_email_domain($role);
    $base = role_email_local_part($lastName);

    $candidate = $base . '@' . $domain;
    $counter = 0;
    while (true) {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        if (!$check) {
            throw new RuntimeException('Unable to prepare email lookup.');
        }

        $check->bind_param('s', $candidate);
        if (!$check->execute()) {
            $check->close();
            throw new RuntimeException('Unable to verify generated email.');
        }
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$exists) {
            return $candidate;
        }

        $counter++;
        $candidate = $base . $counter . '@' . $domain;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user_account'])) {
    save_admin_account_form_input($_POST);

    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $notifyEmail = trim($_POST['notify_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    $allowedRoles = ['staff', 'priest', 'minister'];
    if ($firstName === '' || $middleName === '' || $lastName === '' || $password === '' || $notifyEmail === '') {
        set_flash('danger', 'Please complete all required account fields.');
        header('Location: admin_create_account.php');
        exit();
    }
    if (!filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Please enter a valid notification email.');
        header('Location: admin_create_account.php');
        exit();
    }
    if (!in_array($role, $allowedRoles, true)) {
        set_flash('danger', 'Invalid account role.');
        header('Location: admin_create_account.php');
        exit();
    }
    $passwordError = password_strength_error($password);
    if ($passwordError !== null) {
        set_flash('danger', $passwordError);
        header('Location: admin_create_account.php');
        exit();
    }

    $fullName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName, $suffix], static function ($part) {
        return $part !== '';
    })));
    try {
        $email = generate_role_based_email($conn, $lastName, $role);
    } catch (Throwable $e) {
        set_flash('danger', 'Unable to generate the account email right now. Please try again.');
        header('Location: admin_create_account.php');
        exit();
    }
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $creator = $admin['email'] ?? '';

    $insert = $conn->prepare('INSERT INTO users (full_name, first_name, middle_name, last_name, suffix, email, password, role, created_by_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$insert) {
        set_flash('danger', 'Unable to create the account right now. Please try again.');
        header('Location: admin_create_account.php');
        exit();
    }
    $insert->bind_param('sssssssss', $fullName, $firstName, $middleName, $lastName, $suffix, $email, $passwordHash, $role, $creator);
    $insertOk = $insert->execute();
    $newUserId = (int)$insert->insert_id;
    $insert->close();
    if (!$insertOk || $newUserId <= 0) {
        set_flash('danger', 'Unable to create the account right now. Please try again.');
        header('Location: admin_create_account.php');
        exit();
    }

    notify_user($newUserId, 'Your account has been created by admin (' . $creator . ').');

    $mailError = null;
    $emailSent = send_created_account_email($notifyEmail, $fullName, strtoupper($role), $email, $password, $creator, $mailError);
    clear_admin_account_form_input();
    remember_admin_created_account([
        'email' => $email,
        'full_name' => $fullName,
        'notify_email' => $notifyEmail,
        'role' => $role,
        'mail_status' => $emailSent ? 'sent' : 'failed',
    ]);
    if ($emailSent) {
        set_flash('success', 'Account created and notification email sent.');
    } else {
        set_flash('warning', 'Account created, but email notification failed: ' . ($mailError ?: 'unknown mail error'));
    }
    header('Location: admin_create_account.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $requestId = (int)$_POST['request_id'];
    $action = trim($_POST['action']);
    $note = trim($_POST['admin_note'] ?? '');

    $stmt = $conn->prepare('SELECT id, user_id, title, requested_date, requested_time, status FROM service_requests WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($req) {
        if (in_array($req['status'], ['confirmed', 'rejected'], true)) {
            set_flash('warning', 'This request is already finalized.');
            header('Location: admin_create_account.php');
            exit();
        }

        if ($action === 'confirm') {
            $hasConflict = false;

            if (!empty($req['requested_date']) && !empty($req['requested_time'])) {
                $conflictStmt = $conn->prepare('SELECT s.id FROM schedules s
                    JOIN service_requests r ON r.id = s.request_id
                    WHERE s.event_date = ? AND s.event_time = ? AND r.status = "confirmed"
                    LIMIT 1');
                $conflictStmt->bind_param('ss', $req['requested_date'], $req['requested_time']);
                $conflictStmt->execute();
                $hasConflict = $conflictStmt->get_result()->num_rows > 0;
                $conflictStmt->close();
            }

            if ($hasConflict) {
                $status = 'conflict';
                $autoNote = 'Schedule conflict: selected date/time already booked.';
                $newNote = $note !== '' ? $note : $autoNote;
                $update = $conn->prepare('UPDATE service_requests SET status = ?, admin_note = ? WHERE id = ?');
                $update->bind_param('ssi', $status, $newNote, $requestId);
                $update->execute();
                $update->close();

                notify_user((int)$req['user_id'], 'Request #' . $requestId . ' has a schedule conflict. Please choose another date/time.');
                set_flash('warning', 'Conflict detected. User notified.');
            } else {
                $status = 'confirmed';
                $update = $conn->prepare('UPDATE service_requests SET status = ?, admin_note = ? WHERE id = ?');
                $update->bind_param('ssi', $status, $note, $requestId);
                $update->execute();
                $update->close();

                if (!empty($req['requested_date']) && !empty($req['requested_time'])) {
                    $insertSchedule = $conn->prepare('INSERT INTO schedules (request_id, event_title, event_date, event_time) VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE event_title = VALUES(event_title), event_date = VALUES(event_date), event_time = VALUES(event_time)');
                    $insertSchedule->bind_param('isss', $requestId, $req['title'], $req['requested_date'], $req['requested_time']);
                    $insertSchedule->execute();
                    $insertSchedule->close();
                }

                notify_user((int)$req['user_id'], 'Request #' . $requestId . ' has been confirmed by Admin/Staff.');
                set_flash('success', 'Request confirmed and schedule updated.');
            }
        } elseif ($action === 'reject') {
            $status = 'rejected';
            $update = $conn->prepare('UPDATE service_requests SET status = ?, admin_note = ? WHERE id = ?');
            $update->bind_param('ssi', $status, $note, $requestId);
            $update->execute();
            $update->close();

            notify_user((int)$req['user_id'], 'Request #' . $requestId . ' has been rejected. ' . ($note ?: 'Please contact parish office.'));
            set_flash('danger', 'Request rejected and user notified.');
        }
    }

    header('Location: admin_create_account.php');
    exit();
}

$summary = [
    'pending' => 0,
    'confirmed' => 0,
    'conflict' => 0,
    'rejected' => 0
];
$monthStart = app_now()->format('Y-m-01 00:00:00');
$nextMonthStart = app_now()->modify('first day of next month')->format('Y-m-01 00:00:00');

$sumStmt = $conn->prepare('SELECT status, COUNT(*) AS total
    FROM service_requests
    WHERE created_at >= ? AND created_at < ?
    GROUP BY status');
$sumStmt->bind_param('ss', $monthStart, $nextMonthStart);
$sumStmt->execute();
$sumRes = $sumStmt->get_result();
while ($row = $sumRes->fetch_assoc()) {
    if (array_key_exists($row['status'], $summary)) {
        $summary[$row['status']] = (int)$row['total'];
    }
}
$sumStmt->close();

$requests = [];
$reqStmt = $conn->prepare('SELECT r.id, r.title, r.form_type, r.requested_date, r.requested_time, r.status, r.admin_note, r.created_at, u.full_name, u.email
    FROM service_requests r
    JOIN users u ON u.id = r.user_id
    WHERE r.created_at >= ? AND r.created_at < ?
    ORDER BY FIELD(r.status, "pending","conflict","confirmed","rejected"), r.created_at DESC');
$reqStmt->bind_param('ss', $monthStart, $nextMonthStart);
$reqStmt->execute();
$reqRes = $reqStmt->get_result();
$requests = $reqRes->fetch_all(MYSQLI_ASSOC);
$reqStmt->close();

$accountForm = $_SESSION['admin_create_account_form'] ?? [];
$createdAccount = $_SESSION['admin_create_account_result'] ?? null;
$accountFormRole = (string)($accountForm['role'] ?? 'staff');
$accountPreviewEmail = '';
if (is_array($createdAccount) && !empty($createdAccount['email'])) {
    $accountPreviewEmail = (string)$createdAccount['email'];
} elseif (!empty($accountForm['last_name'])) {
    $accountPreviewEmail = generate_role_based_email_preview((string)$accountForm['last_name'], $accountFormRole);
}

render_header('Admin Create Account', 'admin');
?>
<?php require __DIR__ . '/partials/admin_tools_nav.php'; ?>
<h2 class="mb-0">Create Account</h2>

<?php if (is_array($createdAccount) && !empty($createdAccount['email'])): ?>
    <div class="card bg-dark border-info-subtle mb-4 mt-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h5 class="text-info mb-2">Most Recent Account Email</h5>
                    <p class="mb-1">Generated email: <strong><?php echo e($createdAccount['email']); ?></strong></p>
                    <p class="mb-1">Account name: <?php echo e($createdAccount['full_name'] ?? ''); ?></p>
                    <p class="mb-0">Notification email: <?php echo e($createdAccount['notify_email'] ?? ''); ?></p>
                </div>
                <div class="align-self-start">
                    <span class="badge text-bg-<?php echo ($createdAccount['mail_status'] ?? '') === 'sent' ? 'success' : 'warning'; ?>">
                        Notification <?php echo ($createdAccount['mail_status'] ?? '') === 'sent' ? 'sent' : 'failed'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card bg-dark border-warning-subtle mb-4 mt-4">
    <div class="card-body">
        <h5 class="text-warning mb-3">Create Staff/Priest/Minister Account</h5>
        <form method="POST" class="row g-3" id="adminCreateAccountForm" novalidate>
            <input type="hidden" name="create_user_account" value="1">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">First Name *</label>
                <input class="form-control" type="text" name="first_name" value="<?php echo e((string)($accountForm['first_name'] ?? '')); ?>" required>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Middle Name *</label>
                <input class="form-control" type="text" name="middle_name" value="<?php echo e((string)($accountForm['middle_name'] ?? '')); ?>" required>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Last Name *</label>
                <input class="form-control" type="text" name="last_name" id="account_last_name" value="<?php echo e((string)($accountForm['last_name'] ?? '')); ?>" required>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Suffix (Optional)</label>
                <input class="form-control" type="text" name="suffix" value="<?php echo e((string)($accountForm['suffix'] ?? '')); ?>" placeholder="Jr., Sr., III">
            </div>
            <div class="col-lg-5 col-md-12">
                <label class="form-label">Generated Account Email</label>
                <input class="form-control bg-secondary-subtle text-dark" type="text" id="generated_account_email" value="<?php echo e($accountPreviewEmail); ?>" readonly>
                <div class="form-text">This preview updates from the last name and role. The saved account email above shows the final stored value.</div>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Role *</label>
                <select class="form-select" name="role" id="account_role" required>
                    <option value="staff" <?php echo $accountFormRole === 'staff' ? 'selected' : ''; ?>>Church Staff</option>
                    <option value="priest" <?php echo $accountFormRole === 'priest' ? 'selected' : ''; ?>>Priest</option>
                    <option value="minister" <?php echo $accountFormRole === 'minister' ? 'selected' : ''; ?>>Minister</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Password *</label>
                <div class="input-group">
                    <input
                        class="form-control"
                        type="password"
                        name="password"
                        id="account_password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                    <button class="btn btn-outline-light" type="button" id="toggle_account_password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <small class="text-secondary">Minimum 8 characters with uppercase, lowercase, number, and special character.</small>
                <div class="text-danger mt-2 account-inline-feedback d-none" id="account_password_feedback"></div>
            </div>
            <div class="col-lg-3 col-md-12">
                <label class="form-label">Personal Email of the User *</label>
                <input class="form-control" type="email" name="notify_email" value="<?php echo e((string)($accountForm['notify_email'] ?? '')); ?>" placeholder="recipient@email.com" required>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-warning" type="submit">Create Account</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function () {
        var form = document.getElementById('adminCreateAccountForm');
        if (!form) return;

        var roleDomains = {
            staff: 'cstaff.basilica',
            priest: 'priest.basilica',
            minister: 'minister.basilica'
        };

        var lastNameInput = document.getElementById('account_last_name');
        var roleInput = document.getElementById('account_role');
        var emailPreview = document.getElementById('generated_account_email');
        var passwordInput = document.getElementById('account_password');
        var passwordFeedback = document.getElementById('account_password_feedback');
        var toggleButton = document.getElementById('toggle_account_password');

        function sanitizeLocalPart(value) {
            var cleaned = String(value || '').toLowerCase().replace(/[^a-z0-9]/g, '');
            return cleaned || 'user';
        }

        function getPasswordError(value) {
            var password = String(value || '');
            if (password.length < 8) return 'Password must be at least 8 characters long.';
            if (!/[A-Z]/.test(password)) return 'Password must include at least 1 uppercase letter.';
            if (!/[a-z]/.test(password)) return 'Password must include at least 1 lowercase letter.';
            if (!/\d/.test(password)) return 'Password must include at least 1 number.';
            if (!/[^A-Za-z\d]/.test(password)) return 'Password must include at least 1 special character.';
            return '';
        }

        function updateGeneratedEmail(force) {
            if (!emailPreview || !roleInput || !lastNameInput) return;
            if (!force && !String(lastNameInput.value || '').trim()) return;
            emailPreview.value = sanitizeLocalPart(lastNameInput.value) + '@' + (roleDomains[roleInput.value] || roleDomains.staff);
        }

        function syncPasswordValidation() {
            if (!passwordInput) return true;
            var message = getPasswordError(passwordInput.value);
            passwordInput.setCustomValidity(message);
            passwordInput.classList.toggle('is-invalid', message !== '');
            if (passwordFeedback) {
                passwordFeedback.textContent = message;
                passwordFeedback.classList.toggle('d-none', message === '');
            }
            return message === '';
        }

        if (lastNameInput) {
            lastNameInput.addEventListener('input', function () {
                updateGeneratedEmail(true);
            });
        }

        if (roleInput) {
            roleInput.addEventListener('change', function () {
                updateGeneratedEmail(true);
            });
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', syncPasswordValidation);
        }

        if (toggleButton && passwordInput) {
            toggleButton.addEventListener('click', function () {
                var showing = passwordInput.type === 'text';
                passwordInput.type = showing ? 'password' : 'text';
                toggleButton.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
                toggleButton.innerHTML = showing ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
            });
        }

        form.addEventListener('submit', function (event) {
            var passwordValid = syncPasswordValidation();
            if (!form.checkValidity() || !passwordValid) {
                event.preventDefault();
                event.stopPropagation();
                form.classList.add('was-validated');
                if (typeof form.reportValidity === 'function') {
                    form.reportValidity();
                }
            }
        });

        updateGeneratedEmail(false);
    })();
</script>
<?php render_footer(); ?>
