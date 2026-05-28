<?php

namespace App\Application\Ports;

use App\Domain\Orders\Order;

interface WhatsAppNotifierInterface
{
    /**
     * Send a new order notification to the configured WhatsApp group.
     *
     * @param array[] $items Raw item rows (product_name, qty, line_total_cents)
     */
    public function notifyNewOrder(Order $order, array $items, array $settings): void;
}
