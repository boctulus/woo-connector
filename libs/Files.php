<?php

/*
	@author boctulus
*/

namespace connector\libs;

class Files
{
	// http://25labs.com/alternative-for-file_get_contents-using-curl/
	static function file_get_contents_curl($url, $retries=5)
	{
		$ua = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.82 Safari/537.36';

		if (extension_loaded('curl') === true)
		{
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url); // The URL to fetch. This can also be set when initializing a session with curl_init().
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); // TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // The number of seconds to wait while trying to connect.
			curl_setopt($ch, CURLOPT_USERAGENT, $ua); // The contents of the "User-Agent: " header to be used in a HTTP request.
			curl_setopt($ch, CURLOPT_FAILONERROR, TRUE); // To fail silently if the HTTP code returned is greater than or equal to 400.
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); // To follow any "Location: " header that the server sends as part of the HTTP header.
			curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE); // To automatically set the Referer: field in requests where it follows a Location: redirect.
			curl_setopt($ch, CURLOPT_TIMEOUT, 10); // The maximum number of seconds to allow cURL functions to execute.
			curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // The maximum number of redirects

			$result = curl_exec($ch);

			curl_close($ch);
		}
		else
		{
			$result = file_get_contents($url);
		}        

		if (empty($result) === true)
		{
			$result = false;

			if ($retries >= 1)
			{
				sleep(1);
				return self::file_get_contents_curl($url, --$retries);
			}
		}    

		return $result;
	}


	static function logger($data, $filename = 'log.txt'){	
		$path = __DIR__ . '/../logs/'. $filename; 
		
		if (is_array($data) || is_object($data))
			$data = json_encode($data);
		
		$data = date("Y-m-d H:i:s"). "\t" .$data;

		return file_put_contents($path, $data. "\n", FILE_APPEND);
	}

	static function dump($object, $filename = 'dump.txt', $append = false){
		if (!Strings::contains('/', $filename)){
			$path = __DIR__ . '/../downloads/'. $filename; 
		} else {
			$path = $filename;
		}

		if ($append){
			file_put_contents($path, var_export($object,  true) . "\n", FILE_APPEND);
		} else {
			file_put_contents($path, var_export($object,  true) . "\n");
		}		
	}

	static function get_rel_path(){
		$ini = strpos(__DIR__, '/wp-content/');
		$rel_path = substr(__DIR__, $ini);
		$rel_path = substr($rel_path, 0, strlen($rel_path)-4);
		
		return $rel_path;
	}			
	

}




