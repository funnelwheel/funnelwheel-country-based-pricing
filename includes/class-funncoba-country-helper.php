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
            'AF' => ['name' => 'Afghanistan', 'currency' => 'AFN', 'symbol' => '؋', 'flag' => '🇦🇫'],
            'AL' => ['name' => 'Albania', 'currency' => 'ALL', 'symbol' => 'L', 'flag' => '🇦🇱'],
            'DZ' => ['name' => 'Algeria', 'currency' => 'DZD', 'symbol' => 'د.ج', 'flag' => '🇩🇿'],
            'AD' => ['name' => 'Andorra', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇦🇩'],
            'AO' => ['name' => 'Angola', 'currency' => 'AOA', 'symbol' => 'Kz', 'flag' => '🇦🇴'],
            'AR' => ['name' => 'Argentina', 'currency' => 'ARS', 'symbol' => '$', 'flag' => '🇦🇷'],
            'AM' => ['name' => 'Armenia', 'currency' => 'AMD', 'symbol' => '֏', 'flag' => '🇦🇲'],
            'AU' => ['name' => 'Australia', 'currency' => 'AUD', 'symbol' => 'A$', 'flag' => '🇦🇺'],
            'AT' => ['name' => 'Austria', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇦🇹'],
            'AZ' => ['name' => 'Azerbaijan', 'currency' => 'AZN', 'symbol' => '₼', 'flag' => '🇦🇿'],

            'BH' => ['name' => 'Bahrain', 'currency' => 'BHD', 'symbol' => '.د.ب', 'flag' => '🇧🇭'],
            'BD' => ['name' => 'Bangladesh', 'currency' => 'BDT', 'symbol' => '৳', 'flag' => '🇧🇩'],
            'BY' => ['name' => 'Belarus', 'currency' => 'BYN', 'symbol' => 'Br', 'flag' => '🇧🇾'],
            'BE' => ['name' => 'Belgium', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇧🇪'],
            'BR' => ['name' => 'Brazil', 'currency' => 'BRL', 'symbol' => 'R$', 'flag' => '🇧🇷'],
            'BG' => ['name' => 'Bulgaria', 'currency' => 'BGN', 'symbol' => 'лв', 'flag' => '🇧🇬'],

            'CA' => ['name' => 'Canada', 'currency' => 'CAD', 'symbol' => 'C$', 'flag' => '🇨🇦'],
            'CL' => ['name' => 'Chile', 'currency' => 'CLP', 'symbol' => '$', 'flag' => '🇨🇱'],
            'CN' => ['name' => 'China', 'currency' => 'CNY', 'symbol' => '¥', 'flag' => '🇨🇳'],
            'CO' => ['name' => 'Colombia', 'currency' => 'COP', 'symbol' => '$', 'flag' => '🇨🇴'],
            'CR' => ['name' => 'Costa Rica', 'currency' => 'CRC', 'symbol' => '₡', 'flag' => '🇨🇷'],
            'HR' => ['name' => 'Croatia', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇭🇷'],
            'CY' => ['name' => 'Cyprus', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇨🇾'],
            'CZ' => ['name' => 'Czech Republic', 'currency' => 'CZK', 'symbol' => 'Kč', 'flag' => '🇨🇿'],

            'DK' => ['name' => 'Denmark', 'currency' => 'DKK', 'symbol' => 'kr', 'flag' => '🇩🇰'],
            'DO' => ['name' => 'Dominican Republic', 'currency' => 'DOP', 'symbol' => 'RD$', 'flag' => '🇩🇴'],

            'EG' => ['name' => 'Egypt', 'currency' => 'EGP', 'symbol' => '£', 'flag' => '🇪🇬'],
            'EE' => ['name' => 'Estonia', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇪🇪'],
            'FI' => ['name' => 'Finland', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇫🇮'],
            'FR' => ['name' => 'France', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇫🇷'],

            'DE' => ['name' => 'Germany', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇩🇪'],
            'GR' => ['name' => 'Greece', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇬🇷'],
            'HK' => ['name' => 'Hong Kong', 'currency' => 'HKD', 'symbol' => 'HK$', 'flag' => '🇭🇰'],
            'HU' => ['name' => 'Hungary', 'currency' => 'HUF', 'symbol' => 'Ft', 'flag' => '🇭🇺'],

            'IS' => ['name' => 'Iceland', 'currency' => 'ISK', 'symbol' => 'kr', 'flag' => '🇮🇸'],
            'IN' => ['name' => 'India', 'currency' => 'INR', 'symbol' => '₹', 'flag' => '🇮🇳'],
            'ID' => ['name' => 'Indonesia', 'currency' => 'IDR', 'symbol' => 'Rp', 'flag' => '🇮🇩'],
            'IE' => ['name' => 'Ireland', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇮🇪'],
            'IL' => ['name' => 'Israel', 'currency' => 'ILS', 'symbol' => '₪', 'flag' => '🇮🇱'],
            'IT' => ['name' => 'Italy', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇮🇹'],

            'JP' => ['name' => 'Japan', 'currency' => 'JPY', 'symbol' => '¥', 'flag' => '🇯🇵'],
            'KE' => ['name' => 'Kenya', 'currency' => 'KES', 'symbol' => 'KSh', 'flag' => '🇰🇪'],
            'KR' => ['name' => 'South Korea', 'currency' => 'KRW', 'symbol' => '₩', 'flag' => '🇰🇷'],
            'KW' => ['name' => 'Kuwait', 'currency' => 'KWD', 'symbol' => 'د.ك', 'flag' => '🇰🇼'],

            'LV' => ['name' => 'Latvia', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇱🇻'],
            'LT' => ['name' => 'Lithuania', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇱🇹'],
            'LU' => ['name' => 'Luxembourg', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇱🇺'],

            'MY' => ['name' => 'Malaysia', 'currency' => 'MYR', 'symbol' => 'RM', 'flag' => '🇲🇾'],
            'MX' => ['name' => 'Mexico', 'currency' => 'MXN', 'symbol' => '$', 'flag' => '🇲🇽'],
            'MA' => ['name' => 'Morocco', 'currency' => 'MAD', 'symbol' => 'د.م.', 'flag' => '🇲🇦'],

            'NL' => ['name' => 'Netherlands', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇳🇱'],
            'NZ' => ['name' => 'New Zealand', 'currency' => 'NZD', 'symbol' => 'NZ$', 'flag' => '🇳🇿'],
            'NG' => ['name' => 'Nigeria', 'currency' => 'NGN', 'symbol' => '₦', 'flag' => '🇳🇬'],
            'NO' => ['name' => 'Norway', 'currency' => 'NOK', 'symbol' => 'kr', 'flag' => '🇳🇴'],

            'PK' => ['name' => 'Pakistan', 'currency' => 'PKR', 'symbol' => '₨', 'flag' => '🇵🇰'],
            'PH' => ['name' => 'Philippines', 'currency' => 'PHP', 'symbol' => '₱', 'flag' => '🇵🇭'],
            'PL' => ['name' => 'Poland', 'currency' => 'PLN', 'symbol' => 'zł', 'flag' => '🇵🇱'],
            'PT' => ['name' => 'Portugal', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇵🇹'],

            'QA' => ['name' => 'Qatar', 'currency' => 'QAR', 'symbol' => 'ر.ق', 'flag' => '🇶🇦'],

            'RO' => ['name' => 'Romania', 'currency' => 'RON', 'symbol' => 'lei', 'flag' => '🇷🇴'],
            'RU' => ['name' => 'Russia', 'currency' => 'RUB', 'symbol' => '₽', 'flag' => '🇷🇺'],

            'SA' => ['name' => 'Saudi Arabia', 'currency' => 'SAR', 'symbol' => '﷼', 'flag' => '🇸🇦'],
            'SG' => ['name' => 'Singapore', 'currency' => 'SGD', 'symbol' => 'S$', 'flag' => '🇸🇬'],
            'ZA' => ['name' => 'South Africa', 'currency' => 'ZAR', 'symbol' => 'R', 'flag' => '🇿🇦'],
            'ES' => ['name' => 'Spain', 'currency' => 'EUR', 'symbol' => '€', 'flag' => '🇪🇸'],
            'SE' => ['name' => 'Sweden', 'currency' => 'SEK', 'symbol' => 'kr', 'flag' => '🇸🇪'],
            'CH' => ['name' => 'Switzerland', 'currency' => 'CHF', 'symbol' => 'CHF', 'flag' => '🇨🇭'],

            'TH' => ['name' => 'Thailand', 'currency' => 'THB', 'symbol' => '฿', 'flag' => '🇹🇭'],
            'TR' => ['name' => 'Turkey', 'currency' => 'TRY', 'symbol' => '₺', 'flag' => '🇹🇷'],

            'UA' => ['name' => 'Ukraine', 'currency' => 'UAH', 'symbol' => '₴', 'flag' => '🇺🇦'],
            'AE' => ['name' => 'United Arab Emirates', 'currency' => 'AED', 'symbol' => 'د.إ', 'flag' => '🇦🇪'],
            'GB' => ['name' => 'United Kingdom', 'currency' => 'GBP', 'symbol' => '£', 'flag' => '🇬🇧'],
            'US' => ['name' => 'United States', 'currency' => 'USD', 'symbol' => '$', 'flag' => '🇺🇸'],

            'VN' => ['name' => 'Vietnam', 'currency' => 'VND', 'symbol' => '₫', 'flag' => '🇻🇳'],
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
        if ( isset( $_COOKIE['funncoba_selected_country'] ) ) {
            return sanitize_text_field( wp_unslash( $_COOKIE['funncoba_selected_country'] ) );
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