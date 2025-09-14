<?php
/**
 * Plugin Name: FunnelWheel Country Based Pricing
 * Plugin URI:  https://github.com/funnelwheel
 * Description: Apply country-specific pricing adjustments in WooCommerce using geolocation, billing address, or store base.
 * Version:     1.0
 * Author:      FunnelWheel
 * Author URI:  https://profiles.wordpress.org/funnelwheel/
 * License:     GPLv3 or later
 * Requires Plugins: woocommerce
 * Text Domain: funnelwheel-country-based-pricing
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load WooCommerce settings tab
add_filter( 'woocommerce_get_settings_pages', 'funnelwheel_add_settings_tab' );
function funnelwheel_add_settings_tab( $settings ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-fwcp-settings-tab.php';
    $settings[] = new FWCP_Settings_Tab();
    return $settings;
}

class FunnelWheel_Country_Based_Pricing {

    public function __construct() {
        add_filter( 'woocommerce_product_get_price', [ $this, 'adjust_price_based_on_country' ], 999, 2 );
        add_filter( 'woocommerce_product_get_regular_price', [ $this, 'adjust_price_based_on_country' ], 999, 2 );
        add_action( 'init', [ $this, 'ensure_geolocation_enabled' ] );
    }

    /**
     * Adjust product price based on user's country and discount settings.
     */
    public function adjust_price_based_on_country( $price, $product ) {
        $country   = $this->get_user_country();
        $discounts = get_option( 'fwcp_country_discounts', [] );

        foreach ( $discounts as $rule ) {
            if ( isset( $rule['country'], $rule['type'], $rule['amount'] ) && $rule['country'] === $country ) {
                $amount = floatval( $rule['amount'] );

                if ( $rule['type'] === 'percent' ) {
                    $price -= ( $price * $amount / 100 );
                } else {
                    $price -= $amount;
                }

                $price = max( 0, $price );
                break; // Only apply the first matching rule
            }
        }

        return $price;
    }

    /**
     * Get user's country using geolocation, billing, or store base.
     */
    private function get_user_country() {
        $default_location = get_option( 'woocommerce_default_customer_address' );

        // 1. Geolocation (if enabled)
        if ( in_array( $default_location, [ 'geolocation', 'geolocation_ajax' ], true ) ) {
            if ( class_exists( 'WC_Geolocation' ) ) {
                $location = WC_Geolocation::geolocate_ip();
                if ( ! empty( $location['country'] ) ) {
                    return $location['country'];
                }
            }
        }

        // 2. Billing country (logged-in user)
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $billing_country = get_user_meta( $user_id, 'billing_country', true );
            if ( ! empty( $billing_country ) ) {
                return $billing_country;
            }
        }

        // 3. Billing country (guest in session)
        if ( WC()->customer ) {
            $billing_country = WC()->customer->get_billing_country();
            if ( ! empty( $billing_country ) ) {
                return $billing_country;
            }
        }

        // 4. Store base country (fallback)
        $base_country = WC()->countries->get_base_country();
        return ! empty( $base_country ) ? $base_country : '';
    }

    /**
     * Ensure WooCommerce geolocation is enabled for pricing logic to work.
     */
    public function ensure_geolocation_enabled() {
        $option = get_option( 'woocommerce_default_customer_address' );
        if ( $option !== 'geolocation_ajax' ) {
            update_option( 'woocommerce_default_customer_address', 'geolocation_ajax' );
        }
    }
}

// Check if WooCommerce is active
add_action( 'plugins_loaded', 'funnelwheel_check_woocommerce_dependency' );

function funnelwheel_check_woocommerce_dependency() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'funnelwheel_woocommerce_missing_notice' );
        return;
    }

    // Initialize the plugin only if WooCommerce is active
    new FunnelWheel_Country_Based_Pricing();
}

function funnelwheel_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong><?php esc_html_e( 'FunnelWheel Country Based Pricing requires WooCommerce to be installed and active.', 'funnelwheel-country-based-pricing' ); ?></strong></p>
    </div>
    <?php
}