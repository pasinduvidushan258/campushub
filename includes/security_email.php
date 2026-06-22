<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function campushub_send_security_code(string $recipientEmail, string $recipientName, string $subject, string $heading, string $message, string $code, string $footerNote = 'If you did not request this, please ignore this email.'): array
{
    $env = parse_ini_file(__DIR__ . '/../.env');

    if (!$env || empty($env['SMTP_EMAIL']) || empty($env['SMTP_APP_PASSWORD'])) {
        return [
            'success' => false,
            'error' => 'SMTP credentials are not configured.',
        ];
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $env['SMTP_EMAIL'];
        $mail->Password   = $env['SMTP_APP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom($env['SMTP_EMAIL'], 'CampusHub Security');
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #F97316; text-align: center;'>CampusHub</h2>
                <h3 style='color: #111827;'>" . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . "</h3>
                <p>Hello <b>" . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . "</b>,</p>
                <p>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <span style='font-size: 24px; font-weight: bold; background: #f3f4f6; padding: 15px 25px; border-radius: 8px; letter-spacing: 5px; color: #111;'>" . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . "</span>
                </div>
                <p>" . htmlspecialchars($footerNote, ENT_QUOTES, 'UTF-8') . "</p>
                <p>Best Regards,<br>The CampusHub Team</p>
            </div>
        ";

        $mail->send();
        return ['success' => true, 'error' => ''];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}
