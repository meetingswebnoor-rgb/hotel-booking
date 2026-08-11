<?php

declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

/**
 * SMTP mail via PHPMailer. Requires `composer install` — until then,
 * calling send() throws a clear error instead of a silent no-op.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $view, array $data = []): bool
    {
        if (!class_exists(PHPMailer::class)) {
            throw new RuntimeException('PHPMailer is not installed. Run "composer install" first.');
        }

        $cfg = config('mail');
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = (string) $cfg['host'];
            $mail->SMTPAuth = true;
            $mail->Username = (string) $cfg['username'];
            $mail->Password = (string) $cfg['password'];
            $mail->SMTPSecure = (string) $cfg['encryption'];
            $mail->Port = (int) $cfg['port'];

            $mail->setFrom((string) $cfg['from_address'], (string) $cfg['from_name']);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = View::render($view, $data);

            return $mail->send();
        } catch (PHPMailerException $e) {
            error_log('Mailer error: ' . $e->getMessage());

            return false;
        }
    }
}
