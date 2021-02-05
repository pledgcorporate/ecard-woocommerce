(function ($) {
  const regex = /pledg[1-5]{0,1}/;

  $( 'body' )
    .on( 'updated_checkout', function() {
      document.querySelectorAll('input[name="payment_method"]').forEach((elem) => {
        elem.addEventListener("change", function(event) {
          var item = event.target;
          if(item.value.match(regex)){
            payment_detail(item);
          }
        });
      });
      var el = document.querySelector('input[name="payment_method"]:checked');
      if(el && el.value.match(regex)){
        payment_detail(el);
      }
    });


    async function payment_detail(el){
      try{
        var box = el.parentNode.querySelector('div.payment_box');
        var child = box.querySelector('#payment-detail-container');
        if(child !== null){
          child.remove();
        }
        var merchantUid = box.querySelector('input[name=merchantUid_'+el.value).value;
        child = document.createElement('div');
        child.classList.add('spinner-parent');
        child.id = "payment-detail-container";
        child.innerHTML = '<span class="spinner-border"></span>';

        box.appendChild(child);

        // Call the API here

        // Fake return of the api call
        var ret = `
        <div class="screen-section" style="padding-top: 0px;">
          <p style="margin-top: 30px;">
            <b style="float: left;">1<sup>ère</sup> échéance le 02/02/2021</b>
            <b style="float: right; text-align: right;"> 218,00&nbsp;€<br>
              <b><span style="font-size: 0.85em;">(dont 18,00&nbsp;€ de frais) <br></span></b>
              <!---->
            </b>
          </p>
        <div style="clear: both; margin-bottom: 30px;"></div>
        <p class="deadline">
          <b style="float: left;">2<sup>e</sup> échéance le 02/03/2021</b>
          <b style="float: right;">200,00&nbsp;€</b>
        </p>
        <div style="clear: both; margin-bottom: 30px;"></div>
        <p class="deadline">
          <b style="float: left;">3<sup>e</sup> échéance le 02/04/2021</b>
          <b style="float: right;">200,00&nbsp;€</b>
        </p>
        <div style="clear: both;"></div></div>
        `;

        setTimeout(function(){
          child.innerHTML = ret;
          child.classList.remove('spinner-parent');
        }, 1000);

      } catch(e){
        console.log(e);
      }
      
    }
})(jQuery);