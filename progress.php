<?php

require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

$order_id = $_GET['order'];
$order = wc_get_order( $order_id );
$result = json_decode(stripslashes($_GET['pledg_result']));

if($result->transaction->status == 'completed'):
	
	$order->payment_complete();
	WC()->cart->empty_cart();
	wp_redirect($_GET['redirect']);

else:

	wp_redirect($_GET['redirect']);

endif;