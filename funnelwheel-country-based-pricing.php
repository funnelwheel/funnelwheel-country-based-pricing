<?php
/**
 * Plugin Name: FunnelWheel Country Based Pricing
 * Plugin URI:  https://github.com/funnelwheel
 * Description: Apply country-specific pricing adjustments in WooCommerce using geolocation, billing address, or store base.
 * Version:     1.0.0
 * Author:      FunnelWheel
 * Author URI:  https://profiles.wordpress.org/funnelwheel/
 * License:     GPLv3 or later
 * Requires Plugins: woocommerce
 * Text Domain: funnelwheel-country-based-pricing
 * Domain Path: /languages/
 */

namespace FunnelWheel\CountryBasedPricing;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use FunnelWheel\CountryBasedPricing\FUNNCOBA_Settings_Tab;

add_action( 'plugins_loaded', __NAMESPACE__ . '\\funncoba_init' );

function funncoba_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', __NAMESPACE__ . '\\funncoba_missing_wc_notice' );
        return;
    }

    add_filter( 'woocommerce_get_settings_pages', __NAMESPACE__ . '\\funncoba_add_settings_tab' );

    new FUNNCOBA_Main();
}

function funncoba_missing_wc_notice() {
    echo '<div class="notice notice-error"><p><strong>' .
        esc_html__( 'FunnelWheel Country Based Pricing requires WooCommerce to be installed and active.', 'funnelwheel-country-based-pricing' ) .
        '</strong></p></div>';
}

function funncoba_add_settings_tab( $settings ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-funncoba-settings-tab.php';
    $settings[] = new FUNNCOBA_Settings_Tab();
    return $settings;
}

/**
 * ------------------------------
 * MAIN CLASS
 * ------------------------------
 */
class FUNNCOBA_Main {

    public function __construct() {
        add_filter( 'woocommerce_product_get_regular_price', [ $this, 'get_regular_price' ], 999, 2 );
        add_filter( 'woocommerce_product_get_sale_price', [ $this, 'get_sale_price' ], 999, 2 );
        add_filter( 'woocommerce_product_get_price', [ $this, 'get_final_price' ], 999, 2 );
    }

    public function get_regular_price( $price, $product ) {
        $currency = funncoba_get_currency_for_user();
        $custom = get_post_meta( $product->get_id(), "_funncoba_regular_price_{$currency}", true );
        return $custom !== '' ? (float) $custom : (float) $price;
    }

    public function get_sale_price( $price, $product ) {
        $currency = funncoba_get_currency_for_user();
        $custom = get_post_meta( $product->get_id(), "_funncoba_sale_price_{$currency}", true );
        return $custom !== '' ? (float) $custom : null;
    }

    public function get_final_price( $price, $product ) {
        $regular = $this->get_regular_price( $product->get_regular_price(), $product );
        $sale    = $this->get_sale_price( $product->get_sale_price(), $product );

        $final_price = ($sale !== null && $sale < $regular) ? $sale : $regular;

        // Apply country-specific discount
        $country = funncoba_get_user_country();
        $discounts = get_option( 'funncoba_country_discounts', [] );

        foreach ( $discounts as $rule ) {
            if ( isset( $rule['country'], $rule['type'], $rule['amount'] ) && $rule['country'] === $country ) {
                $amount = floatval( $rule['amount'] );
                if ( $rule['type'] === 'percent' ) {
                    $final_price -= ($final_price * $amount / 100);
                } else {
                    $final_price -= $amount;
                }
                $final_price = max( 0, $final_price );
                break;
            }
        }

        return $final_price;
    }

}


register_activation_hook( __FILE__, __NAMESPACE__ . '\\funncoba_set_default_geolocation' );

function funncoba_set_default_geolocation() {
    $current = get_option( 'woocommerce_default_customer_address', '' );
    if ( empty( $current ) ) {
        // Only set default if not already set
        update_option( 'woocommerce_default_customer_address', 'geolocation_ajax' );
    }
}


/**
 * ------------------------------
 * HELPER FUNCTIONS
 * ------------------------------
 */
function funncoba_get_user_country() {
    // Manual session selection first
    if ( function_exists( 'WC' ) && WC()->session ) {
        $manual = WC()->session->get( 'funncoba_selected_country' );
        if ( $manual ) {
            return $manual;
        }
    }

    if ( ! empty( $_COOKIE['funncoba_selected_country'] ) ) {
        return sanitize_text_field( $_COOKIE['funncoba_selected_country'] );
    }

    // Default store base as fallback
    return function_exists( 'WC' ) ? WC()->countries->get_base_country() : 'US';
}


/**
 * Get currency based on country.
 */
