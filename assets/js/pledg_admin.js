(function ($) {
    $(document).ready(function () {
        var el = document.querySelector('input[name^=woocommerce_pledg][name$=_logo]');
        el.addEventListener('click', function () {
            var frame = new wp.media.view.MediaFrame.Select({
                title: pledg_trad.modal_title,
                multiple: false,
                library: {
                    order: 'ASC',
                    orderby: 'title',
                    type: 'image',
                    uploadedTo: null
                },
                button: {
                    text: pledg_trad.modal_button
                }
            });
            frame.on( 'select', function() {
				el.setAttribute('value', (frame.state().get('selection').models[0].attributes.url));
			} );
            frame.open();
        });

        var title_lang = document.querySelector('select[name^=woocommerce_pledg][name$=_title_lang]');
        var description_lang = document.querySelector('select[name^=woocommerce_pledg][name$=_description_lang]');

        var titles = [];
        var descriptions = [];

        for (let i = 0; i < title_lang.options.length; i++) {
            titles.push( document.querySelector(
                'input[name^=woocommerce_pledg][name$=_title_' + title_lang.options[i].text + ']'
                ));
            if (title_lang.selectedIndex !== i){
                titles[i].parentElement.parentElement.parentElement.setAttribute('style', 'display:none');
            }
        }
        for (let i = 0; i < description_lang.options.length; i++) {
            descriptions.push( document.querySelector(
                'input[name^=woocommerce_pledg][name$=_description_' + description_lang.options[i].text + ']'
                ));
            if (description_lang.selectedIndex !== i){
                descriptions[i].parentElement.parentElement.parentElement.setAttribute('style', 'display:none');
            }
        }

        title_lang.addEventListener('change', function(){
            titles.forEach(function(v, i){
                if( i !== title_lang.selectedIndex){
                    v.parentElement.parentElement.parentElement.setAttribute('style', 'display:none');
                }
                else{
                    v.parentElement.parentElement.parentElement.setAttribute('style', 'display:');
                }
            })
        });
        description_lang.addEventListener('change', function(){
            descriptions.forEach(function(v, i){
                if( i !== description_lang.selectedIndex){
                    v.parentElement.parentElement.parentElement.setAttribute('style', 'display:none');
                }
                else{
                    v.parentElement.parentElement.parentElement.setAttribute('style', 'display:');
                }
            })
        });
    });
})(jQuery);