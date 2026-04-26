
<?php

function get_mail_config(): array
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

function send_password_reset_email(string $toEmail, string $toName, string $resetUrl, DateTimeImmutable $expiresAt, ?string &$error = null): bool
{
    $error = null;
    $mailConfig = get_mail_config();

    $phpMailerBase = __DIR__ . '/PHPMailer-master/src/';
    $exceptionFile = $phpMailerBase . 'Exception.php';
    $phpMailerFile = $phpMailerBase . 'PHPMailer.php';
    $smtpFile = $phpMailerBase . 'SMTP.php';

    $subject = 'Reset Your Minor Basilica Password';
    $expiresLabel = $expiresAt->format('F j, Y g:i A');

    $plain = "Hello {$toName},\n\n";
    $plain .= "We received a request to reset your password.\n\n";
    $plain .= "Reset link (valid until {$expiresLabel}):\n{$resetUrl}\n\n";
    $plain .= "If you did not request this, you can ignore this email.";

    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
    $html = "<p>Hello {$safeName},</p>";
    $html .= "<p>We received a request to reset your password.</p>";
    $html .= "<p><a href=\"{$safeUrl}\">Reset your password</a></p>";
    $html .= "<p><small>This link is valid until {$expiresLabel}.</small></p>";

    if (is_file($exceptionFile) && is_file($phpMailerFile) && is_file($smtpFile)) {
        require_once $exceptionFile;
        require_once $phpMailerFile;
        require_once $smtpFile;

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPDebug = (int)($mailConfig['debug'] ?? 0);
            $mail->Debugoutput = static function (string $str, int $level) : void {
                error_log('PHPMailer[' . $level . ']: ' . $str);
            };
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

    $headers = "From: no-reply@basilica.local\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $ok = mail($toEmail, $subject, $plain, $headers);
    if (!$ok) {
        $error = 'PHPMailer not installed and mail() failed.';
    }

    return $ok;
}

?>
