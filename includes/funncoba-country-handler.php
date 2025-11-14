<?php
namespace FunnelWheel\CountryBasedPricing;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle user country selection
 */
add_action( 'init', __NAMESPACE__ . '\\funncoba_handle_country_selection' );
function funncoba_handle_country_selection() {

    // Handle POST only when user selects a country
    if (
        isset( $_POST['funncoba_country'], $_POST['funncoba_nonce'] ) &&
        wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['funncoba_nonce'] ) ),
            'funncoba_select_country'
        )
    ) {
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
