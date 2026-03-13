<?php
class License_Service_Category {
	
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
		$model = new License_Model_Category();
		$option = $model->getOption($defaultLanguage["Code"]);
		
		$returnOption = array();
		$returnOption[""] = "ทั้งหมด";
		if (count($option) > 0) {
			foreach ($option as $key => $val) {
				$returnOption[$key] = $val;
			}
		}
		
		return $returnOption;
	}
}

?>
