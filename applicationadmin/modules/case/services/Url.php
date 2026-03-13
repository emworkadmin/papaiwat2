<?php
class Case_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {



		$url = "/case";
		switch ($controller) {
			case "category" :
				$url .= "/category/index";
				// if (isset($params["category"])) {
					// $url .= "/id/" . $params["category"];
				// }
				break;
		}

		return $url;
	}
}

?>
