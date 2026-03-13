<?php

class System_Model_Email {

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
	 * @param number $limit
	 */
	public static function getList($limit = 50) {
		if (empty($limit))
			$limit = 50;
		
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$data = array();
		try {
			$select = $db->select()->from(array("tbl" => 'email_queue'), array('*'))
			->where("is_success is null")
			->order("priority desc")
			->order("created")
			->limitPage(1, $limit)
			;
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	/**
	 * 
	 * @param int $id
	 * @param bool $result
	 */
	public static function updateStatus($id, $result) {
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$data = new stdClass();
		$data->result = false;
		try {
				
			// update
			$form = array();
			$where = array();
			$form['is_success'] = $result ? 'Y' : 'N';
			$form['modified'] = date('Y-m-d H:i:s');
			$where["id = ?"] = $id;
			$result = $db->update('email_queue', $form, $where);
				
			$data->result = $result > 0;
		} catch (Exception $e) {
				
		}
		return $data;
	}
	
	/**
	 * 
	 * @param string $toEmail
	 * @param string $subject
	 * @param string $body
	 * @param string $attach
	 * @param int $priority
	 */
	public static function insert ($toEmail, $subject, $body, $attach, $priority) {
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$authobj = Zend_Auth::getInstance();
		$userId = null;
		if ($authobj->hasIdentity()) {
			$userId = $authobj->getIdentity()->UserID;
		}
		
		$data = new stdClass();
		$data->result = false;
		try {
			
			// Insert 
			$form = array();
			$form['to_email'] = $toEmail;
			$form['subject'] = $subject;
			$form['body'] = $body;
			$form['attach'] = empty($attach) ? null : $attach;
			$form['priority'] = $priority;
			$form['is_success'] = null;
			$form['created_by'] = $userId;
			$form['created'] = date('Y-m-d H:i:s');
			$result = $db->insert('email_queue', $form);
			
			$data->result = $result > 0;
		} catch (Exception $e) {
			
		}
		return $data;		
	}
}

?>
