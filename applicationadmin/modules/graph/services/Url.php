<?php
class Graph_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		
		$url = "/graph";
		switch ($controller) {
			case "index" :				
				if (isset($params["type"])) {
					$url .= "/index/" . $params["type"];
				}
				break;
			case "inbound" :
				$url .= "/inbound/index";
				break;
			case "outbound" :
				$url .= "/outbound/index";
				break;
			case "internal" :
				$url .= "/internal/index";
				break;
			case "research" :
				$url .= "/research/index";
				break;
			case "guide" :
				$url .= "/guide/index";
				break;
			case "investment" :
				$url .= "/investment/index";
				break;
			case "stathotel" :
				$url .= "/stathotel/index";
				break;
			case "travelrank" :
				$url .= "/travelrank/index";
				break;
			case "travelplace" :
				$url .= "/travelplace/index";
				break;
			case "travelstatistics" :
				$url .= "/travelstatistics/index";
				break;
		}
		return $url;
	}
}

?>