function funncoba_get_currency_for_country( $country ) {
    // Admin-defined mapping stored in option
    $custom_map = get_option( 'funncoba_country_currency_map', [] );

    if ( isset( $custom_map[ $country ] ) ) {
        return $custom_map[ $country ];
    }

    // Fallback map (only essential currencies)
    $fallback = [
        'US' => 'USD',
        'CA' => 'CAD',
        'GB' => 'GBP',
        'FR' => 'EUR',
        'DE' => 'EUR',
        'IN' => 'INR',
        'AU' => 'AUD',
        'NZ' => 'NZD',
        'JP' => 'JPY',
        'CN' => 'CNY',
        'BR' => 'BRL',
        'ZA' => 'ZAR',
    ];

    return $fallback[ $country ] ?? get_woocommerce_currency();
}


/**
 * Get user's applicable currency.
 */
function funncoba_get_currency_for_user() {
    $country = funncoba_get_user_country();
    return funncoba_get_currency_for_country( $country );
}

/**
 * Get only enabled/supported currencies.
 */
function funncoba_supported_countries() {
    $enabled = get_option( 'funncoba_enabled_countries', [] );

    if ( empty( $enabled ) ) {
        $enabled = [ 'US', 'IN', 'GB', 'DE', 'FR', 'NL', 'ES', 'IT', 'AF' ]; // Default list
    }

    $wc_countries = \WC()->countries->get_countries();
    $countries = [];

    foreach ( $enabled as $code ) {
        $countries[ $code ] = $wc_countries[ $code ] ?? $code;
    }

    return $countries;
}


/**
 * ------------------------------
 * COUNTRY-SPECIFIC PRICES SECTION
 * ------------------------------
 */
function funncoba_get_currency_symbol_for_country( $country ) {
    $custom_map = get_option( 'funncoba_country_currency_symbol_map', [] );

    if ( isset( $custom_map[ $country ] ) ) {
        return $custom_map[ $country ];
    }

    $fallback = [
        'US' => '$',
        'IN' => '₹',
        'AF' => '؋',
        'GB' => '£',
        'FR' => '€',
        'DE' => '€',
        'AU' => 'A$',
        'NZ' => 'NZ$',
        'JP' => '¥',
        'CN' => '¥',
        'CA' => 'C$',
        'BR' => 'R$',
        'ZA' => 'R',
    ];

    return $fallback[ $country ] ?? '';
}


add_action( 'woocommerce_product_options_pricing', __NAMESPACE__ . '\\funncoba_add_country_specific_prices' );
function funncoba_add_country_specific_prices() {
    $countries = funncoba_supported_countries();

    if ( empty( $countries ) ) {
        return; // nothing to show
    }

    echo '<div class="options_group funncoba_country_specific_prices">';
    echo '<h2>' . esc_html__( 'Country-specific prices', 'funnelwheel-country-based-pricing' ) . '</h2>';

    foreach ( $countries as $code => $label ) {
        $currency_code   = funncoba_get_currency_for_country( $code );
        $currency_symbol = funncoba_get_currency_symbol_for_country( $code );

        // Skip countries with no currency symbol
        if ( empty( $currency_symbol ) ) {
            continue;
        }

        // Only show fields if needed (optional: you can remove this check if you always want to show)
        $regular_price = get_post_meta( get_the_ID(), "_funncoba_regular_price_{$currency_code}", true );
        $sale_price    = get_post_meta( get_the_ID(), "_funncoba_sale_price_{$currency_code}", true );

        // Regular Price
        echo '<p class="form-field">';
        echo '<label for="_funncoba_regular_price_' . esc_attr( $currency_code ) . '">' .
             sprintf( __( 'Regular price (%s)', 'funnelwheel-country-based-pricing' ), esc_html( $currency_symbol ) ) .
             '</label>';
        echo '<input type="text" class="short" name="_funncoba_regular_price_' . esc_attr( $currency_code ) . '" id="_funncoba_regular_price_' . esc_attr( $currency_code ) . '" value="' . esc_attr( $regular_price ) . '" />';
        echo '</p>';

        // Sale Price
        echo '<p class="form-field">';
        echo '<label for="_funncoba_sale_price_' . esc_attr( $currency_code ) . '">' .
             sprintf( __( 'Sale price (%s)', 'funnelwheel-country-based-pricing' ), esc_html( $currency_symbol ) ) .
             '</label>';
        echo '<input type="text" class="short" name="_funncoba_sale_price_' . esc_attr( $currency_code ) . '" id="_funncoba_sale_price_' . esc_attr( $currency_code ) . '" value="' . esc_attr( $sale_price ) . '" />';
        echo '</p>';
    }

    echo '</div>';
}


