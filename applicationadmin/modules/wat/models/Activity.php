<?php

class Wat_Model_Activity {
	
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
			
			$select = $this->db->select()
			->from(array('tbl' => 'wat_activity_info'), '')
			->joinLeft(array('d' => 'wat_activity_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->columns(array(
					"ID" => "tbl.ID",
					"Ordering" => "tbl.Ordering",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"Name" => "d.Name",
					"AuthorName" => "concat(u.FirstName, ' ', u.LastName)"
			))
			->where("tbl.IsDeleted ='N'")
			;
			
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("d.Name like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['isEnable'])) {
				$select = $select->where("tbl.IsEnable = ? ", $advancesearch['isEnable']);
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
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
				}
			}
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
	
	function getInfo($id) {
		$data = new stdClass();
		try {
			$select= $this->db->select()->distinct()
			->from(array('tbl' => 'wat_activity_info'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"IconImageID" => "tbl.IconImageID",
					"Ordering" => "tbl.Ordering",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted"
			))
			->where("tbl.ID = ? " , $id)
			;
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getDataByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'wat_activity_info'), '')
			->joinLeft(array('d' => 'wat_activity_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array("fi" => "file_info"), "tbl.IconImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Ordering" => "tbl.Ordering",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
					
					"Lang" => "d.Lang",
					"Name" => "d.Name",
					
					"IconImageID" => "tbl.IconImageID",
					"IconImageFileName" => "fi.Name",
					"IconImageHashName" => "fi.HashName",
					"IconImageFileType" => "fi.FileType",
					"IconImageFileSize" => "fi.FileSize",
					"IconImageFileExtension" => "fi.Extension",
			))
			->where("tbl.ID = ? " ,$id)
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
	
	function save($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$id = '';
			$modified = date("Y-m-d H:i:s");
			$form = array();
			$form['IconImageID'] = $params['IconImageID'];
			$form['Ordering']= empty($param[$defaultLanguageCode]['Ordering']) ? null : $param[$defaultLanguageCode]['Ordering'];
			$form['Created'] = $modified;
			$form['CreatedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("wat_activity_info", $form);
			$id = $this->db->lastInsertId();

			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$formDetail['ID'] = $id;
					$formDetail['Lang'] = $lang;
					$formDetail['Name'] = $value['Name'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("wat_activity_detail", $formDetail);
				}
			}
			if($result){
				$data->id = $id;
				$data->result = true;
			}

		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function update($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$id = $params['hdfID'];
			$modified = date("Y-m-d H:i:s");
			$form = array();
			$where = array();
			$form['IconImageID'] = $params['IconImageID'];
			$form['Ordering']= empty($param[$defaultLanguageCode]['Ordering']) ? null : $param[$defaultLanguageCode]['Ordering'];
			$form['Modified'] = $modified;
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$where['ID = ? '] = $id;
			$result = $this->db->update("wat_activity_info", $form, $where);
			
			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$whereDetail = array();
					$formDetail['Name'] = $value['Name'];
					$formDetail['Modified'] = $modified;
					$whereDetail['ID = ? '] = $id;
					$whereDetail['Lang = ? '] = $lang;
					$affRow = $this->db->update("wat_activity_detail", $formDetail, $whereDetail);
					if ($affRow == 0) {
						$formDetail['ID'] = $id;
						$formDetail['Lang'] = $lang;
						$formDetail['Created'] = $modified;
						$formDetail['IsEnable'] = 'Y';
						$formDetail['IsDeleted'] = 'N';
						$this->db->insert("wat_activity_detail", $formDetail);
					}
				}
			}
			
			if($result){
				$data->id = $id;
				$data->result = true;
			}
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function delete($params) {
		$data = new stdClass();
		$data->result = false;
		try {

			$modified = date("Y-m-d H:i:s");
			
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $params['id'];
			$result = $this->db->update("wat_activity_info", $form, $where);

			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	public function getOption($lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'wat_activity_info'), array( 'ID'))
		->joinInner(array('d' => 'wat_activity_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->order("convert(d.Name USING tis620) ASC")
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		$result = array();
		
		foreach ($data as $value) {
			$result[$value["ID"]] = $value["Name"];
		}
		return $result;
	}

	function listPublish($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			$status = $params['status'];
			$affRow = 0;
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {
					$form = array ();
					$where = array ();
					$form['IsEnable'] = empty($status) ? 'N' : $status;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = $this->UserID;
					$where['ID = ? '] = $id;
					$affRow += $this->db->update("wat_activity_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function listCopy($params) { 
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			$defaultLanguage = $params['defaultLanguage'];
			
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {

					// Info
					$selectInfo = $this->db->select ()
					->from(array('tbl'=>'wat_activity_info'), array(
							'ID',
							'IconImageID',
							'Ordering'
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['Ordering'] = $dataInfo->Ordering;
					$form['IconImageID'] = empty($dataInfo->IconImageID) ? null : $dataInfo->IconImageID;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("wat_activity_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'wat_activity_detail'), array('Lang','Name'))
						->where("tbl.ID = ? ", $id)
						;
						$stmtDetail = $this->db->query($selectDetail);
						$dataDetail = $stmtDetail->fetchAll();

						if(count($dataDetail)>0){
							foreach($dataDetail as $d){
								$form = array();
								$form['ID'] = $tmpID;
								$form['Lang'] = empty($d['Lang']) ? null : $d['Lang'];
								$form['Name'] = empty($d['Name']) ? null : $d['Name'];
								if($defaultLanguage == $d['Lang']){
									$form['Name'] = $form['Name'] . ' (Copy)';
								}
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("wat_activity_detail", $form);
							}
						}

					}

				} // End Foreach
			} // End Count
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function listDelete($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			
			$affRow = 0;
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {
					$form = array ();
					$where = array ();
					$form['IsDeleted'] = 'Y';
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = $this->UserID;
					$where['ID = ? '] = $id;
					$affRow += $this->db->update("wat_activity_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
}

?>
