<?php

class System_Model_Picture {

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
	
	function item($id) {
		$data = new stdClass();
		try {
			$select = $this->db->select()->from(array('tbl' => 'picture'), array('*'))->where("id = ?", $id);

			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function itemByHashName($hashName) {
		$data = new stdClass();
		try {
			$select = $this->db->select()->from(array('tbl' => 'picture'), array('*'))->where("hash_name = ?", $hashName);

			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function save($baseName, $hashBaseName, $path, $fileSize, $ext, $fileType) {
		$data = new stdClass();
		$data->result = false;
		try {
			$form["name"] = $baseName;
			$form["hash_name"] = $hashBaseName;
			$form["file_size"] = $fileSize;
			$form["extension"] = $ext;
			$form["file_type"] = $fileType;
			$form["path"] = $path;
			$form["is_deleted"] = 'N';
			$form["created"] = date('Y-m-d H:i:s');
			$form["created_by"] = empty($this->UserID) ? null : $this->UserID;
			$result = $this->db->insert('picture', $form);
			
			$id = $this->db->lastInsertId();

			$data->result = $result > 0;
			$data->id = $id;
		} catch (Exception $e) {
			print_r($e); exit;
		}
		return $data;
	}
}

?>
