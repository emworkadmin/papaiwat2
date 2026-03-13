<?php

class System_Model_Usergroup {

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

	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch) {
		
		$data = array();
		try {
			// set query
			$select = $this->db->select()
			->from(array('tbl' => 'sys_usergroup'), array(
					"ID",
					"NameAbbr",
					"Name",
					"IsEnable"			
			))
			->where("tbl.IsDeleted = 'N' ")
			;
			
			// set criteria
			if (!empty($advancesearch['searchText'])) {
				$select = $select->where("Name like " . $this->db->quote("%" . $advancesearch['searchText'] . "%") ."OR tbl.NameAbbr like " . $this->db->quote("%" . $advancesearch['searchText'] . "%"));
			}			
			if (!empty($advancesearch['enable'])) {
				$select = $select->where("tbl.IsEnable = ?", $advancesearch['enable']);
			} 
			
			// find total
			$records = $this->getListCount($select);
			
			// set ordering and total
			if (empty($sidx))
				$sidx = "ID";
			$rowSort = $sidx;
			
			$select = $this->db->select()
				->from(array('tbl' => $select), array('*'))
				->columns(array("RECORD" => "abs($records)"))
// 				->order("$rowSort $sord")
				->limitPage($page, $rows)
			; 
				if (in_array($rowSort, array('NameAbbr', 'Name'))) {
					$select = $select->order("convert($rowSort USING tis620) $sord");
				} else {
					$select = $select->order("$rowSort $sord");
				}
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {
			
		}		
		return $data;
	}

	function getListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}
	
	function getInfo($id) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sys_usergroup'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"Name" => "tbl.Name",
					"NameAbbr" => "tbl.NameAbbr",
					"Description" => "tbl.Description",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
			))
			->where("tbl.ID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
	
		}
		return $data;
	}
	
	function save($params) {
		$data = new stdClass();
		$data->result = false;
		try {

			$form = array();
			$form['Name'] = empty($params['txtName']) ? null : $params['txtName'];
			$form['NameAbbr'] = empty($params['txtNameAbbr']) ? null : $params['txtNameAbbr'];
			$form['Description'] = empty($params['txtDescription']) ? null : $params['txtDescription'];
			$form['Created'] = date('Y-m-d H:i:s');
			$form['CreatedBy'] = $this->UserID;
			$form['IsEnable'] = $params["isStatus"];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("sys_usergroup", $form);
			$usergroupID = $this->db->lastInsertId();
	
			if($result == true){
				$data->result = true;
				$data->id = $usergroupID;
			}
	
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function update($params) {
		$data = new stdClass();
		$data->result = false;
		try {
				
			$form = array();
			$form['Name'] = empty($params['txtName']) ? null : $params['txtName'];
			$form['NameAbbr'] = empty($params['txtNameAbbr']) ? null : $params['txtNameAbbr'];
			$form['Description'] = empty($params['txtDescription']) ? null : $params['txtDescription'];
			$form['IsEnable'] = $params['isStatus'];
			$form['Modified'] = date('Y-m-d H:i:s');
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $params['hdfID'];
			$result = $this->db->update("sys_usergroup", $form, $where);
				
			if($result){
				$data->result = true;
			}
				
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function delete($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$id = $params['id'];
				
			// User
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = date('Y-m-d H:i:s');
			$form['ModifiedBy'] = $this->UserID;
			$where['id = ? '] = $id;
			$result = $this->db->update("sys_usergroup", $form, $where);
				
			$data->result = count($result) > 0;
				
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	
	/**
	 * 
	 * @param unknown $params
	 * @return stdClass
	 */
	function updateRights($params) {
		$data = new stdClass();
		$data->result = false;
		try {

			// Delete
			$form = array();
			$where = array();
			$form['ModifiedBy'] = $this->UserID;
			$form['Modified'] = date('Y-m-d H:i:s');
			$form['IsDeleted'] = 'Y';
			$where["UsergroupID = ?"] = $params["hdfID"];
			$where["IsDeleted = ?"] = "N";
			$result = $this->db->update("sys_usergroup_action", $form, $where);
			
			// Insert New
			if (count($params['chkAction'])) {
				foreach ($params['chkAction'] as $actionID) {
					$form = array();
					$form['ActionID'] = $actionID;
					$form['UsergroupID'] = $params["hdfID"];
					$form['CreatedBy'] = $this->UserID;
					$form['Created'] = date('Y-m-d H:i:s');
					$this->db->insert("sys_usergroup_action", $form);
				}
			}
	
			$data->result = true;
			$data->id = $params["hdfID"];
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	/**
	 *
	 * @param array $params
	 * @return stdClass
	 */
	function updateActions($params) {
		$data = new stdClass();
		$data->result = false;
		try {
						
			$actionGroup = $params["hdfActionGroup"];
			$id = $params["hdfID"];
			
			// Delete
			$sqlDelete = "DELETE FROM sys_usergroup_action "
					. "WHERE     (sys_usergroup_action.ActionID in ( SELECT ID FROM sys_action WHERE ActionGroupID = '$actionGroup')) "
					. " and (sys_usergroup_action.UsergroupID = '$id')";
			$this->db->query($sqlDelete)->execute();
						
			// Insert New
			if (count($params['chkAction'])) {
				foreach ($params['chkAction'] as $actionID) {
					$form = array();
					$form['ActionID'] = $actionID;
					$form['UsergroupID'] = $params["hdfID"];
					$form['CreatedBy'] = $this->UserID;
					$form['Created'] = date('Y-m-d H:i:s');
					$this->db->insert("sys_usergroup_action", $form);
				}
			}
			
			$data->result = true;
			$data->id = $id;
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	

	public function getOption() {
	
		$select = $this->db->select()
		->from(array('tbl' => 'sys_usergroup'), array( "ID", "Name"))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->order("convert(tbl.Name USING TIS620) ASC")
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		$result = array();
	
		foreach ($data as $value) {
			$result[$value["ID"]] = $value["Name"];
		}
		return $result;
	}
	
	public static function getActions($usergroupID) {
		$db = Zend_Db_Table::getDefaultAdapter();
		$data = array();
		try {
	
			$select = $db->select()
			->from(array('tbl' => 'sys_usergroup_action'), array("*"))
			->joinLeft(array('a' => 'sys_action'), "tbl.ActionID = a.ID", array("ActionCode" => "Code"))
			->columns(array(
					"UserGroupID" => "tbl.UsergroupID",
			))
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.UsergroupID = ? ", $usergroupID)
			;
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i++) {
					$data[$i]['Created'] = empty($data[$i]['Created']) ? null : new DateTime($data[$i]['Created']);
					$data[$i]['Modified'] = empty($data[$i]['Modified']) ? null : new DateTime($data[$i]['Modified']);
				}			
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	public static function getNameById($id) {
		$db = Zend_Db_Table::getDefaultAdapter();
		$data = array();
		try {
	
			$select = $db->select()
			->from(array('tbl' => 'sys_usergroup'), array("Name", "NameAttr"))
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.ID = ? ", $id)
			;
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
				
		}
		return $data->Name.' ('.$data->NameAttr.')';
	}
}

?>
