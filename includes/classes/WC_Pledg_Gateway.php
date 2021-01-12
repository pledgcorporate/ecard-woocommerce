<?php


class WC_Pledg_Gateway extends WC_Payment_Gateway {

    public function __construct() {

        //$this->icon = '/wp-content/plugins/woocommerce-pledg/logo.png';
        $this->has_fields = true;
        $this->method_title = 'Pledg';
        $this->method_description = ( ($this->get_option( 'description' ) ) ? $this->get_option( 'description' ) : 'Le paiement en plusieurs fois, simple et accessible.');

        $this->supports = array(
            'products'
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );

        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

        add_action( 'woocommerce_api_wc_gateway_pledg', array( $this, 'check_ipn_response' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

    }

    private function create_metadata() {
		$metadata = [];
		
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
				$md_product['description'] = $product->get_short_description();
				$md_product['unit_amount_cents'] = $cart_item['data']->get_price();
				$md_product['category'] = '';
				$md_product['slug'] = $product->get_slug();
								
				$md_merchant_data = [];
				$md_merchant_data['category_path'] = '';
				$md_merchant_data['global_trade_item_number'] = '';
				$md_merchant_data['manufacturer_part_number'] = '';
				$md_merchant_data['brand'] = '';

				$md_product['merchant_data'] = $md_merchant_data;
				array_push($md_products, $md_product);
			}
			$metadata['products'] = $md_products;
		}
		catch (Exception $exp) {
			wc_get_logger()->error( 'pledg_create_metadata - exception : '.$exp->getMessage(), array( 'source' => 'pledg_woocommerce' ) );
		}
		return json_encode($metadata);
	}

