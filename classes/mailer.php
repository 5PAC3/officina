<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer-master/src/SMTP.php';

class Mailer {
    public static function invia(string $to, string $subject, string $body): bool {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'esercizio-5binf@ismonnet.eu';
            $mail->Password = 'hjmr bcab tegm oshp';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('esercizio-5binf@ismonnet.eu', 'Officina');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }
}
?>