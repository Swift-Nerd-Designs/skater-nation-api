<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBannerImageToCategories extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('shop_categories', [
            'banner_image' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
                'after'      => 'name',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('shop_categories', 'banner_image');
    }
}
