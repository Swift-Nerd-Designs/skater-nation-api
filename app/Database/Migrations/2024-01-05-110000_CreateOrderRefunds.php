<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrderRefunds extends Migration
{
    public function up(): void
    {
        // FK constraints omitted — forge-created tables (shop_orders, shop_order_items)
        // use the server's default collation which may differ from utf8mb4_unicode_ci,
        // causing errno: 150 on Afrihost shared hosting. App enforces referential integrity.
        $this->db->query("
            CREATE TABLE shop_order_refunds (
                id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                order_id     INT UNSIGNED NOT NULL,
                amount_cents INT UNSIGNED NOT NULL,
                note         VARCHAR(500) NULL,
                created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_order (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->query("
            CREATE TABLE shop_order_refund_items (
                id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
                refund_id     INT UNSIGNED NOT NULL,
                order_item_id INT UNSIGNED NOT NULL,
                qty           INT UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                KEY idx_refund (refund_id),
                KEY idx_item (order_item_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS shop_order_refund_items');
        $this->db->query('DROP TABLE IF EXISTS shop_order_refunds');
    }
}
