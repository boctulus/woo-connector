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

            console.log({vendor: vendor, shop : shop});

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
                addNotice('Error desconocido', 'danger', 'alert_container', true);
            });

        }
        		
    })
    .fail(function (jqXHR, textStatus) {
        console.log(jqXHR);
        console.log(textStatus);
        addNotice('Error desconocido', 'danger',  'alert_container', true);
    });
}