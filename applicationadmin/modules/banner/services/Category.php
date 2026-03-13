<?php
class Banner_Service_Category {
	
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
		$bannerCategoryModel = new Banner_Model_Category();
		$bannerCategoryOption = $bannerCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
		
		return $bannerCategoryOption;
	}
}

?>
