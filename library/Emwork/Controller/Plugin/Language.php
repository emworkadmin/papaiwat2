<?php
class Emwork_Controller_Plugin_Language extends Zend_Controller_Plugin_Abstract
{
	public function routeShutdown(Zend_Controller_Request_Abstract $request)
	{
		$lang = $request->getParam('lang', null);
		
		// Check Lang
		if (in_array($lang, array('en', 'th', 'zh'))) {
		} else {
			$lang = 'th';
		}
		Zend_Registry::set('lang', $lang);
		
		// Set language to global param so that our language route can
		// fetch it nicely.
		$front = Zend_Controller_Front::getInstance();
		$router = $front->getRouter();
		$router->setGlobalParam('lang', $lang);
	}
}