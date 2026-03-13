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
			->joinLeft(array('c' => 'calendar_category_info') , "tbl.CalendarCategoryID = c.ID" , '')
			->joinLeft(array('cd' => 'calendar_category_detail') , "c.ID = cd.ID and cd.Lang = '{$lang}' " , '')
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->columns(array(
					"ID" => "tbl.ID",
					"ViewAmount" => "tbl.ViewAmount",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"Title" => "d.Title",
					"Ordering" => "tbl.Ordering",
					"CategoryTitle" => "cd.Title",
					"AuthorName" => "concat(u.FirstName, ' ', u.LastName)"
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
			if (!empty($advancesearch['isCat'])) {
				//$select = $select->where("tbl.calendar_category_id = ? ", $advancesearch['isCat']);
				$select = $select->distinct()
				->joinInner(array('ca' => "calendar_category_article"), "tbl.ID = ca.CalendarArticleID", '')
				->where("ca.CalendarCategoryID = ? ", $advancesearch['isCat'])
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
			->from(array('tbl' => 'calendar_article_info'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
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
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where("tbl.ID = ? " , $id)
			->where("tbl.IsDeleted ='N'")
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
			$select = $this->db->select()
			->from(array('tbl' => 'calendar_article_info'), '')
			->joinLeft(array('d' => 'calendar_article_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
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
					"Place" => "d.Place",
					"Contact" => "d.Contact",
					"EventOrganizer" => "d.EventOrganizer"
			))
			->where("tbl.ID = ? " ,$id)
			->where("tbl.IsDeleted ='N'")
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
	
	function save($params) {
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
			if($defaultLanguageCode == 'th'){
				$param[$defaultLanguageCode]['StartDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['StartDate']);
				$param[$defaultLanguageCode]['StartDate'] .= empty($param[$defaultLanguageCode]['StartDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['StartDateTime']);
				$param[$defaultLanguageCode]['EndDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['EndDate']);
				$param[$defaultLanguageCode]['EndDate'] = empty($param[$defaultLanguageCode]['EndDate']) ? null : $param[$defaultLanguageCode]['EndDate'] . " " . (empty($param[$defaultLanguageCode]['EndDateTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['EndDateTime']));
				$param[$defaultLanguageCode]['PublishDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}else {
				$param[$defaultLanguageCode]['StartDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['StartDate']);
				$param[$defaultLanguageCode]['StartDate'] .= empty($param[$defaultLanguageCode]['StartDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['StartDateTime']);
				$param[$defaultLanguageCode]['EndDate'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['EndDate']);
				$param[$defaultLanguageCode]['EndDate'] = empty($param[$defaultLanguageCode]['EndDate']) ? null : $param[$defaultLanguageCode]['EndDate'] . " " . (empty($param[$defaultLanguageCode]['EndDateTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['EndDateTime']));
				$param[$defaultLanguageCode]['PublishDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}
			$form = array();
			$form['CalendarCategoryID'] = count($params['CategoryIds']) > 0 ? $params['CategoryIds'][0] : null;
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['Telephone'] = $param[$defaultLanguageCode]['Telephone'];
			$form['EventLink'] = $param[$defaultLanguageCode]['EventLink'];
			$form['StartDate'] = $param[$defaultLanguageCode]['StartDate'];
			$form['EndDate'] = $param[$defaultLanguageCode]['EndDate'];
			$form['Latitude'] = $param[$defaultLanguageCode]['Latitude'];
			$form['Longitude'] = $param[$defaultLanguageCode]['Longitude'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['ViewAmount'] = 0;
			$form['TemplateType'] = $params['TemplateType'];
			$form['CanComment'] = $param[$defaultLanguageCode]['CanCommentType'] == 'C' ? $param[$defaultLanguageCode]['CanComment'] : $param[$defaultLanguageCode]['CanCommentType'];			
			$form['MetaData'] = $param[$defaultLanguageCode]['MetaData'];
			$form['SocialNetworkFacebook'] = $param[$defaultLanguageCode]['SocialNetworkFacebook'];
			$form['SocialNetworkTwitter'] = $param[$defaultLanguageCode]['SocialNetworkTwitter'];
			$form['SocialNetworkGooglePlus'] = $param[$defaultLanguageCode]['SocialNetworkGooglePlus'];
			$form['ShowQrcode'] = $param[$defaultLanguageCode]['ShowQrcode'];
			$form['ShowPrint'] = $param[$defaultLanguageCode]['ShowPrint'];
			$form['Created'] = $modified;
			$form['CreatedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("calendar_article_info", $form);
			$id = $this->db->lastInsertId();

			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['CalendarArticleID'] = $id;
					$formDetail['CalendarCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("calendar_category_article", $formDetail);					
				}
			}
			
			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$formDetail['ID'] = $id;
					$formDetail['Lang'] = $lang;
					$formDetail['Title'] = $value['Title'];
					$formDetail['IntroText'] = $value['IntroText'];
					$formDetail['Detail'] = $value['Detail'];
					$formDetail['Place'] = $value['Place'];
					$formDetail['Contact'] = $value['Contact'];
					$formDetail['EventOrganizer'] = $value['EventOrganizer'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("calendar_article_detail", $formDetail);
				}
			}
			
			if(count($params['GalleryIds'])>0){
				foreach ($params['GalleryIds'] as $fileID){
					$formFile = array();
					$formFile['CalendarArticleID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'image';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("calendar_article_file", $formFile);
				}
			}
			
			if(count($params['DocumentIds'])>0){
				foreach ($params['DocumentIds'] as $fileID){
					$formFile = array();
					$formFile['CalendarArticleID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'document';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("calendar_article_file", $formFile);
				}
			}
			
			if(count($params['VdoIds'])>0){
				foreach ($params['VdoIds'] as $fileID){
					$formFile = array();
					$formFile['CalendarArticleID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'multimedia';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("calendar_article_file", $formFile);
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
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$id = $params['hdfID'];
			$modified = date("Y-m-d H:i:s");
			if($defaultLanguageCode == 'th'){
				$param[$defaultLanguageCode]['StartDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['StartDate']);
				$param[$defaultLanguageCode]['StartDate'] .= empty($param[$defaultLanguageCode]['StartDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['StartDateTime']);
				$param[$defaultLanguageCode]['EndDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['EndDate']);
				$param[$defaultLanguageCode]['EndDate'] = empty($param[$defaultLanguageCode]['EndDate']) ? null : $param[$defaultLanguageCode]['EndDate'] . " " . (empty($param[$defaultLanguageCode]['EndDateTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['EndDateTime']));
				$param[$defaultLanguageCode]['PublishDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			} else {
				$param[$defaultLanguageCode]['StartDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['StartDate']);
				$param[$defaultLanguageCode]['StartDate'] .= empty($param[$defaultLanguageCode]['StartDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['StartDateTime']);
				$param[$defaultLanguageCode]['EndDate'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['EndDate']);
				$param[$defaultLanguageCode]['EndDate'] = empty($param[$defaultLanguageCode]['EndDate']) ? null : $param[$defaultLanguageCode]['EndDate'] . " " . (empty($param[$defaultLanguageCode]['EndDateTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['EndDateTime']));
				$param[$defaultLanguageCode]['PublishDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}
			$form = array();
			$where = array();
			$form['CalendarCategoryID'] = count($params['CategoryIds']) > 0 ? $params['CategoryIds'][0] : null;
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['Telephone'] = $param[$defaultLanguageCode]['Telephone'];
			$form['EventLink'] = $param[$defaultLanguageCode]['EventLink'];
			$form['StartDate'] = $param[$defaultLanguageCode]['StartDate'];
			$form['EndDate'] = $param[$defaultLanguageCode]['EndDate'];
			$form['Latitude'] = $param[$defaultLanguageCode]['Latitude'];
			$form['Longitude'] = $param[$defaultLanguageCode]['Longitude'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['TemplateType'] = $params['TemplateType'];
			$form['CanComment'] = $param[$defaultLanguageCode]['CanCommentType'] == 'C' ? $param[$defaultLanguageCode]['CanComment'] : $param[$defaultLanguageCode]['CanCommentType'];
			$form['MetaData'] = $param[$defaultLanguageCode]['MetaData'];
			$form['SocialNetworkFacebook'] = $param[$defaultLanguageCode]['SocialNetworkFacebook'];
			$form['SocialNetworkTwitter'] = $param[$defaultLanguageCode]['SocialNetworkTwitter'];
			$form['SocialNetworkGooglePlus'] = $param[$defaultLanguageCode]['SocialNetworkGooglePlus'];
			$form['ShowQrcode'] = $param[$defaultLanguageCode]['ShowQrcode'];
			$form['ShowPrint'] = $param[$defaultLanguageCode]['ShowPrint'];
			$form['Modified'] = $modified;
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$where['ID = ? '] = $id;
			$result = $this->db->update("calendar_article_info", $form, $where);
			
			// Delete All Category
			$whereIdDelete = array();
			$whereIdDelete['CalendarArticleID = ? '] = $id;
			$this->db->delete("calendar_category_article",$whereIdDelete);
			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['CalendarArticleID'] = $id;
					$formDetail['CalendarCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("calendar_category_article", $formDetail);
				}
			}
			
			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$whereDetail = array();
					$formDetail['Title'] = $value['Title'];
					$formDetail['IntroText'] = $value['IntroText'];
					$formDetail['Detail'] = $value['Detail'];
					$formDetail['Place'] = $value['Place'];
					$formDetail['Contact'] = $value['Contact'];
					$formDetail['EventOrganizer'] = $value['EventOrganizer'];
					$formDetail['Modified'] = $modified;
					$whereDetail['ID = ? '] = $id;
					$whereDetail['Lang = ? '] = $lang;
					$affRow = $this->db->update("calendar_article_detail", $formDetail, $whereDetail);
					if ($affRow == 0) {
						$formDetail['ID'] = $id;
						$formDetail['Lang'] = $lang;
						$formDetail['Created'] = $modified;
						$formDetail['IsEnable'] = 'Y';
						$formDetail['IsDeleted'] = 'N';
						$this->db->insert("calendar_article_detail", $formDetail);
					}
				}
			}
						
			// Delete All File
			$whereIdDelete = array();
			$whereIdDelete['CalendarArticleID = ? '] = $id;
			$this->db->delete("calendar_article_file",$whereIdDelete);
			
			if(count($params['GalleryIds'])>0){
				foreach ($params['GalleryIds'] as $fileID){
					$formFile = array();
					$formFile['CalendarArticleID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'image';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("calendar_article_file", $formFile);
				}
			}
			
			if(count($params['DocumentIds'])>0){
				foreach ($params['DocumentIds'] as $fileID){
					$formFile = array();
					$formFile['CalendarArticleID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'document';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("calendar_article_file", $formFile);
				}
			}
			
			if(count($params['VdoIds'])>0){
				foreach ($params['VdoIds'] as $fileID){
					$formFile = array();
					$formFile['CalendarArticleID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'multimedia';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("calendar_article_file", $formFile);
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
			
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $params['id'];
			$result = $this->db->update("calendar_article_info", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	public function getOption($lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'calendar_article_info'), array( 'ID'))
		->joinInner(array('d' => 'calendar_article_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'=>'Title'))
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
	
	function getCategories($calendarArticleID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'calendar_category_article'), '')
			->columns(array(
					"CalendarCategoryID" => "tbl.CalendarCategoryID"
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

	function importExcel($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$id = '';
			$modified = date("Y-m-d H:i:s");

			if(count($params)>0){
				foreach($params as $k=>$d){
					$form = array();
					$form['Telephone'] = $d['E'];
					$form['EventLink'] = $d['F'];
					$form['StartDate'] = $d['H'];
					$form['EndDate'] = $d['I'];
					$form['Latitude'] = $d['J'];
					$form['Longitude'] = $d['K'];
					$form['PublishDate'] = $d['L'];
					$form['PublishDown'] = $d['M'];
					$form['ViewAmount'] = 0;
					$form['TemplateType'] = 1;
					$form['CanComment'] = 'D';			
					$form['MetaData'] = null;
					$form['SocialNetworkFacebook'] = 'Y';
					$form['SocialNetworkTwitter'] = 'Y';
					$form['SocialNetworkGooglePlus'] = 'Y';
					$form['ShowQrcode'] = 'Y';
					$form['ShowPrint'] = 'Y';
					$form['Created'] = $modified;
					$form['CreatedBy']= $this->UserID;
					$form['IsEnable']= 'Y';
					$form['IsDeleted'] = 'N';
					$result = $this->db->insert("calendar_article_info", $form);
					$id = $this->db->lastInsertId();

					$formDetail = array();
					$formDetail['ID'] = $id;
					$formDetail['Lang'] = 'th';
					$formDetail['Title'] = $d['A'];
					$formDetail['IntroText'] = $d['B'];
					$formDetail['Detail'] = $d['N'];
					$formDetail['Place'] = $d['C'];
					$formDetail['Contact'] = $d['D'];
					$formDetail['EventOrganizer'] = $d['G'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("calendar_article_detail", $formDetail);
				}

				$data->result = true;
			}else{
				$data->message = 'ไม่พบรายการข้อมูล';
			}

			$data->result = true;

		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function importCSV($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$id = '';
			$modified = date("Y-m-d H:i:s");

			if(count($params)>0){
				foreach($params as $k=>$d){
					$form = array();
					$form['Telephone'] = $d['4'];
					$form['EventLink'] = $d['5'];
					$form['StartDate'] = $d['7'];
					$form['EndDate'] = $d['8'];
					$form['Latitude'] = $d['9'];
					$form['Longitude'] = $d['10'];
					$form['PublishDate'] = $d['11'];
					$form['PublishDown'] = $d['12'];
					$form['ViewAmount'] = 0;
					$form['TemplateType'] = 1;
					$form['CanComment'] = 'D';			
					$form['MetaData'] = null;
					$form['SocialNetworkFacebook'] = 'Y';
					$form['SocialNetworkTwitter'] = 'Y';
					$form['SocialNetworkGooglePlus'] = 'Y';
					$form['ShowQrcode'] = 'Y';
					$form['ShowPrint'] = 'Y';
					$form['Created'] = $modified;
					$form['CreatedBy']= $this->UserID;
					$form['IsEnable']= 'Y';
					$form['IsDeleted'] = 'N';
					$result = $this->db->insert("calendar_article_info", $form);
					$id = $this->db->lastInsertId();

					$formDetail = array();
					$formDetail['ID'] = $id;
					$formDetail['Lang'] = 'th';
					$formDetail['Title'] = $d['0'];
					$formDetail['IntroText'] = $d['1'];
					$formDetail['Detail'] = $d['13'];
					$formDetail['Place'] = $d['2'];
					$formDetail['Contact'] = $d['3'];
					$formDetail['EventOrganizer'] = $d['6'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("calendar_article_detail", $formDetail);
				}

				$data->result = true;
			}else{
				$data->message = 'ไม่พบรายการข้อมูล';
			}

			$data->result = true;

		} catch (Exception $e) {
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
					$affRow += $this->db->update("calendar_article_info", $form, $where);
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
					->from(array('tbl'=>'calendar_article_info'), array(
						'CalendarCategoryID',
						'IntroImageID',
						'MainTitle',
						'TemplateType',
						'Telephone',
						'EventLink',
						'StartDate',
						'EndDate',
						'Latitude',
						'Longitude',
						'PublishDate',
						'PublishDown',
						'ViewAmount',
						'Ordering',
						'CanComment',
						'Settings',
						'MetaData',
						'SocialNetworkFacebook',
						'SocialNetworkTwitter',
						'SocialNetworkGoogleplus',
						'ShowQrcode',
						'ShowPrint'
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['CalendarCategoryID'] = empty($dataInfo->CalendarCategoryID) ? null : $dataInfo->CalendarCategoryID;
					$form['IntroImageID'] = empty($dataInfo->IntroImageID) ? null : $dataInfo->IntroImageID;
					$form['MainTitle'] = empty($dataInfo->MainTitle) ? null : $dataInfo->MainTitle;
					$form['TemplateType'] = empty($dataInfo->TemplateType) ? 1 : $dataInfo->TemplateType;
					$form['Telephone'] = empty($dataInfo->Telephone) ? null : $dataInfo->Telephone;
					$form['EventLink'] = empty($dataInfo->EventLink) ? null : $dataInfo->EventLink;
					$form['StartDate'] = empty($dataInfo->StartDate) ? null : $dataInfo->StartDate;
					$form['EndDate'] = empty($dataInfo->EndDate) ? null : $dataInfo->EndDate;
					$form['Latitude'] = empty($dataInfo->Latitude) ? null : $dataInfo->Latitude;
					$form['Longitude'] = empty($dataInfo->Longitude) ? null : $dataInfo->Longitude;
					$form['PublishDate'] = empty($dataInfo->PublishDate) ? null : $dataInfo->PublishDate;
					$form['PublishDown'] = empty($dataInfo->PublishDown) ? null : $dataInfo->PublishDown;
					$form['ViewAmount'] = 0;
					$form['Ordering'] = empty($dataInfo->Ordering) ? null : $dataInfo->Ordering;
					$form['CanComment'] = empty($dataInfo->CanComment) ? 'N' : $dataInfo->CanComment;
					$form['Settings'] = empty($dataInfo->Settings) ? null : $dataInfo->Settings;
					$form['MetaData'] = empty($dataInfo->MetaData) ? null : $dataInfo->MetaData;
					$form['SocialNetworkFacebook'] = empty($dataInfo->SocialNetworkFacebook) ? 'N' : $dataInfo->SocialNetworkFacebook;
					$form['SocialNetworkTwitter'] = empty($dataInfo->SocialNetworkTwitter) ? 'N' : $dataInfo->SocialNetworkTwitter;
					$form['SocialNetworkGoogleplus'] = empty($dataInfo->SocialNetworkGoogleplus) ? 'N' : $dataInfo->SocialNetworkGoogleplus;
					$form['ShowQrcode'] = empty($dataInfo->ShowQrcode) ? 'N' : $dataInfo->ShowQrcode;
					$form['ShowPrint'] = empty($dataInfo->ShowPrint) ? 'N' : $dataInfo->ShowPrint;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("calendar_article_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'calendar_article_detail'), array('Lang','Title','IntroText','Detail','Place','Contact','EventOrganizer'))
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
								$form['Place'] = empty($d['Place']) ? null : $d['Place'];
								$form['Contact'] = empty($d['Contact']) ? null : $d['Contact'];
								$form['EventOrganizer'] = empty($d['EventOrganizer']) ? null : $d['EventOrganizer'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("calendar_article_detail", $form);
							}
						}

						// Category Article
						$selectCatArt = $this->db->select ()
						->from(array('tbl'=>'calendar_category_article'), array('CalendarCategoryID'))
						->where("tbl.CalendarArticleID = ? ", $id)
						;
						$stmtCatArt = $this->db->query($selectCatArt);
						$dataCatArt = $stmtCatArt->fetchAll();

						if(count($dataCatArt)>0){
							foreach($dataCatArt as $d){
								$form = array();
								$form['CalendarArticleID'] = $tmpID;
								$form['CalendarCategoryID'] = empty($d['CalendarCategoryID']) ? null : $d['CalendarCategoryID'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("calendar_category_article", $form);
							}
						}

						// Tag
						$selectTag = $this->db->select ()
						->from(array('tbl'=>'calendar_article_tags'), array('Title'))
						->where("tbl.CalendarArticleID = ? ", $id)
						;
						$stmtTag = $this->db->query($selectTag);
						$dataTag = $stmtTag->fetchAll();

						if(count($dataTag)>0){
							foreach($dataTag as $d){
								$form = array();
								$form['CalendarArticleID'] = $tmpID;
								$form['Title'] = empty($d['Title']) ? null : $d['Title'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("calendar_article_tags", $form);
							}
						}

						// File
						$selectFile = $this->db->select ()
						->from(array('tbl'=>'calendar_article_file'), array('FileInfoID','FileType'))
						->where("tbl.CalendarArticleID = ? ", $id)
						;
						$stmtFile = $this->db->query($selectFile);
						$dataFile = $stmtFile->fetchAll();

						if(count($dataFile)>0){
							foreach($dataFile as $d){
								$form = array();
								$form['CalendarArticleID'] = $tmpID;
								$form['FileInfoID'] = empty($d['FileInfoID']) ? null : $d['FileInfoID'];
								$form['FileType'] = empty($d['FileType']) ? null : $d['FileType'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("calendar_article_file", $form);
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
					$affRow += $this->db->update("calendar_article_info", $form, $where);
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
