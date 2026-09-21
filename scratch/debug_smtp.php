<?php
require_once __DIR__ . '/../api/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; 
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'imironmann195@gmail.com';
    $mail->Password   = 'tkwx kgml tmth qsio'; // App password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom('imironmann195@gmail.com', 'WashEase Team');
    $mail->addAddress('bbanu2341@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = 'WashEase Test OTP';
    $mail->Body    = 'Your test OTP is: 123456';

    $mail->send();
    echo "\nMessage has been sent successfully via Gmail SMTP!\n";
} catch (Exception $e) {
    echo "\nMessage could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
