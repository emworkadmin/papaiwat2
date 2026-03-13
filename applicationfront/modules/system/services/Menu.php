<?php
class System_Service_Menu {
	
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
	public static function getParents($menuID) {
		
		$result = array();
		$model = new System_Model_Menu();
		$menuObj = $model->getInfo($menuID);
		
		while (!empty($menuObj->ParentID)) {
			$result[] = $menuObj->ParentID;
			$menuObj = $model->getInfo($menuObj->ParentID);
		}		
		return $result;
	}
}

?>
