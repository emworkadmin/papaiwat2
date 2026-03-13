<?php
class Profile_Model_Register {
	
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

	public function checkUsername($username) {
		$result = false;
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sys_user'), array('Username'))
			->where("tbl.Username = ?", $username)
			->where("tbl.IsDeleted = ?", 'N')
			->where("tbl.UserFrontType = ?", 'F')
			;
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			$result = !empty($data->Username);
		} catch (Exception $e) {
			
		}
		
		return $result;
	}

	public function checkEmail($email) {
		$result = false;
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sys_user'), array('Email'))
			->where("tbl.Email = ?", $email)
			->where("tbl.IsDeleted = ?", 'N')
			->where("tbl.UserFrontType = ?", 'F')
			;
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			$result = !empty($data->Email);
		} catch (Exception $e) {
			
		}
		
		return $result;
	}

	public function register($params)
	{
		$result = new stdClass();
		$result->result = false;

		try {

			$form = array();
			$form['UserFrontType'] = 'F';
			$form['Username'] = $params['txtEmail'];
			$form['Password'] = md5($params['txtPassword']);

			$form['TitleName'] = empty($params['txtTitle']) ? null : $params['txtTitle'];
			$form['FirstName'] = empty($params['txtFirstName']) ? null : $params['txtFirstName'];
			$form['LastName'] = empty($params['txtLastName']) ? null : $params['txtLastName'];

			$form['Gender'] = empty($params['lstGender']) ? null : $params['lstGender'];
			if($params['lang'] == 'th'){
			    $form['BirthDate'] = empty($params['txtBirthday']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL($params['txtBirthday']);
			}else{
			    $form['BirthDate'] = empty($params['txtBirthday']) ? null : Emwork_Utility_DateTime::dateEngToMySQL($params['txtBirthday']);
			}
			$form['Telephone'] = empty($params['txtTelephone']) ? null : $params['txtTelephone'];
			$form['Fax'] = empty($params['txtFax']) ? null : $params['txtFax'];
			$form['Mobile'] = empty($params['txtMobile']) ? null : $params['txtMobile'];
			$form['Email'] = $params['txtEmail'];
			$form['ProfileImageID'] = empty($params['profilePictureId']) ? null : $params['profilePictureId'];
			$form['Created'] = date('Y-m-d H:i:s');
			$form['IsEnable'] = 'N';
			$form['IsDeleted'] = 'N';

			$result = $this->db->insert('sys_user', $form);
			$userId = $this->db->lastInsertId();
			
			// สร้าง Activate code
			$uniqid = uniqid();
			$activateCode = $uniqid . md5($uniqid . $userId);
			$verifyCode = rand(100000,999999);
			
			// Update created by
			$form = array();
			$form['CreatedBy'] = $userId;
			$form['ActivateCode'] = $activateCode;
			$form['VerifyCode'] = $verifyCode;
			$where['ID = ?'] = $userId;
			$result = $this->db->update('sys_user', $form, $where);

			$data->result = $result > 0;
			$data->id = $userId;
			$data->activateCode = $activateCode;
			$data->verifyCode = $verifyCode;

		} catch (Exception $e) {

		}
		return $data;
	}

	function getInfoByEmail($email) {
		$db = Zend_Db_Table::getDefaultAdapter();
	
		$data = null;
		try {
			$select = $db->select()
			->from(array('tbl' => 'sys_user'), array('tbl.*'))
			->where("tbl.UserFrontType = 'F' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.Email = ?", $email)
			;
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
	
		}
		return $data;
	}	

	public function getInfoActivateCode($activateCode) {
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$data = null;
		try {
			$select = $db->select()
			->from(array('tbl' => 'sys_user'), array('tbl.*'))
			->where("tbl.ActivateCode = ?", $activateCode);
			
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function updateProperty($userID, $paramArray) {
		
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
}
?>