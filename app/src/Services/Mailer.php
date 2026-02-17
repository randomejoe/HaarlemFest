<?php

namespace App\Services;

use App\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mailer
{
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): void
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = Config::env('SMTP_HOST', 'mailhog');
        $mail->Port = Config::envInt('SMTP_PORT', 1025);

        $encryption = strtolower(Config::env('SMTP_ENCRYPTION', ''));
        if ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $user = Config::env('SMTP_USER', '');
        $pass = Config::env('SMTP_PASS', '');
        if ($user !== '' || $pass !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;
        }

        $fromEmail = Config::env('SMTP_FROM', 'no-reply@localhost');
        $fromName = Config::env('SMTP_FROM_NAME', 'HaarlemFest');

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->send();
    }
}
