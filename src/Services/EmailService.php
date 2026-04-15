<?php

declare(strict_types=1);

namespace IMEIBot\Services;

use IMEIBot\Core\Logger;

class EmailService
{
    private Logger $logger;
    private string $fromEmail;
    private string $fromName;
    private bool $enabled;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->fromEmail = $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@imei-bot.com';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'IMEI Bot Platform';
        $this->enabled = $_ENV['MAIL_ENABLED'] === 'true';
    }

    public function sendWelcomeEmail(string $toEmail, string $name): bool
    {
        if (!$this->enabled) {
            $this->logger->info('Email service disabled, skipping welcome email');
            return true;
        }

        try {
            $subject = 'Welcome to IMEI Bot Platform!';
            $body = $this->getWelcomeEmailTemplate($name);
            
            return $this->sendEmail($toEmail, $subject, $body);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send welcome email', [
                'to' => $toEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendPasswordResetEmail(string $toEmail, string $name, string $resetToken): bool
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            $subject = 'Password Reset Request';
            $body = $this->getPasswordResetEmailTemplate($name, $resetToken);
            
            return $this->sendEmail($toEmail, $subject, $body);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send password reset email', [
                'to' => $toEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendEmailVerificationEmail(string $toEmail, string $name, string $verificationToken): bool
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            $subject = 'Verify Your Email Address';
            $body = $this->getEmailVerificationTemplate($name, $verificationToken);
            
            return $this->sendEmail($toEmail, $subject, $body);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email verification', [
                'to' => $toEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function sendEmail(string $to, string $subject, string $body): bool
    {
        $headers = [
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0'
        ];

        if (mail($to, $subject, $body, implode("\r\n", $headers))) {
            $this->logger->info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject
            ]);
            return true;
        }

        $this->logger->error('Failed to send email', [
            'to' => $to,
            'subject' => $subject
        ]);
        return false;
    }

    private function getWelcomeEmailTemplate(string $name): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8080';
        
        return "
        <html>
        <head>
            <title>Welcome to IMEI Bot Platform</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9fafb; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to IMEI Bot Platform!</h1>
                </div>
                <div class='content'>
                    <p>Hi " . htmlspecialchars($name) . ",</p>
                    <p>Thank you for joining the IMEI Bot Platform! We're excited to have you on board.</p>
                    <p>With your new account, you can:</p>
                    <ul>
                        <li>Generate IMEI numbers for testing purposes</li>
                        <li>Download firmware files</li>
                        <li>Access our bot services on Telegram and WhatsApp</li>
                        <li>Track your usage and statistics</li>
                    </ul>
                    <p>Get started by logging into your account:</p>
                    <a href='{$baseUrl}/login' class='button'>Login to Your Account</a>
                    <p>If you have any questions, feel free to contact our support team.</p>
                    <p>Best regards,<br>The IMEI Bot Team</p>
                </div>
                <div class='footer'>
                    <p>This email was sent to " . htmlspecialchars($to) . ". If you didn't create an account, please ignore this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getPasswordResetEmailTemplate(string $name, string $resetToken): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8080';
        $resetUrl = $baseUrl . '/reset-password?token=' . $resetToken;
        
        return "
        <html>
        <head>
            <title>Password Reset</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc2626; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9fafb; }
                .button { display: inline-block; background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Reset Request</h1>
                </div>
                <div class='content'>
                    <p>Hi " . htmlspecialchars($name) . ",</p>
                    <p>We received a request to reset your password for your IMEI Bot Platform account.</p>
                    <p>Click the button below to reset your password:</p>
                    <a href='{$resetUrl}' class='button'>Reset Password</a>
                    <p>Or copy and paste this link into your browser:</p>
                    <p>{$resetUrl}</p>
                    <p>This link will expire in 1 hour for security reasons.</p>
                    <p>If you didn't request a password reset, please ignore this email or contact support if you have concerns.</p>
                    <p>Best regards,<br>The IMEI Bot Team</p>
                </div>
                <div class='footer'>
                    <p>This email was sent to " . htmlspecialchars($to) . ". If you didn't request this, please ignore this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getEmailVerificationTemplate(string $name, string $verificationToken): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8080';
        $verifyUrl = $baseUrl . '/verify-email?token=' . $verificationToken;
        
        return "
        <html>
        <head>
            <title>Verify Your Email</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #059669; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9fafb; }
                .button { display: inline-block; background: #059669; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Verify Your Email Address</h1>
                </div>
                <div class='content'>
                    <p>Hi " . htmlspecialchars($name) . ",</p>
                    <p>Thank you for registering with IMEI Bot Platform! To complete your registration, please verify your email address.</p>
                    <p>Click the button below to verify your email:</p>
                    <a href='{$verifyUrl}' class='button'>Verify Email</a>
                    <p>Or copy and paste this link into your browser:</p>
                    <p>{$verifyUrl}</p>
                    <p>This link will expire in 24 hours.</p>
                    <p>If you didn't create an account, please ignore this email.</p>
                    <p>Best regards,<br>The IMEI Bot Team</p>
                </div>
                <div class='footer'>
                    <p>This email was sent to " . htmlspecialchars($to) . ". If you didn't create an account, please ignore this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
