<?php

class Calendar_Model_Article {
	
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
			->from(array('tbl' => 'calendar_article_info'), '')
			->joinLeft(array('d' => 'calendar_article_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array("c" => "calendar_category_info"), "tbl.CalendarCategoryID = c.ID ", "")
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Place" => "d.Place",
					"CalendarCategoryID" => "tbl.CalendarCategoryID",
					"StartDate" => "tbl.StartDate",
					"EndDate" => "tbl.EndDate",
					"PublishDate" => "tbl.PublishDate",
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

					"Color" => "c.Color"
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
				->joinInner(array('ca' => "calendar_category_article"), "tbl.ID = ca.CalendarArticleID", '') 
				->where("ca.CalendarCategoryID = ? ", $advancesearch['isCat'])
				->where("ca.IsDeleted = 'N' ")
				;
			}
			if (count($advancesearch['CategoryIds'])>0) {
				$select = $select->distinct()
				->joinInner(array('ca' => "calendar_category_article"), "tbl.ID = ca.CalendarArticleID", '') 
				->where("ca.CalendarCategoryID in (". implode(",",$advancesearch['CategoryIds']). ")")
				->where("ca.IsDeleted = 'N' ")
				;
			}

			// For Calendar
			if (!empty($advancesearch['StartDate'])) {
				$select = $select->where("tbl.StartDate >= ?", ($advancesearch['StartDate']));
			} 
			if (!empty($advancesearch['EndDate'])) {
				$select = $select->where("tbl.EndDate <= ?", ($advancesearch['EndDate'].' 23:59'));
			}

			// For Widget Calendar Link
			if (!empty($advancesearch['FilterDate'])) {
				$select = $select->where("? BETWEEN DATE(tbl.StartDate) AND DATE(tbl.EndDate)", ($advancesearch['FilterDate']));
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
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["StartDate"] = empty($data[$i]["StartDate"]) ? null : new DateTime($data[$i]["StartDate"]);
					$data[$i]["EndDate"] = empty($data[$i]["EndDate"]) ? null : new DateTime($data[$i]["EndDate"]);
					$data[$i]["PublishDate"] = empty($data[$i]["PublishDate"]) ? null : new DateTime($data[$i]["PublishDate"]);
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
			$CreatedBy = "concat(ifnull(u1.TitleName,''), ifnull(u1.FirstName,''), ' ', ifnull(u1.LastName,''))";
			$ModifiedBy = "concat(ifnull(u2.TitleName,''), ifnull(u2.FirstName,''), ' ', ifnull(u2.LastName,''))";

			$select= $this->db->select()->distinct()
			->from(array('tbl' => 'calendar_article_info'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->joinLeft(array("u1" => "sys_user"), "tbl.CreatedBy = u1.ID ", "")
			->joinLeft(array("u2" => "sys_user"), "tbl.ModifiedBy = u2.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"CalendarCategoryID" => "tbl.CalendarCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					"TemplateType" => "tbl.TemplateType",
					"Telephone" => "tbl.Telephone",
					"EventLink" => "tbl.EventLink",
					"StartDate" => "tbl.StartDate",
					"EndDate" => "tbl.EndDate",
					"Latitude" => "tbl.Latitude",
					"Longitude" => "tbl.Longitude",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"ViewAmount" => "tbl.ViewAmount",
					"Ordering" => "tbl.Ordering",
					"CanComment" => "tbl.CanComment",
					"ShowPrint" => "tbl.ShowPrint",
					"ShowQrcode" => "tbl.ShowQrcode",
					"SocialNetworkTwitter" => "tbl.SocialNetworkTwitter",
					"SocialNetworkGoogleplus" => "tbl.SocialNetworkGoogleplus",
					"SocialNetworkFacebook" => "tbl.SocialNetworkFacebook",
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

					"LastModifiedBy" => "(case when tbl.ModifiedBy then " . $ModifiedBy . " else " . $CreatedBy . " end)"
			))
			->where("tbl.ID = ? " , $id)
			;
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->StartDate = empty($data->StartDate) ? null : new DateTime($data->StartDate);
				$data->EndDate = empty($data->EndDate) ? null : new DateTime($data->EndDate);
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
			$CreatedBy = "concat(ifnull(u1.TitleName,''), ifnull(u1.FirstName,''), ' ', ifnull(u1.LastName,''))";
			$ModifiedBy = "concat(ifnull(u2.TitleName,''), ifnull(u2.FirstName,''), ' ', ifnull(u2.LastName,''))";

			$select = $this->db->select()
			->from(array('tbl' => 'calendar_article_info'), '')
			->joinLeft(array('d' => 'calendar_article_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->joinLeft(array('c' => 'calendar_category_info'),"tbl.CalendarCategoryID = c.ID ", '')
			->joinLeft(array("u1" => "sys_user"), "tbl.CreatedBy = u1.ID ", "")
			->joinLeft(array("u2" => "sys_user"), "tbl.ModifiedBy = u2.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"CalendarCategoryID" => "tbl.CalendarCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					"TemplateType" => "tbl.TemplateType",
					"Telephone" => "tbl.Telephone",
					"EventLink" => "tbl.EventLink",
					"StartDate" => "tbl.StartDate",
					"EndDate" => "tbl.EndDate",
					"Latitude" => "tbl.Latitude",
					"Longitude" => "tbl.Longitude",
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
					"Detail" => "d.Detail",
					"Place" => "d.Place",
					"Contact" => "d.Contact",
					"EventOrganizer" => "d.EventOrganizer",

					"LastModifiedBy" => "(case when tbl.ModifiedBy then " . $ModifiedBy . " else " . $CreatedBy . " end)",

					"CalendarCategoryParentID" => "c.ParentID",
					"Settings" => "c.Settings"
			))
			->where("tbl.ID = ? " ,$id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->StartDate = empty($data->StartDate) ? null : new DateTime($data->StartDate);
				$data->EndDate = empty($data->EndDate) ? null : new DateTime($data->EndDate);
				$data->PublishDate = empty($data->PublishDate) ? null : new DateTime($data->PublishDate);
				$data->PublishDown = empty($data->PublishDown) ? null : new DateTime($data->PublishDown);
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getFiles($calendarArticleID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'calendar_article_file'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
			->columns(array(
					"FileInfoID" => "tbl.FileInfoID",
					"ArticleFileType" => "tbl.FileType",
					
					"FileName" => "fi.Name",
					"FileHashName" => "fi.HashName",
					"FileType" => "fi.FileType",
					"FileSize" => "fi.FileSize",
					"FileExtension" => "fi.Extension"
			))
			->where("tbl.CalendarArticleID = ? ", $calendarArticleID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getCommentList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			$fullname = 'concat(ifnull(u.TitleName,""), ifnull(u.FirstName,""), " ", ifnull(u.LastName,""))';
			$select = $this->db->select()
			->from(array('tbl' => 'calendar_article_comment'), "")
			->joinLeft(array("u" => "sys_user"), "tbl.MemberID = u.ID ", "")
			->joinLeft(array("fi" => "file_info"), "u.ProfileImageID = fi.ID ", "")
			->columns(array(
				'ID',
				'Message',
				'ActionDate',
				'ActionFullname'=>$fullname,
				'ProfileImageHashName'=>'fi.HashName'
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted ='N'")
			->where("tbl.CalendarArticleID = ?", $advancesearch['CalendarArticleID'])
			;
			$records = $this->getListCount($select);
			
			if (empty($sidx)) {
				$sidx = "tbl.ActionDate";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			->order("$rowSort $sord")
			->limitPage($page, $rows)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			// echo $e->getMessage();
		}
		return $data;
	}

	public function saveComment($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			$request = Zend_Controller_Front::getInstance()->getRequest();
			
			$created = date("Y-m-d H:i:s");
			$form = array();
			$form["CalendarArticleID"] = $params['hdfID'];
			$form["MemberID"] = empty($this->UserID) ? null : $this->UserID;
			$form["Lang"] = $params['DefaultLanguageCode'];
			$form["AccessFrom"] = "Web";
			$form["RemoteIP"] = $request->getServer('REMOTE_ADDR');
			if (!empty($form["RemoteIP"])) {
				$countryCode = geoip_country_code_by_name($form["RemoteIP"]);
				$form["GeoIP"] = $countryCode;
			}
			$form["ActionDate"] = $created;
			$form["Rating"] = null;
			$form["Message"] = $params['txtMessage'];
			$form["Created"] = $created;
			$form["CreatedBy"] = empty($this->UserID) ? null : $this->UserID;
			$form["IsEnable"] = empty($params['hdfArticlePublicComment']) ? 'N' : $params['hdfArticlePublicComment'];
			$data->result = $this->db->insert("calendar_article_comment", $form);

		} catch (Exception $e) {

		}
		return $data;
	}
	
	public static function saveView($calendarArticleID, $memberID, $userID, $lang, $accessFrom) {
		$data = new stdClass();
		$data->result = false;
		try {
			$db = Zend_Db_Table::getDefaultAdapter();
			$request = Zend_Controller_Front::getInstance()->getRequest();
			
			$created = date("Y-m-d H:i:s");
			$form = array();
			$form["CalendarArticleID"] = $calendarArticleID;
			if (!empty($memberID)) {
				$form["MemberInfoID"] = $memberID;
			}
			$form["Lang"] = $lang;
			$form["AccessFrom"] = $accessFrom;
			$form["RemoteIP"] = $request->getServer('REMOTE_ADDR');
			if (!empty($form["RemoteIP"])) {
				$countryCode = geoip_country_code_by_name($form["RemoteIP"]);
				$form["GeoIP"] = $countryCode;
			}
			//$form["ActionDate"] = $created;
			$form["Created"] = $created;
			if (!empty($userID)) {
				$form["CreatedBy"] = $userID;
			}			
			$affectedRows = $db->insert("calendar_article_view", $form);
			
			if ($affectedRows) {
				$select = $db->select()->from(array("tbl" => "calendar_article_view"), array("TotalView" => "count(tbl.ID)"))
				->where("tbl.CalendarArticleID = ?", $calendarArticleID)
				;
				$stmt = $db->query($select);
				$dataTotal = $stmt->fetchObject();
				
				$formSummary = array();
				$whereSummary = array();
				$formSummary["ViewAmount"] = $dataTotal->TotalView;
				$whereSummary["ID = ?"] = $calendarArticleID;
				$affectedRows = $db->update("calendar_article_info", $formSummary, $whereSummary);
			}
			$data->result = true;
		} catch (Exception $e) {
			
		}
		return $data;
	}
}

?>
