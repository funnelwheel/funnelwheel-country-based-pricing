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
use FunnelWheel\CountryBasedPricing\FUNNCOBA_Settings_Tab;
use FunnelWheel\CountryBasedPricing\FUNNCOBA_Country_Helper;


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\funncoba_init' );


function funncoba_init() {

    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', __NAMESPACE__ . '\\funncoba_missing_wc_notice' );
        return;
    }

    // Load settings tab
    add_filter( 'woocommerce_get_settings_pages', __NAMESPACE__ . '\\funncoba_add_settings_tab' );

    // Core helpers (safe early)
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-funncoba-main.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-funncoba-country-helper.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-funncoba-currency-rates.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/funncoba-country-handler.php';

    // Delay batch + main logic until WooCommerce is ready
    add_action( 'woocommerce_loaded', __NAMESPACE__ . '\\funncoba_boot_after_wc', 20 );

    // Initialize main class
    new FUNNCOBA_Main();
}

function funncoba_boot_after_wc() {

    require_once plugin_dir_path( __FILE__ ) . 'includes/class-funncoba-price-batch.php';

    // Init batch hooks (Action Scheduler is now available)
    FUNNCOBA_Price_Batch::init();
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
 * Enqueue plugin assets (admin + frontend)
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\funncoba_admin_assets' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\funncoba_public_assets' );

function funncoba_admin_assets( $hook ) {
    // Load only on WooCommerce settings pages
    if ( strpos( $hook, 'woocommerce_page_wc-settings' ) === false ) {
        return;
    }

    wp_enqueue_style(
        'funncoba-admin',
        plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
        [],
        '1.0.0'
    );

    wp_enqueue_script(
        'funncoba-admin',
        plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
        [ 'jquery' ],
        '1.0.0',
        true
    );
}

function funncoba_public_assets() {
    wp_enqueue_style(
        'funncoba-public',
        plugin_dir_url( __FILE__ ) . 'assets/css/public.css',
        [],
        '1.0.0'
    );

    wp_enqueue_script(
        'funncoba-public',
        plugin_dir_url( __FILE__ ) . 'assets/js/public.js',
        [],
        '1.0.0',
        true
    );
}


register_activation_hook( __FILE__, __NAMESPACE__ . '\\funncoba_set_default_geolocation' );
add_action( 'woocommerce_loaded', __NAMESPACE__ . '\\funncoba_schedule_price_batch_once', 20 );

function funncoba_schedule_price_batch_once() {

    // Only run once
    if ( get_option( 'funncoba_batch_scheduled' ) ) {
        return;
    }

    require_once plugin_dir_path( __FILE__ ) . 'includes/class-funncoba-price-batch.php';

    if ( class_exists( '\FunnelWheel\CountryBasedPricing\FUNNCOBA_Price_Batch' ) ) {
        FUNNCOBA_Price_Batch::schedule();
        update_option( 'funncoba_batch_scheduled', 1 );
    }
}


add_action( 'init', function() {

    $next = as_next_scheduled_action(
        'funncoba_process_price_batch',
        [],
        'funncoba_price_batch'
    );

});

