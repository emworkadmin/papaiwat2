<?php

class System_Model_Element {

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
			// setup database
			$this->db = Zend_Db_Table::getDefaultAdapter();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance();
			$this->auth = $authobj->getIdentity();
			$this->UserID = isset($this->auth->UserID) ? $this->auth->UserID : '';
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	
	
	public static function getInfoByRef($type, $refTypeName) {
		$db = Zend_Db_Table::getDefaultAdapter();
		$data = null;
		try {
			// set query maininfo
			$select = $db->select()
			->from(array("tbl" => "sys_element"), "")
			->columns(array(
					"ID" => "tbl.ID",
					"tbl.Name",
					"tbl.Type",
					"tbl.RefTypeName"
			))
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.Type = ? ", $type)
			->where("tbl.RefTypeName = ? ", $refTypeName)
			;
			
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
			
		} catch (Exception $e) {
			//echo $e->getMessage();
		}
		
		return $data;
	}

}

?>
