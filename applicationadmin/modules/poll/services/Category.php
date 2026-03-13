<?php
class Poll_Service_Category {
	
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
		$pollCategoryModel = new Poll_Model_Category();
		$pollCategoryOption = $pollCategoryModel->getOption($defaultLanguage["Code"]);
		
		return $pollCategoryOption;
	}
}

?>
