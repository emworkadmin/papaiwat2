<?php

class System_Model_Action {

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
			
		}
	}
	
	function getFullList() {

		$data = array();
		try {
			// set query
			$select = $this->db->select()
			->from(array('tbl' => 'sys_action_group'), '')
			->joinLeft(array("a" => "sys_action"), "tbl.ID = a.ActionGroupID", '')
			->columns(array(
					"ActionGroupID" => "tbl.ID",
					"ActionGroupName" => "tbl.Name",
					"ActionGroupOrdering" => "tbl.Ordering",
					"ActionID" => "a.ID",
					"ActionParentID" => "a.ParentID",
					"ActionName" => "a.Name",
					"ActionOrdering" => "a.Ordering",
			))
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.IsEnable = 'Y' ")
			->where("a.IsDeleted = 'N' ")
			->where("a.IsEnable = 'Y' ")
			;
				
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		
		} catch (Exception $e) {
			
		}
		
		return $data;
	}
}

?>
