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
                    title: "'.( ($title)? implode(', ', $title) : '' ).'",
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
                        city: jQuery("#billing_address_1").val() + " " + jQuery("#billing_address_2").val(),
                        zipcode: jQuery("#billing_postcode").val(),
                        stateProvince: "",
                        country: jQuery("#billing_country").val()
                    },
                    externalCheckoutValidation: true,
                    showCloseButton: false,
                    onCheckoutFormStatusChange: function(readiness){
                        button_order.disabled = !readiness;
                    },
                    // the function which triggers the payment
                    onSuccess: function (resultpayment) {console.log(resultpayment);
                        if (resultpayment.transaction === undefined) {
                            jQuery(\'input[name="pledg'. $this->id . '"]\').val(resultpayment.uid);
                        } else {
                            jQuery(\'input[name="pledg'. $this->id . '"]\').val(resultpayment.transaction.id);
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