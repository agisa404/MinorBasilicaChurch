<?php
return [
    // Gmail SMTP settings for OTP sending
    // Use an App Password, not your Gmail account password.
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => '',
    'password' => '',
    'from_email' => '',
    'from_name' => 'Minor Basilica',
    'security' => 'tls', // tls or ssl
    'debug' => 0, // 0=off, 2=client/server conversation
];

