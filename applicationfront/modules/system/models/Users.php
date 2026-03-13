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
	
	public static function login($username, $password) {
		$db = Zend_Db_Table::getDefaultAdapter();

		$data = null;
		try {
			$select = $db->select()
			->from(array('tbl' => 'sys_user'), array('tbl.*'))
			//->joinLeft(array('d' => 'sys_user_more_detail'), "tbl.id = d.user_id", array("facebook_id", "facebook_name"))
			->where("tbl.IsDeleted = ?", "N")
			->where("tbl.Username = ?", $username)
			->where("tbl.Password = ?", md5($password))
			;
		
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
				
		}
		return $data;
	}
	
	public static function getInfoByID($userID) {
		$db = Zend_Db_Table::getDefaultAdapter();

		$data = null;
		try {
			$select = $db->select()
			->from(array('tbl' => 'sys_user'), array('tbl.*'))
			->where("tbl.ID = ?", $userID);
		
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	public static function getInfoByFacebookID($facebookID) {
		$db = Zend_Db_Table::getDefaultAdapter();

		$data = null;
		try {
			$select = $db->select()
			->from(array('tbl' => 'sys_user'), array('tbl.*'))
			->joinInner(array('md' => 'sys_user_more_detail'), "tbl.id = md.user_id", "")
			->where("tbl.is_deleted = 'N' ")
			->where("md.facebook_id = ?", $facebookID);
		
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	public static function getInfoByUsername($username) {
		$db = Zend_Db_Table::getDefaultAdapter();

		$data = null;
		try {
			$select = $db->select()
			->from(array('tbl' => 'sys_user'), array('tbl.*'))
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.UserType = 'F' ")
			->where("tbl.Username = ?", $username);
		
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
				
		}
		return $data;
	}
	

	public static function getInfoByEmail($email) {
		$db = Zend_Db_Table::getDefaultAdapter();
	
		$data = null;
		try {
			$select = $db->select()
			->from(array('tbl' => 'sys_user'), array('tbl.*'))
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.UserType = 'F' ")
			->where("tbl.Email = ?", $email);
	
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
	
		}
		return $data;
	}
	
	public static function getInfoActivateCode($activateCode) {
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$memberFullnameTH = "(concat(m.title_name_other_th, m.first_name_th, ' ', m.last_name_th))";
		$memberFullnameEN= "(concat(m.title_name_other_en, m.first_name_en, ' ', m.last_name_en))";
		
		$data = null;
		try {
			$select = $db->select()
			->from(array('tbl' => 'sys_users'), array('tbl.*'))
			->joinLeft(array("m" => "member_info"), "tbl.id = m.user_id", array(
					 "memberFullnameTH" => $memberFullnameTH
					,"memberFullnameEN" => $memberFullnameEN
			))
			->where("tbl.activate_code = ?", $activateCode)
			->where("tbl.user_type = 'F'")
			;
			
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
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
}

?>
