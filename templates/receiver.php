<?php defined( 'ABSPATH' ) or die; ?><?php echo $orderData['order_id']; ?>|DHL-RECEIVER|<?php echo str_replace( array( '|', '(%%)' ), array( ':', '|' ), implode( '(%%)', $orderData['order_details'] ) ); ?>

