<?php
class Ebook_Service_Book {
	
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
		$ebookBookModel = new Ebook_Model_Ebook();
		$ebookBookOption = $ebookBookModel->getOption($defaultLanguage["Code"]);

		return $ebookBookOption;
	}
}

?>
