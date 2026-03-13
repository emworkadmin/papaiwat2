<?php

class Profile_Model_Profile {

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
	
	function getInfo($userID) {
		$data = new stdClass();
		try {
			
			$select = $this->db->select()	
			->from(array('tbl' => 'sys_user'), array("tbl.*"))
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
		
	function update($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$form = array();	
			$form['TitleName'] = $params['txtTitle'];
			$form['FirstName'] = $params['txtFirstName'];
			$form['LastName'] = $params['txtLastname'];
			$form['Gender'] = $params['lstGender'];
			$form['BirthDate'] = empty($params['txtBirthDate']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL($params['txtBirthDate']);
			$form['Telephone'] = $params['txtTelephone'];
			$form['Mobile'] = $params['txtMobile'];
			$form['Fax'] = $params['txtFax'];
			$form['Modified'] = date('Y-m-d H:i:s');
			$form['ModifiedBy'] = $this->UserID;
			$whereUser['ID = ? '] = $this->UserID;
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
			$where = array();
			$form['Password'] = md5( $params['txtNewPassword'] );
			$form['Modified'] = date('Y-m-d H:i:s');
			$form['ModifiedBy'] = $this->UserID;
			$where["ID = ?"] = $this->UserID;
			$result = $this->db->update('sys_user', $form, $where);
			
			if($result){
				$data->result = true;
				$data->id = $this->UserID;
			}
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;		
	}
}

?>
