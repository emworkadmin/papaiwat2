<?php
class Webboard_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		
		$url = "/webboard";
		switch ($controller) {
			case "home" :
				$url .= "/home/index";
				break;
		}
		return $url;
	}
}

?>
