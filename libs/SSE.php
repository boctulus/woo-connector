<?php

namespace connector\libs;

/*
	Author: boctulus
	
	Reference:
	https://developer.mozilla.org/es/docs/Web/API/Server-sent_events/Using_server-sent_events
*/

class SSE
{
	private $channel  = 'default';
	private $interval = 1;
	private $debug    = false;

	function __construct(string $channel = null){
		if (php_sapi_name() == "cli") {
			$this->debug = true;
		}
		
		if (!$this->debug && !headers_sent()) {
			header("Content-Type: text/event-stream\n\n");
		}

		$this->setDefaultChannel($channel);
	}

	function setDefaultChannel(string $name){
		// validar. Solo permitidos [a-z-#] y alguno más. Ej: vendor-x#created
		$this->channel = $name; 
		return $this;
	}

	function setInterval(int $seconds){
		$this->interval = $seconds;
		return $this;
	}

	function setRetry($milliseconds){
		echo "retry: $milliseconds\n";
		return $this;
	}

	/*
		Enviar periódicamente para evitar que se cierre la conexión
	*/
	function sendComment(){
		echo ": hi\n\n";
	}

	function send($data, string $channel = null){
		if (empty($channel)){
			$channel = $this->channel;
		}

		echo "event: $channel\n";

		if (is_array($data)){
			echo 'data: ' . json_encode($data);	
		} else {
			echo 'data: ' . $data;
		}
		
		echo "\n\n";

		if (!$this->debug){
			ob_flush();
			flush();

			sleep($this->interval);
		}		
	}

	function sendError($data, $channel = null){
		if (empty($channel)){
			$channel = $this->channel;
		}

		echo "event: $channel\n";

		if (is_array($data)){
			echo 'error: ' . json_encode($data);	
		} else {
			echo 'error: ' . $data;
		}
		
		echo "\n\n";

		if(!$this->debug){
			ob_flush();
			flush();

			sleep($this->interval);
		}
	}


}
