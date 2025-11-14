<?php
namespace FunnelWheel\CountryBasedPricing;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Country data helper.
 * Provides mapping of country code → currency code, symbol, flag emoji (optional).
 */
class FUNNCOBA_Country_Helper {

    /**
     * Get the full country data map.
     *
     * @return array
     */
    public static function get_country_map() {
        return [
            'US' => ['name'=>'United States','currency'=>'USD','symbol'=>'$','flag'=>'🇺🇸'],
            'IN' => ['name'=>'India','currency'=>'INR','symbol'=>'₹','flag'=>'🇮🇳'],
            'AF' => ['name'=>'Afghanistan','currency'=>'AFN','symbol'=>'؋','flag'=>'🇦🇫'],
            'GB' => ['name'=>'United Kingdom','currency'=>'GBP','symbol'=>'£','flag'=>'🇬🇧'],
            'FR' => ['name'=>'France','currency'=>'EUR','symbol'=>'€','flag'=>'🇫🇷'],
            'DE' => ['name'=>'Germany','currency'=>'EUR','symbol'=>'€','flag'=>'🇩🇪'],
            'AU' => ['name'=>'Australia','currency'=>'AUD','symbol'=>'A$','flag'=>'🇦🇺'],
            'NZ' => ['name'=>'New Zealand','currency'=>'NZD','symbol'=>'NZ$','flag'=>'🇳🇿'],
            'JP' => ['name'=>'Japan','currency'=>'JPY','symbol'=>'¥','flag'=>'🇯🇵'],
            'CN' => ['name'=>'China','currency'=>'CNY','symbol'=>'¥','flag'=>'🇨🇳'],
            'CA' => ['name'=>'Canada','currency'=>'CAD','symbol'=>'C$','flag'=>'🇨🇦'],
            'BR' => ['name'=>'Brazil','currency'=>'BRL','symbol'=>'R$','flag'=>'🇧🇷'],
            'ZA' => ['name'=>'South Africa','currency'=>'ZAR','symbol'=>'R','flag'=>'🇿🇦'],
            // Add more countries here
        ];
    }

    /**
     * Get currency code by country code.
     *
     * @param string $country_code
     * @return string|null
     */
    public static function get_currency_by_country( $country_code ) {
        $map = self::get_country_map();
        $code = strtoupper( $country_code );
        return $map[ $code ]['currency'] ?? null;
    }

    /**
     * Get currency symbol by country code.
     * Alias for backward compatibility.
     *
     * @param string $country_code
     * @return string|null
     */
    public static function get_currency_symbol_by_country( $country_code ) {
        return self::get_symbol_by_country( $country_code );
    }

    /**
     * Get currency symbol by country code.
     *
     * @param string $country_code
     * @return string|null
     */
    public static function get_symbol_by_country( $country_code ) {
        $map = self::get_country_map();
        $code = strtoupper( $country_code );
        return $map[ $code ]['symbol'] ?? null;
    }

    /**
     * Get flag emoji by country code.
     *
     * @param string $country_code
     * @return string|null
     */
    public static function get_flag_by_country( $country_code ) {
        $map = self::get_country_map();
        $code = strtoupper( $country_code );
        return $map[ $code ]['flag'] ?? null;
    }

    /**
     * Get the current user's country code.
     *
     * Uses WooCommerce geolocation if available, otherwise falls back to store default.
     *
     * @return string Country code (ISO 2-letter)
     */
    public static function get_user_country() {
        // 1. Check WooCommerce session
        if ( function_exists('WC') && WC()->session ) {
            $session_country = WC()->session->get('funncoba_selected_country');
            if ( $session_country ) return $session_country;
        }

        // 2. Check cookie
        if ( isset($_COOKIE['funncoba_selected_country']) ) {
            return sanitize_text_field($_COOKIE['funncoba_selected_country']);
        }

        // 3. WooCommerce geolocation
        if ( class_exists( '\WC_Geolocation' ) ) {
            $location = \WC_Geolocation::geolocate_ip();
            if ( ! empty( $location['country'] ) ) return $location['country'];
        }

        // 4. Fallback: store base country
        if ( function_exists('wc_get_base_location') ) {
            $base_location = wc_get_base_location();
            if ( ! empty( $base_location['country'] ) ) return $base_location['country'];
        }

        // 5. Ultimate fallback
        return 'US';
    }
}