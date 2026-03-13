<?php
class Graphdata_Service_Category {
	
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
		
		// Get Option
		$graphdataCategoryModel = new Graphdata_Model_Category();
		$graphdataCategoryOption = $graphdataCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
		
		return $graphdataCategoryOption;
	}
}

?>
