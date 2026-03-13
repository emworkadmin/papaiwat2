<?php
class Faq_Model_Question {
	
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

	function save($Lang, $FullName, $Email, $Question) {
		$data = new stdClass();
		$data->result = false;
		try {
			$form = array();
			$where = array();
			$form['Lang'] = $Lang;
			$form['FullName'] = $FullName;
			$form['Email'] = $Email;
			$form['Question'] = $Question;
			$form['QuestionDate'] = date("Y-m-d H:i:s");
			$form['QuestionBy'] = empty($this->UserID) ? null : $this->UserID;
			$form['Status'] = 'W';
			$form['Created'] = date("Y-m-d H:i:s");
			$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
			$result = $this->db->insert("faq_question", $form);
			
			if($result){
				$data->result = true;
			}
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function getAdminEmail() {
		$select = $this->db->select()
		->from(array('tbl' => 'sys_user'), array('tbl.ID','tbl.Email', 'FirstName', 'LastName'))
		->joinInner(array('uga' => 'sys_usergroup_action') , "tbl.UsergroupID = uga.UsergroupID" , '')
		->joinInner(array('sa' => 'sys_action') , "uga.ActionID = sa.ID" , '')
		->where("tbl.Email is not null")
		->where("sa.ID = 'FAQ06'")
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();

		return $data;
	}
}

?>
