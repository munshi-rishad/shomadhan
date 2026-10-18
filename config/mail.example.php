<?php
// config/mail.php  (copy of mail.example.php)
// Gmail SMTP settings for sending the forgot-password OTP.
// 1. Turn on 2-Step Verification in your Google account.
// 2. Make an "App password": https://myaccount.google.com/apppasswords
// 3. Put your Gmail address and the 16-letter app password below.
// While these are empty, no OTP email can be sent (the page shows an error).
return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => '',          // e.g. yourname@gmail.com
    'password'   => '',          // 16-letter Google app password (not your normal password)
    'from_email' => '',          // usually the same as username
    'from_name'  => 'Shomadhan',
];
