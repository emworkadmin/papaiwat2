<?php
class Content_Service_Article {
	
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
		$contentArticleModel = new Content_Model_Article();
		$contentArticleOption = $contentArticleModel->getOption($defaultLanguage["Code"]);
		
		return $contentArticleOption;
	}
}

?>
