<?php
class License_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		$url = "/license";
		switch ($controller) {
			case "info" :
				$url .= "/info/category";
				break;
			case "procedure" :
				$url .= "/procedure/list";
				break;
		}
		return $url;
	}
}

?>
