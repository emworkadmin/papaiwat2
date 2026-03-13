<?php
class Wat_Service_Type {
	
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
		$model = new Wat_Model_Type();
		$option = $model->getOption($defaultLanguage["Code"]);
		
		$option = Emwork_Utility::addEmptyOption($option, 'ทุกประเภท');
		
		return $option;
	}
}

?>
