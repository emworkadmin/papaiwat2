<?php
class Vrv_Service_Item {
	
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
		$vrvItemModel = new Vrv_Model_Item();
		$vrvItemOption = $vrvItemModel->getOption($defaultLanguage["Code"]);
		
		return $vrvItemOption;
	}
}

?>
