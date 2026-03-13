<?php
class Information_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		
		$url = "/information";
		switch ($controller) {
			case "category" :
				$url .= "/category/index";
				if (isset($params["category"])) {
					$url .= "/id/" . $params["category"];
				}
				break;
			case "department" :
				$url .= "/department/detail";
				if (isset($params["department"])) {
					$url .= "/id/" . $params["department"];
				}
				break;
		}
		return $url;
	}
}

?>
