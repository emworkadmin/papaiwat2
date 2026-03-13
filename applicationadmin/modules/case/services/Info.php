<?php
class Case_Service_Info {
	
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
		$CaseInfoOption = array();
		// Get Option
		//$CaseInfoModel = new Case_Model_Info();
		//$CaseInfoOption = $CaseInfoModel->getOption($defaultLanguage["Code"]);
		
		return $CaseInfoOption;
	}
}

?>
