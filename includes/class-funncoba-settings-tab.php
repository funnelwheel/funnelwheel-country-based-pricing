<?php

namespace FunnelWheel\CountryBasedPricing;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use FunnelWheel\CountryBasedPricing\FUNNCOBA_Country_Helper;

if ( ! class_exists( 'FUNNCOBA_Settings_Tab' ) ) :

class FUNNCOBA_Settings_Tab extends \WC_Settings_Page {

    public function __construct() {
        $this->id    = 'funncoba';
        $this->label = esc_html__( 'Country Pricing', 'funnelwheel-country-based-pricing' );
        parent::__construct();

        add_action( 'woocommerce_admin_field_funncoba_country_discount_table', [ $this, 'render_discount_table_field' ] );
        add_action( 'woocommerce_update_option_funncoba_country_discounts', [ $this, 'save_discount_table_field' ] );
    }

    public function get_settings() {
        $settings = [
            [
                'name' => esc_html__( 'FunnelWheel Country Pricing Settings', 'funnelwheel-country-based-pricing' ),
                'type' => 'title',
                'desc' => esc_html__( 'Configure which countries should have custom pricing and set discounts by country.', 'funnelwheel-country-based-pricing' ),
                'id'   => 'funncoba_settings_section_title',
            ],
            // Custom multiselect field with label above
            [
                'title'   => esc_html__( 'Enabled Countries', 'funnelwheel-country-based-pricing' ),
                'desc'    => esc_html__( 'Select countries where you want to enable custom pricing and discounts.', 'funnelwheel-country-based-pricing' ),
                'id'      => 'funncoba_enabled_countries',
                'type'    => 'multiselect',
                'class'   => 'wc-enhanced-select',
                'css'     => 'width: 69.3%; max-width: 800px;', // full width
                'options' => \WC()->countries->get_countries(),
            ],
            [
                'name' => esc_html__( 'FunnelWheel Country Discounts', 'funnelwheel-country-based-pricing' ),
                'type' => 'funncoba_country_discount_table',
                'desc' => esc_html__( 'Set discounts by country and type (flat or percent).', 'funnelwheel-country-based-pricing' ),
                'id'   => 'funncoba_country_discounts',
            ],
            [
                'type' => 'sectionend',
                'id'   => 'funncoba_settings_section_end',
            ],
        ];

        return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
    }


    public function render_discount_table_field( $option ) {
        $value = get_option( 'funncoba_country_discounts', [] );
        if ( ! is_array( $value ) ) {
            $value = [];
        }

        // Get only enabled countries
        $enabled_countries = get_option( 'funncoba_enabled_countries', [] );
        if ( ! is_array( $enabled_countries ) ) {
            $enabled_countries = [];
        }

        $all_countries = FUNNCOBA_Country_Helper::get_country_map();
        $countries = array_intersect_key( $all_countries, array_flip($enabled_countries) );

        $discount_types = [
            'amount'  => esc_html__( 'Amount', 'funnelwheel-country-based-pricing' ),
            'percent' => esc_html__( 'Percent', 'funnelwheel-country-based-pricing' ),
        ];

        echo '<table class="widefat funncoba-discount-table">';
        echo '<thead><tr>
                <th>' . esc_html__( 'Country', 'funnelwheel-country-based-pricing' ) . '</th>
                <th>' . esc_html__( 'Discount Type', 'funnelwheel-country-based-pricing' ) . '</th>
                <th>' . esc_html__( 'Amount', 'funnelwheel-country-based-pricing' ) . '</th>
                <th></th>
              </tr></thead>';
        echo '<tbody>';

        if ( empty( $value ) ) {
            $value[] = [ 'country' => '', 'type' => 'amount', 'amount' => '' ];
        }

        foreach ( $value as $index => $row ) {
            $country_val = isset( $row['country'] ) ? esc_attr( $row['country'] ) : '';
            $type_val    = isset( $row['type'] ) ? esc_attr( $row['type'] ) : 'amount';
            $amount_val  = isset( $row['amount'] ) ? esc_attr( $row['amount'] ) : '';

            echo '<tr>';
            echo '<td><select name="funncoba_country_discounts[' . $index . '][country]">';
            foreach ( $countries as $code => $data ) {
                $label = $data['name'] ?? $code;
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr( $code ),
                    selected( $country_val, $code, false ),
                    esc_html( $label )
                );
            }
            echo '</select></td>';

            echo '<td><select name="funncoba_country_discounts[' . $index . '][type]">';
            foreach ( $discount_types as $key => $label ) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr( $key ),
                    selected( $type_val, $key, false ),
                    esc_html( $label )
                );
            }
            echo '</select></td>';

            echo '<td><input type="number" step="0.01" min="0" name="funncoba_country_discounts[' . $index . '][amount]" value="' . esc_attr( $amount_val ) . '" /></td>';
            echo '<td><button class="button remove-row" type="button">×</button></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '<p><button class="button add-row" type="button">' . esc_html__( 'Add Country Discount', 'funnelwheel-country-based-pricing' ) . '</button></p>';

        ?>

        <script>
        jQuery(function($) {
            const tableBody = $('.funncoba-discount-table tbody');

            function getNextIndex() {
                let maxIndex = 0;
                tableBody.find('tr').each(function() {
                    $(this).find('select, input').each(function() {
                        const match = $(this).attr('name').match(/\[(\d+)\]/);
                        if(match && parseInt(match[1]) > maxIndex) maxIndex = parseInt(match[1]);
                    });
                });
                return maxIndex + 1;
            }

            $(document).on('click', '.add-row', function(e) {
                e.preventDefault();
                const lastRow = tableBody.find('tr:last');
                const newRow = lastRow.clone();

                newRow.find('input[type="number"]').val('');
                newRow.find('select').prop('selectedIndex', 0);

                const nextIndex = getNextIndex();
                newRow.find('select, input').each(function() {
                    const name = $(this).attr('name').replace(/\[\d+\]/, '[' + nextIndex + ']');
                    $(this).attr('name', name);
                });

                newRow.hide().appendTo(tableBody).fadeIn(150);
            });

            $(document).on('click', '.remove-row', function(e) {
                e.preventDefault();
                const rows = tableBody.find('tr');
                if (rows.length > 1) {
                    $(this).closest('tr').fadeOut(150, function() { $(this).remove(); });
                }
            });
        });
        </script>
        <?php
    }

    public function save_discount_table_field() {
        if (
            ! isset( $_POST['_wpnonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'woocommerce-settings' )
        ) {
            return;
        }

        if ( ! empty( $_POST['funncoba_country_discounts'] ) && is_array( $_POST['funncoba_country_discounts'] ) ) {
            $raw_data = wp_unslash( $_POST['funncoba_country_discounts'] );
            $data = [];

            foreach ( $raw_data as $row ) {
                $country = isset( $row['country'] ) ? sanitize_text_field( $row['country'] ) : '';
                $type    = isset( $row['type'] ) ? sanitize_text_field( $row['type'] ) : 'amount';
                $amount  = isset( $row['amount'] ) ? floatval( $row['amount'] ) : 0;

                if ( $country && in_array( $type, ['amount','percent'], true ) ) {
                    $data[] = [
                        'country' => $country,
                        'type'    => $type,
                        'amount'  => $amount,
                    ];
                }
            }

            update_option( 'funncoba_country_discounts', array_values($data), false );
        }
    }
}

endif;