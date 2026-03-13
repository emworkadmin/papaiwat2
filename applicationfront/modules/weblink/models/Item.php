<?php

class Weblink_Model_Item {
	
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
			->from(array('tbl' => 'weblink_item_info'), '')
			->joinLeft(array('d' => 'weblink_item_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Url" => "tbl.Url",
					"LinkTarget" => "tbl.LinkTarget",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Ordering" => "tbl.Ordering",
					"ViewAmount" => "tbl.ViewAmount",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"AuthorName" => "concat(u.FirstName, ' ', u.LastName)",
					
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted ='N'")
			->where("tbl.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			;
			
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("d.Title like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['isCat'])) {
				$select = $select->distinct()
				->joinInner(array('ca' => "weblink_category_item"), "tbl.ID = ca.WeblinkItemID", '') 
				->where("ca.WeblinkCategoryID = ? ", $advancesearch['isCat'])
				->where("ca.IsDeleted = 'N' ")
				;
			}
			if (count($advancesearch['isCatIds'])>0) {
				$select = $select->distinct()
				->joinInner(array('ca' => "weblink_category_item"), "tbl.ID = ca.WeblinkItemID", '') 
				->joinInner(array('c' => "weblink_category_info"), "ca.WeblinkCategoryID = c.ID", '')
				->joinInner(array('cd' => "weblink_category_detail"), "ca.WeblinkCategoryID = cd.ID and cd.Lang = '{$lang}' ", '')
				->columns(array(
					'CategoryID'=>'cd.ID',
					'CategoryTitle'=>'cd.Title',
				  	'CategoryOrdering'=>'c.Ordering'
				  ))
				->where("ca.WeblinkCategoryID in (" . implode(",",$advancesearch['isCatIds']) . ")")
				->where("ca.IsDeleted = 'N' ")
				;
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

			if (count($advancesearch['isCatIds'])>0) {
				$select = $select->order("CategoryOrdering");
			}
			if (in_array($rowSort, array('Title'))) {
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
			->from(array('tbl' => 'weblink_item_info'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"WeblinkCategoryID" => "tbl.WeblinkCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					"TemplateType" => "tbl.TemplateType",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"ViewAmount" => "tbl.ViewAmount",
					"Ordering" => "tbl.Ordering",
					"CanComment" => "tbl.CanComment",
					"Settings" => "tbl.Settings",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
					
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where("tbl.ID = ? " , $id)
			;
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
	
	function getDataByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'weblink_item_info'), '')
			->joinLeft(array('d' => 'weblink_item_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"WeblinkCategoryID" => "tbl.WeblinkCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					// "ID" => "tbl.main_title",
					"TemplateType" => "tbl.TemplateType",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"ViewAmount" => "tbl.ViewAmount",
					"Ordering" => "tbl.Ordering",
					"CanComment" => "tbl.CanComment",
					"Settings" => "tbl.Settings",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
					
					"Lang" => "d.Lang",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Detail" => "d.Detail"
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
	
	function getFiles($weblinkItemID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'weblink_item_file'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
			->columns(array(
					"FileInfoID" => "tbl.FileInfoID",
					"ItemFileType" => "tbl.FileType",
					
					"FileName" => "fi.Name",
					"FileHashName" => "fi.HashName",
					"FileType" => "fi.FileType",
					"FileSize" => "fi.FileSize",
					"FileExtension" => "fi.Extension"
			))
			->where("tbl.WeblinkItemID = ? ", $weblinkItemID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	// public static function saveView($weblinkItemID, $memberID, $userID, $lang, $accessFrom) {
	// 	$data = new stdClass();
	// 	$data->result = false;
	// 	try {
	// 		$db = Zend_Db_Table::getDefaultAdapter();
	// 		$request = Zend_Controller_Front::getInstance()->getRequest();
			
	// 		$created = date("Y-m-d H:i:s");
	// 		$form = array();
	// 		$form["WeblinkItemID"] = $weblinkItemID;
	// 		if (!empty($memberID)) {
	// 			$form["MemberInfoID"] = $memberID;
	// 		}
	// 		$form["Lang"] = $lang;
	// 		$form["AccessFrom"] = $accessFrom;
	// 		$form["RemoteIP"] = $request->getServer('REMOTE_ADDR');
	// 		if (!empty($form["RemoteIP"])) {
	// 			$countryCode = geoip_country_code_by_name($form["RemoteIP"]);
	// 			$form["GeoIP"] = $countryCode;
	// 		}
	// 		//$form["ActionDate"] = $created;
	// 		$form["Created"] = $created;
	// 		if (!empty($userID)) {
	// 			$form["CreatedBy"] = $userID;
	// 		}			
	// 		$affectedRows = $db->insert("weblink_item_view", $form);
			
	// 		if ($affectedRows) {
	// 			$select = $db->select()->from(array("tbl" => "weblink_item_view"), array("TotalView" => "count(tbl.ID)"))
	// 			->where("WeblinkItemID = ?", $weblinkItemID)
	// 			;
	// 			$stmt = $db->query($select);
	// 			$dataTotal = $stmt->fetchObject();
				
	// 			$formSummary = array();
	// 			$whereSummary = array();
	// 			$formSummary["ViewAmount"] = $dataTotal->TotalView;
	// 			$whereSummary["ID= ?"] = $weblinkItemID;
	// 			$affectedRows = $db->update("weblink_item_info", $formSummary, $whereSummary);
	// 		}
	// 		$data->result = true;
	// 	} catch (Exception $e) {
			
	// 	}
	// 	return $data;
	// }
	
	protected $recursiveIds = array();
	public function getRecursiveCategoryIds($id = null) {

		$select = $this->db->select()
		->from ( array ('tbs' => 'weblink_category_info' ), array ('ID') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->where ( "tbs.ParentID = ?", $id )
		->order ( "tbs.Ordering ASC" )
		;
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();

		foreach ( $data as $value ) {
			if (empty($id) || ($id != $value["ID"])) {
				$this->getRecursiveCategoryIds($value ["ID"]);
				if(!in_array($value["ID"],$this->recursiveIds)){ 
					$this->recursiveIds[] = $value["ID"]; 
				}
			}
		}
		return $this->recursiveIds;
	}

	function saveClick($id) {

		$result = new stdClass();
		$result->result = false;

		try {

			$select = $this->db->select()
			->from(array("tbl" => "weblink_item_info"), "")
			->columns(array(
				"ViewAmount" => "ifnull(tbl.ViewAmount,0)"
			))
			->where("tbl.ID = ?", $id)
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();

			$ViewAmount = $data->ViewAmount + 1;

			$form = array();
			$where = array();
			$form['ViewAmount'] = $ViewAmount;
			$where['ID = ?'] = $id;
			$result = $this->db->update("weblink_item_info", $form, $where);

			if($result) {
				$result->result = true;
			}

		} catch (Exception $e) {
			$result->message = $e->getMessage();
		}

		return $result;
	}
}

?>
