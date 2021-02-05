<?php

use Firebase\JWT\JWT;

/**
 * Pledg Payment Gateway
 *
 * Provides a form based Gateway for Pledg pyament solution to WooCommerce
 *
 * @class       WC_Pledg_Gateway
 * @extends     WC_Payment_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Pledg_Gateway extends WC_Payment_Gateway {

    /**
	 * Minimum transaction amount, zero does not define a minimum.
	 *
	 * @var int
	 */
	public $min_amount = 0;

    /**
     * Languages for Title and Description
     */
    public $langs =  array('fr', 'en', 'de', 'es', 'it', 'nl');

    public function __construct() {
        $this->icon = ($this->get_option( 'logo' )==='') ? WOOCOMMERCE_PLEDG_PLUGIN_DIR_URL . 'logo.jpg' : $this->get_option( 'logo' ) ;
        $this->has_fields = true;
        $this->min_amount = $this->get_option('minAmount');
        $this->max_amount = $this->get_option('maxAmount');
        $this->method_title = 'Pledg';
        $this->method_description = ( ($this->get_option( 'description' ) ) ? $this->get_option( 'description' ) : __('Instalment payment, simple and accessible.', 'woocommerce-pledg'));

        $this->supports = array(
            'products'
        );

        $this->init_form_fields();
        $this->init_settings();
        if(in_array(substr(get_locale(), 0, 2), $this->langs)){
            $this->title = $this->get_option( 'title_'.substr(get_locale(), 0, 2) );
            $this->description = $this->get_option( 'description_'.substr(get_locale(), 0, 2) );
        }
        else {
            $this->title = $this->get_option( 'title_en' );
            $this->description = $this->get_option( 'description_en' );
        }

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        if(is_checkout()){
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        }
    }

    /**
     *  Function to create metadata
     */
    private function create_metadata() {
        $metadata = [];
		$metadata['plugin'] = 'woocommerce-pledg-plugin' . get_file_data(WOOCOMMERCE_PLEDG_PLUGIN_DIR . "woocommerce-pledg.php", array('version' => 'version'))['version'];
		try
		{
			// Delivery
			foreach ( WC()->cart->get_shipping_packages() as $package_id => $package ) {
				// Check if a shipping for the current package exist
				if ( WC()->session->__isset( 'shipping_for_package_'.$package_id ) ) {
					// Loop through shipping rates for the current package
					foreach ( WC()->session->get( 'shipping_for_package_'.$package_id )['rates'] as $shipping_rate_id => $shipping_rate ) {
						if( WC()->session->get('chosen_shipping_methods')[0] == $shipping_rate_id ){
							$metadata['delivery_mode'] = $shipping_rate->get_method_id() == 'local_pickup' ? 'relay' : 'home'; 
							$metadata['delivery_speed'] = 0;
							$metadata['delivery_label'] = $shipping_rate->get_label();
							$metadata['delivery_cost'] = $shipping_rate->get_cost();
							$metadata['delivery_tax_cost'] = $shipping_rate->get_shipping_tax();
						}
					}
				}
			}
			
			// Products
			$md_products = [];
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$md_product = [];
				
				$product =  wc_get_product( $cart_item['data']->get_id());
				$md_product['reference'] = $product->get_id();
				$md_product['type'] = $product->get_virtual() == false ? 'physical' : 'virtual';
				$md_product['quantity'] = $cart_item['quantity'];
				$md_product['name'] = $product->get_name();
				$md_product['unit_amount_cents'] = intval($cart_item['data']->get_price() * 100);
				$md_product['category'] = '';
				$md_product['slug'] = $product->get_slug();
				array_push($md_products, $md_product);
			}
			$metadata['products'] = $md_products;
		}
		catch (Exception $exp) {
			wc_get_logger()->error( 'pledg_create_metadata - exception : '.$exp->getMessage(), array( 'source' => 'pledg_woocommerce' ) );
		}
		return $metadata;
	}

    /**
     * Function to declare admin options
     */
    public function init_form_fields(){

        $lang = $this->langs;

        
        $a_titles = array(
            'title_lang' => array(
                'title'       => __('Title lang', 'woocommerce-pledg'),
                'type'        => 'select',
                'options'     => $lang,
                'default'     => 'en',
            )
        );
        $a_descriptions = array(
            'description_lang' => array(
                'title'       => __('Description lang', 'woocommerce-pledg'),
                'type'        => 'select',
                'options'     => $lang,
                'default'     => 'en',
            )
        );

        foreach ($lang as $key=>$value){
            $a_titles = array_merge($a_titles,
                array(
                    ('title_' . $value) => array(
                        'title'       => __('Title', 'woocommerce-pledg') . ' (' . $value . ')',
                        'type'        => 'text',
                        'default'     => '',
                    )
                )
            );
            $a_descriptions = array_merge($a_descriptions,
                array(
                    ('description_' . $value) => array(
                        'title'       => __('Description', 'woocommerce-pledg') . ' (' . $value . ')',
                        'type'        => 'text',
                        'default'     => '',
                    )
                )
            );
        }

        $this->form_fields = array_merge(
            array(
                'enabled' => array(
                    'title'       => __('Activate/Deactivate', 'woocommerce-pledg'),
                    'label'       => __('Activate Pledg', 'woocommerce-pledg'),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'mode' => array(
                    'title'       => __('Sandbox mode/Production Mode', 'woocommerce-pledg'),
                    'label'       => __('Production Mode', 'woocommerce-pledg'),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'merchant_id' => array(
                    'title'       => __('Merchant ID', 'woocommerce-pledg'),
                    'type'        => 'text',
                    'default'     => '',
                ),
                'secret_key' => array(
                    'title'       => __('Secret Key', 'woocommerce-pledg'),
                    'type'        => 'text',
                    'default'     => '',
                )),
            $a_titles,
            $a_descriptions,
            array(
                'minAmount' => array(
                    'title'       => __('Order minimum amount', 'woocommerce-pledg'),
                    'type'        => 'number',
                    'desc'        => true,
                    'desc_tip'    => __('Minimum transaction amount, zero does not define a minimum', 'woocommerce-pledg'),
                    'default'     => 0
                ),
                'maxAmount' => array(
                    'title'       => __('Order maximum amount', 'woocommerce-pledg'),
                    'type'        => 'number',
                    'desc'        => true,
                    'desc_tip'    => __('Maximum transaction amount, zero does not define a maximum', 'woocommerce-pledg'),
                    'default'     => 0
                ),
                'logo' => array(
                    'title'       => __('Logo', 'woocommerce-pledg'),
                    'type'        => 'text',
                    'desc'        => true,
                    'desc_tip'    => __('Logo to show next to payment method. Click on the input box to add an image or keep blank for default image.', 'woocommerce-pledg'),
                    'default'     => ''
                )
            )
        );

    }

    /**
     * Function called once button "Place order" has been called
     * Redirecting to Pledg front
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        return array(
            'result' 	=> 'success',
            'redirect'	=> $this->get_request_url( $order )
        );

    }

    /**
     * Function to manage the creation of the url to Pledg Front
     * redirectUrl : URL redirected by Pledg once payment has succeeded
     * cancelUrl : URL redirected by Pledg when payment has been canceled
     * paymentNotificationUrl : Webhook used by Pledg to update the payment status
     */
    public function get_request_url(WC_Order $order)
    {
        $endpoint = (($this->get_option( 'mode' ) == 'yes' )? 'https://front.ecard.pledg.co/purchase?' : 'https://staging.front.ecard.pledg.co/purchase?' );
        $items = $order->get_items();
        $id = $order->get_id();
        $title = [];
        foreach($items as $item) {
            array_push($title, stripslashes($item->get_name()));
        }

        $ref = "Pledg_" . $order->get_id() . "_" . time();
        $args =
            array(
                'merchantUid'       => $this->get_option('merchant_id'),
                'amountCents'       => intval($order->get_total() * 100),
                'title'             => addslashes( ($title)? implode(', ', $title) : '' ), 
                'subtitle'          => addslashes(get_bloginfo( 'name' )),
                'currency'          => get_woocommerce_currency(),
                'lang'              => get_locale(),
                'showCloseButton'   => true,
                'countryCode'       => $order->get_billing_country(), 
                'metadata'          => $this->create_metadata(),
                'email'             => $order->get_billing_email(),
                'reference'         => $ref,
                'firstName'         => $order->get_billing_first_name(),
                'lastName'          => $order->get_billing_last_name(),
                'phoneNumber'       => $order->get_billing_phone(),
                'address'           => array(
                    'street'            => $order->get_billing_address_1(),
                    'city'              => $order->get_billing_city(),
                    'zipcode'           => $order->get_billing_postcode(),
                    'stateProvince'     => "",
                    'country'           => $order->get_billing_country(),
                ),
                'shippingAddress'   => array(
                    'street'            => $order->get_shipping_address_1(),
                    'city'              => $order->get_shipping_city(),
                    'zipcode'           => $order->get_shipping_postcode(),
                    'stateProvince'     => "",
                    'country'           => $order->get_shipping_country(),
                ),
                'redirectUrl'        => esc_url_raw( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) ),
                'cancelUrl' => esc_url_raw( $order->get_cancel_order_url_raw(wc_get_checkout_url()) ),
                'paymentNotificationUrl' => esc_url_raw( WC_Webhook_REST_Controller::get_order_webhook_from_id($id) ),
            );

        if(empty($this->get_option( 'secret_key' ))){
            $args['metadata'] = json_encode($args['metadata']);
            $args['address'] = json_encode($args['address']);
            $args['shippingAddress'] = json_encode($args['shippingAddress']);
            return $endpoint . http_build_query( $args, '', '&' );
        }
        else{
            $signature = $this->JWT_sign(array('data'=>$args), $this->get_option( 'secret_key' ));
            return $endpoint . $signature;
        }
    }

    /**
     * Function to JWT sign the payload.
     */
    public function JWT_sign($args, $secret){
        $signature = 'signature='.JWT::encode($args, $secret);
        return $signature;
    }

    /**
     * Returns either the locale in WP settings if avalaible in Pledg API
     * or fr_FR otherwise (list can be managed in the main plugin file)
     * NOT USED
     */
    public function get_available_locale()
    {
        $locale = get_locale();
        if(in_array($locale, ['de_DE', 'en_GB', 'es_ES', 'fr_FR', 'it_IT', 'nl_NL'])){
            return $locale;
        }
        return 'fr_FR';
    }

    /**
     * Returns either the currency in WC settings if avalaible in Pledg API
     * or EUR otherwise (list can be managed in the main plugin file)
     * NOT USED
     */
    public function get_available_currency()
    {
        $currency = get_woocommerce_currency();
        if(in_array($currency, ['EUR', 'GBP', 'CZK', 'NZD'])){
            return $currency;
        }
        return 'EUR';
    }


    public function is_available(){
        if (WC()->cart && 0 < $this->get_order_total() && $this->min_amount > 0 && $this->get_order_total() < $this->min_amount) {
            return false;
        }
        return parent::is_available();
    }

    public function payment_scripts(){
        global $pledg_payment_scripts;
        if(!isset($pledg_payment_scripts)){
            $pledg_payment_scripts = true;
            wp_enqueue_script( 'woocommerce_pledg', WOOCOMMERCE_PLEDG_PLUGIN_DIR_URL.'assets/js/pledg_payments.js', array('jquery'), false, true);
            wp_enqueue_style( 'woocommerce_pledg', WOOCOMMERCE_PLEDG_PLUGIN_DIR_URL.'assets/css/pledg_payments.css');
        }
    }

    public function payment_fields() {
        echo '<input type="hidden" name="merchantUid_'. $this->id . '" value="' . $this->get_option('merchant_id') . '"/>';
		parent::payment_fields();
	}
}
