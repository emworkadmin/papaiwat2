<?php
class Graph_Service_Info {
	
	/**
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $db = null;
	protected $UserID = null;
	protected $DeptID = null;
	protected $auth = null;
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function getOption() {
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$GraphInfoOption = array();
		
// 		// Get Option
// 		$GraphInfoModel = new Graph_Model_Info();
// 		$GraphInfoOption = $GraphInfoModel->getOption($defaultLanguage["Code"]);
		
		return $GraphInfoOption;
	}
}

?>
