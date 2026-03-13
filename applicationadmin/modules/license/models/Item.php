<?php
class License_Model_Item {

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
	
	function getInfo($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbl' => 'license_item_info'), "")
			->joinLeft(array("i" => "license_issue_info"), "tbl.LicenseIssueID = i.ID", "")
			->columns(array(
					"tbl.ID",
					"LicenseCategoryID"=>"i.LicenseCategoryID",
					"tbl.LicenseIssueID",
					// "tbl.LicenseDepartmentID",
					"IsDeleted",
					"IsEnable" ,
					"PublishDate",
					"PublishDown",
					"Created",
					"Modified"
			))
			->where ( "tbl.IsDeleted = 'N' " )
			->where ( "tbl.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();

			if (!empty($data->ID)) {
				$data->PublishDate = empty($data->PublishDate) ? null : new DateTime($data->PublishDate);
				$data->PublishDown = empty($data->PublishDown) ? null : new DateTime($data->PublishDown);
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}

		} catch ( Exception $e ) {
			
		}
		return $data;
	}
	
	function getDataByLanguage($id, $lang)
	{
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('m' => 'license_item_info'), "")
			->joinLeft(array("md" => "license_item_detail"), "m.ID = md.ID and md.Lang = '{$lang}' ", "")
			->joinLeft(array("i" => "license_issue_info"), "m.LicenseIssueID = i.ID", "")
			->columns(array(
					"ID" => "m.ID",
					"LicenseCategoryID"=>"i.LicenseCategoryID",
					"m.LicenseIssueID",
					// "m.LicenseDepartmentID",
					"m.PublishDate",
					"m.PublishDown",
					"m.Created",
					"m.Modified",
					"Lang" => 'md.Lang',
					"Title" => "md.Title",
					"IsDeleted" => "m.IsDeleted",
					"IsEnable" => "m.IsEnable"
			))
			->where("m.ID = ?", $id)
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
		
