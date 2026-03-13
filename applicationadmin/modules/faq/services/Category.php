<?php
class Faq_Service_Category {
	
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
		$faqCategoryModel = new Faq_Model_Category();
		$faqCategoryOption = $faqCategoryModel->getOption($defaultLanguage["Code"]);
		
		return $faqCategoryOption;
	}
}

?>
