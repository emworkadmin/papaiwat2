<?php
class Story_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		
		$url = "/story";
		switch ($controller) {
			case "category" :
				$url .= "/category/index";
				if (isset($params["category"])) {
					$url .= "/id/" . $params["category"];
				}
				break;
			case "page" :
				$url .= "/page/index";
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
