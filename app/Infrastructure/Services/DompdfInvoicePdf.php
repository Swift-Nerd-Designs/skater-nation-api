<?php

namespace App\Infrastructure\Services;

use App\Application\Ports\InvoicePdfInterface;
use App\Domain\Orders\Order;
use Dompdf\Dompdf;
use Dompdf\Options;

class DompdfInvoicePdf implements InvoicePdfInterface
{
    public function generate(Order $order, array $items, array $settings): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildHtml($order, $items, $settings));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function buildHtml(Order $order, array $items, array $settings): string
    {
        $currency   = $order->currency;
        $vatRate    = (float)($settings['shop_vat_rate']     ?? 15);
        $vatEnabled = ($settings['shop_vat_enabled'] ?? '0') === '1';

        $fmt = fn(int $cents) => (new \NumberFormatter('en-ZA', \NumberFormatter::CURRENCY))
            ->formatCurrency($cents / 100, $currency);

        $invoiceDate = $order->createdAt->format('d F Y');
        $orderRef    = '#' . $order->id;

        $itemRows = '';
        foreach ($items as $item) {
            $name = htmlspecialchars($item['product_name']);
            if (!empty($item['variant_name'])) {
                $name .= ' <span style="color:#6b7280;font-size:11px;">(' . htmlspecialchars($item['variant_name']) . ')</span>';
            }
            $itemRows .= sprintf(
                '<tr><td>%s</td><td class="center">%d</td><td class="right">%s</td><td class="right">%s</td></tr>',
                $name,
                $item['qty'],
                $fmt((int)$item['unit_price_cents']),
                $fmt((int)$item['line_total_cents'])
            );
        }

        $totals = '<tr class="subtotal-row"><td colspan="3" class="right">Subtotal</td><td class="right">' . $fmt($order->subtotal->amountCents) . '</td></tr>';
        if ($vatEnabled && $order->vat->amountCents > 0) {
            $totals .= '<tr class="subtotal-row"><td colspan="3" class="right">VAT (' . $vatRate . '%)</td><td class="right">' . $fmt($order->vat->amountCents) . '</td></tr>';
        }
        if ($order->shipping->amountCents > 0) {
            $totals .= '<tr class="subtotal-row"><td colspan="3" class="right">Shipping</td><td class="right">' . $fmt($order->shipping->amountCents) . '</td></tr>';
        } else {
            $totals .= '<tr class="subtotal-row"><td colspan="3" class="right">Shipping</td><td class="right">Free</td></tr>';
        }
        $totals .= '<tr class="total-row"><td colspan="3" class="right">Total</td><td class="right amount">' . $fmt($order->total->amountCents) . '</td></tr>';

        $addr = $order->address;
        $addressParts = [htmlspecialchars($addr->line1)];
        if ($addr->line2 !== '') $addressParts[] = htmlspecialchars($addr->line2);
        $addressParts[] = htmlspecialchars($addr->city);
        if ($addr->province !== '') $addressParts[] = htmlspecialchars($addr->province);
        $addressParts[] = htmlspecialchars($addr->postalCode);
        $address = implode(', ', $addressParts);

        $firstName = htmlspecialchars($order->firstName);
        $lastName  = htmlspecialchars($order->lastName);
        $email     = htmlspecialchars($order->email);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:12px; color:#1f2937; line-height:1.5; background:#ffffff; }
    .wrap { padding:40px; }
    .top-bar { background:#d10000; height:4px; margin-bottom:36px; }
    .header-table { width:100%; margin-bottom:36px; }
    .brand-name { font-size:22px; font-weight:700; color:#0a0a0a; text-transform:uppercase; letter-spacing:-0.5px; }
    .brand-name span { color:#d10000; }
    .invoice-label { font-size:28px; font-weight:700; color:#0a0a0a; text-transform:uppercase; letter-spacing:2px; text-align:right; }
    .invoice-meta { font-size:11px; color:#6b7280; text-align:right; margin-top:6px; line-height:1.7; }
    .bill-to-label { font-size:10px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#aaaaaa; margin-bottom:6px; }
    .bill-to-name { font-size:13px; font-weight:700; color:#0a0a0a; }
    .bill-to-detail { font-size:11px; color:#6b7280; line-height:1.7; }
    .divider { border:none; border-top:1px solid #e5e7eb; margin:28px 0; }
    table.items { width:100%; border-collapse:collapse; }
    table.items th { padding:8px 10px; background:#f9fafb; border-bottom:2px solid #0a0a0a; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#6b7280; }
    table.items td { padding:10px 10px; border-bottom:1px solid #f3f4f6; font-size:12px; color:#1f2937; vertical-align:top; }
    .subtotal-row td { padding:5px 10px; border:none; font-size:11px; color:#6b7280; }
    .total-row td { padding:12px 10px; border-top:2px solid #d10000; font-size:14px; font-weight:700; color:#0a0a0a; }
    .total-row .amount { color:#d10000; font-size:16px; }
    .center { text-align:center; }
    .right  { text-align:right; }
    .footer { margin-top:48px; padding-top:16px; border-top:1px solid #e5e7eb; font-size:10px; color:#9ca3af; text-align:center; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top-bar"></div>
    <table class="header-table" cellpadding="0" cellspacing="0">
      <tr>
        <td style="width:50%;vertical-align:top;">
          <div class="brand-name">SKATER<span> NATION</span></div>
          <div style="margin-top:20px;">
            <div class="bill-to-label">Bill To</div>
            <div class="bill-to-name">{$firstName} {$lastName}</div>
            <div class="bill-to-detail">{$email}<br>{$address}</div>
          </div>
        </td>
        <td style="width:50%;vertical-align:top;">
          <div class="invoice-label">Invoice</div>
          <div class="invoice-meta">
            <strong>Ref:</strong> {$orderRef}<br>
            <strong>Date:</strong> {$invoiceDate}
          </div>
        </td>
      </tr>
    </table>
    <hr class="divider">
    <table class="items">
      <thead>
        <tr>
          <th style="text-align:left;">Description</th>
          <th class="center" style="width:60px;">Qty</th>
          <th class="right" style="width:100px;">Unit Price</th>
          <th class="right" style="width:110px;">Line Total</th>
        </tr>
      </thead>
      <tbody>
        {$itemRows}
      </tbody>
      <tfoot>
        {$totals}
      </tfoot>
    </table>
    <div class="footer">
      Thank you for supporting the culture. This document serves as your official invoice.<br>
      snonline.co.za &mdash; Born in Secunda, Mpumalanga.
    </div>
  </div>
</body>
</html>
HTML;
    }
}
