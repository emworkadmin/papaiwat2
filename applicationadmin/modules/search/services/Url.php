<?php
class Search_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		
		$url = "/search";
		switch ($controller) {
			case "search" :
				$url .= "/search/index";
				break;
		}
		return $url;
	}
}

?>
