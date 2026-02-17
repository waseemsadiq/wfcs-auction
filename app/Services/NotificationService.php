<?php
declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use App\Repositories\SettingsRepository;

class NotificationService
{
    // -------------------------------------------------------------------------
    // Mailer factory
    // -------------------------------------------------------------------------

    private function mailer(): PHPMailer
    {
        $settings = new SettingsRepository();
        $mail     = new PHPMailer(true);

        $smtpHost = $settings->get('smtp_host') ?: '';

        if (!empty($smtpHost)) {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $settings->get('smtp_username') ?: '';
            $mail->Password   = $settings->get('smtp_password') ?: '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($settings->get('smtp_port') ?: 587);
        } else {
            // Fallback to PHP mail() function
            $mail->isMail();
        }

        $fromEmail = $settings->get('email_from')      ?: 'noreply@wellfoundation.org.uk';
        $fromName  = $settings->get('email_from_name') ?: 'WFCS Auction';
        $mail->setFrom($fromEmail, $fromName);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;

        return $mail;
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Send email verification link.
     */
    public function sendVerification(array $user, string $token, string $baseUrl): void
    {
        $verifyUrl = rtrim($baseUrl, '/') . '/verify-email?token=' . urlencode($token);
        $name      = $user['name'] ?? 'there';
        $email     = $user['email'] ?? '';

        $subject = 'Verify your WFCS Auction email address';

        $html = $this->layout(
            $subject,
            'Verify your email address',
            "Hi {$name},<br><br>
            Welcome to WFCS Auction! Before you can start bidding, please verify your
            email address by clicking the button below.<br><br>
            This link will expire in 24 hours.",
            $verifyUrl,
            'Verify Email Address',
            "If you did not register for an account, you can safely ignore this email."
        );

        $text = "Hi {$name},\n\nVerify your email address:\n{$verifyUrl}\n\n"
              . "This link expires in 24 hours.\n\n"
              . "If you did not register, ignore this email.\n\n"
              . $this->footerText();

        $this->send($email, $name, $subject, $html, $text);
    }

    /**
     * Send password reset link.
     */
    public function sendPasswordReset(array $user, string $token, string $baseUrl): void
    {
        $resetUrl = rtrim($baseUrl, '/') . '/reset-password?token=' . urlencode($token);
        $name     = $user['name'] ?? 'there';
        $email    = $user['email'] ?? '';

        $subject = 'Reset your WFCS Auction password';

        $html = $this->layout(
            $subject,
            'Reset your password',
            "Hi {$name},<br><br>
            We received a request to reset the password for your WFCS Auction account.
            Click the button below to choose a new password.<br><br>
            This link will expire in 1 hour.",
            $resetUrl,
            'Reset Password',
            "If you did not request a password reset, you can safely ignore this email.
             Your password will not be changed."
        );

        $text = "Hi {$name},\n\nReset your password:\n{$resetUrl}\n\n"
              . "This link expires in 1 hour.\n\n"
              . "If you did not request this, ignore this email.\n\n"
              . $this->footerText();

        $this->send($email, $name, $subject, $html, $text);
    }

    /**
     * Notify winner that they won an item.
     */
    public function sendWinnerNotification(
        array  $user,
        array  $item,
        float  $amount,
        string $paymentUrl
    ): void {
        $name      = $user['name']  ?? 'there';
        $email     = $user['email'] ?? '';
        $itemTitle = $item['title'] ?? 'Auction Item';
        $formatted = '&pound;' . number_format($amount, 2);

        $subject = "Congratulations! You won: {$itemTitle}";

        $html = $this->layout(
            $subject,
            "You won the auction!",
            "Hi {$name},<br><br>
            Congratulations! You have won the auction for
            <strong>{$itemTitle}</strong> with a winning bid of
            <strong>{$formatted}</strong>.<br><br>
            To complete your purchase, please proceed to payment as soon as possible.
            Items not paid for within the allotted time may be forfeited.",
            $paymentUrl,
            'Complete Payment',
            "If you believe you received this in error, please contact us at
             <a href=\"mailto:info@wellfoundation.org.uk\" style=\"color:#45a2da;\">info@wellfoundation.org.uk</a>."
        );

        $formattedText = '£' . number_format($amount, 2);
        $text = "Hi {$name},\n\nCongratulations! You won the auction for {$itemTitle} "
              . "with a winning bid of {$formattedText}.\n\n"
              . "Complete your payment here:\n{$paymentUrl}\n\n"
              . $this->footerText();

        $this->send($email, $name, $subject, $html, $text);
    }

    /**
     * Notify bidder they have been outbid.
     */
    public function sendOutbidNotification(
        array  $user,
        array  $item,
        float  $newBid,
        string $itemUrl
    ): void {
        $name      = $user['name']  ?? 'there';
        $email     = $user['email'] ?? '';
        $itemTitle = $item['title'] ?? 'Auction Item';
        $formatted = '&pound;' . number_format($newBid, 2);

        $subject = "You've been outbid on: {$itemTitle}";

        $html = $this->layout(
            $subject,
            "You have been outbid",
            "Hi {$name},<br><br>
            Someone has placed a higher bid on <strong>{$itemTitle}</strong>.
            The new leading bid is <strong>{$formatted}</strong>.<br><br>
            Don't miss out — place a new bid now to stay in the running!",
            $itemUrl,
            'Bid Again',
            "If you no longer wish to bid on this item, no action is needed."
        );

        $formattedText = '£' . number_format($newBid, 2);
        $text = "Hi {$name},\n\nYou have been outbid on {$itemTitle}. "
              . "The new leading bid is {$formattedText}.\n\n"
              . "Place a new bid:\n{$itemUrl}\n\n"
              . $this->footerText();

        $this->send($email, $name, $subject, $html, $text);
    }

    /**
     * Send payment confirmation.
     */
    public function sendPaymentConfirmation(array $user, array $item, float $amount): void
    {
        $name      = $user['name']  ?? 'there';
        $email     = $user['email'] ?? '';
        $itemTitle = $item['title'] ?? 'Auction Item';
        $formatted = '&pound;' . number_format($amount, 2);

        $subject = "Payment confirmed — {$itemTitle}";

        $html = $this->layout(
            $subject,
            'Payment confirmed — thank you!',
            "Hi {$name},<br><br>
            Your payment of <strong>{$formatted}</strong> for
            <strong>{$itemTitle}</strong> has been received. Thank you for your generosity!<br><br>
            We will be in touch shortly to arrange delivery or collection of your item.
            If you have any questions, please contact us at
            <a href=\"mailto:info@wellfoundation.org.uk\" style=\"color:#45a2da;\">info@wellfoundation.org.uk</a>.",
            'https://wellfoundation.org.uk',
            'Visit Our Website',
            null
        );

        $formattedText = '£' . number_format($amount, 2);
        $text = "Hi {$name},\n\nYour payment of {$formattedText} for {$itemTitle} has been received. "
              . "Thank you!\n\nWe will be in touch to arrange delivery/collection.\n\n"
              . $this->footerText();

        $this->send($email, $name, $subject, $html, $text);
    }

    /**
     * Send a general email.
     */
    public function send(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = ''
    ): void {
        $mail = $this->mailer();
        $mail->addAddress($to, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        if ($textBody !== '') {
            $mail->AltBody = $textBody;
        }

        $mail->send();
    }

    // -------------------------------------------------------------------------
    // Private template helpers
    // -------------------------------------------------------------------------

    /**
     * Render a full HTML email with a single CTA button.
     * $footerNote is optional — pass null to omit.
     */
    private function layout(
        string  $subject,
        string  $heading,
        string  $bodyHtml,
        string  $ctaUrl,
        string  $ctaLabel,
        ?string $footerNote
    ): string {
        $footerNoteHtml = '';
        if ($footerNote !== null) {
            $footerNoteHtml = '<p style="font-size:13px;color:#666666;margin:16px 0 0 0;">'
                            . $footerNote
                            . '</p>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$subject}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

<!--[if mso]><table role="presentation" width="100%"><tr><td><![endif]-->

<!-- Preheader (hidden preview text) -->
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
{$heading} — WFCS Auction
</div>

<!-- Wrapper -->
<table role="presentation" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#f4f4f5;padding:32px 16px;">
  <tr>
    <td align="center">

      <!-- Card -->
      <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
             style="max-width:560px;background-color:#ffffff;border-radius:8px;
                    box-shadow:0 1px 3px rgba(0,0,0,0.08);overflow:hidden;">

        <!-- Header -->
        <tr>
          <td style="background-color:#45a2da;padding:24px 32px;text-align:center;">
            <span style="font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">
              WFCS Auction
            </span>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:32px 32px 24px 32px;">
            <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0 0 16px 0;
                        line-height:1.3;">{$heading}</h1>
            <p style="font-size:15px;color:#374151;line-height:1.6;margin:0 0 24px 0;">
              {$bodyHtml}
            </p>
            <table role="presentation" cellpadding="0" cellspacing="0">
              <tr>
                <td style="border-radius:6px;background-color:#45a2da;">
                  <a href="{$ctaUrl}"
                     style="background-color:#45a2da;color:#ffffff;padding:12px 24px;
                            text-decoration:none;border-radius:6px;font-weight:600;
                            font-size:15px;display:inline-block;line-height:1;">
                    {$ctaLabel}
                  </a>
                </td>
              </tr>
            </table>
            {$footerNoteHtml}
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background-color:#f9fafb;border-top:1px solid #e5e7eb;
                     padding:20px 32px;text-align:center;">
            <p style="font-size:12px;color:#9ca3af;margin:0 0 4px 0;">
              WFCS Auction &bull; Well Foundation Community Services
            </p>
            <p style="font-size:12px;color:#9ca3af;margin:0 0 4px 0;">
              Building 2, Unit C, Ground Floor, 4 Parklands Way, Eurocentral, Holytown, ML1 4WR
            </p>
            <p style="font-size:12px;color:#9ca3af;margin:0;">
              <a href="mailto:info@wellfoundation.org.uk"
                 style="color:#9ca3af;text-decoration:underline;">info@wellfoundation.org.uk</a>
              &bull; Charity No: SC040105
            </p>
          </td>
        </tr>

      </table>
      <!-- /Card -->

    </td>
  </tr>
</table>

<!--[if mso]></td></tr></table><![endif]-->
</body>
</html>
HTML;
    }

    /**
     * Plain-text email footer.
     */
    private function footerText(): string
    {
        return "---\nWFCS Auction | Well Foundation Community Services\n"
             . "Building 2, Unit C, Ground Floor, 4 Parklands Way, Eurocentral, Holytown, ML1 4WR\n"
             . "info@wellfoundation.org.uk | Charity No: SC040105\n";
    }
}
