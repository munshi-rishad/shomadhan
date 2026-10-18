<?php
// app/helpers/mail.php
// send_mail() sends an email with Gmail SMTP (PHPMailer). Settings: config/mail.php
// Returns true when sent, false when not (not configured, or Gmail refused).

require_once APP_PATH . '/libs/PHPMailer/Exception.php';
require_once APP_PATH . '/libs/PHPMailer/PHPMailer.php';
require_once APP_PATH . '/libs/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

function mail_config()
{
    $file = ROOT_PATH . '/config/mail.php';
    return is_file($file) ? require $file : require ROOT_PATH . '/config/mail.example.php';
}

function mail_is_configured()
{
    $c = mail_config();
    return $c['username'] !== '' && $c['password'] !== '';
}

function send_mail($toEmail, $toName, $subject, $html, $text)
{
    if (!mail_is_configured()) {
        return false;
    }
    $c = mail_config();
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $c['host'];
        $mail->Port       = (int)$c['port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $c['username'];
        $mail->Password   = $c['password'];
        $mail->SMTPSecure = $c['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;
        $mail->setFrom($c['from_email'] !== '' ? $c['from_email'] : $c['username'], $c['from_name']);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text;
        $mail->send();
        return true;
    } catch (Exception $ex) {
        app_log('mail', 'Could not send to ' . $toEmail . ': ' . $mail->ErrorInfo);
        return false;
    }
}
