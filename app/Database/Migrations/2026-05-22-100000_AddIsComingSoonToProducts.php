<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsComingSoonToProducts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('shop_products', [
            'is_coming_soon' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
                'null'       => false,
                'after'      => 'active',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('shop_products', 'is_coming_soon');
    }
}
