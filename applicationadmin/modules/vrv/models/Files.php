<?php

class Vrv_Model_Files {
	
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
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {

			$selectInfo = $this->db->select()
			->from(array('tbl' => 'vrv_item_info'), array('ID','VirtualRealityFileID'))
			->where("tbl.IsDeleted ='N'");

			$select = $this->db->select()
			->from(array('tbl' => 'vrv_item_files'), '')
			->joinLeft(array('f' => 'file_info') , 'tbl.FileInfoID = f.ID' , '')
			->joinLeft(array('u' => 'sys_user') , 'tbl.CreatedBy = u.ID' , '')
			->joinLeft(array('i' => $selectInfo) , 'tbl.FileInfoID = i.VirtualRealityFileID' , '')
			->columns(array(
				"tbl.ID",
				"tbl.FileInfoID",
				"tbl.Created",

				"f.Name",
				"f.HashName",
				"f.FileType",
				"f.FileSize",
				"f.Extension",

				"CreatedBy" => "concat(ifnull(u.FirstName,''), ' ', ifnull(u.LastName,''))",

				"Status" => "(case when i.ID is not null then 'Y' else 'N' end)"
			))
			->where("tbl.IsDeleted ='N'")
			;
			
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("f.Name like " . $this->db->quote("%" . $val . "%" ));
			}
			
			$records = $this->getListCount($select);
			
			if (empty($sidx)) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			->limitPage($page, $rows)
			;
			if (in_array($rowSort, array('Name'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		return $data;
	}
	
	function getListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}
	
	function listDelete($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			
			$selectInfo = $this->db->select()
			->from(array('tbl' => 'vrv_item_info'), array('ID','VirtualRealityFileID'))
			->where("tbl.IsDeleted ='N'");
			
			$affRow = 0;
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {

					$select = $this->db->select()
					->from(array('tbl' => 'vrv_item_files'), '')
					->joinLeft(array('f' => 'file_info') , 'tbl.FileInfoID = f.ID' , '')
					->joinLeft(array('i' => $selectInfo) , 'tbl.FileInfoID = i.VirtualRealityFileID' , '')
					->columns(array(
						"tbl.ID",
						"tbl.FileInfoID",
						"f.HashName", 
						"f.Path",
						"Status" => "(case when i.ID is not null then 'Y' else 'N' end)"
					))
					->where("tbl.IsDeleted = 'N'")
					->where("tbl.ID = ?", $id)
					;
					$stmt = $this->db->query($select);
					$obj = $stmt->fetchObject();

					if($obj->Status=='Y'){
						continue;
					}

					if($obj->ID){
						$form = array ();
						$where = array ();
						$form['IsDeleted'] = 'Y';
						$form['Modified'] = $modified;
						$form['ModifiedBy'] = $this->UserID;
						$where['ID = ? '] = $obj->ID;
						$affRow += $this->db->update("vrv_item_files", $form, $where);

						$form = array ();
						$where = array ();
						$form['IsDeleted'] = 'Y';
						$form['Modified'] = $modified;
						$form['ModifiedBy'] = $this->UserID;
						$where['ID = ? '] = $obj->FileInfoID;
						$this->db->update("file_info", $form, $where);

						// Remove VR zip file
						$vrZipPath = APPLICATION_ROOT . DS . 'files' . DS . $obj->Path . DS . $obj->HashName;
						$vrZipPath = str_replace('/', DS, $vrZipPath);
						$vrZipPath = str_replace("\\", DS, $vrZipPath);
						if(file_exists($vrZipPath)){
							unlink($vrZipPath);
						}

						// Remove VR public file
						$vrPublicPath = APPLICATION_ROOT . DS . 'public' . DS . 'vrv' . DS . str_replace('.zip','',$obj->HashName);
						if(file_exists($vrPublicPath)){
							$this->deleteDir($vrPublicPath);
						}
					}

				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function save($fileInfoID) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			if ($fileInfoID) {
				$form = array ();
				$form['FileInfoID'] = $fileInfoID;
				$form['Created'] = date("Y-m-d H:i:s");
				$form['CreatedBy'] = $this->UserID;
				$data->result = $this->db->insert("vrv_item_files", $form);
			}
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	private function deleteDir($dirPath) {
		if (! is_dir($dirPath)) {
			throw new InvalidArgumentException("$dirPath must be a directory");
		}
		if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
			$dirPath .= '/';
		}
		$files = glob($dirPath . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				self::deleteDir($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dirPath);
	}
}

?>
