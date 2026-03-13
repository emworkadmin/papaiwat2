<?php
class Rss_Service_Item {
	
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
		$rssItemModel = new Rss_Model_Item();
		$rssItemOption = $rssItemModel->getOption($defaultLanguage["Code"]);
		
		return $rssItemOption;
	}
}

?>
