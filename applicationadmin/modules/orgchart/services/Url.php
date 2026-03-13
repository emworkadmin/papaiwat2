<?php
class Orgchart_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		$url = "/orgchart";
		switch ($controller) {
			case "item" :
				$url .= "/item/index";
				if (isset($params["item"])) {
					$url .= "/id/" . $params["item"];
				}
				break;
			case "index" :
				$url .= "/index/index";
				// if (isset($params["index"])) {
				// 	$url .= "/id/" . $params["index"];
				// }
				// $url .= "/index";
				break;
		}
		return $url;
	}
}

?>
