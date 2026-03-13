<?php
class Faq_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		
		$url = "/faq";
		switch ($controller) {
			case "category" :
				$url .= "/category/index";
				if (isset($params["category"])) {
					$url .= "/id/" . $params["category"];
				}
				break;
			case "item" :
				$url .= "/item/index";
				break;
			case "question" :
				$url .= "/question/index";
				break;
		}
		return $url;
	}
}

?>
