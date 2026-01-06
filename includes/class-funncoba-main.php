<?php
namespace FunnelWheel\CountryBasedPricing;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
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

        // 🔒 HARD SAFETY LOCK
        add_filter( 'woocommerce_is_purchasable', [ $this, 'is_purchasable' ], 999, 2 );
    }

    /**
     * Check if current currency is base currency
     */
    private function is_base_currency() {
        return funncoba_get_currency_for_user() === get_option( 'woocommerce_currency' );
    }

    /**
     * Regular price
     * ✅ Meta-only
     * ❌ No conversion
     */
    public function get_regular_price( $price, $product ) {

        // ✅ Base currency → do nothing
        if ( $this->is_base_currency() ) {
            return $price;
        }

        $currency = funncoba_get_currency_for_user();
        $meta_key = "_funncoba_regular_price_{$currency}";

        $custom = $product->get_meta( $meta_key, true );

        // 🔒 Meta missing → no price
        if ( $custom === '' ) {
            return '';
        }

        return (float) $custom;
    }

    /**
     * Sale price
     * ✅ Meta-only
     */
    public function get_sale_price( $price, $product ) {

        // ✅ Base currency → do nothing
        if ( $this->is_base_currency() ) {
            return $price;
        }

        $currency = funncoba_get_currency_for_user();
        $meta_key = "_funncoba_sale_price_{$currency}";

        $custom = $product->get_meta( $meta_key, true );

        return $custom !== '' ? (float) $custom : '';
    }

    /**
     * Final price logic with country-specific discounts
     */
    public function get_final_price( $price, $product ) {

        // ✅ Base currency → do nothing
        if ( $this->is_base_currency() ) {
            return $price;
        }

        // Get regular price
        $regular = $this->get_regular_price(
            $product->get_regular_price(),
            $product
        );

        // 🔒 No regular price → no final price
        if ( $regular === '' ) {
            return '';
        }

        // Get sale price if exists
        $sale = $this->get_sale_price(
            $product->get_sale_price(),
            $product
        );

        // Determine initial final price
        $final_price = ($sale !== '' && $sale < $regular) ? $sale : $regular;

        // Apply country-specific discount
        $country = FUNNCOBA_Country_Helper::get_user_country();
        $discounts = get_option( 'funncoba_country_discounts', [] );

        foreach ( $discounts as $rule ) {
            if ( isset( $rule['country'], $rule['type'], $rule['amount'] ) && $rule['country'] === $country ) {
                $amount = floatval( $rule['amount'] );

                if ( $rule['type'] === 'percent' ) {
                    $final_price -= ( $final_price * $amount / 100 );
                } else { // amount
                    $final_price -= $amount;
                }

                $final_price = max( 0, $final_price ); // prevent negative prices
                break; // stop after first matching rule
            }
        }

        return $final_price;
    }


    /**
     * 🔒 Absolute safety: block purchase if meta missing
     */
    public function is_purchasable( $purchasable, $product ) {

        // ✅ Base currency → allow WooCommerce logic
        if ( $this->is_base_currency() ) {
            return $purchasable;
        }

        $currency = funncoba_get_currency_for_user();
        $meta_key = "_funncoba_regular_price_{$currency}";

        if ( $product->get_meta( $meta_key, true ) === '' ) {
            return false;
        }

        return $purchasable;
    }
}