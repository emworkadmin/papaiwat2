<?php 

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {
	
// 	protected function _initDb()
// 	{
// // 		$resource = $this->getPluginResource('db');	
// // 		$db = $resource->getDbAdapter();		
// // 		Zend_Registry::set("db", $db);  		
// 	}
	
	/**
	 * Start session
	 */
	public function _initSessionAfterDb()
	{
		$this->bootstrap('db');
		$this->bootstrap('session');
		Zend_Session::start();
	}
}
