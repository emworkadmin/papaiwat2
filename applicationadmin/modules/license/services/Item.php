<?php
class License_Service_Item {
	
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
		$model = new License_Model_Item();
		$option = $model->getOption($defaultLanguage["Code"]);
		
		return $option;
	}
	public static function getOptionByCategory($categoryID) {
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		// Get Option
		$model = new License_Model_Item();
		$option = $model->getOptionByCategory($categoryID, $defaultLanguage["Code"]);
		
		return $option;
	}
}

?>
