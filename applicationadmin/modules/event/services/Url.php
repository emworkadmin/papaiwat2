<?php
class Event_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		
		$url = "/event";
		switch ($controller) {
			case "category" :
				$url .= "/category/index";
				if (isset($params["category"])) {
					$url .= "/id/" . $params["category"];
				}
				break;
			case "event" :
				$url .= "/event/index";
				if (isset($params["event"])) {
					$url .= "/id/" . $params["event"];
				}
				break;
		}
		return $url;
	}
}

?>
