<?php
class Story_Service_Article {
	
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
		$storyArticleModel = new Story_Model_Article();
		$storyArticleOption = $storyArticleModel->getOption($defaultLanguage["Code"]);
		
		return $storyArticleOption;
	}
}

?>
