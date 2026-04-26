
<?php
require_once __DIR__ . '/mail_helpers.php';

$to = trim((string)($_POST['to'] ?? ''));
$status = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $status = 'Please enter a valid recipient email.';
    } else {
        $cfg = get_mail_config();
        if (isset($_POST['debug']) && $_POST['debug'] === '1') {
            $cfg['debug'] = 2;
        }

        $phpMailerBase = __DIR__ . '/PHPMailer-master/src/';
        $exceptionFile = $phpMailerBase . 'Exception.php';
        $phpMailerFile = $phpMailerBase . 'PHPMailer.php';
        $smtpFile = $phpMailerBase . 'SMTP.php';

        if (!is_file($exceptionFile) || !is_file($phpMailerFile) || !is_file($smtpFile)) {
            $status = 'PHPMailer files not found in PHPMailer-master/src.';
        } else {
            require_once $exceptionFile;
            require_once $phpMailerFile;
            require_once $smtpFile;

            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->SMTPDebug = (int)($cfg['debug'] ?? 0);
                $mail->Debugoutput = static function (string $str, int $level) : void {
                    error_log('PHPMailer[' . $level . ']: ' . $str);
                };

                $mail->Host = (string)($cfg['host'] ?? 'smtp.gmail.com');
                $mail->SMTPAuth = true;
                $mail->Username = (string)($cfg['username'] ?? '');
                $mail->Password = (string)($cfg['password'] ?? '');

                if ($mail->Username === '' || $mail->Password === '') {
                    throw new RuntimeException('SMTP username/password missing in mail_config.php');
                }

                $security = strtolower((string)($cfg['security'] ?? 'tls'));
                $mail->SMTPSecure = $security === 'ssl'
                    ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                    : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = (int)($cfg['port'] ?? 587);

                $fromEmail = (string)($cfg['from_email'] ?? '');
                if ($fromEmail === '') {
                    $fromEmail = $mail->Username;
                }
                $fromName = (string)($cfg['from_name'] ?? 'Minor Basilica');

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($to);
                $mail->isHTML(false);
                $mail->Subject = 'SMTP Test (Basilica)';
                $mail->Body = 'This is a test email from your Basilica project.';

                $mail->send();
                $status = 'Sent successfully.';
            } catch (Throwable $e) {
                $status = 'Send failed.';
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SMTP Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 860px; margin: 24px auto; padding: 0 16px; }
        label { display:block; font-weight: 700; margin: 12px 0 6px; }
        input[type=email] { width: 100%; padding: 10px; font-size: 16px; }
        button { margin-top: 12px; padding: 10px 14px; font-size: 16px; }
        .box { border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin-top: 14px; }
        .err { color: #b00020; white-space: pre-wrap; }
        code { background: #f3f3f3; padding: 1px 4px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>SMTP Test</h1>
    <p>This sends a test email using <code>mail_config.php</code>.</p>

    <form method="post">
        <label>Send To</label>
        <input name="to" type="email" value="<?php echo htmlspecialchars($to, ENT_QUOTES, 'UTF-8'); ?>" placeholder="recipient@gmail.com" required>
        <label><input type="checkbox" name="debug" value="1"> Enable SMTP debug (writes to PHP error log)</label>
        <button type="submit">Send Test</button>
    </form>

    <?php if ($status !== null): ?>
        <div class="box">
            <div><strong>Status:</strong> <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php if ($error): ?>
                <div class="err"><strong>Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>
