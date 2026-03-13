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
			->joinLeft(array('ca' => 'information_category_detail') ,"c.ID = ca.ID and ca.Lang = '{$lang}' " , '')
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Title" => "d.Name",
					"CategoryName" => "ca.Title",
					"InfomationCategoryID" => "tbl.InfomationCategoryID",
					"Address" => "tbl.Address",
					"Jurisdiction" => "tbl.Jurisdiction",
					"Telephone" => "tbl.Telephone",
					"Fax" => "tbl.Fax",
					"Email" => "tbl.Email",
					"Website" => "tbl.Website",
					"Latitude" => "tbl.Latitude",
					"Longitude" => "tbl.Longitude",

					"ShowCategory" => "tbl.ShowCategory",
					"ShowDocument" => "tbl.ShowDocument",
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
			->where("case when tbl.PublishDown is null then 1 else (case when tbl.PublishDown >= ? then 1 else 0 end) end = 1", date('Y-m-d H:i:s'))
			->where("d.Name is not null")
			->where("d.Name <> '' ")
			;
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("d.Name like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['CategoryID'])) {
				//$select = $select->where("tbl.information_category_id = ? ", $advancesearch['isCat']);
				$select = $select->distinct()
				->where("tbl.InfomationCategoryID = ? ", $advancesearch['CategoryID'])
				;
			}
			if (!empty($advancesearch['Title'])) {
				$val = $advancesearch['Title'];
				$select = $select->where("d.Name like " . $this->db->quote("%" . $val . "%" ));
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
			->from(array('tbl' => 'information_department_info'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"InformationCategoryID" => "tbl.InfomationCategoryID",
					// "ID" => "tbl.main_title",
					"PublishDate" => "tbl.PublishDate",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"ViewAmount" => "tbl.ViewAmount",
					"Ordering" => "tbl.Ordering",
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
			->joinLeft(array('c' => 'information_category_info'),"tbl.InfomationCategoryID = c.ID ", '')
			->joinLeft(array('ca' => 'information_category_detail'),"ca.ID = c.ID  and ca.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"InformationCategoryID" => "tbl.InfomationCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					"BackgroundColor" => "tbl.BackgroundColor",
					"BackgroundImageID" => "tbl.BackgroundImageID",
					"ShowDocument" => "tbl.ShowDocument",
					"ShowCategory" => "tbl.ShowCategory",
					"SlidingBackground" => "tbl.SlidingBackground",
					// "ID" => "tbl.main_title",
					"CategoryName" => "ca.Title",
					"InfomationCategoryID" => "tbl.InfomationCategoryID",
					"Address" => "tbl.Address",
					"Jurisdiction" => "tbl.Jurisdiction",
					"Telephone" => "tbl.Telephone",
					"Fax" => "tbl.Fax",
					"Email" => "tbl.Email",
					"Website" => "tbl.Website",
					"Latitude" => "tbl.Latitude",
					"Longitude" => "tbl.Longitude",

					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"ViewAmount" => "tbl.ViewAmount",
					"Ordering" => "tbl.Ordering",
					"Settings" => "tbl.Settings",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
					
					"Lang" => "d.Lang",
					"Title" => "d.Name",
					"Detail" => "d.Detail",
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
	public function getCategory($lang) {
		$select = $this->db->select()
		->from ( array ('tbs' => 'information_category_info' ), array ('ID') )
		->joinInner ( array ('d' => 'information_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		// ->where ( "d.Lang = ?", $lang )
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
	function getFile($BackgroundImageID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('fi' => 'file_info'), '')
			->columns(array(
					"FileInfoID" => "fi.ID",					
					"FileName" => "fi.Name",
					"FileHashName" => "fi.HashName",
					"FileSize" => "fi.FileSize",
					"FileExtension" => "fi.Extension"
			))
			->where("fi.ID = ? ", $BackgroundImageID)
			->where("fi.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	public static function saveView($informationDepartmentID, $memberID, $userID, $lang, $accessFrom) {
		$data = new stdClass();
		$data->result = false;
		try {
			$db = Zend_Db_Table::getDefaultAdapter();
			$request = Zend_Controller_Front::getInstance()->getRequest();
			
			$created = date("Y-m-d H:i:s");

			$form = array();
			$form["InfomationDepartmentID"] = $informationDepartmentID;
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
			
			$affectedRows = $db->insert("information_department_view", $form);
			
			if ($affectedRows) {
				$select = $db->select()->from(array("tbl" => "information_department_view"), array("TotalView" => "count(tbl.ID)"))
				->where("InfomationDepartmentID = ?", $informationDepartmentID)
				;
				$stmt = $db->query($select);
				$dataTotal = $stmt->fetchObject();
				
				$formSummary = array();
				$whereSummary = array();
				$formSummary["ViewAmount"] = $dataTotal->TotalView;
				$whereSummary["ID = ?"] = $informationDepartmentID;
				$affectedRows = $db->update("information_department_info", $formSummary, $whereSummary);
			}
			
			$data->result = true;
		} catch (Exception $e) {
			//print_r($e);
		}
		return $data;
	}

}

?>
