<?php
class Wat_Model_Category {
	
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
		->from ( array ('tbs' => 'wat_category_info' ), array ('ID') )
		->joinInner ( array ('d' => 'wat_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
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
	public function getRecursiveOption($lang, $id = null,$myid =null) {
		
		$select = $this->db->select()
		->from ( array ('tbs' => 'wat_category_info' ), array ('ID') )
		->joinInner ( array ('d' => 'wat_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->where("tbs.ParentID is null")
		->order ( "tbs.Ordering ASC" )
		->order ( "convert(d.Title USING TIS620)  ASC" );

		if($myid){
			$select=$select->where("tbs.ID !=?",$myid);
		}

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
		->from ( array ('tbs' => 'wat_category_info' ), array ('ID') )
		->joinInner ( array ('d' => 'wat_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
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
			
			$selectAmount = $this->db->select()->from(array('tbl' => "wat_category_item"), '')
			->joinInner(array("a" => "wat_item_info"), "tbl.WatItemID = a.ID" , "")
			->columns(array("tbl.WatCategoryID", "Amount" => "count(distinct a.ID)"))
			->where("tbl.IsDeleted = 'N' ")
			->where("a.IsDeleted = 'N' ")
			->group("tbl.WatCategoryID")
			;
			
			// set query
			$select = $this->db->select ()
			->from ( array ('tbs' => 'wat_category_info'), '')
			->joinLeft ( array ("d" => "wat_category_detail" ), "tbs.ID = d.ID and d.Lang = '{$lang}' ", '')
			->joinLeft(array('a' => $selectAmount), "tbs.ID = a.WatCategoryID", "")
			->columns(array(
					"ID" => "tbs.ID",
					"Title" => "d.Title",
					"ArticleAmount" => "ifnull(a.Amount, 0)",
					"Ordering" => "tbs.Ordering",
					"CssClass" => "tbs.CssClass",
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
			->from(array ('tbs' => 'wat_category_info'), array (
					"ID",
					"ParentID",
					"Ordering" ,
					"CssClass" ,
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
			->from(array ('tbs' => 'wat_category_detail'), array (
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
			->from(array('m' => 'wat_category_info'), "")
			->joinLeft(array("md" => "wat_category_detail"), "m.ID = md.ID and md.Lang = '{$lang}' ", "")
			->joinLeft(array("fi" => "file_info"), "m.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "m.ID",
					"Lang" => 'md.Lang',
					"ParentID" => 'm.ParentID',
					"Title" => "md.Title",
					"IntroText" => "md.IntroText",
					"Detail" => "md.Detail",
					"Ordering" => "m.Ordering",
					"CssClass" => "m.CssClass",
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
			$form ['CssClass'] = empty ( $param[$defaultLanguageCode]['CssClass'] ) ? null : $param[$defaultLanguageCode]['CssClass'];
			$form ['Created'] = $modified;
			$form ['CreatedBy'] = $this->UserID;
			$form ['IsEnable'] = empty ( $param[$defaultLanguageCode]['IsEnable'] ) ? 'N' : $param[$defaultLanguageCode]['IsEnable'];
			$form ['IsDeleted'] = 'N';
			$result = $this->db->insert ( "wat_category_info", $form);
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
						$this->db->insert ( "wat_category_detail", $formDetail );
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
			$form ['CssClass'] = empty ( $param[$defaultLanguageCode]['CssClass'] ) ? null : $param[$defaultLanguageCode]['CssClass'];
			$form ['IsEnable'] = empty ( $param[$defaultLanguageCode]['IsEnable'] ) ? 'N' : $param[$defaultLanguageCode]['IsEnable'];
			$form ['Modified'] = $modified;
			$form ['ModifiedBy'] = $this->UserID;
			$result = $where ["ID = ?"] = $id;
			$this->db->update ( "wat_category_info", $form, $where );
			
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
						$affectedRows = $this->db->update ( "wat_category_detail", $formDetail, $whereDetail );
						if ($affectedRows == 0) {
							$formDetail["ID"] = $id;
							$formDetail["Lang"] = $lang;
							$formDetail['Created'] = $modified;
							$formDetail["IsEnable"] = 'Y';
							$formDetail["IsDeleted"] = 'N';
							$this->db->insert("wat_category_detail", $formDetail);
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
			$result = $this->db->update ( "wat_category_info", $form, $where );
			
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
			->from(array ('tbs' => 'wat_category_info'), array (
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
			$result = $this->db->update ( "wat_category_info", $form, $where );
			
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
					$affRow += $this->db->update("wat_category_info", $form, $where);
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
					->from(array('tbl'=>'wat_category_info'), array('ParentID','IntroImageID','CssClass','Ordering','Settings'))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['ParentID'] = empty($dataInfo->ParentID) ? null : $dataInfo->ParentID;
					$form['IntroImageID'] = empty($dataInfo->IntroImageID) ? null : $dataInfo->IntroImageID;
					$form['CssClass'] = empty($dataInfo->CssClass) ? null : $dataInfo->CssClass;
					$form['Ordering'] = empty($dataInfo->Ordering) ? null : $dataInfo->Ordering;
					$form['Settings'] = empty($dataInfo->Settings) ? null : $dataInfo->Settings;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("wat_category_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'wat_category_detail'), array('Lang','Title','IntroText','Detail'))
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
								$this->db->insert("wat_category_detail", $form);
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
					$affRow += $this->db->update("wat_category_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	// -------------------------------------- Query For Category List -------------------------------------- //

	public function getRecursiveList($lang) {

		$selectAmount = $this->db->select()
		->from(array('tbl' => "wat_category_item"), '')
		->joinInner(array("a" => "wat_item_info"), "tbl.WatItemID = a.ID" , "")
		->columns(array("tbl.WatCategoryID", "Amount" => "count(distinct a.ID)"))
		->where("tbl.IsDeleted = 'N' ")
		->where("a.IsDeleted = 'N' ")
		->group("tbl.WatCategoryID")
		;
		
		$select = $this->db->select()
		->from ( array ('tbs' => 'wat_category_info' ), array ('ID','Ordering','IsEnable') )
		->joinInner ( array ('d' => 'wat_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
		->joinLeft(array('a' => $selectAmount), "tbs.ID = a.WatCategoryID", array("ItemAmount" => "ifnull(a.Amount, 0)") )
		->where ( "tbs.IsDeleted = ?", "N" )
		// ->where ( "tbs.IsEnable = ?", "Y" )
		->where ( "tbs.ParentID is null" )
		->order ( "tbs.Ordering ASC" )
		->order ( "convert(d.Title USING TIS620)  ASC" );

		if($myid){
			$select=$select->where("tbs.ID != ?",$myid);
		}

		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array ();
		
		foreach ( $data as $value ) {
			$result[$value["ID"]] = array(
				'ID' => $value["ID"],
				'Title' => $value["Title"],
				'Ordering' => $value["Ordering"],
				'ItemAmount' => $value["ItemAmount"],
				'IsEnable' => $value["IsEnable"],
				'Child' => array(),
			);
			
			if (empty($id) || ($id != $value["ID"])) {
				$this->getRecursiveToChild($lang, $value ["ID"], 0, $result, $id);
			}
		}
		return $result;
	}

	private function getRecursiveToChild($lang, $parentID, $level, &$option, $id = null) {

		$selectAmount = $this->db->select()
		->from(array('tbl' => "wat_category_item"), '')
		->joinInner(array("a" => "wat_item_info"), "tbl.WatItemID = a.ID" , "")
		->columns(array("tbl.WatCategoryID", "Amount" => "count(distinct a.ID)"))
		->where("tbl.IsDeleted = 'N' ")
		->where("a.IsDeleted = 'N' ")
		->group("tbl.WatCategoryID")
		;
		
		$select = $this->db->select()
		->from ( array ('tbs' => 'wat_category_info' ), array ('ID','Ordering','IsEnable') )
		->joinInner ( array ('d' => 'wat_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
		->joinLeft(array('a' => $selectAmount), "tbs.ID = a.WatCategoryID", array("ItemAmount" => "ifnull(a.Amount, 0)") )
		->where ( "tbs.IsDeleted = ?", "N" )
		// ->where ( "tbs.IsEnable = ?", "Y" )
		->where ( "tbs.ParentID = ? ", $parentID )
		->order ( "tbs.Ordering ASC" )
		->order ( "convert(d.Title USING TIS620)  ASC" );
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$level ++;
		foreach ( $data as $value ) {

			if($level>=1){
				if(count($option)>0){
					foreach($option as $k1=>$v1){

						if($level==1 && $parentID==$k1){
							$option[$k1]['Child'][$value["ID"]] = array(
								'ID' => $value["ID"],
								'Title' => $value["Title"],
								'Ordering' => $value["Ordering"],
								'ItemAmount' => $value["ItemAmount"],
								'IsEnable' => $value["IsEnable"],
								'Child' => array(),
							);
						}

						if($level>=2){
							if(count($v1['Child'])>0){
								foreach($v1['Child'] as $k2=>$v2){

									if($level==2 && $parentID==$k2){
										$option[$k1]['Child'][$k2]['Child'][$value["ID"]] = array(
											'ID' => $value["ID"],
											'Title' => $value["Title"],
											'IsEnable' => $value["IsEnable"],
											'Ordering' => $value["Ordering"],
											'ItemAmount' => $value["ItemAmount"],
											'Child' => array(),
										);
									}

									if($level>=3){
										if(count($v2['Child'])>0){
											foreach($v2['Child'] as $k3=>$v3){

												if($level==3 && $parentID==$k3){
													$option[$k1]['Child'][$k2]['Child'][$k3]['Child'][$value["ID"]] = array(
														'ID' => $value["ID"],
														'Title' => $value["Title"],
														'Ordering' => $value["Ordering"],
														'ItemAmount' => $value["ItemAmount"],
														'IsEnable' => $value["IsEnable"],
														'Child' => array(),
													);
												}

												if($level>=4){
													if(count($v3['Child'])>0){
														foreach($v3['Child'] as $k4=>$v4){

															if($level==4 && $parentID==$k4){
																$option[$k1]['Child'][$k2]['Child'][$k3]['Child'][$k4]['Child'][$value["ID"]] = array(
																	'ID' => $value["ID"],
																	'Title' => $value["Title"],
																	'Ordering' => $value["Ordering"],
																	'ItemAmount' => $value["ItemAmount"],
																	'IsEnable' => $value["IsEnable"],
																	'Child' => array(),
																);
															}

															if($level>=5){
																if(count($v4['Child'])>0){
																	foreach($v4['Child'] as $k5=>$v5){

																		if($level==5 && $parentID==$k5){
																			$option[$k1]['Child'][$k2]['Child'][$k3]['Child'][$k4]['Child'][$k5]['Child'][$value["ID"]] = array(
																				'ID' => $value["ID"],
																				'Title' => $value["Title"],
																				'Ordering' => $value["Ordering"],
																				'ItemAmount' => $value["ItemAmount"],
																				'IsEnable' => $value["IsEnable"],
																				'Child' => array(),
																			);
																		}

																		if($level>=6){
																			if(count($v5['Child'])>0){
																				foreach($v5['Child'] as $k6=>$v6){
			
																					if($level==6 && $parentID==$k6){
																						$option[$k1]['Child'][$k2]['Child'][$k3]['Child'][$k4]['Child'][$k5]['Child'][$k6]['Child'][$value["ID"]] = array(
																							'ID' => $value["ID"],
																							'Title' => $value["Title"],
																							'Ordering' => $value["Ordering"],
																							'ItemAmount' => $value["ItemAmount"],
																							'IsEnable' => $value["IsEnable"],
																							'Child' => array(),
																						);
																					}

																					if($level>=7){
																						if(count($v6['Child'])>0){
																							foreach($v6['Child'] as $k7=>$v7){
						
																								if($level==7 && $parentID==$k7){
																									$option[$k1]['Child'][$k2]['Child'][$k3]['Child'][$k4]['Child'][$k5]['Child'][$k6]['Child'][$k7]['Child'][$value["ID"]] = array(
																										'ID' => $value["ID"],
																										'Title' => $value["Title"],
																										'Ordering' => $value["Ordering"],
																										'ItemAmount' => $value["ItemAmount"],
																										'IsEnable' => $value["IsEnable"],
																										'Child' => array(),
																									);
																								}

																								if($level>=8){
																									if(count($v7['Child'])>0){
																										foreach($v7['Child'] as $k8=>$v8){
									
																											if($level==8 && $parentID==$k8){
																												$option[$k1]['Child'][$k2]['Child'][$k3]['Child'][$k4]['Child'][$k5]['Child'][$k6]['Child'][$k7]['Child'][$k8]['Child'][$value["ID"]] = array(
																													'ID' => $value["ID"],
																													'Title' => $value["Title"],
																													'Ordering' => $value["Ordering"],
																													'ItemAmount' => $value["ItemAmount"],
																													'IsEnable' => $value["IsEnable"],
																													'Child' => array(),
																												);
																											}

																											if($level>=9){
																												if(count($v8['Child'])>0){
																													foreach($v8['Child'] as $k9=>$v9){
												
																														if($level==9 && $parentID==$k9){
																															$option[$k1]['Child'][$k2]['Child'][$k3]['Child'][$k4]['Child'][$k5]['Child'][$k6]['Child'][$k7]['Child'][$k8]['Child'][$k9]['Child'][$value["ID"]] = array(
																																'ID' => $value["ID"],
																																'Title' => $value["Title"],
																																'Ordering' => $value["Ordering"],
																																'ItemAmount' => $value["ItemAmount"],
																																'IsEnable' => $value["IsEnable"],
																																'Child' => array(),
																															);
																														}

																														if($level>=10){
																															if(count($v9['Child'])>0){
																																foreach($v9['Child'] as $k10=>$v10){
															
																																	if($level==10 && $parentID==$k10){
																																		$option[$k1]['Child'][$k2]['Child'][$k3]['Child'][$k4]['Child'][$k5]['Child'][$k6]['Child'][$k7]['Child'][$k8]['Child'][$k9]['Child'][$k10]['Child'][$value["ID"]] = array(
																																			'ID' => $value["ID"],
																																			'Title' => $value["Title"],
																																			'Ordering' => $value["Ordering"],
																																			'ItemAmount' => $value["ItemAmount"],
																																			'IsEnable' => $value["IsEnable"],
																																			'Child' => array(),
																																		);
																																	}
																											
																																} // End Foreach
																															} // End Foreach
																														} // End Level 10
																								
																													} // End Foreach
																												} // End Foreach
																											} // End Level 9
																					
																										} // End Foreach
																									} // End Foreach
																								} // End Level 8
																		
																							} // End Foreach
																						} // End Foreach
																					} // End Level 7
															
																				} // End Foreach
																			} // End Foreach
																		} // End Level 6
												
																	} // End Foreach
																} // End Foreach
															} // End Level 5
									
														} // End Foreach
													} // End Foreach
												} // End Level 4
						
											} // End Foreach
										} // End Foreach
									} // End Level 3
			
								} // End Foreach
							} // End Foreach
						} // End Level 2

					} // End Foreach
				} // End Foreach
			} // End Level 1
			
			
			if (empty($id) || ($id != $value["ID"])) {
				$this->getRecursiveToChild($lang, $value["ID"], $level, $option);
			}
		}
		return $result;
	}

	function updateParent($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$form = array ();
			$where = array ();
			$form['ParentID'] = empty($params['parent_id']) ? null : $params['parent_id'];
			$form['Modified'] = date("Y-m-d H:i:s");
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $params['id'];
			$result = $this->db->update("wat_category_info", $form, $where);
			
			$data->result = $result;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
}

?>
