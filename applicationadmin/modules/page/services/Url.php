<?php
class Page_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		
		$url = "/page";
		switch ($controller) {
			default: 
			case "item" :
				$url .= "/item/index";
				if (isset($params["page"])) {
					$url .= "/id/" . $params["page"];
				}
				break;
		}
		return $url;
	}
}

?>
