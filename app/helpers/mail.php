<?php
// app/helpers/mail.php
// send_mail() sends an email with Gmail SMTP (PHPMailer). Settings: config/mail.php
// Returns true when sent, false when not (not configured, or Gmail refused).
// Used by PasswordController to send the forgot-password OTP.

require_once APP_PATH . '/libs/PHPMailer/Exception.php';
require_once APP_PATH . '/libs/PHPMailer/PHPMailer.php';
require_once APP_PATH . '/libs/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

// ---- Mail settings ----
// config/mail.php when it exists, otherwise the empty example file (= mail is off)
function mail_config()
{
    $file = ROOT_PATH . '/config/mail.php';
    return is_file($file) ? require $file : require ROOT_PATH . '/config/mail.example.php';
}

// true when a Gmail username and app password are filled in
function mail_is_configured()
{
    $config = mail_config();
    return $config['username'] !== '' && $config['password'] !== '';
}

// ---- Send one email ----
// $html = the email body, $text = the plain-text copy for mail apps that don't show HTML
function send_mail($toEmail, $toName, $subject, $html, $text)
{
    if (!mail_is_configured()) {
        return false;
    }
    $config = mail_config();
    $mail = new PHPMailer(true);
    try {
        // Gmail SMTP login
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->Port       = (int)$config['port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;

        // the message itself
        $fromEmail = $config['from_email'] !== '' ? $config['from_email'] : $config['username'];
        $mail->setFrom($fromEmail, $config['from_name']);
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
