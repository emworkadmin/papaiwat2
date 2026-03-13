<?php

class Ebook_Model_Book {
	
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

	function getInfo($id) {
		$data = new stdClass();
		try {
			$select= $this->db->select()->distinct()
			->from(array('tbl' => 'ebook_book_info'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->joinLeft(array("fia" => "file_info"), "tbl.AuthorImageID = fia.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"EbookCategoryID" => "tbl.EbookCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					"TemplateType" => "tbl.TemplateType",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"DateOfCreation" => "tbl.DateOfCreation",
					"AuthorImageID" => "tbl.AuthorImageID",
					"ViewAmount" => "tbl.ViewAmount",
					"Ordering" => "tbl.Ordering",
					"CanComment" => "tbl.CanComment",
					"Settings" => "tbl.Settings",
					"MetaData" => "tbl.MetaData",
					"SocialNetworkFacebook" => "tbl.SocialNetworkFacebook",
					"SocialNetworkTwitter" => "tbl.SocialNetworkTwitter",
					"SocialNetworkGoogleplus" => "tbl.SocialNetworkGoogleplus",
					"ShowQrcode" => "tbl.ShowQrcode",
					"ShowPrint" => "tbl.ShowPrint",
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

					"AuthorImageFileName" => "fia.Name",
					"AuthorImageHashName" => "fia.HashName",
					"AuthorImageFileType" => "fia.FileType",
					"AuthorImageFileSize" => "fia.FileSize",
					"AuthorImageFileExtension" => "fia.Extension",
			))
			->where("tbl.ID = ? " , $id)
			;
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->PublishDate = empty($data->PublishDate) ? null : new DateTime($data->PublishDate);
				$data->PublishDown = empty($data->PublishDown) ? null : new DateTime($data->PublishDown);
				$data->DateOfCreation = empty($data->DateOfCreation) ? null : new DateTime($data->DateOfCreation);
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
			->from(array('tbl' => 'ebook_book_info'), '')
			->joinLeft(array('d' => 'ebook_book_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"EbookCategoryID" => "tbl.EbookCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					"TemplateType" => "tbl.TemplateType",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"DateOfCreation" => "tbl.DateOfCreation",
					"AuthorImageID" => "tbl.AuthorImageID",
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

					"SizeType" => "tbl.SizeType",
					"Height" => "tbl.Height",
					"Width" => "tbl.Width",
					
					"Lang" => "d.Lang",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Detail" => "d.Detail",
					"AuthorName" => "d.AuthorName"
			))
			->where("tbl.ID = ? " ,$id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->PublishDate = empty($data->PublishDate) ? null : new DateTime($data->PublishDate);
				$data->PublishDown = empty($data->PublishDown) ? null : new DateTime($data->PublishDown);
				$data->DateOfCreation = empty($data->DateOfCreation) ? null : new DateTime($data->DateOfCreation);
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}

	public static function saveView($itemID, $memberID, $userID, $lang, $accessFrom) {
		$data = new stdClass();
		$data->result = false;
		try {
			$db = Zend_Db_Table::getDefaultAdapter();
			$request = Zend_Controller_Front::getInstance()->getRequest();
			
			$created = date("Y-m-d H:i:s");
			$form = array();
			$form["EbookBookID"] = $itemID;
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
			$affectedRows = $db->insert("ebook_book_view", $form);
			
			if ($affectedRows) {
				$select = $db->select()->from(array("tbl" => "ebook_book_view"), array("TotalView" => "count(tbl.ID)"))
				->where("EbookBookID = ?", $itemID)
				;
				$stmt = $db->query($select);
				$dataTotal = $stmt->fetchObject();
				
				$formSummary = array();
				$whereSummary = array();
				$formSummary["ViewAmount"] = $dataTotal->TotalView;
				$whereSummary["ID = ?"] = $itemID;
				$affectedRows = $db->update("ebook_book_info", $formSummary, $whereSummary);
			}
			$data->result = true;
		} catch (Exception $e) {

		}
		return $data;
	}

	function getFiles($ebookBookID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'ebook_page_info'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
			->columns(array(
					"FileInfoID" => "tbl.FileInfoID",
					"FileTitle" => "tbl.Title",
					"FileName" => "fi.Name",
					"FileHashName" => "fi.HashName",
					"FileType" => "fi.FileType",
					"FileSize" => "fi.FileSize",
					"FileExtension" => "fi.Extension"
			))
			->where("tbl.EbookBookID = ? ", $ebookBookID)
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();

		try {
			
			$select = $this->db->select()
			->from(array('tbl' => 'ebook_book_info'), '')
			->joinLeft(array('d' => 'ebook_book_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->joinLeft(array("cb" => "ebook_category_book"), "tbl.ID = cb.EbookBookID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Ordering" => "tbl.Ordering",
					"ViewAmount" => "tbl.ViewAmount",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"Cat" => "cb.EbookCategoryID",
					"AuthorName" => "concat(u.FirstName, ' ', u.LastName)",
					
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsEnable = 'Y' ")
			->where("cb.EbookCategoryID =  '{$advancesearch["id"]}'")
			->where("tbl.IsDeleted ='N'")
			->where("tbl.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("case when tbl.PublishDown is null then 1 else (case when tbl.PublishDown >= ? then 1 else 0 end) end = 1", date('Y-m-d H:i:s'))
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			;

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

	function getCommentList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			$fullname = 'concat(ifnull(u.TitleName,""), ifnull(u.FirstName,""), " ", ifnull(u.LastName,""))';
			$select = $this->db->select()
			->from(array('tbl' => 'ebook_book_comment'), "")
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
			->where("tbl.EbookBookID = ?", $advancesearch['EbookBookID'])
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
			$form["EbookBookID"] = $params['hdfID'];
			$form["MemberID"] = $this->UserID;
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
			$form["CreatedBy"] = $this->UserID;
			$form["IsEnable"] = empty($params['hdfEbookPublicComment']) ? 'N' : $params['hdfEbookPublicComment'];
			$data->result = $this->db->insert("ebook_book_comment", $form);

		} catch (Exception $e) {

		}
		return $data;
	}
}

?>
