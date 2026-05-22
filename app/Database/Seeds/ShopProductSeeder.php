<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the 3 SN shop products and their categories.
 *
 * Run:  php spark db:seed ShopProductSeeder
 *
 * Safe to run multiple times — uses INSERT IGNORE on categories,
 * and skips products whose slug already exists.
 */
class ShopProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // ── Categories ────────────────────────────────────────────────

        $this->db->query("
            INSERT IGNORE INTO shop_categories (slug, name, position, created_at, updated_at)
            VALUES
                ('grip-tape', 'Grip Tape', 1, ?, ?),
                ('decks',     'Decks',     2, ?, ?)
        ", [$now, $now, $now, $now]);

        $gripId  = (int) $this->db->table('shop_categories')->where('slug', 'grip-tape')->get()->getRow()?->id;
        $deckId  = (int) $this->db->table('shop_categories')->where('slug', 'decks')->get()->getRow()?->id;

        echo "  Categories: Grip Tape (id={$gripId}), Decks (id={$deckId})\n";

        // ── Products ──────────────────────────────────────────────────

        $products = [
            [
                'slug'        => 'sn-black-grip',
                'name'        => 'SN Black Grip Sheet',
                'description' => 'SN-branded black griptape. Full-deck sheet with the iconic SN cube logo grid on the backpaper. Used by SA skaters from Secunda to Joburg.',
                'price'       => 120.00,
                'category_id' => $gripId,
                'stock_qty'   => 50,
            ],
            [
                'slug'        => 'sn-white-grip',
                'name'        => 'SN White Grip Sheet',
                'description' => 'SN-branded white griptape. Full-deck sheet with the SN cube logo grid on the backpaper. Clean colourway for those who like to stand out.',
                'price'       => 120.00,
                'category_id' => $gripId,
                'stock_qty'   => 50,
            ],
            [
                'slug'        => 'sn-maple-deck',
                'name'        => 'Size 8 Canadian Maple Deck',
                'description' => '7-ply Canadian maple skateboard deck. Size 8. Built for the streets of Secunda and beyond.',
                'price'       => 500.00,
                'category_id' => $deckId,
                'stock_qty'   => 20,
            ],
        ];

        foreach ($products as $p) {
            $exists = $this->db->table('shop_products')
                ->where('slug', $p['slug'])
                ->countAllResults();

            if ($exists > 0) {
                echo "  Product '{$p['slug']}' already exists — skipping.\n";
                continue;
            }

            $this->db->table('shop_products')->insert([
                'slug'                => $p['slug'],
                'name'                => $p['name'],
                'description'         => $p['description'],
                'price'               => $p['price'],
                'category_id'         => $p['category_id'],
                'vat_exempt'          => 0,
                'track_stock'         => 1,
                'stock_qty'           => $p['stock_qty'],
                'low_stock_threshold' => 5,
                'active'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);

            echo "  Product '{$p['name']}' created (R" . number_format($p['price'], 2) . ").\n";
        }

        echo "ShopProductSeeder done.\n";
    }
}
