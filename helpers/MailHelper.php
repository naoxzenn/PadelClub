<?php
// helpers/MailHelper.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper {
    /**
     * Send email using HTML templates
     * 
     * @param string $to Recipient email address
     * @param string $subject Subject of the email
     * @param string $templateName Template file name (without extension)
     * @param array $data Data array to extract inside template variables
     * @return bool True if mail sent successfully, false otherwise
     */
    public static function send($to, $subject, $templateName, $data = []) {
        $mail = new PHPMailer(true);

        try {
            // Set up SMTP configuration from environment variables
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USERNAME'] ?? '';
            $mail->Password   = $_ENV['SMTP_PASSWORD'] ?? '';
            
            $encryption = strtolower($_ENV['SMTP_ENCRYPTION'] ?? 'tls');
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);
            $mail->CharSet    = 'UTF-8';

            // Set sender and recipient
            $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? 'info@padelclub.com';
            $fromName  = $_ENV['SMTP_FROM_NAME'] ?? 'PadelClub Premium';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            // Set email content format to HTML
            $mail->isHTML(true);
            $mail->Subject = $subject;

            // Generate HTML body using the templates
            $mail->Body = self::renderTemplate($templateName, $data);

            return $mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Render the PHP template file with variables
     * 
     * @param string $templateName
     * @param array $data
     * @return string Rendered HTML content
     */
    private static function renderTemplate($templateName, $data) {
        $templatePath = __DIR__ . '/../views/emails/' . $templateName . '.php';
        if (!file_exists($templatePath)) {
            error_log("Email template not found: " . $templatePath);
            return isset($data['content']) ? htmlspecialchars($data['content']) : '';
        }

        // Extract data keys as variables inside the template context
        extract($data);

        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
}
