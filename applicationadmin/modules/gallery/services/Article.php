<?php
class Gallery_Service_Article {
	
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
		$galleryArticleModel = new Gallery_Model_Article();
		$galleryArticleOption = $galleryArticleModel->getOption($defaultLanguage["Code"]);
		
		return $galleryArticleOption;
	}
}

?>
