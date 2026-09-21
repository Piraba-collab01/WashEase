<?php
require_once __DIR__ . '/../api/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; 
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'imironmann195@gmail.com';
    $mail->Password   = 'tkwxkgmltmthqsio'; // without spaces
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

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
    echo "\nSUCCESSFULLY SENT EMAIL VIA PORT 465!\n";
} catch (Exception $e) {
    echo "\nFAILED PORT 465: {$mail->ErrorInfo}\n";
}
