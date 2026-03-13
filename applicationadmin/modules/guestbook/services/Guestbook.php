<?php
class Guestbook_Service_Guestbook {
	
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
		
		$modelBook = new Guestbook_Model_Guestbook();
		$bookOption = $modelBook->getOptionMenu($defaultLanguage["Code"]);
		
		return $bookOption;
	}
}

?>
