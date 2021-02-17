<?php
/*
 * Plugin Name: Woocommerce Pledg
 * Author: Lucas Fougeras
 * Text Domain: woocommerce-pledg
 * Domain Path: /languages/
 * Plugin URI: https://pledg.co
 * Description: Instalment payment, simple and accessible.
 * Author URI: https://fougeras.me
 * Version: 2.2.4
 */

use Firebase\JWT\JWT;

define('WOOCOMMERCE_PLEDG_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
define('WOOCOMMERCE_PLEDG_PLUGIN_DIR_URL', plugin_dir_URL( __FILE__ ));

/**
 * Load dynamically the textdomain (will work even if the plugin dir name has been changed)
 */
preg_match('/wp-content\/plugins\/([a-zA-Z0-9\-]+)\//', WOOCOMMERCE_PLEDG_PLUGIN_DIR, $s);
load_plugin_textdomain( 'woocommerce-pledg', false, $s[1].'/languages');

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'pledg_add_gateway_class' );
function pledg_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Pledg_Gateway_1';
	$gateways[] = 'WC_Pledg_Gateway_2';
	$gateways[] = 'WC_Pledg_Gateway_3';
	$gateways[] = 'WC_Pledg_Gateway_4';
	$gateways[] = 'WC_Pledg_Gateway_5';
	$gateways[] = 'WC_Pledg_Gateway_6';
	return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'pledg_init_gateway_class' );
function pledg_init_gateway_class() {
	require_once WOOCOMMERCE_PLEDG_PLUGIN_DIR . '/dist/vendor/autoload.php';
	require_once WOOCOMMERCE_PLEDG_PLUGIN_DIR . '/includes/classes/WC_Pledg_Gateway.php';
	require_once WOOCOMMERCE_PLEDG_PLUGIN_DIR . '/includes/classes/WC_Pledg_REST_Controller.php';
	$wh = new WC_Webhook_REST_Controller();
	$wh->register_routes();

	class WC_Pledg_Gateway_1 extends WC_Pledg_Gateway {

 		public function __construct() {
			$this->id = 'pledg';
			parent::__construct();
 		}
 	}

 	class WC_Pledg_Gateway_2 extends WC_Pledg_Gateway {

 		public function __construct() {
			$this->id = 'pledg2';
            parent::__construct();
        }
    }


 	class WC_Pledg_Gateway_3 extends WC_Pledg_Gateway {

 		public function __construct() {
			$this->id = 'pledg3';
            parent::__construct();
        }
    }

 	class WC_Pledg_Gateway_4 extends WC_Pledg_Gateway {

 		public function __construct() {
			$this->id = 'pledg4';
            parent::__construct();
        }
    }

 	class WC_Pledg_Gateway_5 extends WC_Pledg_Gateway {

 		public function __construct() {
			$this->id = 'pledg5';
            parent::__construct();
        }
    }

 	class WC_Pledg_Gateway_6 extends WC_Pledg_Gateway {

 		public function __construct() {
			$this->id = 'pledg6';
            parent::__construct();
        }
    }

}

add_action('admin_enqueue_scripts', 'wc_pledg_admin_enqueue_script');

function wc_pledg_admin_enqueue_script($hook){
	if($hook !== "woocommerce_page_wc-settings"){	return;	}
	
	// Register the script
	wp_register_script( 'pledg_admin', WOOCOMMERCE_PLEDG_PLUGIN_DIR_URL . '/assets/js/pledg_admin.js',  'jQuery', false, true);
	
	// Localize the script with new data
	$pledg_trad = array(
		'modal_button' => __( 'Set logo', 'woocommerce-pledg' ),
		'modal_title' => __( 'Select logo for Pledg payment', 'woocommerce-pledg' ),
	);
	wp_localize_script( 'pledg_admin', 'pledg_trad', $pledg_trad );
	
	// Enqueued script with localized data.
	wp_enqueue_script( 'pledg_admin' );
	
}

add_filter('woocommerce_order_cancelled_notice', 'cancel_order_notice', 10);
add_filter('woocommerce_order_cancelled_notice_type', 'cancel_order_notice_type', 10);

/**
 * Add a custom notice to notify that the order has been canceled due to "msg" reason
 * Mark it as error type as well
 * But check that the type of pledg_error is not 'abandonment' (cancel case, should be typed as notice)
 */
function cancel_order_notice($initArg){
	if (
		isset($_GET['cancel_order']) &&
		isset($_GET['order']) &&
		isset($_GET['order_id']) &&
		isset($_GET['pledg_error']) &&
		(isset($_GET['_wpnonce']) && wp_verify_nonce(wp_unslash($_GET['_wpnonce']), 'woocommerce-cancel_order')) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		) {
			$logger = wc_get_logger();
			$order = wc_get_order($_GET['order_id']);
			$pledg_error = json_decode( stripslashes($_GET['pledg_error']));
			if( property_exists($pledg_error, 'signature') ){
				try{
					$secret = WC()->payment_gateways->payment_gateways()[$order->get_payment_method()]->get_option( 'secret_key' );
					$error_msg = JWT::decode($pledg_error->signature, $secret, array('HS256')	);
					$cancel = ($error_msg->error->type === "abandonment") ;
					$error_msg = $error_msg->error->message;
				} catch (Exception $e) {
					$cancel = false;
					$error_msg = __('Unknown error : '.$e->getMessage(), 'woocommerce-pledg');
				}
			}
			else{
				$cancel = ($pledg_error->type === "abandonment") ;
				$error_msg = $pledg_error->message;
			}
			$logger->error('Pledg Error (Id : '. $order->get_id().') : '. $error_msg, array('source' => 'pledg_woocommerce_webhook'));
			$order->add_order_note('Pledg Error : '. $error_msg);
			if($cancel){
				return $initArg;
			}
			return $error_msg;
		}
}
function cancel_order_notice_type($initArg){
	if (
		isset($_GET['cancel_order']) &&
		isset($_GET['order']) &&
		isset($_GET['order_id']) &&
		isset($_GET['pledg_error']) &&
		(isset($_GET['_wpnonce']) && wp_verify_nonce(wp_unslash($_GET['_wpnonce']), 'woocommerce-cancel_order')) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		) {
			$order = wc_get_order($_GET['order_id']);
			$pledg_error = json_decode( stripslashes($_GET['pledg_error']));
			if( property_exists($pledg_error, 'signature') ){
				try{
					$secret = WC()->payment_gateways->payment_gateways()[$order->get_payment_method()]->get_option( 'secret_key' );
					$error_msg = JWT::decode($pledg_error->signature, $secret, array('HS256')	);
					$cancel = ($error_msg->error->type === "abandonment") ;
				} catch (Exception $e) {
					$cancel = false;
				}
			}
			else{
				$cancel = ($pledg_error->type === "abandonment") ;
			}
			if($cancel){
				return $initArg;
			}
			return 'error';
		}
}