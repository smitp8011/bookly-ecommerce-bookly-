<?php
/**
 * Mailer helper — wraps PHPMailer (bundled in includes/PHPMailer/, no
 * Composer needed) with the SMTP settings from mail_config.php.
 */
require_once __DIR__ . '/../mail_config.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Send an HTML email with Anti-Spam headers.
 */
function send_email(string $to_email, string $to_name, string $subject, string $html_body): bool {
    $mail = new PHPMailer(true);
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        // Anti-Spam & Encoding Settings
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';

        // Sender & Recipient Setup
        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addReplyTo(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        // Standard Email Headers to avoid Spam Triggers
        $mail->Priority   = 3; // Normal Priority
        $mail->addCustomHeader('X-Mailer', 'PHP/' . phpversion());
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;

        // Clean Plain-Text Version (Spam filters require a matching plain text body)
        $clean_text = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</tr>'], "\n", $html_body));
        $mail->AltBody = trim(preg_replace("/[\r\n]+/", "\n", $clean_text));

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('Mail send failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/** Shared wrapper so every email looks consistent and valid HTML structure. */
function email_wrapper(string $title, string $body_html): string {
    return '<!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>' . htmlspecialchars($title) . '</title>
    </head>
    <body style="margin:0; padding:0; background-color:#f4f4f4; font-family:Arial,Helvetica,sans-serif;">
      <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout:fixed; background-color:#f4f4f4; padding:20px 0;">
        <tr>
          <td align="center">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:550px; background-color:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e0e0e0;">
              <tr>
                <td style="background-color:#1c1815; padding:18px 24px; color:#e3cb8a; font-size:22px; font-weight:bold; letter-spacing:1px; border-bottom:2px solid #e5c158;">
                  <span style="display:inline-block; vertical-align:middle; width:28px; height:28px; background:#6e1f2a; border-radius:6px; text-align:center; line-height:28px; color:#e5c158; font-size:18px; margin-right:8px; font-family:Georgia,serif; font-weight:bold;">B</span>
                  <span style="vertical-align:middle; color:#e5c158;">BOOKNEST</span>
                </td>
              </tr>
              <tr>
                <td style="padding:28px 24px; color:#333333; font-size:15px; line-height:1.6;">
                  <h2 style="color:#1c1815; margin-top:0; font-size:18px;">' . htmlspecialchars($title) . '</h2>
                  ' . $body_html . '
                </td>
              </tr>
              <tr>
                <td style="background-color:#fafafa; padding:16px 24px; text-align:center; color:#888888; font-size:12px; border-top:1px solid #eeeeee;">
                  &copy; ' . date('Y') . ' BookNest — Book Ordering and Delivery System. All rights reserved.<br>
                  This is an automated notification, please do not reply directly.
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </body>
    </html>';
}

/** Send a password-reset OTP email. */
function send_otp_email(string $to_email, string $to_name, string $otp): bool {
    $body = '
      <p>Hi ' . htmlspecialchars($to_name) . ',</p>
      <p>Use the code below to reset your BookNest password. It expires in <strong>10 minutes</strong>.</p>
      <div style="text-align:center; margin:24px 0;">
        <span style="display:inline-block; background-color:#f3ede2; color:#6e1f2a; font-size:28px; font-weight:bold; letter-spacing:6px; padding:12px 24px; border-radius:6px; border:1px dashed #6e1f2a;">
          ' . htmlspecialchars($otp) . '
        </span>
      </div>
      <p style="color:#666666; font-size:13px;">If you didn\'t request this, you can safely ignore this email.</p>
    ';
    return send_email($to_email, $to_name, 'Your BookNest password reset code', email_wrapper('Reset your password', $body));
}

/** Send an order-status-update email. */
function send_order_status_email(string $to_email, string $to_name, int $order_id, string $status): bool {
    $status_labels = [
        'pending'              => 'Pending',
        'processing'           => 'Processing',
        'shipped'              => 'Shipped',
        'delivered'            => 'Delivered',
        'cancelled'            => 'Cancelled',
        'replacement_requested'=> 'Replacement Requested',
        'replacement_approved' => 'Replacement Approved',
        'replaced'             => 'Replaced',
        'replacement_rejected' => 'Replacement Declined',
    ];
    $status_messages = [
        'pending'              => 'We have received your order and it is being prepared.',
        'processing'           => 'Your order is currently being packed and processed with care.',
        'shipped'              => 'Great news! Your books are on their way to your delivery address.',
        'delivered'            => 'Your order has been delivered successfully. Thank you for choosing BookNest!',
        'cancelled'            => 'Your order has been cancelled. If you need any assistance, please contact support.',
        'replacement_requested'=> 'We have received your replacement request and our concierge team is reviewing it.',
        'replacement_approved' => 'Good news! Your replacement request has been approved and a fresh copy is being arranged.',
        'replaced'             => 'Your replacement copy has been successfully delivered.',
        'replacement_rejected' => 'Your replacement request could not be approved at this time. Please contact support for details.',
    ];
    $label = $status_labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    $message = $status_messages[$status] ?? 'Your order status has been updated.';

    // Status pill colors
    $badge_color = '#e3cb8a';
    $text_color = '#1c1815';
    if ($status === 'delivered' || $status === 'replaced' || $status === 'replacement_approved') {
        $badge_color = '#d1fae5';
        $text_color = '#065f46';
    } elseif ($status === 'shipped' || $status === 'processing') {
        $badge_color = '#dbeafe';
        $text_color = '#1e40af';
    } elseif ($status === 'cancelled' || $status === 'replacement_rejected') {
        $badge_color = '#fee2e2';
        $text_color = '#991b1b';
    } elseif ($status === 'replacement_requested') {
        $badge_color = '#fef3c7';
        $text_color = '#92400e';
    }

    $body = '
      <p style="font-size:16px; margin-bottom:12px;">Hi <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
      <p style="font-size:15px; color:#444; line-height:1.6; margin-bottom:20px;">' . htmlspecialchars($message) . '</p>
      <table style="width:100%; margin:20px 0; border-collapse:collapse; background:#faf8f5; border-radius:6px; border:1px solid #eee;">
        <tr style="border-bottom:1px solid #eee;">
          <td style="padding:12px 16px; color:#666666; font-size:14px;">Order Number</td>
          <td style="padding:12px 16px; text-align:right; font-weight:bold; color:#1c1815; font-size:15px;">#' . $order_id . '</td>
        </tr>
        <tr>
          <td style="padding:12px 16px; color:#666666; font-size:14px;">Current Status</td>
          <td style="padding:12px 16px; text-align:right;">
            <span style="background-color:' . $badge_color . '; color:' . $text_color . '; padding:6px 14px; border-radius:14px; font-size:13px; font-weight:bold; display:inline-block;">' . htmlspecialchars($label) . '</span>
          </td>
        </tr>
      </table>
      <div style="margin-top:24px; text-align:center;">
        <a href="http://localhost/booknest/orders.php" style="background:#6e1f2a; color:#e5c158; text-decoration:none; padding:10px 22px; border-radius:6px; font-size:14px; font-weight:bold; display:inline-block;">View in My Orders</a>
      </div>
      <p style="color:#888888; font-size:12px; margin-top:24px; text-align:center;">You can track real-time delivery status or download your digital invoice anytime from your account dashboard.</p>
    ';
    
    // Clean subject line without special dash characters
    $subject = 'BookNest Order #' . $order_id . ' Status: ' . $label;
    return send_email($to_email, $to_name, $subject, email_wrapper('Order Status Update', $body));
}