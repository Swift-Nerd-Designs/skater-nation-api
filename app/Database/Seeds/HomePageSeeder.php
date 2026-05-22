<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the home page with SN widget blocks.
 *
 * Run:  php spark db:seed HomePageSeeder
 */
class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        $blocks = [
            ['id' => 'sn_hero_1',        'type' => 'sn_hero',         'data' => new \stdClass()],
            ['id' => 'sn_shop_1',        'type' => 'sn_shop_preview', 'data' => new \stdClass()],
            ['id' => 'sn_heritage_1',    'type' => 'sn_heritage',     'data' => new \stdClass()],
            ['id' => 'sn_ambassadors_1', 'type' => 'sn_ambassadors',  'data' => new \stdClass()],
            ['id' => 'sn_about_1',       'type' => 'sn_about',        'data' => new \stdClass()],
        ];

        $pageData = json_encode([
            'seoTitle'       => 'Skater Nation — Born in Secunda. Built for the streets.',
            'seoDescription' => 'Skater Nation is a South African skateboarding movement. Community, culture, and gear from Secunda, Mpumalanga. Est. 2016.',
            'content'        => ['blocks' => $blocks],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $existing = $this->db->table('pages')->where('slug', 'home')->get()->getRowArray();

        if ($existing) {
            $this->db->table('pages')->where('slug', 'home')->update([
                'data'       => $pageData,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            echo "HomePageSeeder: home page updated with " . count($blocks) . " SN blocks.\n";
        } else {
            $this->db->table('pages')->insert([
                'slug'       => 'home',
                'data'       => $pageData,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            echo "HomePageSeeder: home page created with " . count($blocks) . " SN blocks.\n";
        }
    }
}
