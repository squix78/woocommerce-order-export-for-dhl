<?php
/**
 * Plugin Name: WooCommerce Order export for DHL
 * Description: Export orders from WooCommerce for DHL.
 * Author: David Kiss
 * Version: 1.0
 */
 
// No direct access
defined('ABSPATH') or die;

// Restrict this plugin only to admin area
if( !is_admin() ) return;

class WcOrderExportForDHL {
    
    /* todo: wc settings area */
    protected $config = array(
        
    );
    /* /todo: wc settings area */
    
    public function __construct( ) {
        
        add_action( 'admin_footer-edit.php', array( $this, 'generate_admin_footer' ) );
        
        add_action( 'load-edit.php', array( $this, 'generate_export_for_dhl' ) );
    }
    
    /**
     * Add some JS to append our new export option to the bulk actions list.
     */
    public function generate_admin_footer( ) {
        
		global $post_type;

		if( $post_type === 'shop_order' ) {
			echo
				"<script type='text/javascript'>
				jQuery(document).ready(function() {
					jQuery('<option>').val('generate_export_for_dhl').text('" . __( 'Export a DHL számára' ) . "').appendTo(" . '"' . "select[name='action'],select[name='action2']" . '"' . ");
				});
			</script>";
		}
	}
    
    public function generate_export_for_dhl( ) {
        
        // get the action
        $wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
        $action        = $wp_list_table->current_action();
        
        // return if we don't have to work
        if ( $action != 'generate_export_for_dhl' ) return;
        
        // security check
		check_admin_referer( 'bulk-posts' );
        
        // make sure ids are submitted. depending on the resource type, this may be 'media' or 'ids'
        if ( isset( $_REQUEST['post'] ) ) {
            $post_ids = array_map( 'intval', $_REQUEST['post'] );
        }

        // return if we don't have to work
        if ( empty( $post_ids ) ) return;
        
        $output = '';
        
        foreach ( $post_ids as $post_id ) {
            
            $order = new WC_Order_Factory();
            $order = $order->get_order( $post_id );
            $order_id = $order->id;
            //$order_id = $order->id + 100000; //test
            
            $output .= $order_id . '|DHL-SHIPMENT|0||ECX|' . date('Ymd') . '|1|412294262||||||456-332||||||||P|||||||LED bulbs

' . $order_id . '|DHL-SENDER|BUD|Mészáros Gergely E.V|CleanLEDs|Galamb utca 19.|||4030|Debrecen|HU||ask.cleanleds@gmail.com|+02081333298||||||1

';

            $order_details = array(
                $order->shipping_company,
                //'Company_árvíztűrőtükörfúrógép', //test only
                '',
                $order->get_formatted_billing_full_name(),
                trim( $order->shipping_address_1 . ' ' . $order->shipping_address_2 ),
                '',
                '',
                $order->shipping_postcode,
                $order->shipping_city,
                $order->shipping_country,
                '',
                '',
                str_replace( ' ', '', $order->billing_phone ),
                '',
                '',
                '',
                '',
                '',
                '1'
            );
            
            $output .= $order_id . '|DHL-RECEIVER|' . str_replace( array( '|', '(%%)' ), array( ':', '|' ), implode( '(%%)', $order_details ) ) . '

';

            $weight = 1;/*disable calculation
            foreach( $order->get_items() as $item ) {
                if ( $item['product_id'] > 0 ) {
                    $_product = $order->get_product_from_item( $item );
                    if ( ! $_product->is_virtual() ) $weight += $_product->get_weight() * $item['qty'];
                }
            }*/

            $output .= $order_id . '|DHL-ITEM|' . $weight . '|20|12|15

';
            
        }
        
        $output = iconv('UTF-8', 'ISO-8859-2', $output);
        
        header( 'Content-Type: text/csv;charset=iso-8859-2' );
        header( 'Content-Disposition: attachment;filename="WC_orders_DHL_' . date( 'Y-m-d_H:i:s' ) . '.txt' );
        
        $handle  = fopen('php://output', 'w');
        fwrite( $handle , $output );
        fclose( $handle );
        
        exit;
    }

}

new WcOrderExportForDHL();
