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
     * Regular price
     * ✅ Meta-only
     * ❌ No conversion
     */
    public function get_regular_price( $price, $product ) {

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

        $currency = funncoba_get_currency_for_user();
        $meta_key = "_funncoba_sale_price_{$currency}";

        $custom = $product->get_meta( $meta_key, true );

        return $custom !== '' ? (float) $custom : '';
    }

    /**
     * Final price logic
     */
    public function get_final_price( $price, $product ) {

        $regular = $this->get_regular_price(
            $product->get_regular_price(),
            $product
        );

        // 🔒 No regular price → no final price
        if ( $regular === '' ) {
            return '';
        }

        $sale = $this->get_sale_price(
            $product->get_sale_price(),
            $product
        );

        if ( $sale !== '' && $sale < $regular ) {
            return $sale;
        }

        return $regular;
    }

    /**
     * 🔒 Absolute safety: block purchase if meta missing
     */
    public function is_purchasable( $purchasable, $product ) {

        $currency = funncoba_get_currency_for_user();
        $meta_key = "_funncoba_regular_price_{$currency}";

        if ( $product->get_meta( $meta_key, true ) === '' ) {
            return false;
        }

        return $purchasable;
    }
}
