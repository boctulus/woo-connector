document.addEventListener('DOMContentLoaded', () => {
    document.getElementById("wh_connector_form").addEventListener('submit', function(event){
        register_webhooks();
        event.preventDefault();
        return;
    });
});

function register_webhooks(){
              
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
        if (typeof response['error'] != 'undefined' && response['error'] != null){
            addNotice(response['error'], 'danger', 'alert_container', true);
            return;
        }

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
            if (response[i]['shop'] == null || typeof response[i]['shop'] == 'undefined'){

                if (response[i]['vendor']['slug'] == null || typeof response[i]['vendor']['slug'] == 'undefined'){
                    addNotice("Varios posibles errores en el archivo de api keys", 'danger');
                } else {
                    addNotice(`No se encontró el 'shop' para el vendor '${response[i]['vendor']['slug']}'. Posible error en el archivo de api keys`, 'danger');
                }
                
                return;
            }

            let shop   = response[i]['shop']; // "act-and-beee"
            let vendor = response[i]['vendor']['slug']; 

            //console.log({vendor: vendor, shop : shop});

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
                //console.log(response);

                /*
                    {
                        "weboook_product_create": null,
                        "weboook_product_update": null,
                        "weboook_product_delete": null
                    }
                */

                if (response["weboook_product_create"] != null){
                    addNotice("WebHook para 'product create' listo para " + vendor, 'success');
                } 

                if (response["weboook_product_update"] != null){
                    setTimeout(function(){
                        addNotice("WebHook para 'product update' listo para " + vendor, 'success');
                    },500);
                } 

                if (response["weboook_product_delete"] != null){
                    setTimeout(function(){
                        addNotice("WebHook para 'product delete' listo para " + vendor, 'success');
                    },1000);
                } 
               
            })
            .fail(function (jqXHR, textStatus) {
                console.log(jqXHR);
                console.log(textStatus);
                addNotice('Error desconocido', 'danger');
            });

        }
        		
    })
    .fail(function (jqXHR, textStatus) {
        console.log(jqXHR);
        console.log(textStatus);
        addNotice('Error desconocido', 'danger',  'alert_container', true);
    });
}