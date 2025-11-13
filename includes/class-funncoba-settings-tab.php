<?php

namespace FunnelWheel\CountryBasedPricing;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
            [
                'name' => esc_html__( 'Enabled Countries', 'funnelwheel-country-based-pricing' ),
                'type' => 'multiselect',
                'desc' => esc_html__( 'Select countries where you want to enable custom pricing and discounts.', 'funnelwheel-country-based-pricing' ),
                'id'   => 'funncoba_enabled_countries',
                'options' => \WC()->countries->get_countries(),
                'class' => 'wc-enhanced-select',
                'css'   => 'min-width: 350px;',
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

        $enabled   = get_option( 'funncoba_enabled_countries', [] );
        $countries = \WC()->countries->get_countries();

        // Filter to enabled countries only
        $filtered = [];
        foreach ( $countries as $code => $label ) {
            if ( in_array( $code, $enabled, true ) ) {
                $filtered[ $code ] = $label;
            }
        }

        // Fallback if no countries enabled
        if ( empty( $filtered ) ) {
            $filtered = $countries;
        }

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
            $index_attr  = esc_attr( $index );
            $country_val = isset( $row['country'] ) ? esc_attr( $row['country'] ) : '';
            $type_val    = isset( $row['type'] ) ? esc_attr( $row['type'] ) : 'amount';
            $amount_val  = isset( $row['amount'] ) ? esc_attr( $row['amount'] ) : '';

            echo '<tr>';
            echo '<td><select name="funncoba_country_discounts[' . $index_attr . '][country]">';
            foreach ( $filtered as $code => $label ) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr( $code ),
                    selected( $country_val, $code, false ),
                    esc_html( $label )
                );
            }
            echo '</select></td>';

            echo '<td><select name="funncoba_country_discounts[' . $index_attr . '][type]">';
            foreach ( $discount_types as $key => $label ) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr( $key ),
                    selected( $type_val, $key, false ),
                    esc_html( $label )
                );
            }
            echo '</select></td>';

            echo '<td><input type="number" step="0.01" min="0" name="funncoba_country_discounts[' . $index_attr . '][amount]" value="' . esc_attr( $amount_val ) . '" /></td>';
            echo '<td><button class="button remove-row" type="button">×</button></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '<p><button class="button add-row" type="button">' . esc_html__( 'Add Country Discount', 'funnelwheel-country-based-pricing' ) . '</button></p>';

        // Enhanced JS + CSS
        ?>
        <style>
        .funncoba-discount-table {
            border-collapse: collapse;
            width: 100%;
            max-width: 800px;
            margin-top: 10px;
        }
        .funncoba-discount-table th,
        .funncoba-discount-table td {
            padding: 8px;
            vertical-align: middle;
        }
        .funncoba-discount-table th {
            background: #f9f9f9;
            font-weight: 600;
        }
        .funncoba-discount-table tr:nth-child(even) {
            background: #fcfcfc;
        }
        .funncoba-discount-table select,
        .funncoba-discount-table input[type="number"] {
            width: 100%;
            box-sizing: border-box;
        }
        .funncoba-discount-table .remove-row {
            background: transparent;
            border: none;
            color: #a00;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
        }
        .funncoba-discount-table .remove-row:hover {
            color: #d63638;
        }
        .add-row {
            margin-top: 8px;
        }
        </style>

        <script>
        jQuery(function($) {
            const tableBody = $('.funncoba-discount-table tbody');

            // Add new row
            $(document).on('click', '.add-row', function(e) {
                e.preventDefault();

                const lastRow = tableBody.find('tr:last');
                const newRow = lastRow.clone();

                // Reset inputs but preserve select defaults
                newRow.find('input[type="number"]').val('');
                newRow.find('select[name*="[country]"]').prop('selectedIndex', 0);
                newRow.find('select[name*="[type]"]').prop('selectedIndex', 0);

                // Update index names
                const rowCount = tableBody.find('tr').length;
                newRow.find('select, input').each(function() {
                    const name = $(this).attr('name').replace(/\[\d+\]/, '[' + rowCount + ']');
                    $(this).attr('name', name);
                });

                newRow.hide().appendTo(tableBody).fadeIn(150);
            });

            // Remove row but keep at least one
            $(document).on('click', '.remove-row', function(e) {
                e.preventDefault();
                const rows = tableBody.find('tr');
                if (rows.length > 1) {
                    $(this).closest('tr').fadeOut(150, function() {
                        $(this).remove();
                    });
                } else {
                    $(this).fadeOut(50).fadeIn(50);
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

        if ( isset( $_POST['funncoba_country_discounts'] ) && is_array( $_POST['funncoba_country_discounts'] ) ) {
            $raw_data = wp_unslash( $_POST['funncoba_country_discounts'] );
            $data = [];

            foreach ( $raw_data as $row ) {
                $country = isset( $row['country'] ) ? sanitize_text_field( $row['country'] ) : '';
                $type    = isset( $row['type'] ) ? sanitize_text_field( $row['type'] ) : 'amount';
                $amount  = isset( $row['amount'] ) ? floatval( $row['amount'] ) : 0;

                if ( $country && in_array( $type, [ 'amount', 'percent' ], true ) && $amount > 0 ) {
                    $data[] = [
                        'country' => $country,
                        'type'    => $type,
                        'amount'  => $amount,
                    ];
                }
            }

            update_option( 'funncoba_country_discounts', $data, false );
        }
    }
}

endif;