<?php

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Application\Core\Commands\UpdateSettingsCommand;
use App\Application\Core\Queries\GetSettingsQuery;
use App\Infrastructure\Http\Controllers\BaseController;

class Settings extends BaseController
{
    private const ADMIN_SETTINGS_KEYS = [
        'site_name', 'site_tagline', 'contact_email', 'contact_notification_email', 'contact_phone',
        'contact_address', 'social_facebook', 'social_instagram',
        'social_linkedin', 'social_twitter', 'accreditations',
        'shop_enabled', 'shop_mode', 'shop_featured_product_slug',
        'shop_currency', 'shop_vat_enabled', 'shop_vat_rate',
        'shop_shipping_rate', 'shop_free_shipping_from',
        'shop_guest_checkout',
        'shop_notification_email', 'shop_low_stock_alert_email',
        'shop_payfast_enabled', 'shop_payfast_merchant_id',
        'shop_payfast_merchant_key', 'shop_payfast_passphrase',
        'shop_ozow_enabled', 'shop_ozow_site_code',
        'shop_ozow_private_key', 'shop_ozow_api_key',
        'shop_wishlist_enabled',
        'whatsapp_notify_phone', 'whatsapp_notify_apikey',
        'shop_all_products_banner',
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
        // Contact page
        'contact_map_embed_url',
        'contact_hero_eyebrow', 'contact_hero_title', 'contact_hero_body', 'contact_hero_image',
        'contact_form_heading', 'contact_form_subtext', 'contact_service_options',
    ];

    /** Keys that are write-only — returned as '••••••••' when set, empty string when not set. */
    private const SENSITIVE_KEYS = [
        'shop_payfast_merchant_key',
        'shop_payfast_passphrase',
        'shop_ozow_private_key',
        'shop_ozow_api_key',
        'whatsapp_notify_apikey',
    ];

    public function index(): \CodeIgniter\HTTP\ResponseInterface
    {
        $settings = service('getSettingsHandler')->handle(
            new GetSettingsQuery(keys: self::ADMIN_SETTINGS_KEYS)
        );

        // Mask sensitive values — never send secrets over the wire
        foreach (self::SENSITIVE_KEYS as $key) {
            if (!empty($settings[$key])) {
                $settings[$key] = '••••••••';
            }
        }

        return $this->ok($settings);
    }

    public function update(): \CodeIgniter\HTTP\ResponseInterface
    {
        $body = $this->jsonBody();

        if (empty($body)) {
            return $this->error('No data provided.', 400);
        }

        // Strip any sensitive key whose value is still the masked placeholder —
        // this means the admin did not change it and we must preserve the real value.
        foreach (self::SENSITIVE_KEYS as $key) {
            if (isset($body[$key]) && $body[$key] === '••••••••') {
                unset($body[$key]);
            }
        }

        service('updateSettingsHandler')->handle(new UpdateSettingsCommand($body));

        return $this->ok();
    }
}
