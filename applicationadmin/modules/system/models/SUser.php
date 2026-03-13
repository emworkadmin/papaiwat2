<?php
/**
 * EM D2MS
 *
 * @copyright	Copyright (c) 2018 EMWORK Group
 * @license		EMWORK Group
 * @version		1.0.0
 * @since			2018-03-03
 * @author 		EMWORK Group
 *
 */

class System_Model_SUser {

	/**
	 * 
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $db = null;
	protected $errorMessage = '';
	
	protected $dataUser = null;
	protected $actions = array();
	
	protected static $instances = array();

	function __construct($userID = null) {
		// Init
		try {
			// setup database
			$this->db = Zend_Db_Table::getDefaultAdapter();
			
			$this->loadAll($userID);
		} catch (Exception $e) {
			$errorMessage = $e->getMessage();
		}
	}
	
	/**
	 * @return string
	 */
	public function getErrorMessage() {
		return $this->errorMessage;
	}
	
	public function hasData() {
		if (empty($this->dataUser->ID)) {
			return false;
		}
		return true;
	}
	
	protected function loadAll($userID) {
		
		// Load UserInfo & UserGroup
		$select = $this->db->select()
		->from(array("tbl" => "sys_user"), '')
		->joinLeft(array("ug" => "sys_usergroup"), "tbl.UsergroupID = ug.ID", '')
		->columns(array(
				"ID" => "tbl.ID", 
				"Username" => "tbl.Username", 
				"UserGroupID" => "tbl.UsergroupID", 
				"UserType" => "tbl.UserType", 
				"UserSubType" => "tbl.UserSubType", 
				"FirstName" => "tbl.FirstName", 
				"LastName" => "tbl.LastName", 
				"Email" => "tbl.Email", 
				"Telephone" => "tbl.Telephone", 
				"LastLoginDate" => "tbl.LastLoginDate", 
				"IsEnable" => "tbl.IsEnable", 
				"IsDeleted" => "tbl.IsDeleted",
				"UserGroupName" => "ug.name"
		))
		->where("tbl.ID = ?", $userID);
		
		$stmt = $this->db->query($select);
		$this->dataUser = $stmt->fetchObject(); 
		if ($this->dataUser == null) {
			$this->dataUser = new stdClass();
		}
		
		if ($this->dataUser->UserGroupID == null) {
			$this->dataUser->UserGroupID = '';
		}
		
		// Load Action
		$select = $this->db->select()
		->from(array('tbl' => 'sys_usergroup_action'), array("*"))
		->joinLeft(array('a' => 'sys_action'), "tbl.ActionID = a.ID", array("ActionCode" => "Code"))
		->where("tbl.IsDeleted = 'N' ")
		->where("tbl.UsergroupID = ? ", $this->dataUser->UserGroupID)
		;
		$stmt = $this->db->query($select);
		$actions = $stmt->fetchAll();		
		if (count($actions) > 0) {
			foreach ($actions as $action) {
				$this->actions[] = strtolower($action["ActionCode"]);
			}
		}
		
	}
	
	/**
	 * @return stdClass
	 */
	public function getInfo() {
		return $this->dataUser;
	}
	
	/**
	 * 
	 * @param string $actionCode
	 * @return boolean
	 */
	public function isAllow($actionCode) {
		if (in_array(strtolower($actionCode), $this->actions)) {
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 * @param string|int $userID
	 * @return System_Model_SUser 
	 */
	public static function getInstance($userID = null) {
		if (empty($userID)) {
			$authobj = Zend_Auth::getInstance();
			$auth = $authobj->getIdentity();
			$userID = isset($auth->UserID) ? $auth->UserID : null;
		}
		
		if (empty($userID)) {
			return new System_Model_SUser();
		}
		
		// Check if the user ID is already cached.
		if (empty(self::$instances[$userID]))
		{
			$user = new System_Model_SUser($userID);
			self::$instances[$userID] = $user;
		}
		
		return self::$instances[$userID];
	}
}