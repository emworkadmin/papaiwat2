<?php 

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {


	/**
	 * Start session
	 */
// 	public function _initSessionAfterDb()
// 	{
// 		$this->bootstrap('db');
// 		$this->bootstrap('session');
// 		Zend_Session::start();
// 	}
	
	public function _initCookies() 
	{
// 		if (!isset($_COOKIE['lang'])) {
// 			setcookie('lang', 'th', time() + 3600, '/');
// 			$_COOKIE['lang'] = 'th';			
// 			Zend_Registry::set('lang', $_COOKIE['lang']);
// 		} else {
// 			Zend_Registry::set('lang', $_COOKIE['lang']);
// 		}		
// 		$this->bootstrap('frontController');
// 		$front = $this->getResource('frontController');
// 		$front->setRequest(new Zend_Controller_Request_Http());
// 		$request = $front->getRequest();
// 		$requestUri = $request->getRequestUri();		
	}
	
	
	
	
	
	/**
	 * Set Route
	 */
	protected function _initRoutes()
	{
		
		if (true) {
			
			$this->bootstrap('frontController');
			$front = $this->getResource('frontController');
			$front->setRequest(new Zend_Controller_Request_Http());
			$request = $front->getRequest();
			$module = $request->getParam("module", "");
			$requestUri = $request->getRequestUri();
			
			if (strlen($requestUri) <= 1) {
				
			} else {
				if (strpos($requestUri, '/', 1) == 1) {
					
				} else {
					$phase = ""; //strtolower(substr($requestUri, 1, strpos($requestUri, '/', 1) - 1));
					if (strpos($requestUri, '/', 1) === false) {
						$phase = strtolower(substr($requestUri, 1));
					} else {
						$phase = strtolower(substr($requestUri, 1, strpos($requestUri, '/', 1) - 1));
					}
					
					if ($phase != 'api') {
						$languages = array();
						$this->bootstrap(array('db', 'modules'));
						$languageList = System_Model_Setting::getLanguages();
						foreach ($languageList as $l) {
							$languages[] = $l["Code"];
						}
						
						if (!in_array($phase, $languages)) {
							$defaultLang = System_Model_Setting::getDefaultLanguage();
							$response = new Zend_Controller_Response_Http();
							$response->setRedirect('/' . $defaultLang['Code'] . $requestUri);
							$front->setResponse($response);
						}
					} else {
						return;
					}
				}
			}
			
			// TODO: Lang on url
			//$lang = 'en';
			if (Zend_Registry::isRegistered("lang")) {
				$lang = Zend_Registry::get("lang");
			} else {
				$this->bootstrap(array('db', 'modules'));
				$defaultLang = System_Model_Setting::getDefaultLanguage();
				$lang = $defaultLang["Code"]; // Default
				
// 				// Check from geo ip
// 				$ipAddress = $_SERVER["REMOTE_ADDR"];
// 				if (!empty($ipAddress)) {
// 					$countryCode = geoip_country_code_by_name($ipAddress);
// 					if (!empty($countryCode)) {
// 						switch ($countryCode) {
// 							case "TH" :
// 								$lang = 'th';
// 								break;
// 							default :
// 								$lang = 'en';
// 								break;
// 						}
// 					}
// 				}
			}
			
			// Create route with language id (lang)
			$routeLang = new Zend_Controller_Router_Route(
					':lang',
					array(
							'lang' => $lang
					),
					array('lang' => '[a-z]{2}')
					);
			
			$this->bootstrap('FrontController');
			// Now get router from front controller
			$front  = $this->getResource('FrontController');
			$router = $front->getRouter();
			
			// Instantiate default module route
			$routeDefault = new Zend_Controller_Router_Route_Module(
					array(),
					$front->getDispatcher(),
					$front->getRequest()
					);
			
			// Chain it with language route
			$routeLangDefault = $routeLang->chain($routeDefault);
			
			// Add both language route chained with default route and
			// plain language route
			$router->addRoute('default', $routeLangDefault);
			$router->addRoute('lang', $routeLang);
			
			// Register plugin to handle language changes
			$front->registerPlugin(new Emwork_Controller_Plugin_Language());
			Zend_Registry::set("IsUseLanguageCode", true);
		}
	}
	
}
