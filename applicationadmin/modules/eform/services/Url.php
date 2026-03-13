<?php
class Eform_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		$url = "/eform";
		switch ($controller) {
			case "item" :
				$url .= "/item/index";
				if (isset($params["item"])) {
					$url .= "/id/" . $params["item"];
				}
				break;
			case "category" :
				$url .= "/category/item";
				if (isset($params["category"])) {
					$url .= "/id/" . $params["category"];
				}
				break;
		}
		return $url;
	}
}

?>
