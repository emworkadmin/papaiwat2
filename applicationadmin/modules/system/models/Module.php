<?php
class System_Model_Module {
	
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
			$this->db = Zend_Db_Table::getDefaultAdapter ();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance ();
			$this->auth = $authobj->getIdentity ();
			$this->UserID = isset ( $this->auth->UserID ) ? $this->auth->UserID : '';
		} catch ( Exception $e ) {
			
		}
	}
	
	public function getOption($showOnlyEnable = true) {
		$select = $this->db->select()
		->from(array('m' => 'module_info'), array("Code", "Name"))
		->where("m.IsDeleted = ?", "N")
		->order("convert(m.Name USING tis620) asc")
		;
		
		if (!$showOnlyEnable) {
			$select = $select->where("m.IsEnable = ?", "Y");
		}

		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		$result = array();
		
		foreach ($data as $value) {
			$result[$value["Code"]] = $value["Name"];
		}
		return $result;
	}
	
	public function getActionTypeOption($showOnlyEnable = true) {
		$select = $this->db->select()
		->from(array('m' => 'module_action'), array("Code", "Name"))
		->where("m.IsDeleted = ?", "N")
		->order("convert(m.Name USING tis620) asc")
		;
		
		if (!$showOnlyEnable) {
			$select = $select->where("m.IsEnable = ?", "Y");
		}
		
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		$result = array();
		
		foreach ($data as $value) {
			$result[$value["Code"]] = $value["Name"];
		}
		return $result;
	}
}

?>
