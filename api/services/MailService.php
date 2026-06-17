<?php
// washease-api/services/MailService.php

class MailService {
    public static function sendOTP($email, $otp) {
        $subject = "WashEase - Email Verification OTP";
        $body = "<h3>Welcome to WashEase!</h3>"
              . "<p>Your One-Time Password (OTP) for verification is: <b>$otp</b></p>"
              . "<p>This OTP will expire in 10 minutes.</p>";

        // Try using PHPMailer if Composer vendor autoload exists
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // Adjust configuration if needed
                $mail->SMTPAuth   = true;
                $mail->Username   = 'imironmann195@gmail.com'; // User's email
                $mail->Password   = 'tkwx kgml tmth qsio'; // User's SMTP password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('imironmann195@gmail.com', 'WashEase Team');
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
        
        return true; // Return true as fallback is simulated successfully
    }
}
