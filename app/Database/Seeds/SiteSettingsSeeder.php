<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds SN-specific site settings including placeholder hero images.
 *
 * Run:  php spark db:seed SiteSettingsSeeder
 *
 * Safe to re-run — uses upsert.
 */
class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Site identity
            'site_name'    => 'Skater Nation',
            'email'        => 'skaternation2016@gmail.com',

            // Home hero — dark street/skate action placeholder
            'site_hero_image' => 'https://images.unsplash.com/photo-1547447134-cd3f5c716030?auto=format&fit=crop&w=1920&q=80',

            // Shop hero carousel — 3 skate-themed placeholders
            'shop_hero_image'     => 'https://images.unsplash.com/photo-1564415637254-92c66292cd64?auto=format&fit=crop&w=1920&q=80',
            'shop_hero_image_2'   => 'https://images.unsplash.com/photo-1547447134-cd3f5c716030?auto=format&fit=crop&w=1920&q=80',
            'shop_hero_image_3'   => 'https://images.unsplash.com/photo-1580674684081-7617fbf3d745?auto=format&fit=crop&w=1920&q=80',
            'shop_hero_headline'  => 'Built for the Streets',
            'shop_hero_tagline'   => 'SN-branded gear from Secunda to Joburg.',

            // Contact hero
            'contact_hero_image' => 'https://images.unsplash.com/photo-1520045892732-304bc3ac5d8e?auto=format&fit=crop&w=1920&q=80',
        ];

        foreach ($settings as $key => $value) {
            $this->db->table('settings')->upsert([
                'key'   => $key,
                'value' => $value,
            ]);
        }

        echo "SiteSettingsSeeder: " . count($settings) . " settings upserted.\n";
    }
}
