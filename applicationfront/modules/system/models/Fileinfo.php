<?php

class System_Model_Fileinfo {

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
	
	function item($id) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'file_info'), array("*"))
			->where("ID = ?", $id)
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function itemByHashName($hashName) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'file_info'), array("*"))
			->where("HashName = ?", $hashName)
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function save($baseName, $hashBaseName, $path, $fileSize, $ext, $fileType) {
		$data = new stdClass();
		$data->result = false;
		try {
			$form["Name"] = $baseName;
			$form["HashName"] = $hashBaseName;
			$form["FileSize"] = $fileSize;
			$form["Extension"] = $ext;
			$form["FileType"] = $fileType;
			$form["Path"] = $path;
			$form["Created"] = date('Y-m-d H:i:s');
			$form["CreatedBy"] = empty($this->UserID) ? null : $this->UserID;
			$form["IsDeleted"] = 'N';
			$result = $this->db->insert('file_info', $form);			
			$id = $this->db->lastInsertId();

			$data->result = $result > 0;
			$data->id = $id;
		} catch (Exception $e) {
			
		}
		return $data;
	}
}

?>