add_action( 'woocommerce_admin_process_product_object', __NAMESPACE__ . '\\funncoba_save_country_specific_prices' );
function funncoba_save_country_specific_prices( $product ) {
    foreach ( funncoba_supported_countries() as $code => $label ) {
        $currency = funncoba_get_currency_for_country( $code );

        foreach ( [ 'regular', 'sale' ] as $type ) {
            $key = "_funncoba_{$type}_price_{$currency}";
            if ( isset( $_POST[ $key ] ) ) {
                $product->update_meta_data( $key, wc_clean( wp_unslash( $_POST[ $key ] ) ) );
            }
        }
    }
}


/**
 * ------------------------------
 * DYNAMIC CURRENCY FILTER
 * ------------------------------
 */
add_filter( 'woocommerce_currency', __NAMESPACE__ . '\\funncoba_dynamic_currency' );
function funncoba_dynamic_currency( $currency ) {
    static $running = false;
    if ( $running ) {
        return $currency;
    }
    $running = true;

    if ( is_admin() ) {
        $running = false;
        return $currency;
    }

    $new_currency = funncoba_get_currency_for_user();

    $running = false;
    return $new_currency ?: $currency;
}



/**
 * ------------------------------
 * FOOTER COUNTRY SELECTOR (Same line as copyright)
 * ------------------------------
 */
add_action( 'wp_footer', __NAMESPACE__ . '\\funncoba_footer_country_selector' );
function funncoba_footer_country_selector() {
    if ( is_admin() ) return;

    $countries = funncoba_supported_countries();
    if ( empty( $countries ) ) return;

    $current_country = funncoba_get_user_country();
    ?>
    <style>
    .funncoba-footer-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        border-top: 1px solid #eaeaea;
        padding: 12px 20px;
        font-size: 14px;
        background: #fafafa;
        color: #555;
    }
    .funncoba-country-selector {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .funncoba-country-selector select {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background: transparent url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><polygon points="0,0 5,5 10,0" fill="%23555"/></svg>') no-repeat right 6px center;
        background-size: 10px;
        padding: 2px 20px 2px 6px;
        border: none;
        font-size: 14px;
        color: inherit;
        cursor: pointer;
    }
    .funncoba-country-selector select:hover {
        background-color: #f0f0f0;
        opacity: 1;
    }
    @media (max-width: 600px) {
        .funncoba-footer-bar {
            flex-direction: column;
            text-align: center;
        }
    }
    </style>

    <div class="funncoba-footer-bar">
        <div class="funncoba-country-selector">
            <form method="post" id="funncoba_country_form_footer" action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>" style="margin:0;">
                <select name="funncoba_country" id="funncoba_country_footer">
                    <?php foreach ( $countries as $code => $label ) :
                        $curr = funncoba_get_currency_for_country( $code );
                        $flag = funncoba_get_country_flag( $code );
                    ?>
                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current_country, $code ); ?>>
                            <?php echo esc_html( "$flag  $label ($curr)" ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php wp_nonce_field( 'funncoba_select_country', 'funncoba_nonce' ); ?>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('funncoba_country_footer');
        const form = document.getElementById('funncoba_country_form_footer');
        if (select && form) {
            select.addEventListener('change', function() {
                form.submit();
            });
        }
    });
    </script>
    <?php
}

/**
 * Map country code to emoji flag
 */
function funncoba_get_country_flag( $country_code ) {
    if ( empty( $country_code ) ) return '';
    $code = strtoupper( $country_code );
    $first  = mb_ord( $code[0] ) - 65 + 0x1F1E6;
    $second = mb_ord( $code[1] ) - 65 + 0x1F1E6;
    return mb_chr( $first ) . mb_chr( $second );
}


/**
 * Handle user country selection
 */
add_action( 'init', __NAMESPACE__ . '\\funncoba_handle_country_selection' );
function funncoba_handle_country_selection() {

    // Handle POST only when user selects a country
    if ( isset( $_POST['funncoba_country'], $_POST['funncoba_nonce'] ) 
         && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['funncoba_nonce'] ) ), 'funncoba_select_country' ) ) {

        // Sanitize the country code
        $country = sanitize_text_field( wp_unslash( $_POST['funncoba_country'] ) );

        // Store country in WooCommerce session (if available)
        if ( function_exists( 'WC' ) && WC()->session ) {
            WC()->session->set( 'funncoba_selected_country', $country );
        }

        // Store country in cookie for persistence
        setcookie(
            'funncoba_selected_country',
            $country,
            time() + 7 * DAY_IN_SECONDS,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );

        // Determine where to redirect
        $referer = wp_get_referer();
        $current_url = ! empty( $referer )
            ? $referer
            : ( ! empty( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : home_url() );

        // Remove WooCommerce cache-busting query vars like ?v=xxxx
        $redirect_url = remove_query_arg( [ 'v' ], $current_url );

        // Redirect safely back to same page
        wp_safe_redirect( $redirect_url );
        exit;
    }
}