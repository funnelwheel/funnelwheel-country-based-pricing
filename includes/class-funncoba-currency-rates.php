<?php
namespace FunnelWheel\CountryBasedPricing;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles static currency conversion.
 *
 * Design:
 * - All rates are relative to USD
 * - WooCommerce base currency can be anything
 * - No external APIs
 * - Deterministic and fast
 */
class FUNNCOBA_Currency_Rates {

    /**
     * Get static currency exchange rates.
     *
     * Meaning:
     * 1 USD = rate
     *
     * @return array
     */
    public static function get_rates() {

        /**
         * Rates relative to USD
         * 1 USD = rate
         */
        $rates = [

            // Americas
            'USD' => 1,
            'CAD' => 1.36,
            'MXN' => 16.90,
            'BRL' => 4.95,
            'ARS' => 850,
            'CLP' => 980,
            'COP' => 3900,
            'PEN' => 3.75,

            // Europe
            'EUR' => 0.91,
            'GBP' => 0.78,
            'CHF' => 0.88,
            'SEK' => 10.4,
            'NOK' => 10.6,
            'DKK' => 6.8,
            'PLN' => 3.95,
            'CZK' => 22.8,
            'HUF' => 355,
            'RON' => 4.55,
            'BGN' => 1.78,
            'HRK' => 6.9,

            // Asia
            'INR' => 83.20,
            'PKR' => 279,
            'BDT' => 110,
            'LKR' => 310,
            'NPR' => 133,
            'CNY' => 7.20,
            'JPY' => 148,
            'KRW' => 1320,
            'IDR' => 15600,
            'MYR' => 4.70,
            'THB' => 35.8,
            'PHP' => 56.0,
            'VND' => 24500,
            'SGD' => 1.34,
            'HKD' => 7.83,
            'TWD' => 31.5,

            // Middle East
            'AED' => 3.67,
            'SAR' => 3.75,
            'QAR' => 3.64,
            'KWD' => 0.31,
            'BHD' => 0.38,
            'OMR' => 0.38,
            'ILS' => 3.65,

            // Africa
            'ZAR' => 18.5,
            'NGN' => 1450,
            'KES' => 156,
            'EGP' => 48,
            'MAD' => 10.0,
            'GHS' => 12.3,

            // Oceania
            'AUD' => 1.52,
            'NZD' => 1.64,
        ];

        /**
         * Allow extension / override
         */
        return apply_filters( 'funncoba_currency_rates', $rates );
    }


    /**
     * Convert amount from WooCommerce base currency to target currency.
     *
     * @param float  $amount
     * @param string $target_currency
     * @return float
     */
    public static function convert_from_base( $amount, $target_currency ) {

        $amount = (float) $amount;

        if ( $amount <= 0 ) {
            return 0.0;
        }

        $base_currency   = strtoupper( get_option( 'woocommerce_currency' ) );
        $target_currency = strtoupper( $target_currency );

        if ( $base_currency === $target_currency ) {
            return self::round( $amount, $target_currency );
        }

        return self::convert( $amount, $base_currency, $target_currency );
    }

    /**
     * Convert between any two currencies using USD as intermediary.
     *
     * @param float  $amount
     * @param string $from_currency
     * @param string $to_currency
     * @return float
     */
    public static function convert( $amount, $from_currency, $to_currency ) {

        $amount = (float) $amount;

        if ( $amount <= 0 ) {
            return 0.0;
        }

        $from_currency = strtoupper( $from_currency );
        $to_currency   = strtoupper( $to_currency );

        if ( $from_currency === $to_currency ) {
            return self::round( $amount, $to_currency );
        }

        $rates = self::get_rates();

        if ( empty( $rates[ $from_currency ] ) || empty( $rates[ $to_currency ] ) ) {
            return self::round( $amount, $from_currency );
        }

        // FROM → USD
        if ( 'USD' !== $from_currency ) {
            $amount = $amount / (float) $rates[ $from_currency ];
        }

        // USD → TO
        if ( 'USD' !== $to_currency ) {
            $amount = $amount * (float) $rates[ $to_currency ];
        }

        return self::round( $amount, $to_currency );
    }


    /**
     * Currency-aware rounding.
     *
     * @param float  $amount
     * @param string $currency
     * @return float
     */
    protected static function round( $amount, $currency ) {

        $zero_decimals = [ 'JPY', 'KRW', 'VND' ];

        if ( in_array( strtoupper( $currency ), $zero_decimals, true ) ) {
            return round( $amount, 0 );
        }

        return round( $amount, wc_get_price_decimals() );
    }
}