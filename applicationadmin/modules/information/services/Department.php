<?php
class Information_Service_Department {
	
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
		$informationDepartmentModel = new Information_Model_Department();
		$informationDepartmentOption = $informationDepartmentModel->getOption($defaultLanguage["Code"]);
		
		return $informationDepartmentOption;
	}
}

?>
