<?php
require_once __DIR__ . '/layout.php';
$admin = require_admin_only();

require_once __DIR__ . '/carousel_helpers.php';
$carouselImages = load_carousel_images($conn);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_carousel_images'])) { require __DIR__ . '/carousel_admin_save.php'; }

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

function generate_role_based_email(mysqli $conn, string $lastName, string $role): string
{
    $domain = role_email_domain($role);
    $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $lastName));
    if ($base === '') {
        $base = 'user';
    }

    $candidate = $base . '@' . $domain;
    $counter = 0;
    while (true) {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $candidate);
        $check->execute();
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
        header('Location: admin_dashboard.php');
        exit();
    }
    if (!filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Please enter a valid notification email.');
        header('Location: admin_dashboard.php');
        exit();
    }
    if (!in_array($role, $allowedRoles, true)) {
        set_flash('danger', 'Invalid account role.');
        header('Location: admin_dashboard.php');
        exit();
    }
    $strongPasswordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/';
    if (!preg_match($strongPasswordPattern, $password)) {
        set_flash('danger', 'Password must be strong: at least 8 chars, with uppercase, lowercase, number, and special character.');
        header('Location: admin_dashboard.php');
        exit();
    }

    $fullName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName, $suffix], static function ($part) {
        return $part !== '';
    })));
    $email = generate_role_based_email($conn, $lastName, $role);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $creator = $admin['email'] ?? '';

    $insert = $conn->prepare('INSERT INTO users (full_name, first_name, middle_name, last_name, suffix, email, password, role, created_by_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insert->bind_param('sssssssss', $fullName, $firstName, $middleName, $lastName, $suffix, $email, $passwordHash, $role, $creator);
    $insert->execute();
    $newUserId = (int)$insert->insert_id;
    $insert->close();

    notify_user($newUserId, 'Your account has been created by admin (' . $creator . ').');

    $mailError = null;
    $emailSent = send_created_account_email($notifyEmail, $fullName, strtoupper($role), $email, $password, $creator, $mailError);
    if ($emailSent) {
        set_flash('success', 'Account created and notification email sent.');
    } else {
        set_flash('warning', 'Account created, but email notification failed: ' . ($mailError ?: 'unknown mail error'));
    }
    header('Location: admin_dashboard.php');
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
            header('Location: admin_dashboard.php');
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

    header('Location: admin_dashboard.php');
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

render_header('Admin Carousel', 'admin');
?>
<?php require __DIR__ . '/partials/admin_tools_nav.php'; ?>
<h2 class="mb-0">Edit Carousel Photos</h2>
<?php require __DIR__ . '/partials/carousel_admin.php'; ?>
<?php render_footer(); ?>
