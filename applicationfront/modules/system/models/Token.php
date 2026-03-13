<?php

class System_Model_Token {

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
	
	public static function getInfo($token, $type) {
		$db = Zend_Db_Table::getDefaultAdapter();

		$data = null;
		try {
			$select = $db->select()
			->from(array('tbl' => 'token'), array('*'))
			->where("tbl.token = ?", $token)
			->where("tbl.token_type = ?", $type)
			;
		
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
				
		}
		return $data;
	}
	
	public static function getListByUser($userID, $type) {
		$db = Zend_Db_Table::getDefaultAdapter();

		$data = array();
		try {
			$select = $db->select()
			->from(array('tbl' => 'token'), array('*'))
			->where("tbl.user_id = ?", $userID)
			->where("tbl.token_type = ?", $type)
			;
		
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
				
		}
		return $data;
	}
	
	/**
	 * 
	 * @param string $token
	 * @param string $type
	 * @param bool $isUsed
	 */
	public static function updateStatus($token, $type, $isUsed) {
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$data = new stdClass();
		$data->result = false;
		try {
				
			// update
			$form = array();
			$where = array();
			$form['is_used'] = $isUsed ? 'Y' : 'N';
			$form['modified'] = date('Y-m-d H:i:s');
			$where["token = ?"] = $token;
			$where["token_type = ?"] = $type;
			$result = $db->update('token', $form, $where);
				
			$data->result = $result > 0;
		} catch (Exception $e) {
				
		}
		return $data;
	}
	
	public static function insert ($userID, $tokenType, $expiredDate, $ipAddress) {
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$data = new stdClass();
		$data->result = false;
		try {
			$uniqid = uniqid();
			$token = strtolower($tokenType) . $uniqid . md5($uniqid . strtolower($tokenType));			
			
			// Insert 
			$form = array();
			$form['user_id'] = $userID;
			$form['token_type'] = $tokenType;
			$form['token'] = $token;
			$form['expired_date'] = empty($expiredDate) ? null : $expiredDate;
			$form['is_used'] = 'N';
			$form['created_ip'] = $ipAddress;
			$form['created'] = date('Y-m-d H:i:s');
			$result = $db->insert('token', $form);
			
			$data->result = $result > 0;
			$data->token = $token;
		} catch (Exception $e) {
			
		}
		return $data;		
	}
}

?>
