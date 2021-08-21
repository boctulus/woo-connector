document.addEventListener('DOMContentLoaded', () => {
    document.getElementById("wh_connector_form").addEventListener('submit', function(event){
        register_webhooks();
        event.preventDefault();
        return;
    });
});

function register_webhooks(){
    /*
    jQuery(document).ajaxSend(function() {
        jQuery("#overlay").fadeIn(300);　
    });		
    */			
            
    let url = '/index.php/wp-json/connector/v1/shops'; 

    var settings = {
        "url": url,
        "method": "GET",
        "timeout": 0,
        "headers": {
            "Content-Type": "text/plain"
        }
    };

    jQuery.ajax(settings)
    .done(function (response) {

        //console.log(response);

        /*
            [
                {
                    "vendor": {
                        "url": "https://f920c96f987d.ngrok.io",
                        "slug": "hupit",
                        "cms": "shopi",
                        "enabled": true
                    },
                    "shop": "act-and-beee"
                }
            ]
        */

        for (var i=0; i<response.length; i++){
            let shop   = response[i]['shop']; // "act-and-beee"
            let vendor = response[i]['vendor']['slug']; 

            let url = '/index.php/wp-json/connector/v1/webhooks/register'; 

            let data = JSON.stringify({ shop : shop });

            var settings = {
                "url": url,
                "method": "POST",
                "timeout": 0,
                "headers": {
                    "Content-Type": "text/plain"
                },
                "data": data
            };

            jQuery.ajax(settings)
            .done(function (response) {
                console.log(response);

                /*
                    {
                        "weboook_product_create": null,
                        "weboook_product_update": null,
                        "weboook_product_delete": null
                    }
                */

                if (response["weboook_product_create"] != null){
                    console.log("WebHook para 'product create' listo para " + vendor);
                } 

                if (response["weboook_product_update"] != null){
                    console.log("WebHook para 'product update' listo para " + vendor);
                } 

                if (response["weboook_product_delete"] != null){
                    console.log("WebHook para 'product delete' listo para " + vendor);
                } 

            })
            .fail(function (jqXHR, textStatus) {
                console.log(jqXHR);
                console.log(textStatus);
                //addNotice('Error desconocido', 'danger', 'warning', 'alert_container', true);
            });

        }


        /*
        setTimeout(function(){
            jQuery("#overlay").fadeOut(300);
        },500);
        */			
    })
    .fail(function (jqXHR, textStatus) {
        console.log(jqXHR);
        console.log(textStatus);
        //addNotice('Error desconocido', 'danger', 'warning', 'alert_container', true);
    });
}