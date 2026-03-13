<?php
class Gallery_Service_Category {
	
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
		$galleryCategoryModel = new Gallery_Model_Category();
		$galleryCategoryOption = $galleryCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
		
		return $galleryCategoryOption;
	}
}

?>
