<?php
class Wat_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		
		$url = "/wat";
		switch ($controller) {
			case "category" :
				$url .= "/category/index";
				if (isset($params["category"])) {
					$url .= "/id/" . $params["category"];
				}
				break;
			case "region" :
				$url .= "/region/index";
				if (isset($params["region"]) && !empty($params["region"])) {
					$url .= "/id/" . $params["region"];
				}
				break;
			case "province" :
				$url .= "/province/index";
				if (isset($params["province"]) && !empty($params["province"])) {
					$url .= "/id/" . $params["province"];
				}
				break;
			case "type" :
				$url .= "/type/index";
				if (isset($params["type"]) && !empty($params["type"])) {
					$url .= "/id/" . $params["type"];
				}
				break;
			case "item" :
				$url .= "/item/index";
				if (isset($params["page"])) {
					$url .= "/id/" . $params["page"];
				}
				break;
			case "search" :
				$url .= "/search/index";
				break;
		}
		return $url;
	}
}

?>
