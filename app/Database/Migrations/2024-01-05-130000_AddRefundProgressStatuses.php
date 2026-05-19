<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRefundProgressStatuses extends Migration
{
    public function up(): void
    {
        $this->db->query("
            ALTER TABLE shop_orders
            MODIFY status ENUM(
                'pending','paid','processing','shipped','delivered',
                'cancelled','refunded','partially_refunded',
                'refund_requested','refund_in_progress','refund_rejected'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        $this->db->query("
            ALTER TABLE shop_orders
            MODIFY status ENUM(
                'pending','paid','processing','shipped','delivered',
                'cancelled','refunded','partially_refunded','refund_requested'
            ) NOT NULL DEFAULT 'pending'
        ");
    }
}
