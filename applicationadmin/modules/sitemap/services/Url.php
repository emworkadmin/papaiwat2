<?php
class Sitemap_Service_Url {
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function generate($controller, $params = array()) {
		$url = "/sitemap";
		switch ($controller) {
			case "index" :
				$url .= "/index/index";
				if (isset($params["category"])) {
					$url .= "/id/" . $params["category"];
				}
				break;
		}
		return $url;
	}

	public function getOption() {

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		// Get Option
		$model = new Sitemap_Model_Index();
		$sitemapOption = $model->getOption($defaultLanguage["Code"]);
		
		return $sitemapOption;
	}
}

?>
