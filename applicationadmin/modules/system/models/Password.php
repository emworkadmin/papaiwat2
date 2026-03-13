<?php

class System_Model_Password {

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

	function getUserByUserID($UserID) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sys_user'), array(
					'ID',
					'Username',
					'Password'
			))
			->where("tbl.ID = ? ", $UserID);
	
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
				
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
			$where["ID = ?"] = $params['user_id'];
			$result = $this->db->update('sys_user', $form, $where);
				
			if($result){
				$data->result = true;
			}
				
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
}

?>
