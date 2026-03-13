<?php

class Intropage_Model_Info {
	
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
			->from(array('tbl' => 'intropage_info'), '')
			->joinLeft(array('d' => 'intropage_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->columns(array(
					"ID" => "tbl.ID",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"Name" => "tbl.Name",
			))
			->where("tbl.IsDeleted ='N'");
			
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("tbl.Name like " . $this->db->quote("%" . $val . "%" ));
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
			//->order("$rowSort $sord")
			->limitPage($page, $rows)
			;
			if (in_array($rowSort, array('Title','CategoryTitle'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data->PublishDate = empty($data->PublishDate) ? null : new DateTime($data->PublishDate);
					$data->PublishDown = empty($data->PublishDown) ? null : new DateTime($data->PublishDown);
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
			->from(array('tbl' => 'intropage_info'), '')
			->joinLeft(array("d" => "intropage_detail"), "tbl.ID = d.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Name" => "tbl.Name",
					"BackgroundColor" => "tbl.BackgroundColor",
					"ShowButton" => "tbl.ShowButton",
					"ButtonTextColor" => "tbl.ButtonTextColor",
					"ButtonBackgroundColor" => "tbl.ButtonBackgroundColor",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"Css"=>"tbl.Css",
					"Button" => "d.Button",
					
				
				
					"Detail" => "d.Detail",
					"ButtonText" => "d.ButtonText",
			))
			->where("tbl.ID = ? " , $id);
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchObject();

			if (!empty($data->ID)) {
				$data->PublishDate = empty($data->PublishDate) ? null : new DateTime($data->PublishDate);
				$data->PublishDown = empty($data->PublishDown) ? null : new DateTime($data->PublishDown);
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
		
	function getAllInfo() {
		$data = new stdClass();
		try {
			$select= $this->db->select()->distinct()
			->from(array('tbl' => 'intropage_info'), '')
			->joinLeft(array("d" => "intropage_detail"), "tbl.ID = d.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Name" => "tbl.Name",
					"BackgroundColor" => "tbl.BackgroundColor",
					"ShowButton" => "tbl.ShowButton",
					"ButtonTextColor" => "tbl.ButtonTextColor",
					"ButtonBackgroundColor" => "tbl.ButtonBackgroundColor",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
				
					"Detail" => "d.Detail",
					"ButtonText" => "d.ButtonText",
			));
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchAll();

			if (!empty($data->ID)) {
				$data->PublishDate = empty($data->PublishDate) ? null : new DateTime($data->PublishDate);
				$data->PublishDown = empty($data->PublishDown) ? null : new DateTime($data->PublishDown);
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
			->from(array('tbl' => 'intropage_info'), '')
			->joinLeft(array('d' => 'intropage_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"Name" => "tbl.Name",
					"BackgroundColor" => "tbl.BackgroundColor",
					"ShowButton" => "tbl.ShowButton",
					"ButtonTextColor" => "tbl.ButtonTextColor",
					"ButtonBackgroundColor" => "tbl.ButtonBackgroundColor",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"Css"=>"tbl.Css",
					
					"Button"=>"d.Button",
					"Lang" => "d.Lang",
					"Detail" => "d.Detail",
					"ButtonText" => "d.ButtonText",
			))
			->where("tbl.ID = ? " ,$id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();

			if (!empty($data->ID)) {
				$data->PublishDate = empty($data->PublishDate) ? null : new DateTime($data->PublishDate);
				$data->PublishDown = empty($data->PublishDown) ? null : new DateTime($data->PublishDown);
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
			
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function save($params) {
		$data = new stdClass();
		$data->result = false	;
		try {
			$param = $params['params'];
			$id = '';
			$modified=date('Y-m-d H:i:s');
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}

			if($defaultLanguageCode == 'th'){
				$param[$defaultLanguageCode]['PublishDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}else {
				$param[$defaultLanguageCode]['PublishDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}

			$form = array();
			$form['Name'] = $param[$defaultLanguageCode]['Name'];
			$form['BackgroundColor'] = $param[$defaultLanguageCode]['bgColor'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['Css'] = $params['txtcss'];
			$form['Created'] = $modified;
			$form['CreatedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['UseButton'];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("intropage_info", $form);
			$id = $this->db->lastInsertId();
			 // Detail
			if(count($param)>0){
				 foreach($param as $lang=>$value){

					$formDetail = array();
					$formDetail['ID'] = $id;
					$formDetail['Lang'] = $lang;
					$formDetail['Button'] = json_encode($params['button'][$lang]);
					$formDetail['Detail'] = empty ( $value ['Detail'] ) ? null : $value ['Detail'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = $param[$defaultLanguageCode]['UseButton'];
					$formDetail['IsDeleted'] = 'N';

					$result = $this->db->insert("intropage_detail", $formDetail);
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
		
	function updateNOEna($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			$defaultLanguageCode = $params['DefaultLanguageCode'];
				$modified=date('Y-m-d H:i:s');
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$NoEnable='N';
			$form = array();
			$form['Modified'] = $modified;
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $NoEnable;
			$result = $this->db->update("intropage_info", $form);
			$data->result = $result > 0;
			$data->id = $id;
			
			 // Detail
			if(count($param)>0){
				 foreach($param as $lang=>$value){
					$param = $params['params'];
					$formDetail = array();
					$formDetail['Modified'] = $modified;
					$formDetail['ModifiedBy']= $this->UserID;
					$formDetail['IsEnable'] = $NoEnable;
					$result = $this->db->update("intropage_detail", $formDetail);
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
				$modified=date('Y-m-d H:i:s');
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$id =$params['hdfID'];

			if($defaultLanguageCode == 'th'){
				$param[$defaultLanguageCode]['PublishDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}else {
				$param[$defaultLanguageCode]['PublishDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}

			$form = array();
			$form['Name'] = $param[$defaultLanguageCode]['Name'];
			$form['BackgroundColor'] = $param[$defaultLanguageCode]['bgColor'];
			$form['ShowButton'] = $param[$defaultLanguageCode]['ShowButton'];
			$form['ButtonTextColor'] = $param[$defaultLanguageCode]['bttxtColor'];
			$form['ButtonBackgroundColor'] = $param[$defaultLanguageCode]['btColor'];
			$form['Css'] = $params['txtcss'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['Modified'] = $modified;
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= empty ( $param[$defaultLanguageCode]['IsEnable'] ) ? 'N' : $param[$defaultLanguageCode]['IsEnable'];
			$form['IsDeleted'] = 'N';
			$result = $where ["ID = ?"] = $id;
			$result = $this->db->update("intropage_info", $form,$where);
			$data->result = $result > 0;
			$data->id = $id;
			
			 // Detail
			if(count($param)>0){
				 foreach($param as $lang=>$value){

					$formDetail = array();
					$whereDetail=array();
					$formDetail['Detail'] = empty ( $value ['Detail'] ) ? null : $value ['Detail'];
					$formDetail['Button'] = json_encode($params['button'][$lang]);
					$formDetail['Modified'] = $modified;
					$formDetail['ModifiedBy']= $this->UserID;
					$formDetail['IsEnable'] = empty ( $param[$defaultLanguageCode]['UseButton'] ) ? 'N' : $param[$defaultLanguageCode]['IsEnable'];
					$whereDetail ["ID = ?"] = $id;
					$whereDetail ["Lang = ?"] = $lang;
					$result = $this->db->update("intropage_detail", $formDetail,$whereDetail);
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
			$id =$params['id'];

			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $id;
			$result = $this->db->update("intropage_info", $form, $where);
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	public function getOption($lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'intropage_info'), array( 'ID'))
		->joinInner(array('d' => 'intropage_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'=>'Name'))
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
	
	function getIntropage($intropageInfoID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'intropage_info'), '')
			// ->columns(array(
			// 		"ID" => "tbl.ID"
			// ))
			->where("tbl.ID = ? ", $intropageInfoID)
			->where("tbl.IsDeleted = 'N' ")
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getFile($id) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'file_info'), '')
			->columns(array(
					"tbl.ID",
					"tbl.Name",
					"tbl.HashName",
					"tbl.FileSize",
					"tbl.FileType",
			))
			->where("tbl.ID = ? " ,$id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
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
					$affRow += $this->db->update("intropage_info", $form, $where);
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
					->from(array('tbl'=>'intropage_info'), array(
						'Name',
						'BackgroundColor',
						'ShowButton',
						'ButtonTextColor',
						'ButtonBackgroundColor',
						'PublishDate',
						'PublishDown',
						'Css'
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['Name'] = empty($dataInfo->Name) ? null : ($dataInfo->Name. ' (Copy)');
					$form['BackgroundColor'] = empty($dataInfo->BackgroundColor) ? null : $dataInfo->BackgroundColor;
					$form['ShowButton'] = empty($dataInfo->ShowButton) ? null : $dataInfo->ShowButton;
					$form['ButtonTextColor'] = empty($dataInfo->ButtonTextColor) ? 1 : $dataInfo->ButtonTextColor;
					$form['ButtonBackgroundColor'] = empty($dataInfo->ButtonBackgroundColor) ? null : $dataInfo->ButtonBackgroundColor;
					$form['PublishDate'] = empty($dataInfo->PublishDate) ? null : $dataInfo->PublishDate;
					$form['PublishDown'] = empty($dataInfo->PublishDown) ? null : $dataInfo->PublishDown;
					$form['Css'] = empty($dataInfo->Css) ? null : $dataInfo->Css;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("intropage_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'intropage_detail'), array('Lang','Detail','ButtonText','Button'))
						->where("tbl.ID = ? ", $id)
						;
						$stmtDetail = $this->db->query($selectDetail);
						$dataDetail = $stmtDetail->fetchAll();

						if(count($dataDetail)>0){
							foreach($dataDetail as $d){
								$form = array();
								$form['ID'] = $tmpID;
								$form['Lang'] = empty($d['Lang']) ? null : $d['Lang'];
								$form['Detail'] = empty($d['Detail']) ? null : $d['Detail'];
								$form['ButtonText'] = empty($d['ButtonText']) ? null : $d['ButtonText'];
								$form['Button'] = empty($d['Button']) ? null : $d['Button'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("intropage_detail", $form);
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
					$affRow += $this->db->update("intropage_info", $form, $where);
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
