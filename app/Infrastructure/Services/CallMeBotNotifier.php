<?php

namespace App\Infrastructure\Services;

use App\Application\Ports\WhatsAppNotifierInterface;
use App\Domain\Orders\Order;

class CallMeBotNotifier implements WhatsAppNotifierInterface
{
    public function notifyNewOrder(Order $order, array $items, array $settings): void
    {
        $phone  = $settings['whatsapp_notify_phone']  ?? '';
        $apiKey = $settings['whatsapp_notify_apikey'] ?? '';

        if ($phone === '' || $apiKey === '') {
            log_message('info', 'CallMeBotNotifier: whatsapp_notify_phone or apikey not set — skipping');
            return;
        }

        $currency = $order->currency;
        $fmt      = fn(int $cents) => (new \NumberFormatter('en-ZA', \NumberFormatter::CURRENCY))
            ->formatCurrency($cents / 100, $currency);

        $itemLines = array_map(fn($i) => sprintf(
            '%dx %s%s',
            $i['qty'],
            $i['product_name'],
            $i['variant_name'] ? " ({$i['variant_name']})" : ''
        ), $items);

        $message = implode("\n", [
            '🛹 *New Order #' . $order->id . '*',
            $order->firstName . ' ' . $order->lastName,
            implode("\n", $itemLines),
            'Total: ' . $fmt($order->total->amountCents),
            'Gateway: ' . ucfirst($order->gateway ?? 'unknown'),
        ]);

        $url = 'https://api.callmebot.com/whatsapp.php?' . http_build_query([
            'phone'  => $phone,
            'text'   => $message,
            'apikey' => $apiKey,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($curlErr !== '') {
            log_message('error', "CallMeBotNotifier cURL error: {$curlErr}");
        } elseif ($status >= 400) {
            log_message('error', "CallMeBotNotifier failed [{$status}]: {$response}");
        } else {
            log_message('info', "CallMeBotNotifier: order #{$order->id} notification sent");
        }
    }
}
