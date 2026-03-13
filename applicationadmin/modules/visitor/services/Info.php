<?php
class Visitor_Service_Info {
	
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
		$VisitorInfoModel = new Visitor_Model_Visitor();
		$VisitorInfoOption = $VisitorInfoModel->getOption($defaultLanguage["Code"]);
		
		return $VisitorInfoOption;
	}
}

?>
