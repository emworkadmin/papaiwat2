<?php

class Orgchart_Model_Chart {
	
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
			->from(array('tbl' => 'orgchart_chart_info'), '')
			->joinLeft(array('d' => 'orgchart_chart_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->columns(array(
				"ID" => "tbl.ID",
				"Title" => "d.Title",
				"Ordering" => "tbl.Ordering",
				"IsEnable" => "tbl.IsEnable"
			))
			->where("tbl.IsDeleted ='N'")
			;

			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("d.Title like " . $this->db->quote("%" . $val . "%" ));
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

			if (in_array($rowSort, array('Title'))) {
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
	
	public function getOption($lang = 'th') {
		$select = $this->db->select()
		->from(array('tbl' => 'orgchart_chart_info'), array( 'ID'))
		->joinInner(array('d' => 'orgchart_chart_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'=>'Title'))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->order("convert(d.Title USING tis620) ASC")
		;

		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		$result = array();
		
		foreach ($data as $value) {
			$result[$value["ID"]] = $value["Name"];
		}
		return $result;
	}

	function getInfo($id) {
		$data = new stdClass ();
		try {
			$select = $this->db->select ()
			->from(array ('tbs' => 'orgchart_chart_info'), array (
					"ID",
					"Ordering" ,
					"IsDeleted",
					"IsEnable" 
			))
			->where ( "tbs.IsDeleted = 'N' " )
			->where ( "tbs.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
			
		}
		return $data;
	}

	public function getDataByLanguage($id, $lang = 'th') {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'orgchart_chart_info'), "")
			->joinLeft(array("d" => "orgchart_chart_detail"), "tbl.ID = d.ID and d.Lang = '{$lang}' ", "")
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Lang" => 'd.Lang',
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Ordering" => "tbl.Ordering",
					"IsDeleted" => "tbl.IsDeleted",
					"IsEnable" => "tbl.IsEnable",
					
					"IntroImageID" => "tbl.IntroImageID",
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where("tbl.ID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}	

	function save($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			
			$id = '';
			$param = $params['params'];			
			$modified = date("Y-m-d H:i:s");

			$form = array ();
			$form ['IntroImageID'] = $params['IntroImageID'];
			$form ['Ordering'] = empty ( $param[$defaultLanguageCode]['Ordering'] ) ? 0 : $param[$defaultLanguageCode]['Ordering'];
			$form ['Created'] = $modified;
			$form ['CreatedBy'] = $this->UserID;
			$form ['IsEnable'] = empty ( $param[$defaultLanguageCode]['IsEnable'] ) ? 'N' : $param[$defaultLanguageCode]['IsEnable'];
			$form ['IsDeleted'] = 'N';
			$result = $this->db->insert ("orgchart_chart_info", $form);
			$id = $this->db->lastInsertId ();
			
			if ($result) {
				$data->result = true;
				$data->id = $id;
				
				// Detail
				if (count($param) > 0) {
					foreach ($param as $lang => $value) {
						$formDetail = array ();
						$formDetail ['ID'] = $id;
						$formDetail ['Lang'] = $lang;
						$formDetail ['Title'] = empty ( $value ['Title'] ) ? null : $value ['Title'];
						$formDetail ['IntroText'] = empty ( $value ['IntroText'] ) ? null : $value ['IntroText'];
						$formDetail ['Created'] = $modified;
						$formDetail ['CreatedBy'] = $this->UserID;
						$this->db->insert ("orgchart_chart_detail", $formDetail );
					}
				}
			}
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}

	function update($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			
			$id = $params ['hdfID'];
			$param = $params['params'];
			$modified = date("Y-m-d H:i:s");
			
			$form = array ();
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['Ordering'] = empty ( $param[$defaultLanguageCode]['Ordering'] ) ? 0 : $param[$defaultLanguageCode]['Ordering'];
			$form['IsEnable'] = empty ( $param[$defaultLanguageCode]['IsEnable'] ) ? 'N' : $param[$defaultLanguageCode]['IsEnable'];
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$where["ID = ?"] = $id;
			$result = $this->db->update("orgchart_chart_info", $form, $where);
			
			$data->result = $result > 0;
			$data->id = $id;
			
			if ($result) {
				$data->result = true;
				$data->id = $id;
				
				// Detail
				if (count($param) > 0) {
					foreach ($param as $lang => $value) {
						$formDetail = array ();
						$whereDetail = array ();
						$formDetail['Title'] = empty ( $value ['Title'] ) ? null : $value ['Title'];
						$formDetail['IntroText'] = empty ( $value ['IntroText'] ) ? null : $value ['IntroText'];
						$formDetail['Modified'] = $modified;
						$formDetail['ModifiedBy'] = $this->UserID;
						$whereDetail["ID = ?"] = $id;
						$whereDetail["Lang = ?"] = $lang;
						$affectedRows = $this->db->update("orgchart_chart_detail", $formDetail, $whereDetail);
						if ($affectedRows == 0) {
							$formDetail["ID"] = $id;
							$formDetail["Lang"] = $lang;
							$formDetail['Created'] = $modified;
							$formDetail['CreatedBy'] = $this->UserID;
							$formDetail["IsEnable"] = 'Y';
							$formDetail["IsDeleted"] = 'N';
							$this->db->insert("orgchart_chart_detail", $formDetail);
						}
					}
				}
			}
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}

	function delete($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$id = $params['id'];
			
			// User
			$form = array ();
			$where = array ();
			$form ['IsDeleted'] = 'Y';
			$form ['Modified'] = date ( 'Y-m-d H:i:s' );
			$form ['ModifiedBy'] = $this->UserID;
			$where ['ID = ? '] = $id;
			$result = $this->db->update("orgchart_chart_info", $form, $where);
			
			$data->result = count ( $result ) > 0;
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	
	/******************** Setting ***********************/	
	function getSettings($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbs' => 'orgchart_chart_info'), array (
					"Settings"
			))
			->where ( "tbs.IsDeleted = 'N' " )
			->where ( "tbs.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
			
		}
		return $data;
	}

	function saveSetting($id, $settings) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			
			$form = array ();
			$where = array ();
			$form ['Settings'] = $settings;
			$form ['Modified'] = $modified;
			$form ['ModifiedBy'] = $this->UserID;
			$where ["ID = ?"] = $id;
			$result = $this->db->update("orgchart_chart_info", $form, $where );
			
			$data->result = $result > 0;
			
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}

