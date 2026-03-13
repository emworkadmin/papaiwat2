<?php

class Event_Model_Event {
	
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
			->from(array('tbl' => 'event_event_info'), '')
			->joinLeft(array('d' => 'event_event_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('c' => 'event_category_info') , "tbl.EventCategoryID = c.ID" , '')
			->joinLeft(array('cd' => 'event_category_detail') , "c.ID = cd.ID and cd.Lang = '{$lang}' " , '')
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->columns(array(
					"ID" => "tbl.ID",
					"ViewAmount" => "tbl.ViewAmount",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"Title" => "d.Title",
					"CategoryTitle" => "cd.Title",
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
				$select = $select->distinct()
				->joinInner(array('ca' => "event_category_event"), "tbl.ID = ca.EventEventID", '')
				->where("ca.EventCategoryID = ? ", $advancesearch['isCat'])
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
					$data[$i]["PublishDate"] = empty($data[$i]["PublishDate"]) ? null : new DateTime($data[$i]["PublishDate"]);
					$data[$i]["PublishDown"] = empty($data[$i]["PublishDown"]) ? null : new DateTime($data[$i]["PublishDown"]);
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
			$form['EventCategoryID'] = count($params['CategoryIds']) > 0 ? $params['CategoryIds'][0] : null;
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['Ordering'] = $param[$defaultLanguageCode]['Ordering'];
			$form['StartDate'] = $param[$defaultLanguageCode]['StartDate'];
			$form['EndDate'] = $param[$defaultLanguageCode]['EndDate'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['Telephone'] = $param[$defaultLanguageCode]['Telephone'];
			$form['EmailRecipient'] = $param[$defaultLanguageCode]['EmailRecipient'];
			$form['ViewAmount'] = 0;
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
			$result = $this->db->insert("event_event_info", $form);
			$id = $this->db->lastInsertId();

			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['EventEventID'] = $id;
					$formDetail['EventCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("event_category_event", $formDetail);					
				}
			}

			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$formDetail['ID'] = $id;
					$formDetail['Lang'] = $lang;
					$formDetail['Title'] = $value['Title'];
					$formDetail['Place'] = $value['Place'];
					$formDetail['Department'] = $value['Department'];
					$formDetail['Detail'] = $value['Detail'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("event_event_detail", $formDetail);
				}
			}
			
			if(count($params['GalleryIds'])>0){
				foreach ($params['GalleryIds'] as $fileID){
					$formFile = array();
					$formFile['EventEventID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'image';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("event_event_file", $formFile);
				}
			}
			
			if(count($params['DocumentIds'])>0){
				foreach ($params['DocumentIds'] as $fileID){
					$formFile = array();
					$formFile['EventEventID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'document';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("event_event_file", $formFile);
				}
			}
			
			if(count($params['VdoIds'])>0){
				foreach ($params['VdoIds'] as $fileID){
					$formFile = array();
					$formFile['EventEventID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'multimedia';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("event_event_file", $formFile);
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
			$form['EventCategoryID'] = count($params['CategoryIds']) > 0 ? $params['CategoryIds'][0] : null;
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['Ordering'] = $param[$defaultLanguageCode]['Ordering'];
			$form['StartDate'] = $param[$defaultLanguageCode]['StartDate'];
			$form['EndDate'] = $param[$defaultLanguageCode]['EndDate'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['Telephone'] = $param[$defaultLanguageCode]['Telephone'];
			$form['EmailRecipient'] = $param[$defaultLanguageCode]['EmailRecipient'];
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
			$result = $this->db->update("event_event_info", $form, $where);
			
			// Delete All Category
			$whereIdDelete = array();
			$whereIdDelete['EventEventID = ? '] = $id;
			$this->db->delete("event_category_event",$whereIdDelete);
			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['EventEventID'] = $id;
					$formDetail['EventCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("event_category_event", $formDetail);					
				}
			}			
			
			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$whereDetail = array();
					$formDetail['Title'] = $value['Title'];
					$formDetail['Place'] = $value['Place'];
					$formDetail['Department'] = $value['Department'];
					$formDetail['Detail'] = $value['Detail'];
					$formDetail['Modified'] = $modified;
					$whereDetail['ID = ? '] = $id;
					$whereDetail['Lang = ? '] = $lang;
					$affRow = $this->db->update("event_event_detail", $formDetail, $whereDetail);
					if ($affRow == 0) {
						$formDetail['ID'] = $id;
						$formDetail['Lang'] = $lang;
						$formDetail['Created'] = $modified;
						$formDetail['IsEnable'] = 'Y';
						$formDetail['IsDeleted'] = 'N';
						$this->db->insert("event_event_detail", $formDetail);
					}
				}
			}

			// Delete All File
			$whereIdDelete = array();
			$whereIdDelete['EventEventID = ? '] = $id;
			$this->db->delete("event_event_file",$whereIdDelete);
			
			if(count($params['GalleryIds'])>0){
				foreach ($params['GalleryIds'] as $fileID){
					$formFile = array();
					$formFile['EventEventID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'image';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("event_event_file", $formFile);
				}
			}
			
			if(count($params['DocumentIds'])>0){
				foreach ($params['DocumentIds'] as $fileID){
					$formFile = array();
					$formFile['EventEventID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'document';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("event_event_file", $formFile);
				}
			}
			
			if(count($params['VdoIds'])>0){
				foreach ($params['VdoIds'] as $fileID){
					$formFile = array();
					$formFile['EventEventID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'multimedia';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("event_event_file", $formFile);
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
			$result = $this->db->update("event_event_info", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}	
		
	function getInfo($id) {
		$data = new stdClass();
		try {
			$select= $this->db->select()->distinct()
			->from(array('tbl' => 'event_event_info'), '')
			->columns(array(
				"ID",
				"IsEnable",
				"IsDeleted"
			))
			->where("tbl.ID = ? " , $id)
			->where("tbl.IsDeleted ='N'")
			;

			$stmt = $this->db->query($select);
			$data  = $stmt->fetchObject();

		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getDataByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'event_event_info'), '')
			->joinLeft(array('d' => 'event_event_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"IntroImageID" => "tbl.IntroImageID",
					"Ordering" => "tbl.Ordering",
					"StartDate" => "tbl.StartDate",
					"EndDate" => "tbl.EndDate",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"ViewAmount" => "tbl.ViewAmount",
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
					"Place" => "d.Place",
					"Department" => "d.Department",
					"Detail" => "d.Detail",
					"Telephone" => "tbl.Telephone",
					"EmailRecipient" => "tbl.EmailRecipient",

					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension"					
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

	function getOption($lang)
	{
		$select = $this->db->select()
		->from ( array ('tbs' => 'event_event_info' ), array ('ID') )
		->joinInner ( array ('d' => 'event_event_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->order ( "convert(d.Title USING TIS620)  ASC" );
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array();
		if(count($data)>0) {
			foreach ( $data as $value ) {
				$result[$value["ID"]] = $value["Title"];
			}
		}
		return $result;
	}
	
	function getCategories($eventID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'event_category_event'), '')
			->columns(array(
					"EventCategoryID" => "tbl.EventCategoryID"
			))
			->where("tbl.EventEventID = ? ", $eventID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getFiles($eventEventID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'event_event_file'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
			->columns(array(
					"FileInfoID" => "tbl.FileInfoID",
					"EventFileType" => "tbl.FileType",
					
					"FileName" => "fi.Name",
					"FileHashName" => "fi.HashName",
					"FileType" => "fi.FileType",
					"FileSize" => "fi.FileSize",
					"FileExtension" => "fi.Extension"
			))
			->where("tbl.EventEventID = ? ", $eventEventID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
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
					$affRow += $this->db->update("event_event_info", $form, $where);
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
					->from(array('tbl'=>'event_event_info'), array(
						'EventCategoryID',
						'IntroImageID',
						'Ordering',
						'StartDate',
						'EndDate',
						'Telephone',
						'PublishDate',
						'PublishDown',
						'ViewAmount',
						'EmailRecipient',
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
					$form['EventCategoryID'] = empty($dataInfo->EventCategoryID) ? null : $dataInfo->EventCategoryID;
					$form['IntroImageID'] = empty($dataInfo->IntroImageID) ? null : $dataInfo->IntroImageID;
					$form['Ordering'] = empty($dataInfo->Ordering) ? null : $dataInfo->Ordering;
					$form['StartDate'] = empty($dataInfo->StartDate) ? null : $dataInfo->StartDate;
					$form['EndDate'] = empty($dataInfo->EndDate) ? null : $dataInfo->EndDate;
					$form['Telephone'] = empty($dataInfo->Telephone) ? null : $dataInfo->Telephone;
					$form['PublishDate'] = empty($dataInfo->PublishDate) ? null : $dataInfo->PublishDate;
					$form['PublishDown'] = empty($dataInfo->PublishDown) ? null : $dataInfo->PublishDown;
					$form['ViewAmount'] = 0;
					$form['EmailRecipient'] = empty($dataInfo->EmailRecipient) ? null : $dataInfo->EmailRecipient;
					$form['MetaData'] = empty($dataInfo->MetaData) ? null : $dataInfo->MetaData;
					$form['SocialNetworkFacebook'] = empty($dataInfo->SocialNetworkFacebook) ? 'N' : $dataInfo->SocialNetworkFacebook;
					$form['SocialNetworkTwitter'] = empty($dataInfo->SocialNetworkTwitter) ? 'N' : $dataInfo->SocialNetworkTwitter;
					$form['SocialNetworkGoogleplus'] = empty($dataInfo->SocialNetworkGoogleplus) ? 'N' : $dataInfo->SocialNetworkGoogleplus;
					$form['ShowQrcode'] = empty($dataInfo->ShowQrcode) ? 'N' : $dataInfo->ShowQrcode;
					$form['ShowPrint'] = empty($dataInfo->ShowPrint) ? 'N' : $dataInfo->ShowPrint;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("event_event_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'event_event_detail'), array('Lang','Title','Place','Department','Detail'))
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
								$form['Place'] = empty($d['Place']) ? null : $d['Place'];
								$form['Department'] = empty($d['Department']) ? null : $d['Department'];
								$form['Detail'] = empty($d['Detail']) ? null : $d['Detail'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("event_event_detail", $form);
							}
						}

						// Category Event
						$selectCatEvent = $this->db->select ()
						->from(array('tbl'=>'event_category_event'), array('EventCategoryID'))
						->where("tbl.EventEventID = ? ", $id)
						;
						$stmtCatEvent = $this->db->query($selectCatEvent);
						$dataCatEvent = $stmtCatEvent->fetchAll();

						if(count($dataCatEvent)>0){
							foreach($dataCatEvent as $d){
								$form = array();
								$form['EventEventID'] = $tmpID;
								$form['EventCategoryID'] = empty($d['EventCategoryID']) ? null : $d['EventCategoryID'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("event_category_event", $form);
							}
						}

						// File
						$selectFile = $this->db->select ()
						->from(array('tbl'=>'event_event_file'), array('FileInfoID','FileType'))
						->where("tbl.EventEventID = ? ", $id)
						;
						$stmtFile = $this->db->query($selectFile);
						$dataFile = $stmtFile->fetchAll();

						if(count($dataFile)>0){
							foreach($dataFile as $d){
								$form = array();
								$form['EventEventID'] = $tmpID;
								$form['FileInfoID'] = empty($d['FileInfoID']) ? null : $d['FileInfoID'];
								$form['FileType'] = empty($d['FileType']) ? null : $d['FileType'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("event_event_file", $form);
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
					$affRow += $this->db->update("event_event_info", $form, $where);
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