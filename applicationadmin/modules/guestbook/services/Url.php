<?php
class Guestbook_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		
		$url = "/guestbook";
		switch ($controller) {
			case "guestbook" :
				$url .= "/guestbook/index";
				if (isset($params["guestbook"])) {
					$url .= "/id/" . $params["guestbook"];
				}
				break;
		}
		return $url;
	}
}

?>
