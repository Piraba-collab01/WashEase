<?php
// washease-api/services/MailService.php

class MailService {
    public static function sendOTP($email, $otp) {
        $subject = "WashEase - Email Verification OTP";
        $body = "<h3>Welcome to WashEase!</h3>"
              . "<p>Your One-Time Password (OTP) for verification is: <b>$otp</b></p>"
              . "<p>This OTP will expire in 10 minutes.</p>";

        // Load SMTP Config
        $smtpConfig = [];
        $configFile = __DIR__ . '/../config/smtp.php';
        if (file_exists($configFile)) {
            $smtpConfig = require $configFile;
        }

        $smtpHost = $smtpConfig['smtp_host'] ?? (getenv('SMTP_HOST') ?: 'smtp.gmail.com');
        $smtpPort = intval($smtpConfig['smtp_port'] ?? (getenv('SMTP_PORT') ?: 587));
        $smtpUser = $smtpConfig['smtp_user'] ?? (getenv('SMTP_USER') ?: 'imironmann195@gmail.com');
        $smtpPass = $smtpConfig['smtp_pass'] ?? (getenv('SMTP_PASS') ?: 'tkwx kgml tmth qsio');
        $fromName = $smtpConfig['from_name'] ?? 'WashEase Team';

        // Try using PHPMailer if Composer vendor autoload exists
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                // Server settings
                $mail->isSMTP();
                $mail->Host       = $smtpHost;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtpUser;
                $mail->Password   = $smtpPass;
                $mail->SMTPSecure = ($smtpPort === 465) 
                    ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS 
                    : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $smtpPort;

                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                // Recipients
                $mail->setFrom($smtpUser, $fromName);
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;

                $mail->send();
                return true;
            } catch (Exception $e) {
                error_log("PHPMailer failed to send: " . $e->getMessage());
            }
        }

        // Fallback: log the OTP to a file for development and testing
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/otp.log';
        $logMessage = "[" . date('Y-m-d H:i:s') . "] To: $email | OTP: $otp\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        return true; // Return true as OTP was logged successfully for verification
    }
}
