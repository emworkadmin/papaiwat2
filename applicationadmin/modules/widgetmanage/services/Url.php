<?php
class Widgetmanage_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		
		$url = "/widgetmanage";
		switch ($controller) {
			case "Item" :
				$url .= "/Item/index";
				if (isset($params["category"])) {
					$url .= "/id/" . $params["category"];
				}
				break;
		}
		return $url;
	}
}

?>
