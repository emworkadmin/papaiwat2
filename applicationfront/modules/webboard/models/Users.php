<?php

class Webboard_Model_Users {

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
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.FacebookID = ?", $facebookID);
		
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
			->from(array('tbl' => 'webboard_user'), array('tbl.*'))
			->where("tbl.IsDeleted = 'N' ")
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
			->from(array('tbl' => 'webboard_user'), array('tbl.*'))
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.Email = ?", $email);
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
	
		}
		return $data;
	}
	
	public static function getInfoActivateCode($activateCode) {
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$Fullname = "(concat(tbl.TitleName, tbl.FirstName, ' ', tbl.LastName))";
		
		$data = null;
		try {
			$select = $db->select()
			->from(array('tbl' => 'webboard_user'), array('tbl.*'))
			->columns(array(
					"Fullname" => $Fullname
			))
			->where("tbl.ActivateCode = ?", $activateCode)
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
		
		$result = $db->update('webboard_user', $TBL, $where);
		return $result;
	}
}

?>
