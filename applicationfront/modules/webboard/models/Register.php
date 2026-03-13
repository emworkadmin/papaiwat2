<?php

class Webboard_Model_Register {

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
	
	/**
	 * 
	 * @param string $username
	 * @return boolean	return TRUE ถ้ามี Username อยู่ในระบบ, return FALSE ถ้าเป็นกรณีอื่นๆ
	 */
	public function checkUsername($username) {
		$result = false;
		try {
			$select = $this->db->select()->from(array('tbl' => 'webboard_user'), array('Username'))->where("Username = ?", $username)->where("IsDeleted = ?", 'N');
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			$result = !empty($data->Username);
		} catch (Exception $e) {
			
		}
		
		return $result;
	}
	
	/**
	 * 
	 * @param string $email
	 * @return boolean	return TRUE ถ้ามี Email อยู่ในระบบ, return FALSE ถ้าเป็นกรณีอื่นๆ
	 */
	public function checkEmail($email) {
		$result = false;
		try {
			$select = $this->db->select()->from(array('tbl' => 'webboard_user'), array('Email'))->where("Email = ?", $email)->where("IsDeleted = ?", 'N');
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			$result = !empty($data->Email);
		} catch (Exception $e) {
			
		}
		
		return $result;
	}
	
	/**
	 *
	 * @param string $facebookId
	 * @return boolean	return TRUE ถ้ามี facebook Id อยู่ในระบบ, return FALSE ถ้าเป็นกรณีอื่นๆ
	 */
	public function checkFacebookId($facebookId) {
		$result = false;
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sys_user'), array("FacebookID"))
			->where("tbl.FacebookID = ?", $facebookId)
			->where("tbl.IsDeleted = ?", 'N');
				
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
				
			$result = !empty($data->FacebookID);
		} catch (Exception $e) {
				
		}
	
		return $result;
	}
	
	public function register($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			// $BirthDate = null;
			// if( !empty($params['lstBirthDateD']) && !empty($params['lstBirthDateM']) && !empty($params['lstBirthDateY']) ){
			// 	$BirthDate = $params['lstBirthDateD'].'/'.$params['lstBirthDateM'].'/'.$params['lstBirthDateY'];
			// 	$BirthDate = Emwork_Utility_DateTime::dateEngToMySQL($BirthDate);
			// }
			// ---------------------------------------- Insert user ---------------------------------------- //
			$form = array();
			$form['Username'] = $params['txtUser'];
			$form['Password'] = md5($params['txtPassword']);
			$form['TitleName'] = empty($params['txtTitle']) ? null : $params['txtTitle'];
			$form['FirstName'] = empty($params['txtFirstName']) ? null : $params['txtFirstName'];
			$form['LastName'] = empty($params['txtLastName']) ? null : $params['txtLastName'];
			// $form['BirthDate'] = $BirthDate; //empty($params['txtBirthDate']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL($params['txtBirthDate']);
			$form['Telephone'] = $params['txtTelephone'];
            $form['Email'] = $params['txtEmail'];
            $form['Address'] = $params['txtAddress'];
            $form['IdentityCardID'] = $params['txtCardID'];
			// $form['ProfileImageID'] = empty($params['hdfprofile_pictureID']) ? null : $params['hdfprofile_pictureID'];
			$form['Gender'] = empty($params['lstSex']) ? null : $params['lstSex'];
			// $form['UserType'] = 'F';
			$form['Created'] = date('Y-m-d H:i:s');
			$form['IsEnable'] = 'N';
			$form['IsDeleted'] = 'N';
			// echo '<pre>'; print_r($form); echo '</pre>'; exit();
			$result = $this->db->insert('webboard_user', $form);
			$userId = $this->db->lastInsertId();
			
			// สร้าง Activate code
			$uniqid = uniqid();
			$activateCode= $uniqid . md5($uniqid . $userId);
			
			// Update created by
			$form = array();
			$form['CreatedBy'] = $userId;
			$form['ActivateCode'] = $activateCode;
			$where['ID = ?'] = $userId;
			$result = $this->db->update('webboard_user', $form, $where);
						
			$data->result = $result > 0;
			$data->id = $userId;
			$data->activateCode= $activateCode;
			
		} catch (Exception $e) {
		}
		return $data;		
	}
	
	public function registerFacebook($params) {
		//echo '<pre>'; print_r($params); echo '</pre>'; exit();
		
		$data = new stdClass();
		$data->result = false;
		try {
			
			$BirthDate = null;
			if( !empty($params['lstBirthDateD']) && !empty($params['lstBirthDateM']) && !empty($params['lstBirthDateY']) ){
				$BirthDate = $params['lstBirthDateD'].'/'.$params['lstBirthDateM'].'/'.$params['lstBirthDateY'];
				$BirthDate = Emwork_Utility_DateTime::dateEngToMySQL($BirthDate);
			}
				
			// ---------------------------------------- Insert user ---------------------------------------- //
			$form = array();
			$form['Username'] = $params['email'];
			$form['Password'] = null;
			$form['TitleName'] = empty($params['txtTitle']) ? null : $params['txtTitle'];
			$form['FirstName'] = empty($params['txtFirstName']) ? null : $params['txtFirstName'];
			$form['LastName'] = empty($params['txtLastName']) ? null : $params['txtLastName'];
			$form['BirthDate'] = $BirthDate; //empty($params['txtBirthDate']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL($params['txtBirthDate']);
			$form['Telephone'] = $params['txtTelephone'];
			$form['Email'] = $params['email'];
			$form['ProfileImageID'] = empty($params['profilePictureId']) ? null : $params['profilePictureId'];
			$form['Sex'] = empty($params['lstSex']) ? null : $params['lstSex'];
			$form['FacebookID'] = $params['facebook_id'];
			$form['FacebookName'] = $params['facebook_name'];
			$form['UserType'] = 'F';
			$form['Created'] = date('Y-m-d H:i:s');
			$form['IsEnable'] = 'Y';
			$form['IsDeleted'] = 'N';
			
			$result = $this->db->insert('sys_user', $form);
			$userId = $this->db->lastInsertId();
				
			$data->result = $result > 0;
			$data->id = $userId;
				
		} catch (Exception $e) {
				
		}
		return $data;
	}
}

?>
