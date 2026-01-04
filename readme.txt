=== FunnelWheel Country Based Pricing ===
Contributors: funnelwheel, upnrunn, kishores
Donate link: https://github.com/funnelwheel
Tags: woocommerce, pricing, discounts, country based, geolocation
Requires at least: 5.6
Tested up to: 6.9
Requires PHP: 7.2
Requires Plugins: woocommerce
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Apply automatic, country-specific pricing discounts in WooCommerce using geolocation, billing address, or store base country.

== Description ==

**FunnelWheel Country Based Pricing** allows you to automatically apply product price discounts based on the customer's country — perfect for region-specific pricing strategies or promotions.

This plugin leverages WooCommerce’s geolocation system to detect a visitor’s country and adjusts prices dynamically, without needing separate stores or complicated setups.

**Key Features:**

- Set flat or percentage-based discounts per country.
- Detect country via:
  - Geolocation (IP address)
  - Logged-in user's billing address
  - Guest's billing country (via session)
  - Store base country (fallback)
- Easy-to-use admin interface in WooCommerce settings.
- Works with product catalog and individual product views.
- Lightweight, no external dependencies.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory or install via the WordPress Plugin Installer.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **WooCommerce > Settings > Country Pricing** tab.
4. Add discount rules for different countries.
5. Save your settings.
6. Make sure WooCommerce geolocation is enabled. The plugin will auto-enable it if needed.

== Frequently Asked Questions ==

= Does this plugin support currency switching? =

No, this plugin only adjusts the **price** (via discount), not the currency. For currency switching, pair this with a multi-currency plugin.

= Will it work with variable products? =

Yes. Discounts apply to all WooCommerce product types via the standard price filters.

= Does it support tax-inclusive pricing? =

It adjusts the base product price before tax. Your tax settings will still apply afterward.

= How does geolocation work? =

It uses WooCommerce's built-in geolocation (via MaxMind GeoLite2). Make sure it is configured correctly under **WooCommerce > Settings > General > Default Customer Location**.

== Screenshots ==

1. Admin interface to configure country-based discounts.
2. Example: 10% off for US visitors.
3. Example: $5 discount for Canadian customers.

== Changelog ==

= 1.0 =
* Initial release
* Add country-based discounting
* Integrate WooCommerce geolocation
* Admin settings panel

== Upgrade Notice ==

= 1.0 =
First release of the plugin. Apply discounts based on customer country.

== License ==

This plugin is licensed under the GPLv3 or later. See LICENSE file or https://www.gnu.org/licenses/gpl-3.0.html for more details.
