<?php

class Faq_Model_Item {
	
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
			->from(array('tbl' => 'faq_item_info'), '')
			->joinLeft(array('d' => 'faq_item_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('c' => 'faq_category_info') , "tbl.FaqCategoryID = c.ID" , '')
			->joinLeft(array('cd' => 'faq_category_detail') , "c.ID = cd.ID and cd.Lang = '{$lang}' " , '')
			->joinLeft(array('sc' => 'faq_subcategory_info') , "tbl.FaqSubCategoryID = sc.ID" , '')
			->joinLeft(array('scd' => 'faq_subcategory_detail') , "sc.ID = scd.ID and scd.Lang = '{$lang}' " , '')
			->columns(array(
					"ID" => "tbl.ID",
					"FaqCategoryID" => "tbl.FaqCategoryID",
					"FaqSubCategoryID" => "tbl.FaqSubCategoryID",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"Ordering" => "tbl.Ordering",
					"IsPin" => "tbl.IsPin",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",

					"Topic" => "d.Topic",
					"Detail" => "d.Detail",

					"CategoryName" => "cd.Name",
					"SubCategoryName" => "scd.Name",

					"CategoryOrdering" => "c.Ordering",
					"SubCategoryOrdering" => "sc.Ordering",
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted ='N'")
			->where("tbl.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("d.Topic is not null")
			->where("d.Topic <> '' ")
			;
			
			if (!empty($advancesearch['FaqCategoryID'])) {
				$select = $select->where("tbl.FaqCategoryID = ? ", $advancesearch['FaqCategoryID']);
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

			$select = $select->order("CategoryOrdering {$advancesearch['FaqMainCategorySortDirection']}");
			$select = $select->order("SubCategoryOrdering {$advancesearch['FaqSubCategorySortDirection']}");
			$select = $select->order("tbl.IsPin DESC");
			$select = $select->order("{$rowSort} {$sord}");

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
			->from(array('tbl' => 'faq_item_info'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"FaqCategoryID" => "tbl.FaqCategoryID",
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
			->from(array('tbl' => 'faq_item_info'), '')
			->joinLeft(array('d' => 'faq_item_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"FaqCategoryID" => "tbl.FaqCategoryID",
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
	
	function getFiles($faqItemID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'faq_item_file'), '')
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
			->where("tbl.FaqItemID = ? ", $faqItemID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	// public static function saveView($faqItemID, $memberID, $userID, $lang, $accessFrom) {
	// 	$data = new stdClass();
	// 	$data->result = false;
	// 	try {
	// 		$db = Zend_Db_Table::getDefaultAdapter();
	// 		$request = Zend_Controller_Front::getInstance()->getRequest();
			
	// 		$created = date("Y-m-d H:i:s");
	// 		$form = array();
	// 		$form["FaqItemID"] = $faqItemID;
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
	// 		$affectedRows = $db->insert("faq_item_view", $form);
			
	// 		if ($affectedRows) {
	// 			$select = $db->select()->from(array("tbl" => "faq_item_view"), array("TotalView" => "count(tbl.ID)"))
	// 			->where("FaqItemID = ?", $faqItemID)
	// 			;
	// 			$stmt = $db->query($select);
	// 			$dataTotal = $stmt->fetchObject();
				
	// 			$formSummary = array();
	// 			$whereSummary = array();
	// 			$formSummary["ViewAmount"] = $dataTotal->TotalView;
	// 			$whereSummary["ID = ?"] = $faqItemID;
	// 			$affectedRows = $db->update("faq_item_info", $formSummary, $whereSummary);
	// 		}
	// 		$data->result = true;
	// 	} catch (Exception $e) {
			
	// 	}
	// 	return $data;
	// }
	
}

?>
