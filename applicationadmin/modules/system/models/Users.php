<?php

class System_Model_Users {

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
			->from(array('tbl' => 'sys_user'), array("ID", "Email", "FullName" => "CONCAT(FirstName,' ',  LastName)", "IsEnable"))
			//->where("tbl.UserType = 'B' ")
			->where("tbl.IsDeleted = 'N'")
			//->where("tbl.UsergroupID is not null")
			;
			
			// set criteria
			if (!empty($advancesearch['searchText'])) {
				$select = $select->where("(tbl.Email like " . $this->db->quote("%" . $advancesearch['searchText'] . "%") . ") or (CONCAT(FirstName,' ',  LastName) like " . $this->db->quote("%" . $advancesearch['searchText'] . "%") . ")");
				//."OR CONCAT(first_name,' ',  last_name) like " . $this->db->quote("%" . $advancesearch['searchText'] . "%"));
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
				//->order("$rowSort $sord")
				->limitPage($page, $rows)
			; 
			if (in_array($rowSort, array('Email', 'FullName'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {
			echo $e->getMessage();
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
	
	function getInfo($userID) {
		$data = new stdClass();
		try {
	
			$select = $this->db->select()
			->from(array('tbl' => 'sys_user'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"UserFrontType" => "tbl.UserFrontType",
					"UserType" => "tbl.UserType",
					"UserSubType" => "tbl.UserSubType",
					"UserGroupID" => "tbl.UsergroupID",
					"Username" => "tbl.Username",
					"Password" => "tbl.Password",
					"TitleName" => "tbl.TitleName",
					"FirstName" => "tbl.FirstName",
					"LastName" => "tbl.LastName",
					"Gender" => "tbl.Gender",
					"BirthDate" => "tbl.BirthDate",
					"Email" => "tbl.Email",
					"Telephone" => "tbl.Telephone",
					"Fax" => "tbl.Fax",
					"Mobile" => "tbl.Mobile",
					"LastLoginDate" => "tbl.LastLoginDate",
					"LastAttemptDate" => "tbl.LastAttemptDate",
					"AttemptAmount" => "tbl.AttemptAmount",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
					"ActivateCode" => "tbl.ActivateCode",
					"VerifyCode" => "tbl.VerifyCode"
			))
			->where("tbl.ID = ?", $userID)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->BirthDate = empty($data->BirthDate) ? null : new DateTime($data->BirthDate);
				$data->LastLoginDate = empty($data->LastLoginDate) ? null : new DateTime($data->LastLoginDate);
				$data->LastAttemptDate = empty($data->LastAttemptDate) ? null : new DateTime($data->LastAttemptDate);
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
			$form['Username'] = empty($params['txtEmail']) ? null : $params['txtEmail'];
			$form['Password'] = empty($params['txtPassword']) ? null : md5($params['txtPassword']);
			$form['TitleName'] = empty($params['txtTitleName']) ? null : $params['txtTitleName'];
			$form['FirstName'] = empty($params['txtFirstName']) ? null : $params['txtFirstName'];
			$form['LastName'] = empty($params['txtLastName']) ? null : $params['txtLastName'];
			$form['Gender'] = empty($params['lstGender']) ? null : $params['lstGender'];
			$form['BirthDate'] = empty($params['txtBirthDate']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL($params['txtBirthDate']);
			$form['Email'] = empty($params['txtEmail']) ? null : $params['txtEmail'];
			$form['Telephone'] = empty($params['txtTelephone']) ? null : $params['txtTelephone'];
			$form['Fax'] = empty($params['txtFax']) ? null : $params['txtFax'];
			$form['Mobile'] = empty($params['txtMobile']) ? null : $params['txtMobile'];
			$form['UserFrontType'] = empty($params['rdlUserFrontType']) ? null : ($params['rdlUserFrontType'] == 'Y' ? "F" : null);
			$form['UserType'] = empty($params['rdlUserType']) ? null : ($params['rdlUserType'] == 'Y' ? "B" : null);
			$form['UsergroupID'] = $form['UserType'] == 'B' ? (empty($params['lstUsergroup']) ? null : $params['lstUsergroup']) : null;
			$form['Created'] = date('Y-m-d H:i:s');
			$form['CreatedBy'] = $this->UserID;
			$form['IsEnable'] = $params["isStatus"];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("sys_user", $form);
			$userID = $this->db->lastInsertId();
		
			if($result == true){
				$data->result = true;
				$data->id = $userID;
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
			$form['TitleName'] = empty($params['txtTitleName']) ? null : $params['txtTitleName'];
			$form['FirstName'] = empty($params['txtFirstName']) ? null : $params['txtFirstName'];
			$form['LastName'] = empty($params['txtLastName']) ? null : $params['txtLastName'];
			$form['Telephone'] = empty($params['txtTelephone']) ? null : $params['txtTelephone'];
			$form['Gender'] = empty($params['lstGender']) ? null : $params['lstGender'];
			$form['BirthDate'] = empty($params['txtBirthDate']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL($params['txtBirthDate']);
			$form['Fax'] = empty($params['txtFax']) ? null : $params['txtFax'];
			$form['Mobile'] = empty($params['txtMobile']) ? null : $params['txtMobile'];
			$form['UserFrontType'] = empty($params['rdlUserFrontType']) ? null : ($params['rdlUserFrontType'] == 'Y' ? "F" : null);
			$form['UserType'] = empty($params['rdlUserType']) ? null : ($params['rdlUserType'] == 'Y' ? "B" : null);
			$form['UsergroupID'] = $form['UserType'] == 'B' ? (empty($params['lstUsergroup']) ? null : $params['lstUsergroup']) : null;
			$form['IsEnable'] = $params['isStatus'];
			$form['Modified'] = date('Y-m-d H:i:s');
			$form['ModifiedBy'] = $this->UserID;
			$whereUser['ID = ? '] = $params['hdfID'];
			$result = $this->db->update("sys_user", $form, $whereUser);
				
			if($result){
				$data->result = true;
			}
				
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function updatePassword($params) {
		$data = new stdClass();
		$data->result = false;
		try {

			$form = array();
			$form['Password'] = empty($params['txtPassword']) ? null : md5($params['txtPassword']);
			$form['Modified'] = date('Y-m-d H:i:s');
			$form['ModifiedBy'] = $this->UserID;
			$where["ID = ?"] = $params["hdfID"];
			$result = $this->db->update("sys_user", $form, $where);
			$userID = $params["hdfID"];
	
			if($result == true){
				$data->result = true;
				$data->id = $userID;
			}
	
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function checkEmail($email) {
		
		$result = false;
		
		$select = $this->db->select()
		->from(array('tbl' => 'sys_user'), array("Email"))
		->where("Email = ? ", $email)
		->where("IsDeleted = 'N' ")
		//->where("UserType = 'B' ")
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		
		$result = empty($data->Email) ? false : true;
		
		return $result;
	}
	
	public static function getInfoByUsername($username) {
		$db = Zend_Db_Table::getDefaultAdapter();

		$data = null;
		try {
			$select = $db->select()
			->from(array('tbl' => 'sys_user'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"UserType" => "tbl.UserType",
					"UserSubType" => "tbl.UserSubType",
					"UserGroupID" => "tbl.UsergroupID",
					"Username" => "tbl.Username",
					"Password" => "tbl.Password",
					"TitleName" => "tbl.TitleName",
					"FirstName" => "tbl.FirstName",
					"LastName" => "tbl.LastName",
					"Email" => "tbl.Email",
					"Telephone" => "tbl.Telephone",
					"Fax" => "tbl.Fax",
					"Mobile" => "tbl.Mobile",
					"LastLoginDate" => "tbl.LastLoginDate",
					"LastAttemptDate" => "tbl.LastAttemptDate",
					"AttemptAmount" => "tbl.AttemptAmount",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
					"ActivateCode" => "tbl.ActivateCode",
					"VerifyCode" => "tbl.VerifyCode"
			))
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.UserType = 'B' ")
			->where("tbl.Username = ?", $username);
		
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->LastLoginDate = empty($data->LastLoginDate) ? null : new DateTime($data->LastLoginDate);
				$data->LastAttemptDate = empty($data->LastAttemptDate) ? null : new DateTime($data->LastAttemptDate);
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
				
		}
		return $data;
	}
	
	public static function updateProperty ($userID, $paramArray) {
		
		$db = Zend_Db_Table::getDefaultAdapter();
		if(array_keys($paramArray) <= 0)
			return false;

		foreach (array_keys($paramArray) as $key) {
			$TBL[$key] = $paramArray[$key];
		}
		$where["ID = ?"] = $userID;
		
		$result = $db->update('sys_user', $TBL, $where);
		return $result;
	}
	
	function delete($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$id = $params['id'];
			$obj = $this->getInfo($id);
			
			$id = $params['id'];
				
			// User
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = date('Y-m-d H:i:s');
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $id;
			$result = $this->db->update("sys_user", $form, $where);
							
			$data->result = count($result) > 0;
				
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
}

?>
