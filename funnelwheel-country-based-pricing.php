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
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-funncoba-admin-pricing.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-funncoba-country-helper.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-funncoba-currency-rates.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/funncoba-country-handler.php';

    // Delay batch + main logic until WooCommerce is ready
    add_action( 'woocommerce_loaded', __NAMESPACE__ . '\\funncoba_boot_after_wc', 20 );
    register_hooks();
}

/**
 * -------------------------------------------------
 * REGISTER HOOKS
 * -------------------------------------------------
 */
function register_hooks() {

    // Initialize main class
    new FUNNCOBA_Main();

    FUNNCOBA_Admin_Pricing::init();
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
 * Get only enabled/supported countries, always including the base country.
 */
function funncoba_supported_countries() {

    $enabled = get_option( 'funncoba_enabled_countries', [] );

    if ( empty( $enabled ) ) {
        $enabled = [ 'US', 'IN', 'GB', 'DE', 'FR', 'NL', 'ES', 'IT', 'AF' ]; // Default list
    }

    $wc_countries = \WC()->countries->get_countries();
    $countries = [];

    // Get WooCommerce base country
    $base_location = wc_get_base_location();
    $base_country  = $base_location['country'] ?? '';

    // Merge enabled countries + base country
    $all_codes = array_unique( array_merge( $enabled, [ $base_country ] ) );

    foreach ( $all_codes as $code ) {
        $countries[ $code ] = $wc_countries[ $code ] ?? $code;
    }

    return $countries;
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

add_filter( 'woocommerce_available_payment_gateways', __NAMESPACE__ . '\\funncoba_filter_gateways_by_country_currency', 99 );

function funncoba_filter_gateways_by_country_currency( $available_gateways ) {

    // Do not interfere in admin (except AJAX)
    if ( is_admin() && ! wp_doing_ajax() ) {
        return $available_gateways;
    }


    $customer_country  = WC()->customer->get_billing_country() ?: WC()->customer->get_shipping_country();
    $customer_currency = get_woocommerce_currency();

    foreach ( $available_gateways as $gateway_id => $gateway ) {

        // ✅ Get gateway settings
        $allowed_countries  = ! empty( $gateway->get_option( 'funncoba_allowed_countries' ) ) 
                              ? (array) $gateway->get_option( 'funncoba_allowed_countries' ) 
                              : [];
        $excluded_countries = ! empty( $gateway->get_option( 'funncoba_excluded_countries' ) ) 
                              ? (array) $gateway->get_option( 'funncoba_excluded_countries' ) 
                              : [];
        $allowed_currencies  = ! empty( $gateway->get_option( 'funncoba_allowed_currencies' ) ) 
                              ? (array) $gateway->get_option( 'funncoba_allowed_currencies' ) 
                              : [];
        $excluded_currencies = ! empty( $gateway->get_option( 'funncoba_excluded_currencies' ) ) 
                              ? (array) $gateway->get_option( 'funncoba_excluded_currencies' ) 
                              : [];

        // --- COUNTRY LOGIC ---
        if ( ! empty( $allowed_countries ) && ! in_array( $customer_country, $allowed_countries, true ) ) {
            unset( $available_gateways[ $gateway_id ] );
            continue;
        }

        if ( empty( $allowed_countries ) && in_array( $customer_country, $excluded_countries, true ) ) {
            unset( $available_gateways[ $gateway_id ] );
            continue;
        }

        // --- CURRENCY LOGIC ---
        if ( ! empty( $allowed_currencies ) && ! in_array( $customer_currency, $allowed_currencies, true ) ) {
            unset( $available_gateways[ $gateway_id ] );
            continue;
        }

        if ( empty( $allowed_currencies ) && in_array( $customer_currency, $excluded_currencies, true ) ) {
            unset( $available_gateways[ $gateway_id ] );
            continue;
        }
    }

    return $available_gateways;
}




add_action( 'init', __NAMESPACE__ . '\\funncoba_register_gateway_exclusion_fields', 20 );

function funncoba_register_gateway_exclusion_fields() {

    if ( ! class_exists( 'WC_Payment_Gateways' ) || ! WC()->payment_gateways ) {
        return;
    }

    foreach ( WC()->payment_gateways->payment_gateways() as $gateway_id => $gateway ) {

        add_filter(
            "woocommerce_settings_api_form_fields_{$gateway_id}",
            __NAMESPACE__ . '\\funncoba_add_exclusion_fields'
        );
    }
}

function funncoba_add_exclusion_fields( $fields ) {

    // ✅ Include list
    $fields['funncoba_allowed_countries'] = [
        'title'       => __( 'Allowed Countries', 'funnelwheel-country-based-pricing' ),
        'type'        => 'multiselect',
        'class'       => 'wc-enhanced-select',
        'options'     => WC()->countries->get_countries(),
        'description' => __( 'Gateway will be available only for selected countries. Leave empty to allow all.', 'funnelwheel-country-based-pricing' ),
        'desc_tip'    => true,
    ];

    // ✅ Exclude list
    $fields['funncoba_excluded_countries'] = [
        'title'       => __( 'Excluded Countries', 'funnelwheel-country-based-pricing' ),
        'type'        => 'multiselect',
        'class'       => 'wc-enhanced-select',
        'options'     => WC()->countries->get_countries(),
        'description' => __( 'Disable this payment method for selected countries. Ignored if Allowed Countries is set.', 'funnelwheel-country-based-pricing' ),
        'desc_tip'    => true,
    ];

    // ✅ Include & Exclude currencies
    $fields['funncoba_allowed_currencies'] = [
        'title'       => __( 'Allowed Currencies', 'funnelwheel-country-based-pricing' ),
        'type'        => 'multiselect',
        'class'       => 'wc-enhanced-select',
        'options'     => get_woocommerce_currencies(),
        'description' => __( 'Gateway will be available only for selected currencies. Leave empty to allow all.', 'funnelwheel-country-based-pricing' ),
        'desc_tip'    => true,
    ];

    $fields['funncoba_excluded_currencies'] = [
        'title'       => __( 'Excluded Currencies', 'funnelwheel-country-based-pricing' ),
        'type'        => 'multiselect',
        'class'       => 'wc-enhanced-select',
        'options'     => get_woocommerce_currencies(),
        'description' => __( 'Disable this payment method for selected currencies. Ignored if Allowed Currencies is set.', 'funnelwheel-country-based-pricing' ),
        'desc_tip'    => true,
    ];

    return $fields;
}
