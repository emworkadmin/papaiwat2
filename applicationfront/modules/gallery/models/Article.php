<?php

class Gallery_Model_Article {
	
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
			->from(array('tbl' => 'gallery_article_info'), '')
			->joinLeft(array('d' => 'gallery_article_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Ordering" => "tbl.Ordering",
					"ViewAmount" => "tbl.ViewAmount",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"AuthorName" => "concat(u.FirstName, ' ', u.LastName)",
					"tbl.PublishDate",
					
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension"

			))
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.IsDeleted ='N'")
			->where("tbl.IsTrash ='N'")
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
				->joinInner(array('ca' => "gallery_category_article"), "tbl.ID = ca.GalleryArticleID", '') 
				->where("ca.GalleryCategoryID = ? ", $advancesearch['isCat'])
				->where("ca.IsDeleted = 'N' ")
				;
			}

			// set criteria for recommend list
			if (!empty($advancesearch['infoID'])) {
				$select = $select->where("tbl.ID NOT IN (".$advancesearch['infoID'].")");
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
			if(!empty($advancesearch['infoID'])){
				$select = $select->order('RAND()');
			}else if (in_array($rowSort, array('Title'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			}else {
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
			->from(array('tbl' => 'gallery_article_info'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"GalleryCategoryID" => "tbl.GalleryCategoryID",
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
					"IsTrash" => "tbl.IsTrash",
					"ImagePerPage" => "tbl.ImagePerPage",
					
					"MetaData" => "tbl.MetaData",
					"SocialNetworkFacebook" => "tbl.SocialNetworkFacebook",
					"SocialNetworkTwitter" => "tbl.SocialNetworkTwitter",
					"SocialNetworkGooglePlus" => "tbl.SocialNetworkGooglePlus",
					"ShowQrcode" => "tbl.ShowQrcode",
					"ShowPrint" => "tbl.ShowPrint",
					
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension"
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
	
	function getDataByLanguage($id, $lang,$ImagePerPage) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'gallery_article_info'), '')
			->joinLeft(array('d' => 'gallery_article_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->joinLeft(array('c' => 'gallery_category_info'),"tbl.GalleryCategoryID = c.ID ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"GalleryCategoryID" => "tbl.GalleryCategoryID",
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
					"IsTrash" => "tbl.IsTrash",
					"ImagePerPage" => "tbl.ImagePerPage",
					
					
					"MetaData" => "tbl.MetaData",
					"SocialNetworkFacebook" => "tbl.SocialNetworkFacebook",
					"SocialNetworkTwitter" => "tbl.SocialNetworkTwitter",
					"SocialNetworkGooglePlus" => "tbl.SocialNetworkGooglePlus",
					"ShowQrcode" => "tbl.ShowQrcode",
					"ShowPrint" => "tbl.ShowPrint",
					
					"Lang" => "d.Lang",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Detail" => "d.Detail",

					"GalleryCategoryParentID" => "c.ParentID",
					"Settings" => "c.Settings"
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
	
	// function getFiles($galleryArticleID,$ImagePerPage,$Template) {
	// 	$data = array();
	// 	try {
	// 		$select = $this->db->select()
	// 		->from(array('tbl' => 'gallery_article_file'), '')
	// 		->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
	// 		->columns(array(
	// 				"FileInfoID" => "tbl.FileInfoID",
	// 				"ArticleFileType" => "tbl.FileType",
					
	// 				"FileName" => "fi.Name",
	// 				"FileHashName" => "fi.HashName",
	// 				"FileType" => "fi.FileType",
	// 				"FileSize" => "fi.FileSize",
	// 				"FileExtension" => "fi.Extension"
	// 		))
	// 		->where("tbl.GalleryArticleID = ? ", $galleryArticleID)
	// 		->where("tbl.IsDeleted = 'N' ")
	// 		;
	// 		if(($ImagePerPage>0) &&($Template>=4)&&($Template<=5)){
	// 			$select = $select->limit($ImagePerPage)
	// 			;
	// 		}
	// 		$stmt = $this->db->query($select);
	// 		$data = $stmt->fetchAll();
	// 	} catch (Exception $e) {
			
	// 	}
	// 	return $data;
	// }

	function getFile($galleryArticleID,$ImagePerPage,$Template) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'gallery_article_cover'), '')
			->joinLeft(array(
				"md" => "gallery_article_cover_detail"), 
				"tbl.GalleryArticleID = md.GalleryArticleID  and tbl.DataCode = md.DataCode and md.Lang = '{$lang}' ", 
				"")
			->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
			->columns(array(
					"FileInfoID" => "tbl.FileInfoID",
					"Name" =>"md.Title",
					"Description" =>"md.IntroText",

					"FileName" => "fi.Name",
					"FileHashName" => "fi.HashName",
					"FileType" => "fi.FileType",
					"FileSize" => "fi.FileSize",
					"FileExtension" => "fi.Extension"
			))
			->where("tbl.GalleryArticleID = ? ", $galleryArticleID)
			->where("tbl.IsDeleted = 'N' ")
			;
			if(($ImagePerPage>0) &&($Template>=4)&&($Template<=5)){
				$select = $select->limit($ImagePerPage)
				;
			}
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
			->from(array('tbl' => 'gallery_article_comment'), "")
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
			->where("tbl.GalleryArticleID = ?", $advancesearch['GalleryArticleID'])
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
			$form["GalleryArticleID"] = $params['hdfID'];
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
			$form["IsEnable"] = empty($params['hdfArticlePublicComment']) ? 'N' : $params['hdfArticlePublicComment'];
			$data->result = $this->db->insert("gallery_article_comment", $form);

		} catch (Exception $e) {

		}
		return $data;
	}
	
	public static function saveView($galleryArticleID, $memberID, $userID, $lang, $accessFrom) {
		$data = new stdClass();
		$data->result = false;
		try {
			$db = Zend_Db_Table::getDefaultAdapter();
			$request = Zend_Controller_Front::getInstance()->getRequest();
			
			$created = date("Y-m-d H:i:s");
			$form = array();
			$form["GalleryArticleID"] = $galleryArticleID;
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
			$affectedRows = $db->insert("gallery_article_view", $form);
			
			if ($affectedRows) {
				$select = $db->select()->from(array("tbl" => "gallery_article_view"), array("TotalView" => "count(tbl.ID)"))
				->where("GalleryArticleID = ?", $galleryArticleID)
				;
				$stmt = $db->query($select);
				$dataTotal = $stmt->fetchObject();
				
				$formSummary = array();
				$whereSummary = array();
				$formSummary["ViewAmount"] = $dataTotal->TotalView;
				$whereSummary["ID = ?"] = $galleryArticleID;
				$affectedRows = $db->update("gallery_article_info", $formSummary, $whereSummary);
			}
			$data->result = true;
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getAllKeywordByArticle($galleryArticleID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'gallery_article_tags'), '')
			->columns(array(
					"Title" => "tbl.Title"
			))
			->where("tbl.GalleryArticleID = ? ", $galleryArticleID)
			->where("tbl.IsDeleted = 'N' ")
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getFileCoverDetail($ArticleID, $FileInfoID, $lang = 'th') 
	{
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'gallery_article_cover'), '')
			->joinLeft(array(
				"md" => "gallery_article_cover_detail"), 
				"tbl.GalleryArticleID = md.GalleryArticleID  and tbl.DataCode = md.DataCode and md.Lang = '{$lang}' ", 
				"")
			->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
			->columns(array(
					"FileInfoID" => "tbl.FileInfoID",
					"Name" =>"md.Title",
					"Description" =>"md.IntroText",

					"FileName" => "fi.Name",
					"FileHashName" => "fi.HashName",
					"FileType" => "fi.FileType",
					"FileSize" => "fi.FileSize",
					"FileExtension" => "fi.Extension"
			))
			->where("tbl.GalleryArticleID = ?", $ArticleID)
			->where("tbl.FileInfoID = ? ", $FileInfoID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getCommentImageList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			$fullname = 'concat(ifnull(u.TitleName,""), ifnull(u.FirstName,""), " ", ifnull(u.LastName,""))';
			$select = $this->db->select()
			->from(array('tbl' => 'gallery_article_comment_image'), "")
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
			->where("tbl.GalleryImageID = ?", $advancesearch['GalleryImageID'])
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

		}
		return $data;
	}

	public function saveCommentImage($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			$request = Zend_Controller_Front::getInstance()->getRequest();
			
			$created = date("Y-m-d H:i:s");
			$form = array();
			$form["GalleryImageID"] = $params['hdfFileID'];
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
			$form["IsEnable"] = 'Y'; // empty($params['hdfImagePublicComment']) ? 'N' : $params['hdfImagePublicComment'];
			$data->result = $this->db->insert("gallery_article_comment_image", $form);

		} catch (Exception $e) {

		}
		return $data;
	}

	public function saveLikeImage($params)
	{
		$data = new stdClass();
		$data->result = false;
		try {

			$select = $this->db->select()
			->from(array("tbl" => "gallery_article_like_image"), "")
			->columns(array(
				"tbl.ID",
				"tbl.IsEnable"
			))
			->where("tbl.GalleryImageID = ?", $params['hdfFileID'])
			->where("tbl.MemberID = ?", $this->UserID)
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();

			$request = Zend_Controller_Front::getInstance()->getRequest();
			$created = date("Y-m-d H:i:s");

			$form = array();
			$where = array();
			
			$form["GalleryImageID"] = $params['hdfFileID'];
			$form["MemberID"] = $this->UserID;
			$form["Lang"] = $params['DefaultLanguageCode'];
			$form["AccessFrom"] = "Web";
			$form["RemoteIP"] = $request->getServer('REMOTE_ADDR');
			if (!empty($form["RemoteIP"])) {
				$countryCode = geoip_country_code_by_name($form["RemoteIP"]);
				$form["GeoIP"] = $countryCode;
			}
			$form["ActionDate"] = $created;
			$form["Modified"] = $created;
			$form["ModifiedBy"] = $this->UserID;
			$form["IsEnable"] = $data->IsEnable == 'Y' ? 'N' : 'Y';
			$where["GalleryImageID = ?"] = $params['hdfFileID'];
			$where["MemberID = ?"] = $this->UserID;
			$affectedRows = $this->db->update("gallery_article_like_image", $form, $where);
			if ($affectedRows == 0) {
				$form["Created"] = $created;
				$form["CreatedBy"] = $this->UserID;
				$form["IsEnable"] = 'Y';
				$form["IsDeleted"] = 'N';
				$affectedRows = $this->db->insert("gallery_article_like_image", $form);
			}

			$data->result = $affectedRows;
		} catch (Exception $e) {

		}
		return $data;
	}

	public function getlike($GalleryImageID, $UserID)
	{
		$data = new stdClass();

		try {

			$select = $this->db->select()
			->from(array("tbl" => "gallery_article_like_image"), "")
			->columns(array(
				"tbl.ID",
				"tbl.MemberID",
				"tbl.GalleryImageID",
				"tbl.IsEnable"
			))
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.GalleryImageID = ? ", $GalleryImageID)
			->where("tbl.MemberID = ?", $UserID)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();

		} catch (Exception $e) {

		}

		return $data;
	}

	public function getlistlike($GalleryImageID)
	{
		$data = array();

		try {

			$select = $this->db->select()
			->from(array("tbl" => "gallery_article_like_image"), "")
			->columns(array(
				"tbl.ID",
				"tbl.MemberID",
				"tbl.GalleryImageID",
				"tbl.IsEnable"
			))
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.GalleryImageID = ? ", $GalleryImageID)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}

		return $data;
	}

}

?>