add_action( 'admin_init', function () {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! isset( $_GET['funncoba_run_batch'], $_GET['_wpnonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'funncoba_run_batch' ) ) {
        wp_die( esc_html__( 'Invalid nonce.', 'funnelwheel-country-based-pricing' ) );
    }

    require_once plugin_dir_path( __FILE__ ) . 'includes/class-funncoba-price-batch.php';

    FUNNCOBA_Price_Batch::process_batch( 0, time() );

    wp_die( esc_html__( 'Funncoba batch started manually.', 'funnelwheel-country-based-pricing' ) );
});



function funncoba_set_default_geolocation() {
    $current = get_option( 'woocommerce_default_customer_address', '' );
    if ( empty( $current ) ) {
        // Only set default if not already set
        update_option( 'woocommerce_default_customer_address', 'geolocation_ajax' );
    }
}

/**
 * Get user's applicable currency using helper class.
 */
function funncoba_get_currency_for_user() {
    $country = FUNNCOBA_Country_Helper::get_user_country();
    return FUNNCOBA_Country_Helper::get_currency_by_country( $country ) ?: 'USD';
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

add_action( 'woocommerce_product_options_pricing', __NAMESPACE__ . '\\funncoba_add_country_specific_prices' );
function funncoba_add_country_specific_prices() {
    $countries = funncoba_supported_countries();

    if ( empty( $countries ) ) {
        return; // nothing to show
    }

    $base_currency = get_option( 'woocommerce_currency' );

    echo '<div class="options_group funncoba_country_specific_prices">';
    echo '<h2>' . esc_html__( 'Country-specific prices', 'funnelwheel-country-based-pricing' ) . '</h2>';

    foreach ( $countries as $code => $label ) {

        $currency_code = FUNNCOBA_Country_Helper::get_currency_by_country( $code );

        // 🚫 Skip base currency completely
        if ( ! $currency_code || $currency_code === $base_currency ) {
            continue;
        }

        $currency_symbol = FUNNCOBA_Country_Helper::get_currency_symbol_by_country( $code );

        // Skip countries with no currency symbol
        if ( empty( $currency_symbol ) ) {
            continue;
        }

        $regular_price = get_post_meta(
            get_the_ID(),
            "_funncoba_regular_price_{$currency_code}",
            true
        );

        $sale_price = get_post_meta(
            get_the_ID(),
            "_funncoba_sale_price_{$currency_code}",
            true
        );

        // Regular Price
        echo '<p class="form-field">';
        echo '<label for="_funncoba_regular_price_' . esc_attr( $currency_code ) . '">';
        echo esc_html(
            sprintf(
                __( 'Regular price (%s)', 'funnelwheel-country-based-pricing' ),
                $currency_symbol
            )
        );
        echo '</label>';
        echo '<input
            type="number"
            class="short"
            step="0.01"
            min="0"
            name="_funncoba_regular_price_' . esc_attr( $currency_code ) . '"
            id="_funncoba_regular_price_' . esc_attr( $currency_code ) . '"
            value="' . esc_attr( $regular_price ) . '"
        />';
        echo '</p>';

        // Sale Price
        echo '<p class="form-field">';
        echo '<label for="_funncoba_sale_price_' . esc_attr( $currency_code ) . '">';
        echo esc_html(
            sprintf(
                __( 'Sale price (%s)', 'funnelwheel-country-based-pricing' ),
                $currency_symbol
            )
        );
        echo '</label>';
        echo '<input
            type="number"
            class="short"
            step="0.01"
            min="0"
            name="_funncoba_sale_price_' . esc_attr( $currency_code ) . '"
            id="_funncoba_sale_price_' . esc_attr( $currency_code ) . '"
            value="' . esc_attr( $sale_price ) . '"
        />';
        echo '</p>';
    }

    echo '</div>';
}


add_action(
    'woocommerce_admin_process_product_object',
    __NAMESPACE__ . '\\funncoba_save_country_specific_prices'
);

function funncoba_save_country_specific_prices( $product ) {

    $base_currency = strtoupper( get_option( 'woocommerce_currency' ) );

    // True base prices (no filters, no country pricing involved)
    $base_regular = (float) $product->get_meta( '_regular_price', true );
    $base_sale    = (float) $product->get_meta( '_sale_price', true );

    foreach ( funncoba_supported_countries() as $code => $label ) {

        $target_currency = FUNNCOBA_Country_Helper::get_currency_by_country( $code );
        if ( ! $target_currency ) {
            continue;
        }

        foreach ( [ 'regular', 'sale' ] as $type ) {

            $key = "_funncoba_{$type}_price_{$target_currency}";

            // 1️⃣ Admin input (if any)
            $raw_post_value = filter_input( INPUT_POST, $key, FILTER_UNSAFE_RAW );

            if ( $raw_post_value !== null && $raw_post_value !== '' ) {

                // Admin explicitly entered value
                $value = max(
                    0,
                    (float) wc_clean( wp_unslash( $raw_post_value ) )
                );

            } else {

                // 2️⃣ No admin input → check if already calculated earlier
                $existing = $product->get_meta( $key, true );

                if ( $existing !== '' ) {
                    // Already exists → DO NOT recalculate
                    continue;
                }

                // 3️⃣ Meta missing → auto-calculate ONCE
                if ( 'regular' === $type ) {
                    $source_price = $base_regular;
                } else {
                    $source_price = $base_sale ?: $base_regular;
                }

                if ( $source_price <= 0 ) {
                    continue;
                }

                $value = FUNNCOBA_Currency_Rates::convert(
                    $source_price,
                    $base_currency,
                    $target_currency
                );
            }

            // 4️⃣ Persist final value
            $product->update_meta_data( $key, (float) $value );
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

    // Prevent recursion
    if ( $running ) {
        return $currency;
    }

    $running = true;

    // 🔒 Do NOT change currency in admin or AJAX
    if ( is_admin() || wp_doing_ajax() ) {
        $running = false;
        return $currency;
    }

    $new_currency = funncoba_get_currency_for_user();

    $running = false;
    return $new_currency ?: $currency;
}



/**
 * ------------------------------
 * FOOTER COUNTRY SELECTOR 
 * ------------------------------
 */
add_action( 'wp_footer', __NAMESPACE__ . '\\funncoba_footer_country_selector' );
function funncoba_footer_country_selector() {
    if ( is_admin() ) return;

    $countries = funncoba_supported_countries();
    if ( empty( $countries ) ) return;

    $current_country = FUNNCOBA_Country_Helper::get_user_country();

    // PHPCS-compliant: use filter_input() for $_SERVER['REQUEST_URI']
    $action_url = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_UNSAFE_RAW);
    $action_url = $action_url ? wp_unslash($action_url) : '';
    $action_url = esc_url($action_url);
    ?>

    <div class="funncoba-footer-bar">
        <div class="funncoba-country-selector">
            <form method="post" id="funncoba_country_form_footer" action="<?php echo esc_url( filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_UNSAFE_RAW) ? wp_unslash( filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_UNSAFE_RAW) ) : '' ); ?>" style="margin:0;">
                <select name="funncoba_country" id="funncoba_country_footer">
                    <?php foreach ( $countries as $code => $label ) :
                        $curr = FUNNCOBA_Country_Helper::get_currency_by_country( $code );
                        $flag = FUNNCOBA_Country_Helper::get_flag_by_country( $code );
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
    <?php
}


/**
 * Update header mini-cart via AJAX after add-to-cart.
 * Only runs if the theme has a .site-header-cart element.
 * Can be disabled via filter 'funncoba_enable_ajax_mini_cart'.
 */
add_filter( 'woocommerce_add_to_cart_fragments', function( $fragments ) {

    // Allow disabling this behavior via filter
    if ( ! apply_filters( 'funncoba_enable_ajax_mini_cart', true ) ) {
        return $fragments;
    }

    // Only run if mini-cart function exists
    if ( ! function_exists( 'woocommerce_mini_cart' ) ) {
        return $fragments;
    }

    // Capture mini-cart HTML
    ob_start();
    ?>
    <div class="site-header-cart">
        <?php woocommerce_mini_cart(); ?>
    </div>
    <?php
    $mini_cart_html = ob_get_clean();

    // Only add fragment if selector exists in DOM (safer)
    if ( strpos( $mini_cart_html, 'site-header-cart' ) !== false ) {
        $fragments['div.site-header-cart'] = $mini_cart_html;
    }

    return $fragments;
});