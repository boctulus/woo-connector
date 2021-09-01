document.addEventListener('DOMContentLoaded', () => {
    let btn = document.getElementById("wh_connector_form");

    btn.addEventListener('submit', function(event){
        register_webhooks();
        btn.disabled = true;
        event.preventDefault();
        return;
    });
});

function register_webhooks(){
    let source = new EventSource("/index.php/wp-json/connector/v1/shopify/products/process"); 
    let url = '/index.php/wp-json/connector/v1/shopify/shops'; 

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


            /*
                Para cada vendo registro todos los WebHooks
            */


            let url = '/index.php/wp-json/connector/v1/shopify/webhooks/register'; 

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

                    /*
                        Carga inicial de productos
                    */

                    let _url = '/index.php/wp-json/connector/v1/shopify/products?vendor=' + vendor;
                    //console.log(_url);


                    var settings = {
                        "url": _url,
                        "method": "POST",
                        "timeout": 0,
                        "headers": {
                            "Content-Type": "text/plain"
                        }
                    };

                    jQuery.ajax(settings)
                    .done(function (response) {                   
                        
                        if (typeof response['error'] != 'undefined'){
                            addNotice(response['error'], 'danger');
                            return;
                        }

                        if (typeof response['count'] != 'undefined'){                            
                            addNotice('Hay productos de Shopify que se sincronizaran', 'info');
                        }

                        // SSE
     
                        source.addEventListener("shopify_sync", function(e) {
                            addNotice(e.data);
                        }, false);
                    
                    })
                    .fail(function (jqXHR, textStatus) {
                        console.log(jqXHR);
                        console.log(textStatus);
                        addNotice('Error desconocido', 'danger');
                    });

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