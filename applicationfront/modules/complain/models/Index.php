<?php

class Complain_Model_Index {
	
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
			
		}
    }
	
	public function getResponseMessage($lang)
	{
		$data = array();
		
		try {
			$select = $this->db->select()
			->from(array("tbl" => ""), "")
			->where("tbl.lang = ", $lang);
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		
		return $data;
	}
	
	public function save($params)
	{
		$data = new stdClass();
		$data->result = false;
		
		try {
			
			$from = array();
			
			$from['Lang'] = $params['Lang'];
			$from['FullName'] = $params['FullName'];
			$from['Email'] = $params['Email'];
			$from['Telephone'] = $params['Telephone'];
			$from['ComplainTopic'] = $params['Topic'];
			$from['ComplainDetail'] = $params['Detail'];
			$from['FileInfoID'] = empty($params['IntroImageID']) ? null : $params['IntroImageID'];
			$from['ComplainUserID'] = empty($this->userID) ? null : $this->UserID;
			$from['ComplainDate'] = date("Y-m-d H:i:s");
			$from['Status'] = 'W';
			$from['Created'] = date("Y-m-d H:i:s");
			$from['CreatedBy'] = empty($this->userID) ? null : $this->UserID;
			$result = $this->db->insert("complain_info", $from);
			
			if($result) {
				$data->result = true;
			}
			
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		return $data;
	}
	
	function getReCaptchakey() {
		$result = array();
		
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sys_setting'), "")
			->columns(array(
				"tbl.Name",
				"tbl.Value"
			))
			->where("tbl.GroupName = 'General'")
			->where("tbl.Name LIKE '%Captcha%'")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			foreach ($data as $key => $value) {
				$result[$value["Name"]] = $value["Value"]; 
			}
			
			
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		return $result;
	}

	function getAdmin()
	{
		$data = array();

		try {

			$select = $this->db->select()
			->from(array('s' => 'sys_user'), '')
			->joinInner(array('su' => 'sys_usergroup'), 's.UsergroupID = su.ID', '')
			->joinInner(array('sua' => 'sys_usergroup_action'), 'su.ID = sua.UsergroupID', '')
			->joinInner(array('sa' => 'sys_action'), 'sua.ActionID = sa.ID', '')
			->columns(array(
				"s.ID",
				"s.Email",
				"sua.UsergroupID",
				"sa.ID",
				"FullName" => "concat(s.TitleName,' ',s.FirstName,' ',s.LastName)"
			))
			->where("s.UserType = 'B'")
			->where("sua.ActionID = 'COMPLAIN02'")
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}

}
	

?>