    public function payment_fields() {
        global $wp;
        $user                 = wp_get_current_user();
        $display_tokenization = $this->supports( 'tokenization' ) && is_checkout() && $this->saved_cards;
        $total                = WC()->cart->total * 100;
        $user_email           = '';
        $description          = $this->get_description();
        $description          = ! empty( $description ) ? $description : '';
        $firstname            = '';
        $lastname             = '';
        $site_name            = get_bloginfo( 'name' );


        $items = WC()->cart->get_cart();
        $title = [];
        foreach($items as $item) {
            array_push($title, stripslashes($item['data']->get_name()));
        }
        echo '<input type="hidden" name="pledg'. $this->id . '">';
        echo '<style>

        .pledg-iframe-overlay {
            background: transparent;
        }
        #payment .payment_methods li .payment_box.payment_method_'. $this->id . ' {
            padding: 0;
        }
        #payment .payment_methods li .payment_box.payment_method_'. $this->id . ' iframe {
            box-shadow: 0px 0px 3px rgba(0, 0, 0, 0.1);
            border-radius: 0;
        }
        
        </style>';
        echo '<div class="containerPlegd" id="containerPlegd' . $this->id . '">';
        echo '<div class=\'woocommerce-error\'>'.__( 'Merci de compléter vos informations personnelles', 'woocommerce-pledg' ).'</div>';
        echo '</div>';
        echo '<script>';
        echo '
        if(isFormComplete()) {
            jQuery("#containerPlegd'.$this->id.'").html("");
            var button = document.querySelector("label[for=\'payment_method_'.$this->id.'\']");
            var button_order = document.querySelector("#place_order");

                    var pledg'.$this->id.' = new Pledg(button, {
                    // the Pledg merchant id
                    merchantUid: "'.$this->get_option('merchant_id').'",
                    // the amount **in cents** of the purchase
                    amountCents: '.$total.',
                    // the title of the purchase
                    title: "'.addslashes( ($title)? implode(', ', $title) : '' ).'",
                    // the subtitle of the purchase
                    subtitle : "'.addslashes($site_name).'",
                    // the currency of the purchase
					currency : "'.get_woocommerce_currency().'",
					// the lang of the user
					lang : "'.get_locale().'",
					// the country code of the user
					country_code : jQuery("#billing_country").val(),
					// the metadata of the purchase
					metadata: JSON.parse("'.addslashes($this->create_metadata()).'"),
					// the email of the user
                    email: jQuery("#billing_email").val(),
                    // the subtitle of the purchase
                    // the reference of the purchase
                    reference: "order_'.rand().'",
                    // the name of the customer (optional, to improve anti-fraud)
                    firstName: jQuery("#billing_first_name").val(),
                    lastName: jQuery("#billing_last_name").val(),
                    phoneNumber: jQuery("#billing_phone").val(),
                    containerElement: document.querySelector("#containerPlegd'.$this->id.'"),
                    // the shipping address (optional, to improve anti-fraud)
                    address: {
                        street:  jQuery("#billing_address_1").val() + " " + jQuery("#billing_address_2").val(),
                        city: jQuery("#billing_city").val(),
                        zipcode: jQuery("#billing_postcode").val(),
                        stateProvince: "",
                        country: jQuery("#billing_country").val()
                    },
                    shippingAddress: {
						street:  jQuery("#shipping_address_1").val() + " " + jQuery("#shipping_address_2").val(),
                        city: jQuery("#shipping_city").val(),
                        zipcode: jQuery("#shipping_postcode").val(),
                        stateProvince: "",
                        country: jQuery("#shipping_country").val()
					},
                    externalCheckoutValidation: true,
                    showCloseButton: false,
                    onCheckoutFormStatusChange: function(readiness){
                        button_order.disabled = !readiness;
                    },
                    // the function which triggers the payment
                    onSuccess: function (resultpayment) {console.log(resultpayment);
                        if (resultpayment.purchase === undefined) {
                            jQuery(\'input[name="pledg'. $this->id . '"]\').val(resultpayment.uid);
                        } else {
                            jQuery(\'input[name="pledg'. $this->id . '"]\').val(resultpayment.purchase.reference);
                        }
                        
                        jQuery(\'form[name="checkout"]\').submit();
                    },
                    // the function which can be used to handle the errors
                    onError: function (error) {
                        // see the "Errors" section for more a detailed explanation
                        button_order.disabled = true;
                    },
                    onOpen: function() {
                        jQuery("#containerPlegd'.$this->id.' .pledg-iframe-wrapper").show();
                        console.log(jQuery("#containerPlegd'.$this->id.' .pledg-iframe-wrapper"));
                        button_order.disabled = true;
                        button_order.type = "button";
                    }
                });
        }';
        echo '</script>';
    }

    public function init_form_fields(){

        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Activer/Désactiver',
                'label'       => 'Activer Pledg',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'mode' => array(
                'title'       => 'Mode développement/Mode production',
                'label'       => 'Mode production',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Titre',
                'type'        => 'text',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'merchant_id' => array(
                'title'       => 'Merchant ID',
                'type'        => 'text',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
            )
        );

    }

    public function process_payment( $order_id ) {

        global $woocommerce;

        $pledg_url = ( ($this->get_option( 'mode' ) == 'yes' )? 'https://front.ecard.pledg.co' : 'https://staging.front.ecard.pledg.co' );

        $order = wc_get_order( $order_id );

        if (isset($_POST['pledg'. $this->id]) && !empty($_POST['pledg'. $this->id])) {

            $order->payment_complete($_POST['pledg'. $this->id]);

            return array(
                'result' 	=> 'success',
                'redirect'	=> $this->get_return_url( $order )
            );
        }

        return array(
            'result'   => 'fail',
            'redirect' => '',
        );

    }

    public function payment_scripts() {

        if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
            return;
        }

        // if our payment gateway is disabled, we do not have to enqueue JS too
        if ( 'no' === $this->enabled ) {
            return;
        }

        // no reason to enqueue JavaScript if API keys are not set
        if ( empty( $this->get_option('merchant_id') ) ) {
            return;
        }

        if ($this->get_option( 'mode' ) == 'yes') {
            wp_enqueue_script( 'pledg_js' . $this->id, 'https://s3-eu-west-1.amazonaws.com/pledg-assets/ecard-plugin/master/plugin.min.js' );
        } else {
            wp_enqueue_script( 'pledg_js' . $this->id, 'https://s3-eu-west-1.amazonaws.com/pledg-assets/ecard-plugin/staging/plugin.min.js' );
        }


        wp_register_script( 'woocommerce_pledg', plugins_url( 'assets/js/pledg.js', __DIR__ . '/../../../'), array( 'jquery', 'pledg_js' . $this->id ) );
        wp_enqueue_script( 'woocommerce_pledg');
    }

}
