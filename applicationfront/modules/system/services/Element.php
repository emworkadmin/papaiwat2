<?php
class System_Service_Element {
	
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
	public static function isActive ($extensionRefName, $extensionType = "component") {
		
		if (empty($extensionRefName)) {
			$extensionRefName = "";
		}
		if (empty($extensionType)) {
			$extensionType = "component";
		}
		
		$result = false;
		$db = Zend_Db_Table::getDefaultAdapter ();
		
		$select = $db->select()
		->from(array('m' => 'sys_element'), array("ID"))
		->where("m.IsDeleted = ?", "N")
		->where("m.IsEnable = ?", "Y")
		->where("m.RefTypeName = ?", $extensionRefName)
		->where("m.Type = ?", $extensionType)
		;
		$stmt = $db->query($select);
		$data = $stmt->fetchObject();
		
		if (!empty($data->ID)) {
			$result = true;
		}
		return $result;
	}
}

?>
