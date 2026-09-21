<?php
// washease-api/config/smtp.php

return [
    // SMTP Host (e.g. smtp.gmail.com, smtp.office365.com, smtp.mailtrap.io, etc.)
    'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    
    // SMTP Port (587 for TLS/STARTTLS, 465 for SSL)
    'smtp_port' => intval(getenv('SMTP_PORT') ?: 587),
    
    // Sender Email Address
    'smtp_user' => getenv('SMTP_USER') ?: 'bbanu2341@gmail.com',
    
    // Google App Password (16 characters from https://myaccount.google.com/apppasswords)
    'smtp_pass' => getenv('SMTP_PASS') ?: 'sgpm yupy spzy wydh',
    
    // Sender Name
    'from_name' => 'WashEase Team'
];
