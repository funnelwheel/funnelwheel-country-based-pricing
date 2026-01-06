<?php
namespace FunnelWheel\CountryBasedPricing;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * -------------------------------------------------
 * ADMIN PRICING (Country-specific meta prices)
 * -------------------------------------------------
 */
class FUNNCOBA_Admin_Pricing {

    /**
     * Bootstrap hooks
     */
    public static function init() {

        add_action(
            'woocommerce_product_options_pricing',
            [ __CLASS__, 'render_fields' ]
        );

        add_action(
            'woocommerce_admin_process_product_object',
            [ __CLASS__, 'save_fields' ]
        );
    }

    /**
     * Render country-specific price fields
     */
    public static function render_fields() {

        $countries = funncoba_supported_countries();
        if ( empty( $countries ) ) {
            return;
        }

        $base_currency = strtoupper( get_option( 'woocommerce_currency' ) );

        echo '<div class="options_group funncoba_country_specific_prices">';
        echo '<h2>' . esc_html__( 'Country-specific prices', 'funnelwheel-country-based-pricing' ) . '</h2>';

        foreach ( $countries as $code => $label ) {

            $currency = FUNNCOBA_Country_Helper::get_currency_by_country( $code );
            if ( ! $currency || $currency === $base_currency ) {
                continue;
            }

            $symbol = FUNNCOBA_Country_Helper::get_currency_symbol_by_country( $code );
            if ( empty( $symbol ) ) {
                continue;
            }

            self::render_number_field(
                "_funncoba_regular_price_{$currency}",
                sprintf(
                    /* translators: %s is the currency symbol */
                    __( 'Regular price (%s)', 'funnelwheel-country-based-pricing' ),
                    $symbol
                )
            );

            self::render_number_field(
                "_funncoba_sale_price_{$currency}",
                sprintf(
                    /* translators: %s is the currency symbol */
                    __( 'Sale price (%s)', 'funnelwheel-country-based-pricing' ),
                    $symbol
                )
            );
        }

        echo '</div>';
    }

    /**
     * Save country-specific prices
     */
    public static function save_fields( $product ) {

        $base_currency = strtoupper( get_option( 'woocommerce_currency' ) );

        $base_regular = (float) $product->get_meta( '_regular_price', true );
        $base_sale    = (float) $product->get_meta( '_sale_price', true );

        foreach ( funncoba_supported_countries() as $code => $label ) {

            $currency = FUNNCOBA_Country_Helper::get_currency_by_country( $code );
            if ( ! $currency || $currency === $base_currency ) {
                continue;
            }

            foreach ( [ 'regular', 'sale' ] as $type ) {

                $meta_key = "_funncoba_{$type}_price_{$currency}";
                $posted   = filter_input( INPUT_POST, $meta_key, FILTER_UNSAFE_RAW );

                // 1️⃣ Admin override
                if ( $posted !== null && $posted !== '' ) {

                    $value = max(
                        0,
                        (float) wc_clean( wp_unslash( $posted ) )
                    );

                } else {

                    // 2️⃣ Already exists → do not recalc
                    $existing = $product->get_meta( $meta_key, true );
                    if ( $existing !== '' ) {
                        continue;
                    }

                    // 3️⃣ Auto-calc once
                    $source_price = ( 'sale' === $type && $base_sale > 0 )
                        ? $base_sale
                        : $base_regular;

                    if ( $source_price <= 0 ) {
                        continue;
                    }

                    $value = FUNNCOBA_Currency_Rates::convert(
                        $source_price,
                        $base_currency,
                        $currency
                    );
                }

                $product->update_meta_data( $meta_key, (float) $value );
            }
        }
    }

    /**
     * Render reusable number input field
     */
    private static function render_number_field( $meta_key, $label ) {

        $value = get_post_meta( get_the_ID(), $meta_key, true );

        echo '<p class="form-field">';
        echo '<label for="' . esc_attr( $meta_key ) . '">' . esc_html( $label ) . '</label>';
        echo '<input
            type="number"
            class="short"
            step="0.01"
            min="0"
            name="' . esc_attr( $meta_key ) . '"
            id="' . esc_attr( $meta_key ) . '"
            value="' . esc_attr( $value ) . '"
        />';
        echo '</p>';
    }
}