console.log('PLEDGJS');
console.log(jQuery);

var pledgGateways = ['pledg', 'pledg2', 'pledg3', 'pledg4', 'pledg5', 'pledg6'];

var requiredFields = [
    'billing_first_name',
    'billing_last_name',
    'billing_country',
    'billing_address_1',
    'billing_postcode',
    'billing_city',
    'billing_phone',
    'billing_email',
];
var requiredShippingFields = [
    'shipping_first_name',
    'shipping_last_name',
    'shipping_country',
    'shipping_address_1',
    'shipping_postcode',
    'shipping_city',
];
var requiredFieldsFlat = '';

jQuery(function(){
    jQuery( document.body ).on( 'updated_checkout updated_shipping_method init_checkout payment_method_selected', function(e) {

    });
    jQuery( document.body ).on( 'updated_checkout', function(e) {
        if(pledgGateways.includes(jQuery('form[name="checkout"] input[name="payment_method"]:checked').val())) {
            // On simule le clique sur le bouton
            jQuery("label[for='payment_method_"+jQuery('form[name="checkout"] input[name="payment_method"]:checked').val()+"']").trigger('click');
        } else {
            document.querySelector("#place_order").disabled = false;
        }
    });
    jQuery( document.body ).on( 'payment_method_selected', function(e) {
        if(!pledgGateways.includes(jQuery('form[name="checkout"] input[name="payment_method"]:checked').val())) {
            document.querySelector("#place_order").disabled = false;
            document.querySelector("#place_order").type = "submit";
        }
        else if (!isFormComplete()){
            document.querySelector("#place_order").disabled = true;
        }
    });
    jQuery(document).on( 'click', '#place_order', function(e){
        if(pledgGateways.includes(jQuery('form[name="checkout"] input[name="payment_method"]:checked').val())) {
            window['pledg'+jQuery('form[name="checkout"] input[name="payment_method"]:checked').val()].validateCheckout();
            document.querySelector("#place_order").disabled = true;
        }
    });
    jQuery( 'form.checkout' ).on( 'validate change', function(e) {
		if(e.target.name != 'payment_method'){
            if(isFormComplete()){
                jQuery( document.body ).trigger( 'update_checkout');
            }else{
                jQuery('.pledg-iframe-wrapper').html('<div class=\'woocommerce-error\'>Merci de compléter vos informations personnelles</div>');
            }
        }
    });
});

function isFormComplete() {
    var flag = true;
    requiredFields.forEach((item, index) => {
        if (jQuery('#' + item).val() == '') {
            flag = false;
        }
    });
    if (jQuery('#ship-to-different-address-checkbox').prop('checked')) {
        requiredShippingFields.forEach((item, index) => {
            if (jQuery('#' + item).val() == '') {
                flag = false
            }
        });
    }
    return flag;
}

/*



var pledgGateways = ['pledg', 'pledg2', 'pledg3', 'pledg4', 'pledg5', 'pledg6'];
var current_gateway = '';


jQuery(function(){
    current_gateway = jQuery('form[name="checkout"] input[name="payment_method"]:checked').val();
    jQuery( document.body ).on( 'updated_checkout updated_shipping_method init_checkout payment_method_selected', function(e) {
        console.log(e)
        usingGateway();
    });
});


function usingGateway(){
    if(pledgGateways.includes(jQuery('form[name="checkout"] input[name="payment_method"]:checked').val())) {
        var requiredFieldsFlatTmp = getFlatDatas();
        if (requiredFieldsFlat != requiredFieldsFlatTmp || current_gateway != jQuery('form[name="checkout"] input[name="payment_method"]:checked').val()) {
            current_gateway = jQuery('form[name="checkout"] input[name="payment_method"]:checked').val();
            if (requiredFieldsFlat = requiredFieldsFlatTmp) {
                window[current_gateway].isLoad = false;
            }
            var formComplete = isFormComplete();
            if ((!window[current_gateway].isLoad) && isFormComplete()) {
                window[current_gateway].load();
            }
        }
        if (!formComplete) {
            window[current_gateway].displayErrorForm();
        }
        //Etc etc
    }
}

function getFlatDatas() {
    var flat = '';
    requiredFields.forEach((item, index) => {
        flat += jQuery('#' + item).val();
    });
    if (jQuery('#ship-to-different-address-checkbox').prop('checked')) {
        requiredShippingFields.forEach((item, index) => {
            flat += jQuery('#' + item).val();
        });
    }
    if (jQuery('input[name="shipping_method[0]"]').length > 0) {
        flat += jQuery('input[name="shipping_method[0]"]:checked').val();
    }

    return flat;
}



*/
