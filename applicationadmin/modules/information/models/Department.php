<?php

class Information_Model_Department {
	
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
			->from(array('tbl' => 'information_department_info'), '')
			->joinLeft(array('d' => 'information_department_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('c' => 'information_category_info') , "tbl.InfomationCategoryID = c.ID" , '')
			->joinLeft(array('cd' => 'information_category_detail') , "c.ID = cd.ID and cd.Lang = '{$lang}' " , '')
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->columns(array(
					"ID" => "tbl.ID",
					"ViewAmount" => "tbl.ViewAmount",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"Name" => "d.Name",
					"Ordering" => "tbl.Ordering",
					"CategoryTitle" => "cd.Title",
					"AuthorName" => "concat(u.FirstName, ' ', u.LastName)"
			))
			->where("tbl.IsDeleted ='N'")
			;

			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("d.Name like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['isEnable'])) {
				$select = $select->where("tbl.IsEnable = ? ", $advancesearch['isEnable']);
			}
			
			if (!empty($advancesearch['isCat'])) {
				//$select = $select->where("tbl.information_category_id = ? ", $advancesearch['isCat']);
				$select = $select->distinct()
				// ->joinInner(array('ca' => "information_department_info"), "tbl.ID = ca.InfomationCategoryID", '')
				->where("tbl.InfomationCategoryID = ? ", $advancesearch['isCat'])
				// ->where("ca.IsDeleted = 'N' ")
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
	public function getOption($lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'information_department_info'), array( 'ID'))
		->joinInner(array('d' => 'information_department_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->order("convert(d.Name USING tis620) ASC")
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		$result = array();

		foreach ($data as $value) {
			$result[$value["ID"]] = $value["Name"];
		}
		return $result;
	}
	function getInfo($id ,$lang = 'th') {
		$data = new stdClass();
		try {
			$select= $this->db->select()->distinct()
			->from(array('tbl' => 'information_department_info'), '')
			->joinLeft(array("d" => "information_department_detail"), "tbl.ID = d.ID and d.lang='{$lang}'", "")
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Name"=>"d.Name",
					"InfomationCategoryID" => "tbl.InfomationCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					"Jurisdiction" => "tbl.Jurisdiction",
					"ShowCategory" => "tbl.ShowCategory",
					"ShowDocument" => "tbl.ShowDocument",
					"Telephone" => "tbl.Telephone",
					"Fax" => "tbl.Fax",
					"Email" => "tbl.Email",
					"Website" => "tbl.Website",
					"Latitude" => "tbl.Latitude",
					"Longitude" => "tbl.Longitude",
					"BackgroundImageID" => "tbl.BackgroundImageID",
					"BackgroundColor" => "tbl.BackgroundColor",
					"SlidingBackground" => "tbl.SlidingBackground",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"MetaData" => "tbl.MetaData",
					"SocialNetworkFacebook" => "tbl.SocialNetworkFacebook",
					"SocialNetworkTwitter" => "tbl.SocialNetworkTwitter",
					"SocialNetworkGoogleplus" => "tbl.SocialNetworkGoogleplus",
					"ShowQRcode" => "tbl.ShowQRcode",
					"ShowPrint" => "tbl.ShowPrint",
					"ViewAmount" => "tbl.ViewAmount",

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
			->from(array('tbl' => 'information_department_info'), '')
			->joinLeft(array('d' => 'information_department_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->joinLeft(array('ca' => 'information_category_detail'),"tbl.InfomationCategoryID = ca.ID and ca.Lang = '{$lang}' ", '')
			->columns(array(
				"ID" => "tbl.ID",
				"InfomationCategoryID" => "tbl.InfomationCategoryID",
				"IntroImageID" => "tbl.IntroImageID",
				"Jurisdiction" => "tbl.Jurisdiction",
				"ShowCategory" => "tbl.ShowCategory",
				"ShowDocument" => "tbl.ShowDocument",
				"Telephone" => "tbl.Telephone",
				"Address" => "tbl.Address",
				"Fax" => "tbl.Fax",
				"Email" => "tbl.Email",
				"Website" => "tbl.Website",
				"Latitude" => "tbl.Latitude",
				"Longitude" => "tbl.Longitude",
				"BackgroundImageID" => "tbl.BackgroundImageID",
				"BackgroundColor" => "tbl.BackgroundColor",
				"SlidingBackground" => "tbl.SlidingBackground",
				"PublishDate" => "tbl.PublishDate",
				"PublishDown" => "tbl.PublishDown",
				"MetaData" => "tbl.MetaData",
				"SocialNetworkFacebook" => "tbl.SocialNetworkFacebook",
				"SocialNetworkTwitter" => "tbl.SocialNetworkTwitter",
				"SocialNetworkGoogleplus" => "tbl.SocialNetworkGoogleplus",
				"ShowQRcode" => "tbl.ShowQRcode",
				"ShowPrint" => "tbl.ShowPrint",
				"ViewAmount" => "tbl.ViewAmount",
				"Created" => "tbl.Created",
				"CreatedBy" => "tbl.CreatedBy",
				"Modified" => "tbl.Modified",
				"ModifiedBy" => "tbl.ModifiedBy",
				"IsEnable" => "tbl.IsEnable",
				"IsDeleted" => "tbl.IsDeleted",

				"Lang" => "d.Lang",
				"Name" => "d.Name",
				"Detail" => "d.Detail",
				"CategoryName"=>"ca.Title"
			
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
	function getCategories($departmentArticleID) {

		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'information_category_info'), '')
			->columns(array(
					"DepartmentCategoryID" => "tbl.ID"
			))
			->where("tbl.ID = ? ", $departmentArticleID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
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
				$param[$defaultLanguageCode]['PublishDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}else {
				$param[$defaultLanguageCode]['PublishDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}

			$form = array();
			$form['InfomationCategoryID'] = count($params['CategoryIds']) > 0 ? $params['CategoryIds'] : null;
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['Jurisdiction'] = $param[$defaultLanguageCode]['Jurisdiction'];
			$form['Telephone'] = $param[$defaultLanguageCode]['Telephone'];
			$form['Address'] = $param[$defaultLanguageCode]['Address'];
			$form['Fax'] = $param[$defaultLanguageCode]['Fax'];
			$form['Email'] = $param[$defaultLanguageCode]['Email'];
			$form['Website'] = $param[$defaultLanguageCode]['Website'];
			$form['Latitude'] = $param[$defaultLanguageCode]['Latitude'];
			$form['Longitude'] = $param[$defaultLanguageCode]['Longitude'];
			$form['BackgroundImageID'] = $params['BackgroundImageID'];
			$form['BackgroundColor'] = $param[$defaultLanguageCode]['BackgroundColor'];
			$form['SlidingBackground'] = $param[$defaultLanguageCode]['SlidingBackground'];

			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
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

			$result = $this->db->insert("information_department_info", $form);
			$id = $this->db->lastInsertId();

			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$formDetail['ID'] = $id;
					$formDetail['Lang'] = $lang;
					$formDetail['Name'] = empty ($value['Name']) ? null:$value['Name'];
					$formDetail['Detail'] = empty ($value['Detail']) ? null:$value['Detail'];
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("information_department_detail", $formDetail);
				}
			}					
			if(count($params['DocumentIds'])>0){
				foreach ($params['DocumentIds'] as $fileID){
					$formFile = array();
					$formFile['InformationDepartmentID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("information_department_file", $formFile);
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
			$id=$params['hdfID'];

			$modified = date("Y-m-d H:i:s");
			if($defaultLanguageCode == 'th'){
				$param[$defaultLanguageCode]['PublishDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}else {
				$param[$defaultLanguageCode]['PublishDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}

			$form = array();
			$where=array();
			$form['InfomationCategoryID'] = count($params['CategoryIds']) > 0 ? $params['CategoryIds'] : null;
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['Jurisdiction'] = $param[$defaultLanguageCode]['Jurisdiction'];
			$form['Telephone'] = $param[$defaultLanguageCode]['Telephone'];
			$form['Fax'] = $param[$defaultLanguageCode]['Fax'];
			$form['Address'] = $param[$defaultLanguageCode]['Address'];
			$form['Email'] = $param[$defaultLanguageCode]['Email'];
			$form['Website'] = $param[$defaultLanguageCode]['Website'];
			$form['Latitude'] = $param[$defaultLanguageCode]['Latitude'];
			$form['Longitude'] = $param[$defaultLanguageCode]['Longitude'];
			$form['BackgroundImageID'] = $params['BackgroundImageID'];
			$form['BackgroundColor'] = $param[$defaultLanguageCode]['BackgroundColor'];
			$form['SlidingBackground'] = $param[$defaultLanguageCode]['SlidingBackground'];

			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['ViewAmount'] = 0;
			$form['MetaData'] = $param[$defaultLanguageCode]['MetaData'];
			$form['SocialNetworkFacebook'] = $param[$defaultLanguageCode]['SocialNetworkFacebook'];
			$form['SocialNetworkTwitter'] = $param[$defaultLanguageCode]['SocialNetworkTwitter'];
			$form['SocialNetworkGooglePlus'] = $param[$defaultLanguageCode]['SocialNetworkGooglePlus'];
			$form['ShowQrcode'] = $param[$defaultLanguageCode]['ShowQrcode'];
			$form['ShowPrint'] = $param[$defaultLanguageCode]['ShowPrint'];
			$form['Modified'] = $modified;
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$form['IsDeleted'] = 'N';
			$where['ID=?']=$id;
			//  echo "<pre>";print_r($where);echo "</pre>";exit;
			$result = $this->db->update("information_department_info", $form,$where);
			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$whereDetail=array();
					$formDetail['Name'] = empty ($value['Name']) ? null:$value['Name'];
					$formDetail['Detail'] = empty ($value['Detail']) ? null:$value['Detail'];
					$formDetail['Modified'] = $modified;
					$formDetail['ModifiedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$whereDetail['ID = ? '] = $id;
					$whereDetail['Lang = ? '] = $lang;
					$result = $this->db->update("information_department_detail", $formDetail,$whereDetail);
				}
			}					
			// Delete All File
			$whereIdDelete = array();
			$whereIdDelete['InformationDepartmentID = ? '] = $id;
			$this->db->delete("information_department_file",$whereIdDelete);
			if(count($params['DocumentIds'])>0){
				foreach ($params['DocumentIds'] as $fileID){
					$formFile = array();
					$formFile['InformationDepartmentID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['Modified'] = $modified;
					$formFile['ModifiedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("information_department_file", $formFile);
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
			$result = $this->db->update("information_department_info", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function getFiles($informationDepartmentID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'information_department_file'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
			->columns(array(
					"FileInfoID" => "tbl.FileInfoID",
					
					"FileName" => "fi.Name",
					"FileHashName" => "fi.HashName",
					"FileType" => "fi.FileType",
					"FileSize" => "fi.FileSize",
					"FileExtension" => "fi.Extension"
			))
			->where("tbl.InformationDepartmentID = ? ", $informationDepartmentID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	function getFilesFormFileInfo($BackgroundImageID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('fi' => "file_info"), '')
			->columns(array(
					"ID" => "fi.ID",
					
					"FileName" => "fi.Name",
					"FileHashName" => "fi.HashName",
					"FileType" => "fi.FileType",
					"FileSize" => "fi.FileSize",
					"FileExtension" => "fi.Extension"
			))
			->where("fi.ID = ? ", $BackgroundImageID)
			->where("fi.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	function getIdfile($id) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'dbo.InformationInfoFile'), array(""))
			->columns(array(
					"tbl.FileInfoID",
					"tbl.FileType"
			))
			->where("tbl.InformationInfoID = ? " ,$id)
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
					$affRow += $this->db->update("information_department_info", $form, $where);
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
					->from(array('tbl'=>'information_department_info'), array(
						'InfomationCategoryID',
						'Address',
						'IntroImageID',
						'Jurisdiction',
						'Ordering',
						'Telephone',
						'ShowCategory',
						'ShowDocument',
						'ViewAmount',
						'Fax',
						'Email',
						'Website',
						'Latitude',
						'Longitude',
						'BackgroundImageID',
						'BackgroundColor',
						'PublishDate',
						'PublishDown',
						'MetaData',
						'SocialNetworkFacebook',
						'SocialNetworkTwitter',
						'SocialNetworkGoogleplus',
						'ShowQRcode',
						'ShowPrint',
						'ViewAmount'
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['InfomationCategoryID'] = empty($dataInfo->InfomationCategoryID) ? null : $dataInfo->InfomationCategoryID;
					$form['Address'] = empty($dataInfo->Address) ? null : $dataInfo->Address;
					$form['IntroImageID'] = empty($dataInfo->IntroImageID) ? null : $dataInfo->IntroImageID;
					$form['Jurisdiction'] = empty($dataInfo->Jurisdiction) ? null : $dataInfo->Jurisdiction;
					$form['Ordering'] = empty($dataInfo->Ordering) ? null : $dataInfo->Ordering;
					$form['Telephone'] = empty($dataInfo->Telephone) ? null : $dataInfo->Telephone;
					$form['ShowCategory'] = empty($dataInfo->ShowCategory) ? null : $dataInfo->ShowCategory;
					$form['ShowDocument'] = empty($dataInfo->ShowDocument) ? null : $dataInfo->ShowDocument;
					$form['Fax'] = empty($dataInfo->Fax) ? null : $dataInfo->Fax;
					$form['Email'] = empty($dataInfo->Email) ? null : $dataInfo->Email;
					$form['Website'] = empty($dataInfo->Website) ? null : $dataInfo->Website;
					$form['Latitude'] = empty($dataInfo->Latitude) ? null : $dataInfo->Latitude;
					$form['Longitude'] = empty($dataInfo->Longitude) ? null : $dataInfo->Longitude;
					$form['BackgroundImageID'] = empty($dataInfo->BackgroundImageID) ? null : $dataInfo->BackgroundImageID;
					$form['BackgroundColor'] = empty($dataInfo->BackgroundColor) ? null : $dataInfo->BackgroundColor;
					$form['PublishDate'] = empty($dataInfo->PublishDate) ? null : $dataInfo->PublishDate;
					$form['PublishDown'] = empty($dataInfo->PublishDown) ? null : $dataInfo->PublishDown;
					$form['MetaData'] = empty($dataInfo->MetaData) ? null : $dataInfo->MetaData;
					$form['SocialNetworkFacebook'] = empty($dataInfo->SocialNetworkFacebook) ? 'N' : $dataInfo->SocialNetworkFacebook;
					$form['SocialNetworkTwitter'] = empty($dataInfo->SocialNetworkTwitter) ? 'N' : $dataInfo->SocialNetworkTwitter;
					$form['SocialNetworkGoogleplus'] = empty($dataInfo->SocialNetworkGoogleplus) ? 'N' : $dataInfo->SocialNetworkGoogleplus;
					$form['ShowQRcode'] = empty($dataInfo->ShowQRcode) ? 'N' : $dataInfo->ShowQRcode;
					$form['ShowPrint'] = empty($dataInfo->ShowPrint) ? 'N' : $dataInfo->ShowPrint;
					$form['ViewAmount'] = 0;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$form['IsDeleted'] = 'N';
					$data->result = $this->db->insert("information_department_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'information_department_detail'), array('Lang','Name','Detail'))
						->where("tbl.ID = ? ", $id)
						;
						$stmtDetail = $this->db->query($selectDetail);
						$dataDetail = $stmtDetail->fetchAll();

						if(count($dataDetail)>0){
							foreach($dataDetail as $d){
								$form = array();
								$form['ID'] = $tmpID;
								$form['Lang'] = empty($d['Lang']) ? null : $d['Lang'];
								$form['Name'] = empty($d['Name']) ? null : $d['Name'];
								if($defaultLanguage == $d['Lang']){
									$form['Name'] = $form['Name'] . ' (Copy)';
								}
								$form['Detail'] = empty($d['Detail']) ? null : $d['Detail'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("information_department_detail", $form);
							}
						}

						// File
						$selectFile = $this->db->select ()
						->from(array('tbl'=>'information_department_file'), array('FileInfoID'))
						->where("tbl.InformationDepartmentID = ? ", $id)
						;
						$stmtFile = $this->db->query($selectFile);
						$dataFile = $stmtFile->fetchAll();

						if(count($dataFile)>0){
							foreach($dataFile as $d){
								$form = array();
								$form['InformationDepartmentID'] = $tmpID;
								$form['FileInfoID'] = empty($d['FileInfoID']) ? null : $d['FileInfoID'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("information_department_file", $form);
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
					$affRow += $this->db->update("information_department_info", $form, $where);
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
