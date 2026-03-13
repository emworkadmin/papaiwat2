<?php
class Graphdata_Model_Category {
	
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
	public function getOption($lang) {
		
		$select = $this->db->select()
		->from ( array ('tbs' => 'graphdata_category_info' ), array ('ID') )
		->joinInner ( array ('d' => 'graphdata_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->order ( "tbs.Ordering ASC" )
		->order ( "convert(d.Title USING TIS620)  ASC" );
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array ();
		
		foreach ( $data as $value ) {
			$result [$value ["ID"]] = $value ["Title"];
		}
		return $result;
	}
	public function getRecursiveOption($lang, $id = null) {
		
		$select = $this->db->select()
		->from ( array ('tbs' => 'graphdata_category_info' ), array ('ID') )
		->joinInner ( array ('d' => 'graphdata_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->where("tbs.ParentID is null")
		->order ( "tbs.Ordering ASC" )
		->order ( "convert(d.Title USING TIS620)  ASC" );
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array ();
		
		foreach ( $data as $value ) {
			$result[$value["ID"]] = $value["Title"];
			
			if (empty($id) || ($id != $value["ID"])) {
				$this->getRecursiveOptionToChild($lang, $value ["ID"], 0, $result, $id);
			}
		}
		return $result;
	}
	
	private function getRecursiveOptionToChild($lang, $parentID, $level, &$option, $id = null) {
		
		$select = $this->db->select()
		->from ( array ('tbs' => 'graphdata_category_info' ), array ('ID') )
		->joinInner ( array ('d' => 'graphdata_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->where("tbs.ParentID = ? ", $parentID)
		->order ( "tbs.Ordering ASC" )
		->order ( "convert(d.Title USING TIS620)  ASC" );
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$level ++;
		foreach ( $data as $value ) {
			$option[$value ["ID"]] = str_repeat("-- ", $level) . $value ["Title"];
			
			if (empty($id) || ($id != $value["ID"])) {
				$this->getRecursiveOptionToChild($lang, $value["ID"], $level, $option);
			}
		}
		return $result;
	}
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array ();
		try {
			
			$selectAmount = $this->db->select()->from(array('tbl' => "graphdata_category_item"), '')
			->joinInner(array("a" => "graphdata_item_info"), "tbl.GraphdataItemID = a.id" , "")
			->columns(array("tbl.GraphdataCategoryID", "Amount" => "count(distinct a.ID)"))
			->where("tbl.IsDeleted = 'N' ")
			->where("a.IsDeleted = 'N' ")
			->group("tbl.GraphdataCategoryID")
			;
			
			// set query
			$select = $this->db->select ()
			->from ( array ('tbs' => 'graphdata_category_info'), '')
			->joinLeft ( array ("d" => "graphdata_category_detail" ), "tbs.ID = d.ID and d.Lang = '{$lang}' ", '')
			->joinLeft(array('a' => $selectAmount), "tbs.ID = a.GraphdataCategoryID", "")
			->columns(array(
					"ID" => "tbs.ID",
					"Title" => "d.Title",
					"ItemAmount" => "ifnull(a.Amount, 0)",
					"Ordering" => "tbs.Ordering",
					"Created" => "tbs.Created",
					"CreatedBy" => "tbs.CreatedBy",
					"Modified" => "tbs.Modified",
					"ModifiedBy" => "tbs.ModifiedBy",
					"IsEnable" => "tbs.IsEnable",
					"IsDeleted" => "tbs.IsDeleted",
			))
			->where ( "tbs.IsDeleted = 'N' " )
			;
			
			// set criteria
			if (! empty ( $advancesearch ['searchText'] )) {
				$val = $advancesearch ['searchText'];
				$select = $select->where ( "d.Title like " . $this->db->quote ( "%" . $val . "%" ) );
			}
			
			if (! empty ( $advancesearch ['isEnable'] )) {
				$select = $select->where ( "tbs.IsEnable = ? ", $advancesearch ['isEnable'] );
			}
			
			// find total
			$records = $this->getListCount ( $select );
			
			// set ordering and total
			if (empty ( $sidx )) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select ()
			->from ( array ('tbs' => $select ), array ('*'))
			->columns ( array ("RECORD" => "abs($records)" ) )
			->limitPage ( $page, $rows )
			;
			if (in_array($rowSort, array('Title'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchAll ();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
				}
			}
		} catch ( Exception $e ) {
			echo $e->getMessage ();
		}
		return $data;
	}
	function getListCount($select) {
		$select = $this->db->select ()
		->from ( array ('T' => $select), array ("RECORDS" => "count(T.ID)") )
		;
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchObject ();
		return $data->RECORDS;
	}
	function getInfo($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbs' => 'graphdata_category_info'), array (
					"ID",
					"ParentID",
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
	function getDetails($id) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array ('tbs' => 'graphdata_category_detail'), array (
					"ID",
					"Name",
					"IsEnable",
					"Lang"
			))
			->where ( "tbs.IsDeleted = 'N'" )
			->where ( "tbs.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchAll ();
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	
	public function getDataByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('m' => 'graphdata_category_info'), "")
			->joinLeft(array("md" => "graphdata_category_detail"), "m.ID = md.ID and md.Lang = '{$lang}' ", "")
			->joinLeft(array("fi" => "file_info"), "m.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "m.ID",
					"Lang" => 'md.Lang',
					"ParentID" => 'm.ParentID',
					"Title" => "md.Title",
					"IntroText" => "md.IntroText",
					"Detail" => "md.Detail",
					"Ordering" => "m.Ordering",
					"IsDeleted" => "m.IsDeleted",
					"IsEnable" => "m.IsEnable",
					
					"IntroImageID" => "m.IntroImageID",
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where("m.ID = ?", $id)
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
			$form ['ParentID'] = empty ( $param[$defaultLanguageCode]['Parent'] ) ? null : $param[$defaultLanguageCode]['Parent'];
			$form ['Ordering'] = empty ( $param[$defaultLanguageCode]['Ordering'] ) ? 0 : $param[$defaultLanguageCode]['Ordering'];
			$form ['Created'] = $modified;
			$form ['CreatedBy'] = $this->UserID;
			$form ['IsEnable'] = empty ( $param[$defaultLanguageCode]['IsEnable'] ) ? 'N' : $param[$defaultLanguageCode]['IsEnable'];
			$form ['IsDeleted'] = 'N';
			$result = $this->db->insert ( "graphdata_category_info", $form);
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
						$formDetail ['Detail'] = empty ( $value ['Detail'] ) ? null : $value ['Detail'];
						$formDetail ['Created'] = $modified;
						$formDetail ['CreatedBy'] = $this->UserID;
						$this->db->insert ( "graphdata_category_detail", $formDetail );
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
			$form ['IntroImageID'] = $params['IntroImageID'];
			$form ['ParentID'] = empty ( $param[$defaultLanguageCode]['Parent'] ) ? null : $param[$defaultLanguageCode]['Parent'];
			$form ['Ordering'] = empty ( $param[$defaultLanguageCode]['Ordering'] ) ? 0 : $param[$defaultLanguageCode]['Ordering'];
			$form ['IsEnable'] = empty ( $param[$defaultLanguageCode]['IsEnable'] ) ? 'N' : $param[$defaultLanguageCode]['IsEnable'];
			$form ['Modified'] = $modified;
			$form ['ModifiedBy'] = $this->UserID;
			$result = $where ["ID = ?"] = $id;
			$this->db->update ( "graphdata_category_info", $form, $where );
			
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
						$formDetail ['Title'] = empty ( $value ['Title'] ) ? null : $value ['Title'];
						$formDetail ['IntroText'] = empty ( $value ['IntroText'] ) ? null : $value ['IntroText'];
						$formDetail ['Detail'] = empty ( $value ['Detail'] ) ? null : $value ['Detail'];
						$formDetail ['Modified'] = $modified;
						$formDetail ['ModifiedBy'] = $this->UserID;
						$whereDetail ["ID = ?"] = $id;
						$whereDetail ["Lang = ?"] = $lang;
						$affectedRows = $this->db->update ( "graphdata_category_detail", $formDetail, $whereDetail );
						if ($affectedRows == 0) {
							$formDetail["ID"] = $id;
							$formDetail["Lang"] = $lang;
							$formDetail['Created'] = $modified;
							$formDetail["IsEnable"] = 'Y';
							$formDetail["IsDeleted"] = 'N';
							$this->db->insert("graphdata_category_detail", $formDetail);
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
			
			$id = $params ['id'];
			
			// User
			$form = array ();
			$where = array ();
			$form ['IsDeleted'] = 'Y';
			$form ['Modified'] = date ( 'Y-m-d H:i:s' );
			$form ['ModifiedBy'] = $this->UserID;
			$where ['ID = ? '] = $id;
			$result = $this->db->update ( "graphdata_category_info", $form, $where );
			
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
			->from(array ('tbs' => 'graphdata_category_info'), array (
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
			$result = $this->db->update ( "graphdata_category_info", $form, $where );
			
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
					$affRow += $this->db->update("graphdata_category_info", $form, $where);
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
					->from(array('tbl'=>'graphdata_category_info'), array(
						'ParentID',
						'IntroImageID',
						'Ordering',
						'Settings'
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['ParentID'] = empty($dataInfo->ParentID) ? null : $dataInfo->ParentID;
					$form['IntroImageID'] = empty($dataInfo->IntroImageID) ? null : $dataInfo->IntroImageID;
					$form['Ordering'] = empty($dataInfo->Ordering) ? null : $dataInfo->Ordering;
					$form['Settings'] = empty($dataInfo->Settings) ? null : $dataInfo->Settings;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$form['IsDeleted'] = 'N';
					$data->result = $this->db->insert("graphdata_category_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'graphdata_category_detail'), array('Lang','Title','IntroText','Detail'))
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
								$form['Detail'] = empty($d['Detail']) ? null : $d['Detail'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("graphdata_category_detail", $form);
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
					$affRow += $this->db->update("graphdata_category_info", $form, $where);
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