	public function getProcedureByLanguage($itemID, $lang) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('m' => 'license_item_info'), "")
			->joinInner(array("c" => "license_procedure_info"), "m.ID = c.LicenseItemID", "")
			->joinInner(array("cd" => "license_procedure_detail"), "c.ID = cd.ID and cd.Lang = '{$lang}' ", "")
			->columns(array(
					"c.ID",
					"c.LicenseItemID",
					"c.LicenseDepartmentID",
					"c.IconType",
					"c.StartDate",
					"c.EndDate",
					"c.PublishDate",
					"c.PublishDown",
					"c.UrlType",
					"c.Url",
					"c.UrlComment",
					"c.Ordering",
					"cd.Lang",
					"cd.Title",
					"cd.Created",
					"cd.Modified",
					"c.IsEnable"
			))
			->where("m.ID = ?", $itemID)
			->where("c.IsDeleted = 'N' ")
			->order("c.Ordering")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
					$data[$i]["PublishDate"] = empty($data[$i]["PublishDate"]) ? null : new DateTime($data[$i]["PublishDate"]);
					$data[$i]["PublishDown"] = empty($data[$i]["PublishDown"]) ? null : new DateTime($data[$i]["PublishDown"]);
					$data[$i]["StartDate"] = empty($data[$i]["StartDate"]) ? null : new DateTime($data[$i]["StartDate"]);
					$data[$i]["EndDate"] = empty($data[$i]["EndDate"]) ? null : new DateTime($data[$i]["EndDate"]);
				}
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}

	public function getOption($lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'license_item_info'), array( 'ID'))
		->joinInner(array('d' => 'license_item_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'=>'Title'))
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
	
	public function getOptionByCategory($categoryID, $lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'license_item_info'), array( 'ID'))
		->joinInner(array('d' => 'license_item_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'=>'Title'))
		->joinLeft(array("i" => "license_issue_info"), "tbl.LicenseIssueID = i.ID", "")
		->joinLeft(array("c" => "license_category_info"), "i.LicenseCategoryID = c.ID", "")
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("i.IsDeleted = ?", "N")
		->where("i.IsEnable = ?", "Y")
		->where("c.IsDeleted = ?", "N")
		->where("c.IsEnable = ?", "Y")
		->where("c.ID = ?", $categoryID)
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
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th')
	{
		$data = array();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "license_item_info"), "")
			->joinLeft(array("d" => "license_item_detail"), "tbl.ID = d.ID and d.Lang = '{$lang}' ", "")
			->joinLeft(array("i" => "license_issue_info"), "tbl.LicenseIssueID = i.ID", "")
			->joinLeft(array("id" => "license_issue_detail"), "i.ID = id.ID and id.Lang = '{$lang}' ", "")
			->joinLeft(array("c" => "license_category_info"), "i.LicenseCategoryID = c.ID", "")
			->joinLeft(array("cd" => "license_category_detail"), "c.ID = cd.ID and cd.Lang = '{$lang}' ", "")
			->columns(array(
				"ID" => "tbl.ID",
				"Created" => "tbl.Created",
				"Modified" => "tbl.Modified",
				"IsEnable" => "tbl.IsEnable",
				"Title" => "d.Title",
				"CategoryTitle" => "cd.Title",
				"IssueName" => "id.Name",
			))
			->where("tbl.IsDeleted = 'N'")
			;
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("d.Title like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['isCat'])) {
				$select = $select->where("i.LicenseCategoryID = ? ", $advancesearch['isCat']);
			}
			if (!empty($advancesearch['isIssue'])) {
				$select = $select->where("tbl.LicenseIssueID = ? ", $advancesearch['isIssue']);
			}
			if (!empty($advancesearch['isEnable'])) {
				$select = $select->where("tbl.IsEnable = ? ", $advancesearch['isEnable']);
			}

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

			if (in_array($rowSort, array('Title', "CategoryTitle"))) {
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

		}
		return $data;
	}

	function getlistCount($select)
	{
		$select = $this->db->select()
		->from(array("T" => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}

	function save($params)
	{
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

			if($defaultLanguageCode == 'th') {
				$param[$defaultLanguageCode]['PublishDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			} else {
				$param[$defaultLanguageCode]['PublishDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}

			$form = array();
			
			$form['LicenseCategoryID'] =  null;
			$form['LicenseIssueID'] =  empty($params['Issue']) ? null : $params['Issue'];
			$form['LicenseDepartmentID'] =  null;
			$form['PublishDate'] =  $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] =  $param[$defaultLanguageCode]['PublishDown'];
			$form['Created']  = $modified;
			$form['CreatedBy'] =  empty($this->UserID) ? null : $this->UserID;
			$form['IsEnable'] = $param[$defaultLanguageCode]['IsEnable'];
			$form['IsDeleted'] = 'N';

			$result = $this->db->insert("license_item_info", $form);
			$id = $this->db->lastInsertId();

			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$formDetail['ID'] = $id;
					$formDetail['Lang'] = $lang;
					$formDetail['Title'] = $value['Title'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("license_item_detail", $formDetail);
				}
			}
			
			// Procedure
			if (count($param[$defaultLanguageCode]['Procedure']) > 0) {
				$i = 0;
				foreach ($param[$defaultLanguageCode]['Procedure'] as $val) {
					$obj = json_decode($val, true);
					$i ++;
					$form = array();
					$where = array();
					if ((count($obj['Lang']) > 0) && (array_key_exists($defaultLanguageCode, $obj['Lang']))) {
						$objData = $obj['Lang'][$defaultLanguageCode];
						$form['LicenseDepartmentID'] = $objData['LicenseDepartmentID'];
						$form['IconType'] = $objData['IconType'];
						$form['StartDate'] = empty($objData['StartDate']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL($objData['StartDate']);
						$form['EndDate'] = empty($objData['EndDate']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL($objData['EndDate']);
						$form['PublishDate'] = Emwork_Utility_DateTime::dateThaiToMySQL($objData['PublishDate']) . ' ' . (empty($objData['PublishDateTime']) ? "0:00" : str_replace(" ", "", $objData['PublishDateTime']));
						$form['PublishDown'] = empty($objData['PublishDown']) ? null : (Emwork_Utility_DateTime::dateThaiToMySQL($objData['PublishDown']) . ' ' . (empty($objData['PublishDownTime']) ? "0:00" : str_replace(" ", "", $objData['PublishDownTime'])));
						$form['Url'] = $objData['Url'];
						$form['UrlType'] = $objData['UrlType'];
						$form['UrlComment'] = null;
						if($objData['UrlType']=='E'){
							$form['UrlComment'] = $objData['UrlComment'];
						}
						$form['IsEnable'] = $objData['Enable'];
					}
					$form['Ordering'] = $i;
					$form['IsDeleted'] = 'N';
					$form['Created'] = date('Y-m-d H:i:s');
					$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['LicenseItemID'] = $id;
					$form['ID'] = $obj["ID"];
					$affectedRows = $this->db->insert("license_procedure_info", $form);
					
					if (($affectedRows > 0) && (count($obj['Lang']) > 0)) {
						foreach ($obj['Lang'] as $l => $v) {
							$formDetail = array();
							$whereDetail = array();
							$formDetail['Title'] = $v['Title'];
							$formDetail['IsEnable'] = 'Y';
							$formDetail['IsDeleted'] = 'N';
							$formDetail['ID'] = $obj['ID'];
							$formDetail['Lang'] = $l;
							$formDetail['Created'] = date('Y-m-d H:i:s');
							$this->db->insert("license_procedure_detail", $formDetail);
						}
					}
				}
			}	
			
			if($result){
				$data->id = $id;
				$data->result = true;
			}


		} catch (Exception $e) {
			$data->result = false;
		}
		return $data;
	}
		
	function update($params)
	{
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

			if($defaultLanguageCode == 'th') {
				$param[$defaultLanguageCode]['PublishDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			} else {
				$param[$defaultLanguageCode]['PublishDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}

			$form = array();
			$where = array();

			$form['LicenseCategoryID'] = null;
			$form['LicenseIssueID'] =  empty($params['Issue']) ? null : $params['Issue'];
			$form['LicenseDepartmentID'] =  null;
			$form['PublishDate'] =  $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] =  $param[$defaultLanguageCode]['PublishDown'];
			$form['Modified']  = $modified;
			$form['ModifiedBy'] =  empty($this->UserID) ? null : $this->UserID;
			$form['IsEnable'] = $param[$defaultLanguageCode]['IsEnable'];
			$where['ID = ?'] = $id;
			$result = $this->db->update("license_item_info", $form, $where);

			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$whereDetail = array();
					$formDetail['Title'] = $value['Title'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$whereDetail['ID = ?'] = $id;
					$whereDetail['Lang = ?'] = $lang;
					$affectedRows = $this->db->update("license_item_detail", $formDetail, $whereDetail);
					if ($affectedRows == 0) {
						$formDetail['ID'] = $id;
						$formDetail['Lang'] = $lang;
						$this->db->insert("license_item_detail", $formDetail);
					}
				}
			}
			
			// Procedure
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$where['LicenseItemID = ?'] = $id;
			$this->db->update("license_procedure_info", $form, $where);
			if (count($param[$defaultLanguageCode]['Procedure']) > 0) {
				$i = 0;
				foreach ($param[$defaultLanguageCode]['Procedure'] as $val) {
					$obj = json_decode($val, true);
					$i ++;
					$form = array();
					$where = array();
					if ((count($obj['Lang']) > 0) && (array_key_exists($defaultLanguageCode, $obj['Lang']))) {
						$objData = $obj['Lang'][$defaultLanguageCode];
						$form['LicenseDepartmentID'] = $objData['LicenseDepartmentID'];
						$form['IconType'] = $objData['IconType'];
						$form['StartDate'] = empty($objData['StartDate']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL($objData['StartDate']);
						$form['EndDate'] = empty($objData['EndDate']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL($objData['EndDate']);
						$form['PublishDate'] = Emwork_Utility_DateTime::dateThaiToMySQL($objData['PublishDate']) . ' ' . (empty($objData['PublishDateTime']) ? "0:00" : str_replace(" ", "", $objData['PublishDateTime']));
						$form['PublishDown'] = empty($objData['PublishDown']) ? null : (Emwork_Utility_DateTime::dateThaiToMySQL($objData['PublishDown']) . ' ' . (empty($objData['PublishDownTime']) ? "0:00" : str_replace(" ", "", $objData['PublishDownTime'])));
						$form['UrlType'] = $objData['UrlType'];
						$form['Url'] = $objData['Url'];
						$form['UrlComment'] = null;
						if($objData['UrlType']=='E'){
							$form['UrlComment'] = $objData['UrlComment'];
						}
						$form['IsEnable'] = $objData['Enable'];
					}
					$form['IsDeleted'] = 'N';
					$form['Ordering'] = $i;
					$form['Modified'] = date('Y-m-d H:i:s');
					$form['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['LicenseItemID'] = $id;
					$where["ID = ?"] = $obj["ID"];
					$affectedRows = $this->db->update("license_procedure_info", $form, $where);
					if ($affectedRows == 0) {
						$form['Created'] = date('Y-m-d H:i:s');
						$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$form['ID'] = $obj["ID"];
						$affectedRows = $this->db->insert("license_procedure_info", $form);
					}
					
					if (($affectedRows > 0) && (count($obj['Lang']) > 0)) {
						foreach ($obj['Lang'] as $l => $v) {
							$formDetail = array();
							$whereDetail = array();
							$formDetail['Title'] = $v['Title'];
							$formDetail['IsEnable'] = 'Y';
							$formDetail['IsDeleted'] = 'N';
							$formDetail['Modified'] = date('Y-m-d H:i:s');
							$whereDetail["ID = ?"] = $obj['ID'];
							$whereDetail["Lang = ?"] = $l;
							$affectedDetailRows = $this->db->update("license_procedure_detail", $formDetail, $whereDetail);
							if ($affectedDetailRows == 0) {
								$formDetail['ID'] = $obj['ID'];
								$formDetail['Lang'] = $l;
								$formDetail['Created'] = date('Y-m-d H:i:s');
								$this->db->insert("license_procedure_detail", $formDetail);
							}
						}
					}
				}
			}	

			if($result){
				$data->id = $id;
				$data->result = true;
			}
		} catch (Exception $e) {
			$data->message = $e->getMessage();
			$data->result = false;
		}
		return $data;
	}
	
	function delete($params)
	{
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
			$result = $this->db->update("license_item_info", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function importLicense($datas) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$defaultlang = 'th';

			// ------------------------------------ Category ------------------------------------ //

			if(count($datas)>0){
				foreach($datas as $c){

					$select_cat = $this->db->select()
					->from(array("tbl" => "license_category_info"), array('tbl.ID'))
					->joinLeft(array("d" => "license_category_detail"), "tbl.ID = d.ID and d.Lang = '{$defaultlang}' ", "")
					->where("tbl.IsDeleted = 'N'")
					->where("d.Title = ?", $c['CategoryTH'])
					;
					$stmt_cat = $this->db->query($select_cat);
					$obj_cat = $stmt_cat->fetchObject();

					$select_cat_max = $this->db->select()
					->from(array("tbl" => "license_category_info"), array('MaxID'=>'max(tbl.Ordering)'))
					->where("tbl.IsDeleted = 'N'")
					;
					$stmt_cat_max = $this->db->query($select_cat_max);
					$obj_cat_max = $stmt_cat_max->fetchObject();

					if($obj_cat->ID){
						$catID =  $obj_cat->ID;
						$newCat = false;
					}else {
						$form = array();
						$where = array();
						$form['Ordering'] = $obj_cat_max->MaxID + 1;
						$form['Created'] = date("Y-m-d H:i:s");
						$form['CreatedBy'] = $this->UserID;
						$this->db->insert("license_category_info", $form);
						$catID = $this->db->lastInsertId();
						$newCat = true;
					}

					if($catID && $c['CategoryTH']){
						$form = array();
						$where = array();
						$form['Title'] = $c['CategoryTH'];
						if(!$newCat){
							$form['Modified'] = date("Y-m-d H:i:s");
							$form['ModifiedBy'] = $this->UserID;
							$where["ID = ?"] = $catID;
							$where["Lang = ?"] = 'th';
							$this->db->update("license_category_detail", $form, $where);
						}else{
							$form['ID'] = $catID;
							$form['Lang'] = 'th';
							$form['Created'] = date("Y-m-d H:i:s");
							$form['CreatedBy'] = $this->UserID;
							$this->db->insert("license_category_detail", $form);
						}
					}
					if($catID && $c['CategoryEN']){
						$form = array();
						$where = array();
						$form['Title'] = $c['CategoryEN'];
						if(!$newCat){
							$form['Modified'] = date("Y-m-d H:i:s");
							$form['ModifiedBy'] = $this->UserID;
							$where["ID = ?"] = $catID;
							$where["Lang = ?"] = 'en';
							$this->db->update("license_category_detail", $form, $where);
						}else{
							$form['ID'] = $catID;
							$form['Lang'] = 'en';
							$form['Created'] = date("Y-m-d H:i:s");
							$form['CreatedBy'] = $this->UserID;
							$this->db->insert("license_category_detail", $form);
						}
					}

					// -------------------------------------- Issue ------------------------------------- //
					
					if(count($c['Issue'])>0 && $catID){
						foreach($c['Issue'] as $i){

							if(empty($i['IssueTH'])){ continue; }

							$select_issue = $this->db->select()
							->from(array("tbl" => "license_issue_info"), array('tbl.ID'))
							->joinLeft(array("d" => "license_issue_detail"), "tbl.ID = d.ID and d.Lang = '{$defaultlang}' ", "")
							->where("tbl.IsDeleted = 'N'")
							->where("tbl.LicenseCategoryID = ?", $catID)
							->where("d.Name = ?", $i['IssueTH'])
							;
							$stmt_issue = $this->db->query($select_issue);
							$obj_issue = $stmt_issue->fetchObject();

							$select_issue_max = $this->db->select()
							->from(array("tbl" => "license_issue_info"), array('MaxID'=>'max(tbl.Ordering)'))
							->where("tbl.IsDeleted = 'N'")
							;
							$stmt_issue_max = $this->db->query($select_issue_max);
							$obj_issue_max = $stmt_issue_max->fetchObject();

							if($obj_issue->ID){
								$issueID =  $obj_issue->ID;
								$newIssue = false;
							}else {
								$form = array();
								$where = array();
								$form['LicenseCategoryID'] = $catID;
								$form['Ordering'] = $obj_issue_max->MaxID + 1;
								$form['Created'] = date("Y-m-d H:i:s");
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("license_issue_info", $form);
								$issueID = $this->db->lastInsertId();
								$newIssue = true;
							}

							if($issueID && $i['IssueTH']){
								$form = array();
								$where = array();
								$form['Name'] = $i['IssueTH'];
								if(!$newIssue){
									$form['Modified'] = date("Y-m-d H:i:s");
									$form['ModifiedBy'] = $this->UserID;
									$where["ID = ?"] = $issueID;
									$where["Lang = ?"] = 'th';
									$this->db->update("license_issue_detail", $form, $where);
								}else{
									$form['ID'] = $issueID;
									$form['Lang'] = 'th';
									$form['Created'] = date("Y-m-d H:i:s");
									$form['CreatedBy'] = $this->UserID;
									$this->db->insert("license_issue_detail", $form);
								}
							}
							if($issueID && $i['IssueEN']){
								$form = array();
								$where = array();
								$form['Name'] = $i['IssueEN'];
								if(!$newIssue){
									$form['Modified'] = date("Y-m-d H:i:s");
									$form['ModifiedBy'] = $this->UserID;
									$where["ID = ?"] = $issueID;
									$where["Lang = ?"] = 'en';
									$this->db->update("license_issue_detail", $form, $where);
								}else{
									$form['ID'] = $issueID;
									$form['Lang'] = 'en';
									$form['Created'] = date("Y-m-d H:i:s");
									$form['CreatedBy'] = $this->UserID;
									$this->db->insert("license_issue_detail", $form);
								}
							}

							// ------------------------------------- License ------------------------------------ //

							if(count($i['License'])>0 && $issueID){
								foreach($i['License'] as $l){

									if(empty($l['LicenseTH'])){ continue; }
		
									$select_item = $this->db->select()
									->from(array("tbl" => "license_item_info"), array('tbl.ID'))
									->joinLeft(array("d" => "license_item_detail"), "tbl.ID = d.ID and d.Lang = '{$defaultlang}' ", "")
									->where("tbl.IsDeleted = 'N'")
									->where("tbl.LicenseIssueID = ?", $issueID)
									->where("d.Title = ?", $l['LicenseTH'])
									;
									$stmt_item = $this->db->query($select_item);
									$obj_item = $stmt_item->fetchObject();
		
									$select_item_max = $this->db->select()
									->from(array("tbl" => "license_item_info"), array('MaxID'=>'max(tbl.Ordering)'))
									->where("tbl.IsDeleted = 'N'")
									;
									$stmt_item_max = $this->db->query($select_item_max);
									$obj_item_max = $stmt_item_max->fetchObject();
		
									if($obj_item->ID){
										$licenseID =  $obj_item->ID;
										$newLicense = false;
									}else {
										$form = array();
										$where = array();
										$form['LicenseIssueID'] = $issueID;
										$form['PublishDate'] = date("Y-m-d");
										$form['Ordering'] = $obj_item_max->MaxID + 1;
										$form['Created'] = date("Y-m-d H:i:s");
										$form['CreatedBy'] = $this->UserID;
										$this->db->insert("license_item_info", $form);
										$licenseID = $this->db->lastInsertId();
										$newLicense = true;
									}
		
									if($licenseID && $l['LicenseTH']){
										$form = array();
										$where = array();
										$form['Title'] = $l['LicenseTH'];
										if(!$newLicense){
											$form['Modified'] = date("Y-m-d H:i:s");
											$form['ModifiedBy'] = $this->UserID;
											$where["ID = ?"] = $licenseID;
											$where["Lang = ?"] = 'th';
											$this->db->update("license_item_detail", $form, $where);
										}else{
											$form['ID'] = $licenseID;
											$form['Lang'] = 'th';
											$form['Created'] = date("Y-m-d H:i:s");
											$form['CreatedBy'] = $this->UserID;
											$this->db->insert("license_item_detail", $form);
										}
									}
									if($licenseID && $l['LicenseEN']){
										$form = array();
										$where = array();
										$form['Title'] = $l['LicenseEN'];
										if(!$newLicense){	
											$form['Modified'] = date("Y-m-d H:i:s");
											$form['ModifiedBy'] = $this->UserID;
											$where["ID = ?"] = $licenseID;
											$where["Lang = ?"] = 'en';
											$this->db->update("license_item_detail", $form, $where);
										}else{
											$form['ID'] = $licenseID;
											$form['Lang'] = 'en';
											$form['Created'] = date("Y-m-d H:i:s");
											$form['CreatedBy'] = $this->UserID;
											$this->db->insert("license_item_detail", $form);
										}
									}

									// ----------------------------- Department & Procedure ----------------------------- //

									if(count($l['Procedure'])>0 && $licenseID){
										foreach($l['Procedure'] as $p){

											// ----------------------------------- Department ----------------------------------- //

											if(empty($p['ProcedureDepartmentTH'])){ continue; }

											$select_department = $this->db->select()
											->from(array("tbl" => "license_department_info"), array('tbl.ID'))
											->joinLeft(array("d" => "license_department_detail"), "tbl.ID = d.ID and d.Lang = '{$defaultlang}' ", "")
											->where("tbl.IsDeleted = 'N'")
											->where("d.Name = ?", $p['ProcedureDepartmentTH'])
											;
											$stmt_department = $this->db->query($select_department);
											$obj_department = $stmt_department->fetchObject();
				
											$select_department_max = $this->db->select()
											->from(array("tbl" => "license_department_info"), array('MaxID'=>'max(tbl.Ordering)'))
											->where("tbl.IsDeleted = 'N'")
											;
											$stmt_department_max = $this->db->query($select_department_max);
											$obj_department_max = $stmt_department_max->fetchObject();

											if($obj_department->ID){
												$departmentID =  $obj_department->ID;
												$newDepartment = false;
											}else {
												$form = array();
												$where = array();
												$form['Ordering'] = $obj_department_max->MaxID + 1;
												$form['Created'] = date("Y-m-d H:i:s");
												$form['CreatedBy'] = $this->UserID;
												$this->db->insert("license_department_info", $form);
												$departmentID = $this->db->lastInsertId();
												$newDepartment = true;
											}
				
											if($departmentID && $p['ProcedureDepartmentTH']){
												$form = array();
												$where = array();
												$form['Name'] = $p['ProcedureDepartmentTH'];
												if(!$newDepartment){
													$form['Modified'] = date("Y-m-d H:i:s");
													$form['ModifiedBy'] = $this->UserID;
													$where["ID = ?"] = $departmentID;
													$where["Lang = ?"] = 'th';
													$this->db->update("license_department_detail", $form, $where);
												}else{
													$form['ID'] = $departmentID;
													$form['Lang'] = 'th';
													$form['Created'] = date("Y-m-d H:i:s");
													$form['CreatedBy'] = $this->UserID;
													$this->db->insert("license_department_detail", $form);
												}
											}
											if($departmentID && $p['ProcedureDepartmentEN']){
												$form = array();
												$where = array();
												$form['Name'] = $p['ProcedureDepartmentEN'];
												if(!$newDepartment){
													$form['Modified'] = date("Y-m-d H:i:s");
													$form['ModifiedBy'] = $this->UserID;
													$where["ID = ?"] = $departmentID;
													$where["Lang = ?"] = 'en';
													$this->db->update("license_department_detail", $form, $where);
												}else{
													$form['ID'] = $departmentID;
													$form['Lang'] = 'en';
													$form['Created'] = date("Y-m-d H:i:s");
													$form['CreatedBy'] = $this->UserID;
													$this->db->insert("license_department_detail", $form);
												}
											}

											// ------------------------------------ Procedure ----------------------------------- //
				
											if(empty($p['ProcedureTH'])){ continue; }

											if($departmentID){

												$select_procedure = $this->db->select()
												->from(array("tbl" => "license_procedure_info"), array('tbl.ID'))
												->joinLeft(array("d" => "license_procedure_detail"), "tbl.ID = d.ID and d.Lang = '{$defaultlang}' ", "")
												->where("tbl.IsDeleted = 'N'")
												->where("tbl.LicenseItemID = ?", $licenseID)
												->where("tbl.LicenseDepartmentID = ?", $departmentID)
												->where("d.Title = ?", $p['ProcedureTH'])
												;
												$stmt_procedure = $this->db->query($select_procedure);
												$obj_procedure = $stmt_procedure->fetchObject();
					
												$select_procedure_max = $this->db->select()
												->from(array("tbl" => "license_procedure_info"), array('MaxID'=>'max(tbl.Ordering)'))
												->where("tbl.IsDeleted = 'N'")
												;
												$stmt_procedure_max = $this->db->query($select_procedure_max);
												$obj_procedure_max = $stmt_procedure_max->fetchObject();
					
												if($obj_procedure->ID){
													$procedureID =  $obj_procedure->ID;
													$newProcedure = false;
												}else {
													$form = array();
													$where = array();
													$form['ID'] = $p['ProcedureID'];
													$form['LicenseItemID'] = $licenseID;
													$form['LicenseDepartmentID'] = $departmentID;
													$form['StartDate'] = empty($p['ProcedureStartDate']) ? null : $p['ProcedureStartDate'];
													$form['EndDate'] = empty($p['ProcedureEndDate']) ? null : $p['ProcedureEndDate'];
													$form['PublishDate'] = date("Y-m-d");
													$form['Url'] = $p['ProcedureUrlInfo'];
													$form['UrlType'] = $p['ProcedureUrlComment'] ? 'E' : 'D';
													$form['UrlComment'] = $p['ProcedureUrlComment'];
													$form['Ordering'] = $obj_procedure_max->MaxID + 1;
													$form['Created'] = date("Y-m-d H:i:s");
													$form['CreatedBy'] = $this->UserID;
													$this->db->insert("license_procedure_info", $form);
													$procedureID = $p['ProcedureID'];
													$newProcedure = true;
												}
					
												if($procedureID && $p['ProcedureTH']){
													$form = array();
													$where = array();
													$form['Title'] = $p['ProcedureTH'];
													if(!$newProcedure){
														$form['Modified'] = date("Y-m-d H:i:s");
														$form['ModifiedBy'] = $this->UserID;
														$where["ID = ?"] = $procedureID;
														$where["Lang = ?"] = 'th';
														$this->db->update("license_procedure_detail", $form, $where);
													}else{
														$form['ID'] = $procedureID;
														$form['Lang'] = 'th';
														$form['Created'] = date("Y-m-d H:i:s");
														$form['CreatedBy'] = $this->UserID;
														$this->db->insert("license_procedure_detail", $form);
													}
												}
												if($procedureID && $p['ProcedureEN']){
													$form = array();
													$where = array();
													$form['Title'] = $p['ProcedureEN'];
													if(!$newProcedure){
														$form['Modified'] = date("Y-m-d H:i:s");
														$form['ModifiedBy'] = $this->UserID;
														$where["ID = ?"] = $procedureID;
														$where["Lang = ?"] = 'en';
														$this->db->update("license_procedure_detail", $form, $where);
													}else{
														$form['ID'] = $procedureID;
														$form['Lang'] = 'en';
														$form['Created'] = date("Y-m-d H:i:s");
														$form['CreatedBy'] = $this->UserID;
														$this->db->insert("license_procedure_detail", $form);
													}
												}

											}

										}
									} // End Procedure

								}
							} // End License

						}
					} // End Issue

				}
			} // End Category
			
			$data->result = true;

		} catch (Exception $e) {
			print_r($e); exit;
			$data->result = false;
			$data->message = $e->getMessage();
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
					$affRow += $this->db->update("license_item_info", $form, $where);
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
					->from(array('tbl'=>'license_item_info'), array('*'))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['LicenseCategoryID'] = null;
					$form['LicenseIssueID'] = empty($dataInfo->LicenseIssueID) ? null : $dataInfo->LicenseIssueID;
					$form['LicenseDepartmentID'] = null;
					$form['PublishDate'] = empty($dataInfo->PublishDate) ? null : $dataInfo->PublishDate;
					$form['PublishDown'] = empty($dataInfo->PublishDown) ? null : $dataInfo->PublishDown;
					$form['Ordering'] = empty($dataInfo->Ordering) ? null : $dataInfo->Ordering;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$form['IsDeleted'] = 'N';
					$data->result = $this->db->insert("license_item_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'license_item_detail'), array('*'))
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
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("license_item_detail", $form);
							}
						}

						// Procedure
						$selectProcedure = $this->db->select ()
						->from(array('tbl'=>'license_procedure_info'), array('*'))
						->where("tbl.LicenseItemID = ? ", $id)
						->where("tbl.IsDeleted = 'N' ")
						;
						$stmtProcedure = $this->db->query($selectProcedure);
						$dataProcedure = $stmtProcedure->fetchAll();

						if(count($dataProcedure)>0){
							foreach($dataProcedure as $d){
								
								$tmpProcedureID = $this->gen_uuid();
								
								$form = array();
								$form["ID"] = $tmpProcedureID;
								$form['LicenseItemID'] = $tmpID;
								$form['LicenseDepartmentID'] = empty($d['LicenseDepartmentID']) ? null : $d['LicenseDepartmentID'];
								$form['IconType'] = empty($d['IconType']) ? null : $d['IconType'];
								$form['StartDate'] = empty($d['StartDate']) ? null : $d['StartDate'];
								$form['EndDate'] = empty($d['EndDate']) ? null : $d['EndDate'];
								$form['PublishDate'] = empty($d['PublishDate']) ? null : $d['PublishDate'];
								$form['PublishDown'] = empty($d['PublishDown']) ? null : $d['PublishDown'];
								$form['Url'] = empty($d['Url']) ? null : $d['Url'];
								$form['UrlType'] = empty($d['UrlType']) ? null : $d['UrlType'];
								$form['UrlComment'] = empty($d['UrlComment']) ? null : $d['UrlComment'];
								$form['Ordering'] = empty($d['Ordering']) ? null : $d['Ordering'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("license_procedure_info", $form);

								// Procedure Detail
								$selectProcedureDetail = $this->db->select ()
								->from(array('tbl'=>'license_procedure_detail'), array('*'))
								->where("tbl.ID = ? ", $d['ID'])
								;
								$stmtProcedureDetail = $this->db->query($selectProcedureDetail);
								$dataProcedureDetail = $stmtProcedureDetail->fetchAll();

								if(count($dataProcedureDetail)>0){
									foreach($dataProcedureDetail as $dd){
										$formPD = array();
										$formPD['ID'] = $tmpProcedureID;
										$formPD['Lang'] = $dd["Lang"];
										$formPD['Title'] = empty($dd['Title']) ? null : $dd['Title'];
										$formPD['Created'] = $modified;
										$formPD['CreatedBy'] = $this->UserID;
										$this->db->insert("license_procedure_detail", $formPD);
									}
								}

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
					$affRow += $this->db->update("license_item_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function gen_uuid() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				// 32 bits for "time_low"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
				
				// 16 bits for "time_mid"
				mt_rand( 0, 0xffff ),
				
				// 16 bits for "time_hi_and_version",
				// four most significant bits holds version number 4
				mt_rand( 0, 0x0fff ) | 0x4000,
				
				// 16 bits, 8 bits for "clk_seq_hi_res",
				// 8 bits for "clk_seq_low",
				// two most significant bits holds zero and one for variant DCE1.1
				mt_rand( 0, 0x3fff ) | 0x8000,
				
				// 48 bits for "node"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
				);
	}
}
?>
