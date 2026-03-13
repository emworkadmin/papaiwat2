<?php
class Popup_Service_Popup {
	
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
		$popupInfoModel = new Popup_Model_Index();
		$popupInfoOption = $popupInfoModel->getOption($defaultLanguage["Code"]);
		return $popupInfoOption;
	}
}

?>
