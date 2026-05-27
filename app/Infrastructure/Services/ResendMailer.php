<?php

namespace App\Infrastructure\Services;

use App\Application\Ports\MailerInterface;
use App\Domain\Orders\Order;

class ResendMailer implements MailerInterface
{
    public function sendOrderConfirmation(Order $order, array $items, array $settings, string $pdfBase64): void
    {
        $apiKey = env('RESEND_API_KEY', '');
        if ($apiKey === '') {
            log_message('error', "ResendMailer: RESEND_API_KEY not set — order #{$order->id} confirmation skipped");
            return;
        }

        $siteName    = $settings['site_name']               ?? 'Our Shop';
        $fromEmail   = $settings['contact_email']           ?? '';
        $notifyEmail = $settings['shop_notification_email'] ?? '';

        if ($fromEmail === '') {
            log_message('error', "ResendMailer: contact_email setting is empty — order #{$order->id} confirmation skipped");
            return;
        }

        $payload = [
            'from'     => "{$siteName} <{$fromEmail}>",
            'reply_to' => $fromEmail,
            'to'       => [$order->email],
            'subject'  => "Order Confirmed — #{$order->id}",
            'html'     => $this->buildOrderConfirmationHtml($order, $items, $settings),
            'attachments' => [[
                'filename'     => "invoice-{$order->id}.pdf",
                'content'      => $pdfBase64,
                'content_type' => 'application/pdf',
            ]],
        ];

        if ($notifyEmail !== '') {
            $payload['bcc'] = [$notifyEmail];
        }

        log_message('info', "ResendMailer: sending order confirmation #{$order->id} to {$order->email} from {$fromEmail}");
        $this->post($apiKey, $payload);
    }

    public function sendLowStockAlert(array $product, array $settings): void
    {
        $apiKey = getenv('RESEND_API_KEY');
        if (empty($apiKey)) return;

        $toEmail = $settings['shop_low_stock_alert_email']
            ?? getenv('app.contactToEmail')
            ?: '';

        if ($toEmail === '') {
            log_message('error', "ResendMailer: no recipient for low-stock alert product #{$product['id']}");
            return;
        }

        $from = getenv('RESEND_FROM')
            ?: ('noreply@contact.' . parse_url(getenv('app.baseURL') ?: 'localhost', PHP_URL_HOST));

        $qty       = (int) $product['stock_qty'];
        $threshold = (int) $product['low_stock_threshold'];

        try {
            $resend = \Resend::client($apiKey);
            $resend->emails->send([
                'from'    => "Shop Alerts <{$from}>",
                'to'      => [$toEmail],
                'subject' => $qty === 0
                    ? "[Stock Alert] {$product['name']} is OUT OF STOCK"
                    : "[Stock Alert] {$product['name']} is running low ({$qty} remaining)",
                'html'    => $this->buildLowStockHtml($product, $qty, $threshold),
            ]);
        } catch (\Exception $e) {
            log_message('error', "ResendMailer: Resend error for product #{$product['id']}: " . $e->getMessage());
        }
    }

