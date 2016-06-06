<?php
/**
 * Plugin Name: WooCommerce Order export for DHL
 * Description: Export orders from WooCommerce for DHL.
 * Author: David Kiss
 * Version: 1.1
 */
 /**
 * Todo:
 * - wc settings area
 */

// No direct access
defined( 'ABSPATH' ) or die;

// Restrict this plugin only to admin area
if( is_admin( ) && !class_exists( 'WcOrderExportForDHL' ) ) {

    class WcOrderExportForDHL {
        
        private $pluginDir;
        
        private $templatesDir;
        
        public function __construct( ) {
            
            $this->setBasedirs();
            $this->setHooks();
        }
        
        private function setBasedirs( ) {
            
            $this->pluginDir = str_replace( '/', DIRECTORY_SEPARATOR, plugin_dir_path( __FILE__ ) );
            
            $this->templatesDir = $this->pluginDir . 'templates' . DIRECTORY_SEPARATOR;
        }
        
        private function setHooks( ) {
            
            add_action( 'admin_footer-edit.php', array( $this, 'generate_admin_footer' ) );
            add_action( 'load-edit.php', array( $this, 'generate_export_for_dhl' ) );
        }
        
        private function loadTemplate( $template, & $orderData ) {
            
            ob_start();
            require( $this->templatesDir . $template );
            $return = ob_get_contents();
            ob_end_clean();
            
            return $return;
        }
        
        /**
         * Add some JS to append our new export option to the bulk actions list.
         */
        public function generate_admin_footer( ) {
            
            global $post_type;

            if( $post_type === 'shop_order' ) {
                echo '
                <script>
                    jQuery(document).ready(function() {
                        jQuery(\'<option>\').val(\'generate_export_for_dhl\').text(\'' . __( 'Export a DHL számára' ) . '\').appendTo(\'select[name="action"], select[name="action2"]\');
                    });
                </script>';
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
            
            foreach ( $post_ids as $order_id ) {
                
                $order = new WC_Order_Factory();
                $order = $order->get_order( $order_id );
                
                $name = $order->get_formatted_billing_full_name();
                
                $order_details = array(
                    @$order->shipping_company ? $order->shipping_company : $name,
                    '',
                    $name,
                    trim( $order->shipping_address_1 ) . ' ' . trim( $order->shipping_address_2 ),
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
                
                $orderData = array(
                    'order_id' => & $order_id,
                    'order' => & $order,
                    'order_details' => & $order_details
                );
                
                $output .= $this->loadTemplate( 'shipment.php', $orderData );
                
                $output .= $this->loadTemplate( 'sender.php', $orderData );
                
                $output .= $this->loadTemplate( 'receiver.php', $orderData );

                $items = $order->get_items();
                if( !empty( $items ) ) {
                    
                    foreach( $items as $item ) {
                        
                        $weight = 1; //todo calc, etc
                        
                        /*todo calculate
                        
                        $weight = 0;
                        
                        if ( $item['product_id'] < 1 ) continue;
                        
                        $product = $order->get_product_from_item( $item );
                        if ( ! $product->is_virtual() ) {
                            $weight += $product->get_weight() * $item['qty'];
                        }*/
                        
                        $orderData['item'] = array(
                            'weight' => $weight
                        );
                        
                        $output .= $this->loadTemplate( 'item.php', $orderData );
                    }
                }
                
                //only one item at this time
                
            }
            
            $output = iconv('UTF-8', 'ISO-8859-2', $output);
            
            header( 'Content-Type: text/csv;charset=iso-8859-2' );
            header( 'Content-Disposition: attachment;filename="WC_orders_DHL_' . date( 'Y-m-d_H-i-s' ) . '.txt' );
            
            $handle  = fopen('php://output', 'w');
            fwrite( $handle , $output );
            fclose( $handle );
            
            exit;
        }

    }
    
    new WcOrderExportForDHL();

}
