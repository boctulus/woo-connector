<?php

namespace connector\libs;

/*
    @author Pablo Bozzolo <boctulus@gmail.com>
*/

class Url 
{    
    static function getQueryParam(string $url, string $param){
        $query = parse_url($url, PHP_URL_QUERY);

        $x = null;
        if ($query != null){
            $q = explode('&', $query); 
            foreach($q as $p){
                if (Strings::startsWith($param . '=', $p)){
                    $_x = explode('=', $p);
                    $x  = $_x[count($_x)-1];                    
                }
            }
        }

        return $x;
    }

    static function currentUrl(){
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        return $actual_link;
    }

    static function getBaseUrl($url)
    {
        $url_info = parse_url($url);
        return  $url_info['scheme'] . '://' . $url_info['host'];
    }

    /*
        @author     Pablo Bozzolo   boctulus@gmail.com
    */
    static function consume_api(string $url, string $http_verb, Array $body = null, Array $headers = null, Array $options = null)
    {   
        $data = json_encode($body);
    
        $headers = array_merge(
            [
                'Content-Type' => 'application/json'
            ], 
            ($headers ?? [])
        );
    
        $curl = curl_init();
    
        if ($http_verb != 'GET' && !empty($data)){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $headers['Content-Length']   = strlen($data);
        }
    
        $h = [];
        foreach ($headers as $key => $header){
            $h[] = "$key: $header";
        }

        $options = [
            CURLOPT_HTTPHEADER => $h
        ] + ($options ?? []);

        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '' );
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0 );
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_verb);
        curl_setopt($curl, CURLOPT_FAILONERROR, true );
    
    
        $response  = curl_exec($curl);
        $err_msg   = curl_error($curl);	
        $http_code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
        curl_close($curl);
    
    
        $ret = [
            'data'      => json_decode($response, true),
            'http_code' => $http_code,
            'error'     => $err_msg
        ];
    
        return $ret;
    }    
    
}