    public function sendContactEnquiry(
        string  $name,
        string  $email,
        ?string $phone,
        ?string $service,
        string  $message,
        array   $settings,
    ): void {
        $apiKey = env('RESEND_API_KEY', '');
        if ($apiKey === '') return;

        $siteName  = $settings['site_name']   ?? 'Our Shop';
        $toEmail   = $settings['contact_email'] ?? '';
        if ($toEmail === '') return;

        $payload = [
            'from'     => "{$siteName} <{$toEmail}>",
            'reply_to' => $email,
            'to'       => [$toEmail],
            'subject'  => "New Enquiry from {$name}",
            'html'     => $this->buildContactEnquiryHtml($name, $email, $phone, $service, $message, $siteName),
        ];

        $this->post($apiKey, $payload);
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function buildOrderConfirmationHtml(Order $order, array $items, array $settings): string
    {
        $currency = $order->currency;
        $fmt      = fn(int $cents) => (new \NumberFormatter('en-ZA', \NumberFormatter::CURRENCY))
            ->formatCurrency($cents / 100, $currency);

        $vatEnabled = ($settings['shop_vat_enabled'] ?? '0') === '1';
        $vatRate    = (float)($settings['shop_vat_rate'] ?? 15);

        $firstName = $this->e($order->firstName);
        $orderId   = $order->id;

        $itemRows = '';
        foreach ($items as $item) {
            $name = $this->e($item['product_name']);
            if (!empty($item['variant_name'])) {
                $name .= ' <span style="color:#aaaaaa;font-size:12px;">(' . $this->e($item['variant_name']) . ')</span>';
            }
            $itemRows .= sprintf(
                '<tr>
                  <td style="padding:12px 0;border-bottom:1px solid #1a1a1a;color:#f0f0f0;font-size:14px;">%s</td>
                  <td style="padding:12px 0;border-bottom:1px solid #1a1a1a;color:#aaaaaa;font-size:14px;text-align:center;">%d</td>
                  <td style="padding:12px 0;border-bottom:1px solid #1a1a1a;color:#f0f0f0;font-size:14px;text-align:right;">%s</td>
                </tr>',
                $name,
                $item['qty'],
                $fmt((int)$item['line_total_cents'])
            );
        }

        $shippingRow = $order->shipping->amountCents > 0
            ? sprintf('<tr><td colspan="2" style="padding:6px 0;color:#aaaaaa;font-size:13px;text-align:right;">Shipping</td><td style="padding:6px 0;color:#aaaaaa;font-size:13px;text-align:right;">%s</td></tr>', $fmt($order->shipping->amountCents))
            : '<tr><td colspan="2" style="padding:6px 0;color:#aaaaaa;font-size:13px;text-align:right;">Shipping</td><td style="padding:6px 0;color:#aaaaaa;font-size:13px;text-align:right;">Free</td></tr>';

        $vatRow = ($vatEnabled && $order->vat->amountCents > 0)
            ? sprintf('<tr><td colspan="2" style="padding:6px 0;color:#aaaaaa;font-size:13px;text-align:right;">VAT (%s%%)</td><td style="padding:6px 0;color:#aaaaaa;font-size:13px;text-align:right;">%s</td></tr>', $vatRate, $fmt($order->vat->amountCents))
            : '';

        $totalFormatted = $fmt($order->total->amountCents);
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Order Confirmed</title>
</head>
<body style="margin:0;padding:0;background-color:#0a0a0a;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0a0a0a;">
  <tr><td align="center" style="padding:40px 16px;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="580" style="max-width:580px;width:100%;">

      <!-- Header -->
      <tr><td style="padding-bottom:32px;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td>
              <span style="font-size:22px;font-weight:900;letter-spacing:-0.5px;color:#ffffff;text-transform:uppercase;">SKATER</span><span style="font-size:22px;font-weight:900;letter-spacing:-0.5px;color:#d10000;text-transform:uppercase;"> NATION</span>
            </td>
            <td style="text-align:right;">
              <span style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#aaaaaa;">Order #{$orderId}</span>
            </td>
          </tr>
        </table>
      </td></tr>

      <!-- Red accent bar -->
      <tr><td style="height:3px;background-color:#d10000;margin-bottom:32px;display:block;">&nbsp;</td></tr>

      <!-- Hero message -->
      <tr><td style="padding:32px 0 24px;">
        <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#d10000;">Order Confirmed</p>
        <h1 style="margin:0 0 12px;font-size:28px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:-0.5px;line-height:1.1;">You're in, {$firstName}.</h1>
        <p style="margin:0;font-size:15px;color:#aaaaaa;line-height:1.6;">Your order has been confirmed and your invoice is attached. We'll be in touch with shipping details soon.</p>
      </td></tr>

      <!-- Order details card -->
      <tr><td style="background-color:#111111;border-radius:4px;padding:24px;margin-bottom:24px;">
        <p style="margin:0 0 20px;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#aaaaaa;">Order Summary</p>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
          <thead>
            <tr>
              <th style="padding:0 0 8px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#555555;text-align:left;border-bottom:1px solid #1a1a1a;">Item</th>
              <th style="padding:0 0 8px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#555555;text-align:center;border-bottom:1px solid #1a1a1a;">Qty</th>
              <th style="padding:0 0 8px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#555555;text-align:right;border-bottom:1px solid #1a1a1a;">Total</th>
            </tr>
          </thead>
          <tbody>
            {$itemRows}
          </tbody>
          <tfoot>
            {$vatRow}
            {$shippingRow}
            <tr>
              <td colspan="2" style="padding:16px 0 0;font-size:14px;font-weight:700;color:#ffffff;text-align:right;border-top:2px solid #d10000;">Total</td>
              <td style="padding:16px 0 0;font-size:16px;font-weight:900;color:#d10000;text-align:right;border-top:2px solid #d10000;">{$totalFormatted}</td>
            </tr>
          </tfoot>
        </table>
      </td></tr>

      <!-- Invoice note -->
      <tr><td style="padding:24px 0 0;">
        <p style="margin:0;font-size:13px;color:#555555;line-height:1.6;">Your invoice is attached as a PDF. Keep it for your records.</p>
      </td></tr>

      <!-- Footer -->
      <tr><td style="padding:40px 0 0;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top:1px solid #1a1a1a;">
          <tr><td style="padding:24px 0 0;">
            <p style="margin:0 0 4px;font-size:11px;color:#333333;">&copy; {$year} Skater Nation. Born in Secunda.</p>
            <p style="margin:0;font-size:11px;color:#333333;">snonline.co.za</p>
          </td></tr>
        </table>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    private function buildContactEnquiryHtml(
        string  $name,
        string  $email,
        ?string $phone,
        ?string $service,
        string  $message,
        string  $siteName,
    ): string {
        $eName    = $this->e($name);
        $eEmail   = $this->e($email);
        $eMessage = nl2br($this->e($message));
        $ePhone   = $phone   ? $this->e($phone)   : null;
        $eService = $service ? $this->e($service) : null;
        $year     = date('Y');

        $phoneRow = $ePhone
            ? "<tr><td style=\"padding:8px 16px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#555555;background-color:#111111;width:100px;\">Phone</td><td style=\"padding:8px 16px;font-size:14px;color:#f0f0f0;\">{$ePhone}</td></tr>"
            : '';
        $serviceRow = $eService
            ? "<tr><td style=\"padding:8px 16px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#555555;background-color:#111111;\">Service</td><td style=\"padding:8px 16px;font-size:14px;color:#f0f0f0;\">{$eService}</td></tr>"
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>New Enquiry</title>
</head>
<body style="margin:0;padding:0;background-color:#0a0a0a;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0a0a0a;">
  <tr><td align="center" style="padding:40px 16px;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="580" style="max-width:580px;width:100%;">

      <!-- Header -->
      <tr><td style="padding-bottom:32px;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td>
              <span style="font-size:22px;font-weight:900;letter-spacing:-0.5px;color:#ffffff;text-transform:uppercase;">SKATER</span><span style="font-size:22px;font-weight:900;letter-spacing:-0.5px;color:#d10000;text-transform:uppercase;"> NATION</span>
            </td>
            <td style="text-align:right;">
              <span style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#aaaaaa;">New Enquiry</span>
            </td>
          </tr>
        </table>
      </td></tr>

      <!-- Red accent bar -->
      <tr><td style="height:3px;background-color:#d10000;">&nbsp;</td></tr>

      <!-- Heading -->
      <tr><td style="padding:32px 0 24px;">
        <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#d10000;">Contact Form</p>
        <h1 style="margin:0 0 12px;font-size:26px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:-0.5px;line-height:1.1;">Message from {$eName}</h1>
        <p style="margin:0;font-size:14px;color:#aaaaaa;line-height:1.6;">Someone reached out via the contact form. Reply directly to this email to respond.</p>
      </td></tr>

      <!-- Sender details -->
      <tr><td style="background-color:#111111;border-radius:4px;overflow:hidden;margin-bottom:24px;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
          <tr>
            <td style="padding:8px 16px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#555555;background-color:#111111;border-bottom:1px solid #1a1a1a;width:100px;">Name</td>
            <td style="padding:8px 16px;font-size:14px;color:#f0f0f0;border-bottom:1px solid #1a1a1a;">{$eName}</td>
          </tr>
          <tr>
            <td style="padding:8px 16px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#555555;background-color:#111111;border-bottom:1px solid #1a1a1a;">Email</td>
            <td style="padding:8px 16px;font-size:14px;border-bottom:1px solid #1a1a1a;"><a href="mailto:{$eEmail}" style="color:#d10000;text-decoration:none;">{$eEmail}</a></td>
          </tr>
          {$phoneRow}
          {$serviceRow}
        </table>
      </td></tr>

      <!-- Message -->
      <tr><td style="padding:24px 0 0;">
        <p style="margin:0 0 12px;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#555555;">Message</p>
        <div style="background-color:#111111;border-radius:4px;padding:20px 24px;border-left:3px solid #d10000;">
          <p style="margin:0;font-size:14px;color:#f0f0f0;line-height:1.8;">{$eMessage}</p>
        </div>
      </td></tr>

      <!-- Footer -->
      <tr><td style="padding:40px 0 0;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top:1px solid #1a1a1a;">
          <tr><td style="padding:24px 0 0;">
            <p style="margin:0 0 4px;font-size:11px;color:#333333;">&copy; {$year} {$siteName}. Born in Secunda.</p>
            <p style="margin:0;font-size:11px;color:#333333;">snonline.co.za</p>
          </td></tr>
        </table>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    private function post(string $apiKey, array $payload): void
    {
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false || $curlErr !== '') {
            log_message('error', "ResendMailer cURL error: {$curlErr}");
        } elseif ($status >= 400) {
            log_message('error', "ResendMailer failed [{$status}]: {$response}");
        } else {
            log_message('info', "ResendMailer sent [{$status}]: {$response}");
        }
    }

    private function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function buildLowStockHtml(array $product, int $qty, int $threshold): string
    {
        $name       = $this->e($product['name']);
        $slug       = $this->e($product['slug']);
        $qtyColor   = $qty === 0 ? '#dc2626' : '#d97706';
        $qtyLabel   = $qty === 0 ? 'OUT OF STOCK' : "{$qty} remaining";
        $statusText = $qty === 0
            ? 'This product is <strong>out of stock</strong> and cannot be purchased until restocked.'
            : "Stock has dropped to <strong>{$qty} units</strong>, which is at or below the alert threshold of <strong>{$threshold}</strong>.";
        $year   = date('Y');
        $sentAt = date('l, d F Y \a\t H:i');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Low Stock Alert</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f4f5;">
  <tr><td align="center" style="padding:32px 16px;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="560"
      style="max-width:560px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
      <tr><td style="background-color:#7c2d12;padding:24px 32px;">
        <p style="margin:0;font-size:11px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;color:#fca5a5;">Shop Inventory Alert</p>
        <h1 style="margin:6px 0 0;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">Low Stock Warning</h1>
      </td></tr>
      <tr><td style="padding:28px 32px;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
          <tr><td style="background-color:{$qtyColor};border-radius:6px;padding:6px 14px;">
            <span style="font-size:13px;font-weight:700;color:#ffffff;letter-spacing:0.5px;">{$qtyLabel}</span>
          </td></tr>
        </table>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
          style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:20px;">
          <tr style="background-color:#f9fafb;">
            <td style="padding:10px 16px;font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;width:120px;">Product</td>
            <td style="padding:10px 16px;font-size:14px;font-weight:600;color:#111827;border-bottom:1px solid #e5e7eb;">{$name}</td>
          </tr>
          <tr>
            <td style="padding:10px 16px;font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#6b7280;background-color:#f9fafb;border-bottom:1px solid #e5e7eb;">Slug</td>
            <td style="padding:10px 16px;font-size:13px;font-family:monospace;color:#374151;border-bottom:1px solid #e5e7eb;">{$slug}</td>
          </tr>
          <tr style="background-color:#f9fafb;">
            <td style="padding:10px 16px;font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#6b7280;">Stock Qty</td>
            <td style="padding:10px 16px;font-size:14px;font-weight:700;color:{$qtyColor};">{$qty}</td>
          </tr>
        </table>
        <p style="margin:0 0 24px;font-size:14px;color:#374151;line-height:1.7;">
          {$statusText} Please log in to the admin panel to update the stock level.
        </p>
      </td></tr>
      <tr><td style="background-color:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 32px;">
        <p style="margin:0;font-size:11px;color:#9ca3af;">Automated alert &bull; {$sentAt} &bull; &copy; {$year}</p>
        <p style="margin:4px 0 0;font-size:11px;color:#9ca3af;">Alerts are sent at most once every 24 hours per product.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }
}
