<?php
/*
 * Plugin Name: Woocommerce Pledg
 * Author: Lucas Fougeras
 * Text Domain: woocommerce-pledg
 * Domain Path: /languages/
 * Plugin URI: https://pledg.co
 * Description: Instalment payment, simple and accessible.
 * Author URI: https://fougeras.me
 * Version: 2.1.2
 */



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
