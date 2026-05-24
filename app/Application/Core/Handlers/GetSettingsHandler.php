<?php

namespace App\Application\Core\Handlers;

use App\Application\Core\Queries\GetSettingsQuery;
use App\Domain\Core\SettingsRepositoryInterface;

final class GetSettingsHandler
{
    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
    ) {}

    /** @return array<string,string> */
    public function handle(GetSettingsQuery $query): array
    {
        if (!empty($query->keys)) {
            return $this->settings->getMany($query->keys);
        }

        // Return all known settings keys (public-safe set used by content endpoints)
        return $this->settings->getMany([
            'site_name', 'site_tagline', 'contact_email', 'contact_phone',
            'contact_address', 'social_facebook', 'social_instagram',
            'social_linkedin', 'social_twitter', 'accreditations',
            'shop_enabled', 'shop_mode', 'shop_featured_product_slug',
            'shop_currency', 'shop_vat_enabled', 'shop_vat_rate',
            'shop_shipping_rate', 'shop_free_shipping_from',
            'shop_payfast_enabled', 'shop_ozow_enabled', 'shop_wishlist_enabled',
            'shop_guest_checkout',
            // Appearance
            'hero_variant', 'footer_variant', 'cta_headline',
            // Contact (frontend keys)
            'phone_mobile', 'phone_office', 'email', 'tagline',
            'whatsapp_number', 'whatsapp_display',
            'address_physical', 'address_postal',
            // Navigation
            'nav_items', 'nav_cta_label', 'nav_cta_href', 'nav_align',
            // Newsletter
            'newsletters_enabled',
            // Theme
            'active_theme',
            // Transitions
            'page_transition', 'page_transition_speed',
            // Home hero
            'site_hero_image',
            // Shop landing
            'shop_hero_image', 'shop_hero_image_2', 'shop_hero_image_3',
            'shop_hero_headline', 'shop_hero_tagline',
            'shop_specials_title', 'shop_specials_category',
            'shop_all_products_banner',
            // Contact page
            'contact_map_embed_url',
            'contact_hero_eyebrow', 'contact_hero_title', 'contact_hero_body', 'contact_hero_image',
            'contact_form_heading', 'contact_form_subtext', 'contact_service_options',
        ]);
    }
}
