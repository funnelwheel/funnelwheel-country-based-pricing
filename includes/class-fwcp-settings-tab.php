<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'FWCP_Settings_Tab' ) ) :

class FWCP_Settings_Tab extends WC_Settings_Page {

    public function __construct() {
        $this->id    = 'fwcp';
        $this->label = esc_html__( 'Country Pricing', 'funnelwheel-country-based-pricing' );
        parent::__construct();
    }

    public function get_settings() {
        $settings = array(
            array(
                'name' => esc_html__( 'FunnelWheel Country Discounts', 'funnelwheel-country-based-pricing' ),
                'type' => 'title',
                'desc' => esc_html__( 'Set discounts by country and type (flat or percent).', 'funnelwheel-country-based-pricing' ),
                'id'   => 'fwcp_settings_section_title',
            ),
            array(
                'type' => 'fwcp_country_discount_table',
                'id'   => 'fwcp_country_discounts',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'fwcp_settings_section_end',
            )
        );

        return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
    }
}

endif;

// Render the settings field
add_action( 'woocommerce_admin_field_fwcp_country_discount_table', 'funnelwheel_render_discount_table_field' );

// Save handler
add_action( 'woocommerce_update_option_fwcp_country_discounts', 'funnelwheel_save_discount_table_field' );

/**
 * Render country discount table
 */
function funnelwheel_render_discount_table_field( $option ) {
    $value = get_option( 'fwcp_country_discounts', [] );
    if ( ! is_array( $value ) ) {
        $value = [];
    }

    $countries = WC()->countries->get_countries();
    $discount_types = [
        'amount'  => esc_html__( 'Amount', 'funnelwheel-country-based-pricing' ),
        'percent' => esc_html__( 'Percent', 'funnelwheel-country-based-pricing' ),
    ];

    echo '<table class="widefat fwcp-discount-table" style="max-width: 800px;">';
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

        // Country select
        printf(
            '<td><select name="%s">',
            esc_attr( 'fwcp_country_discounts[' . $index_attr . '][country]' )
        );
        foreach ( $countries as $code => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $code ),
                selected( $country_val, $code, false ),
                esc_html( $label )
            );
        }
        echo '</select></td>';

        // Discount type select
        printf(
            '<td><select name="%s">',
            esc_attr( 'fwcp_country_discounts[' . $index_attr . '][type]' )
        );
        foreach ( $discount_types as $key => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $key ),
                selected( $type_val, $key, false ),
                esc_html( $label )
            );
        }
        echo '</select></td>';

        // Amount input
        printf(
            '<td><input type="number" step="0.01" min="0" name="%s" value="%s" /></td>',
            esc_attr( 'fwcp_country_discounts[' . $index_attr . '][amount]' ),
            esc_attr( $amount_val )
        );

        // Remove button
        echo '<td><button class="button remove-row" type="button">×</button></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    echo '<p><button class="button add-row" type="button">' . esc_html__( 'Add Country Discount', 'funnelwheel-country-based-pricing' ) . '</button></p>';
    ?>

    <script>
    jQuery(function($) {
        let table = $('.fwcp-discount-table tbody');

        $('.add-row').on('click', function(e) {
            e.preventDefault();
            let rowCount = table.find('tr').length;
            let newRow = table.find('tr:last').clone();

            newRow.find('select, input').each(function() {
                let name = $(this).attr('name');
                name = name.replace(/\[\d+\]/, '[' + rowCount + ']');
                $(this).attr('name', name);
                $(this).val('');
            });

            table.append(newRow);
        });

        table.on('click', '.remove-row', function(e) {
            e.preventDefault();
            if ( table.find('tr').length > 1 ) {
                $(this).closest('tr').remove();
            }
        });
    });
    </script>
    <?php
}

/**
 * Save country discounts field with sanitization & nonce check
 */
function funnelwheel_save_discount_table_field() {
    // Validate nonce properly
    if (
        ! isset( $_POST['_wpnonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'woocommerce-settings' )
    ) {
        return;
    }

    if ( isset( $_POST['fwcp_country_discounts'] ) && is_array( $_POST['fwcp_country_discounts'] ) ) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $raw_data = wp_unslash( $_POST['fwcp_country_discounts'] );
        $data     = [];

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

        update_option( 'fwcp_country_discounts', $data );
    }
}
