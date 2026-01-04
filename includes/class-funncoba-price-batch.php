<?php
namespace FunnelWheel\CountryBasedPricing;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FUNNCOBA_Price_Batch {

    const GROUP      = 'funncoba_price_batch';
    const ACTION     = 'funncoba_process_price_batch';
    const BATCH_SIZE = 50;

    /**
     * Register batch processor hook
     */
    public static function init() {
        add_action( self::ACTION, [ __CLASS__, 'process_batch' ], 10, 2 );
    }

    /**
     * Schedule initial batch (safe for modern WooCommerce)
     */
    public static function schedule() {

        // ✅ Correct check (WC_Action_Queue is deprecated)
        if ( ! function_exists( 'as_enqueue_async_action' ) ) {
            return;
        }

        // Prevent duplicates
        if ( as_next_scheduled_action( self::ACTION, [], self::GROUP ) ) {
            return;
        }

        as_enqueue_async_action(
            self::ACTION,
            [
                'offset' => 0,
                'run_id' => time(),
            ],
            self::GROUP
        );
    }

    /**
     * Process one batch
     */
    public static function process_batch( $offset, $run_id ) {

        $products = wc_get_products( [
            'status' => [ 'publish', 'private' ],
            'limit'  => self::BATCH_SIZE,
            'offset' => (int) $offset,
            'return' => 'objects',
        ] );

        if ( empty( $products ) ) {
            update_option( 'funncoba_price_batch_completed', time() );
            return;
        }

        $base_currency = get_option( 'woocommerce_currency' );

        foreach ( $products as $product ) {
            self::process_product( $product, $base_currency );
        }

        // Queue next batch
        as_enqueue_async_action(
            self::ACTION,
            [
                'offset' => $offset + self::BATCH_SIZE,
                'run_id' => $run_id,
            ],
            self::GROUP
        );
    }

    /**
     * Process a single product
     */
    protected static function process_product( \WC_Product $product, $base_currency ) {

        $base_regular = (float) $product->get_regular_price();
        $base_sale    = (float) $product->get_sale_price();

        if ( $base_regular <= 0 ) {
            return;
        }

        foreach ( funncoba_supported_countries() as $country_code => $label ) {

            $currency = FUNNCOBA_Country_Helper::get_currency_by_country( $country_code );
            if ( ! $currency ) {
                continue;
            }

            // Regular price
            $regular_key = "_funncoba_regular_price_{$currency}";
            if ( $product->get_meta( $regular_key, true ) === '' ) {

                $converted = FUNNCOBA_Currency_Rates::convert(
                    $base_regular,
                    $base_currency,
                    $currency
                );

                $product->update_meta_data( $regular_key, (float) $converted );
            }

            // Sale price (optional)
            if ( $base_sale > 0 ) {

                $sale_key = "_funncoba_sale_price_{$currency}";
                if ( $product->get_meta( $sale_key, true ) === '' ) {

                    $converted_sale = FUNNCOBA_Currency_Rates::convert(
                        $base_sale,
                        $base_currency,
                        $currency
                    );

                    $product->update_meta_data( $sale_key, (float) $converted_sale );
                }
            }
        }

        $product->update_meta_data( '_funncoba_prices_ready', 'yes' );
        $product->save();
    }
}