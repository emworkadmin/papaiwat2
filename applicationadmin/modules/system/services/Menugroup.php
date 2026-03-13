<?php
class System_Service_Menugroup{
	
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
		
		$db = Zend_Db_Table::getDefaultAdapter ();
		
		$select = $db->select()
		->from(array('m' => 'menugroup_info'), array("ID", "Name"))
		->where("m.IsDeleted = ?", "N")
		->where("m.IsEnable = ?", "Y")
		->order("convert(m.Name USING tis620) $sord");
		;
		$stmt = $db->query($select);
		$data = $stmt->fetchAll();
		$result = array();
		
		foreach ($data as $value) {
			$result[$value["ID"]] = $value["Name"];
		}
		return $result;
	}
}

?>
