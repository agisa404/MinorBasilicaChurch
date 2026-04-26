
<?php
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/mail_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: account_management.php?auth=register');
    exit();
}

function redirect_register(): void
{
    header('Location: account_management.php?auth=register');
    exit();
}

function save_register_form_input(array $source): void
{
    $_SESSION['register_form'] = [
        'first_name' => trim($source['first_name'] ?? ''),
        'middle_name' => trim($source['middle_name'] ?? ''),
        'last_name' => trim($source['last_name'] ?? ''),
        'suffix' => trim($source['suffix'] ?? ''),
        'email' => trim($source['email'] ?? ''),
    ];
}

function clear_register_form_input(): void
{
    unset($_SESSION['register_form']);
}

function has_email_dns_records(string $email): bool
{
    $domain = substr(strrchr($email, '@') ?: '', 1);
    if ($domain === '') {
        return false;
    }

    if (function_exists('checkdnsrr')) {
        if (checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA')) {
            return true;
        }
    }

    if (function_exists('dns_get_record')) {
        $mx = dns_get_record($domain, DNS_MX);
        $a = dns_get_record($domain, DNS_A);
        $aaaa = dns_get_record($domain, DNS_AAAA);
        if (!empty($mx) || !empty($a) || !empty($aaaa)) {
            return true;
        }
    }

    return false;
}

function is_gmail_address(string $email): bool
{
    $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
    return $domain === 'gmail.com' || $domain === 'googlemail.com';
}

function generate_otp_code(): string
{
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function send_signup_otp_email(string $toEmail, string $toName, string $otpCode, DateTimeImmutable $expiresAt, ?string &$error = null): bool
{
    $error = null;
    $mailConfig = get_mail_config();

    $phpMailerBase = __DIR__ . '/PHPMailer-master/src/';
    $exceptionFile = $phpMailerBase . 'Exception.php';
    $phpMailerFile = $phpMailerBase . 'PHPMailer.php';
    $smtpFile = $phpMailerBase . 'SMTP.php';

    $subject = 'Your Signup Verification OTP';
    $expiresLabel = $expiresAt->format('F j, Y g:i A');

    $plain = "Your verification OTP is: {$otpCode}\n\n";
    $plain .= "This code is valid until {$expiresLabel}.\n\n";
    $plain .= "If you did not request this, ignore this email.";

    $safeCode = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
    $html = "<p>Hello {$safeName},</p>";
    $html .= "<p>Your verification OTP is:</p>";
    $html .= "<p style=\"font-size:22px;font-weight:800;letter-spacing:0.08em\">{$safeCode}</p>";
    $html .= "<p><small>This code is valid until {$expiresLabel}.</small></p>";

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
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $plain;

            return $mail->send();
        } catch (Throwable $e) {
            $error = $e->getMessage();
            return false;
        }
    }

    $error = 'PHPMailer missing; cannot send OTP.';
    return false;
}

$action = trim($_POST['signup_action'] ?? '');

$firstName = trim($_POST['first_name'] ?? '');
$middleName = trim($_POST['middle_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$suffix = trim($_POST['suffix'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');
$otp = preg_replace('/\D+/', '', (string)($_POST['otp'] ?? ''));
$role = 'parishioner';

$fullName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName, $suffix], static function ($part) {
    return $part !== '';
})));

save_register_form_input($_POST);

if ($firstName === '' || $middleName === '' || $lastName === '' || $email === '' || $password === '' || $confirmPassword === '') {
    set_flash('danger', 'All fields are required.');
    redirect_register();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Please enter a valid email address.');
    redirect_register();
}

if (!is_gmail_address($email)) {
    set_flash('danger', 'Please use a Gmail address (example: name@gmail.com).');
    redirect_register();
}

if (!has_email_dns_records($email)) {
    set_flash('danger', 'Email domain appears invalid. Please use a real email address.');
    redirect_register();
}

if ($password !== $confirmPassword) {
    set_flash('danger', 'Passwords do not match.');
    redirect_register();
}

$passwordError = password_strength_error($password);
if ($passwordError !== null) {
    set_flash('danger', $passwordError);
    redirect_register();
}

$check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$check->bind_param('s', $email);
$check->execute();
$exists = $check->get_result()->num_rows > 0;
$check->close();

if ($exists) {
    set_flash('danger', 'Email is already registered.');
    redirect_register();
}

$now = app_now();
$nowSql = $now->format('Y-m-d H:i:s');

if ($action === 'request_otp') {
    $otpCode = generate_otp_code();
    $expiresAt = $now->modify('+10 minutes');
    $expireSql = $expiresAt->format('Y-m-d H:i:s');

    $invalidate = $conn->prepare('UPDATE email_otps SET used_at = ? WHERE email = ? AND purpose = "signup" AND used_at IS NULL');
    $invalidate->bind_param('ss', $nowSql, $email);
    $invalidate->execute();
    $invalidate->close();

    $otpHash = password_hash($otpCode, PASSWORD_DEFAULT);
    $ins = $conn->prepare('INSERT INTO email_otps (email, purpose, otp_hash, expires_at) VALUES (?, "signup", ?, ?)');
    $ins->bind_param('sss', $email, $otpHash, $expireSql);
    $ins->execute();
    $ins->close();

    $name = $fullName !== '' ? $fullName : $email;
    $mailError = null;
    $sent = send_signup_otp_email($email, $name, $otpCode, $expiresAt, $mailError);
    if (!$sent) {
        $reason = $mailError ? (' ' . $mailError) : ' (please check mail_config.php SMTP username/password)';
        set_flash('danger', 'Unable to send OTP.' . $reason);
        redirect_register();
    }

    set_flash('success', 'OTP sent. Please check your Gmail and enter the 6-digit code to complete signup.');
    redirect_register();
}

if ($action === 'verify_otp') {
    if (strlen($otp) !== 6) {
        set_flash('danger', 'Please enter the 6-digit OTP.');
        redirect_register();
    }

    $stmt = $conn->prepare('SELECT id, otp_hash, expires_at FROM email_otps WHERE email = ? AND purpose = "signup" AND used_at IS NULL AND expires_at > ? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('ss', $email, $nowSql);
    $stmt->execute();
    $otpRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$otpRow || !password_verify($otp, (string)$otpRow['otp_hash'])) {
        set_flash('danger', 'Invalid or expired OTP. Please request a new one.');
        redirect_register();
    }

    $mark = $conn->prepare('UPDATE email_otps SET used_at = ? WHERE id = ?');
    $mark->bind_param('si', $nowSql, $otpRow['id']);
    $mark->execute();
    $mark->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare('INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $fullName, $email, $passwordHash, $role);
    $stmt->execute();
    $userId = (int)$stmt->insert_id;
    $stmt->close();

    clear_register_form_input();
    notify_user($userId, 'Welcome to the Minor Basilica system. Your account has been created.');
    set_flash('success', 'Registration successful. You can now login.');
    header('Location: account_management.php?auth=login');
    exit();
}

set_flash('danger', 'Invalid signup action.');
redirect_register();