	/******************** Chart ***********************/	
	function getChartDetail($id) {
		$data = new stdClass ();
		try {
			$select = $this->db->select ()
			->from(array ('tbs' => 'orgchart_chart_info'), array (
					"ChartDetail"
			))
			->where ( "tbs.IsDeleted = 'N' " )
			->where ( "tbs.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
			
		}
		return $data;
	}

	function updatechart($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			
			$form = array ();
			$where = array ();
			$form ['ChartDetail'] = empty($params['Hierarchy']) ? null : $params['Hierarchy'];
			$form ['Modified'] = $modified;
			$form ['ModifiedBy'] = $this->UserID;
			$where ["ID = ?"] = $params['itemid'];
			$result = $this->db->update("orgchart_chart_info", $form, $where );
			
			$data->result = $result > 0;
			
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
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
					$affRow += $this->db->update("orgchart_chart_info", $form, $where);
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


					$selectInfo = $this->db->select ()
					->from(array('tbl'=>'orgchart_chart_info'), array(
						'IntroImageID',
						'Ordering',
						'ChartDetail',
						'Settings'
						))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['IntroImageID'] = empty($dataInfo->IntroImageID) ? null : $dataInfo->IntroImageID;
					$form['Ordering'] = empty($dataInfo->Ordering) ? null : $dataInfo->Ordering;
					$form['ChartDetail'] = empty($dataInfo->ChartDetail) ? null : $dataInfo->ChartDetail;
					$form['Settings'] = empty($dataInfo->Settings) ? null : $dataInfo->Settings;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("orgchart_chart_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'orgchart_chart_detail'), array('Lang','Title','IntroText'))
						->where("tbl.ID = ? ", $id)
						;
						$stmtDetail = $this->db->query($selectDetail);
						$dataDetail = $stmtDetail->fetchAll();

						if(count($dataDetail)>0){
							foreach($dataDetail as $d){
								$form = array();
								$form['ID'] = $tmpID;
								$form['Lang'] = empty($d['Lang']) ? null : $d['Lang'];
								$form['Title'] = empty($d['Title']) ? null : $d['Title'];
								if($defaultLanguage == $d['Lang']){
									$form['Title'] = $form['Title'] . ' (Copy)';
								}
								$form['IntroText'] = empty($d['IntroText']) ? null : $d['IntroText'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("orgchart_chart_detail", $form);
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
					$form ['IsDeleted'] = 'Y';
					$form ['Modified'] = $modified;
					$form ['ModifiedBy'] = $this->UserID;
					$where ['ID = ? '] = $id;
					$affRow += $this->db->update("orgchart_chart_info", $form, $where);
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
