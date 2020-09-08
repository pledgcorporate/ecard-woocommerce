<?php
/*
 * Plugin Name: WooCommerce Pledg
 * Plugin URI: https://pledg.co
 * Description: Le paiement en plusieurs fois, simple et accessible.
 * Author: GiniDev
 * Author URI: https://ginidev.com
 * Version: 1.0.1
 */



define('WOOCOMMERCE_PLEDG_PLUGIN_DIR', plugin_dir_path( __FILE__ ));

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
    require_once __DIR__ . '/includes/classes/WC_Pledg_Gateway.php';
	class WC_Pledg_Gateway_1 extends WC_Pledg_Gateway {

 		public function __construct() {
			$this->id = 'pledg';
			parent::__construct();
 		}
 	}

 	/**/

 	class WC_Pledg_Gateway_2 extends WC_Pledg_Gateway {

 		public function __construct() {
			$this->id = 'pledg2';
            parent::__construct();
        }
    }


 	/**/

 	class WC_Pledg_Gateway_3 extends WC_Pledg_Gateway {

 		public function __construct() {
			$this->id = 'pledg3';
            parent::__construct();
        }
    }

 	/**/

 	class WC_Pledg_Gateway_4 extends WC_Pledg_Gateway {

 		public function __construct() {
			$this->id = 'pledg4';
            parent::__construct();
        }
    }

 	/**/

 	class WC_Pledg_Gateway_5 extends WC_Pledg_Gateway {

 		public function __construct() {
			$this->id = 'pledg5';
            parent::__construct();
        }
    }

 	/**/

 	class WC_Pledg_Gateway_6 extends WC_Pledg_Gateway {

 		public function __construct() {
			$this->id = 'pledg6';
            parent::__construct();
        }
    }

}


