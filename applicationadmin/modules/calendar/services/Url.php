<?php
class Calendar_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		
		$url = "/calendar";
		switch ($controller) {
			case "category" :
				$url .= "/category/index";
				if (isset($params["category"])) {
					$url .= "/id/" . $params["category"];
				}
				break;
			case "article" :
				$url .= "/article/index";
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
		}
		return $url;
	}
}

?>
