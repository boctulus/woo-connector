<?php

/*
	@author boctulus
*/

namespace connector\libs;

class Files
{
	static function logger($data, $filename = 'log.txt'){	
		$path = __DIR__ . '/../logs/'. $filename; 
		
		if (is_array($data) || is_object($data))
			$data = json_encode($data);
		
		$data = date("Y-m-d H:i:s"). "\t" .$data;

		return file_put_contents($path, $data. "\n", FILE_APPEND);
	}

	static function dump($object, $filename = 'dump.txt', $append = false){
		$path = __DIR__ . '/../logs/'. $filename; 

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




