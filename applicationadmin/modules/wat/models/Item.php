<?php

class Wat_Model_Item {
	
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
			->from(array('tbl' => 'wat_item_info'), '')
			->joinLeft(array('d' => 'wat_item_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('s' => "wat_sect_info"), "tbl.WatSectID = s.ID", "")
			->joinLeft(array('sd' => "wat_sect_detail"), "s.ID = sd.ID and sd.Lang = '{$lang}' ", "")
			->joinLeft(array('p' => "wat_province_info"), "tbl.WatProvinceCode = p.Code", "")
			->joinLeft(array('pd' => "wat_province_detail"), "p.Code = pd.Code and pd.Lang = '{$lang}' ", "")
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->columns(array(
					"ID" => "tbl.ID",
					"ViewAmount" => "tbl.ViewAmount",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"Title" => "d.Title",
					"SectTitle" => "sd.Title",
					"ProvinceName" => "pd.Name",
					"Ordering" => "tbl.Ordering",
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
			if (!empty($advancesearch['province'])) {
				$select = $select->where("tbl.WatProvinceCode = ? ", $advancesearch['province']);
			}
			if (!empty($advancesearch['sect'])) {
				$select = $select->where("tbl.WatSectID = ? ", $advancesearch['sect']);
			}
			if (!empty($advancesearch['isCat'])) {
				$select = $select->distinct()
				->joinInner(array('ca' => "wat_category_item"), "tbl.ID = ca.WatItemID", '')
				->where("ca.WatCategoryID = ? ", $advancesearch['isCat'])
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
			if (in_array($rowSort, array('Title', 'SectTitle', 'ProvinceName'))) {
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
			->from(array('tbl' => 'wat_item_info'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->joinLeft(array("fc" => "file_info"), "tbl.CoverImageID = fc.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"IntroImageID" => "tbl.IntroImageID",
					"CoverImageID" => "tbl.CoverImageID",
					"TemplateType" => "tbl.TemplateType",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"ViewAmount" => "tbl.ViewAmount",
					"Ordering" => "tbl.Ordering",
					"CanComment" => "tbl.CanComment",
					"Settings" => "tbl.Settings",
					"IsDharmaPracticePlace"=>"IsDharmaPracticePlace",
					"DharmaPracticePlaceNo"=>"DharmaPracticePlaceNo",
					"DharmaPracticePlaceResolutionNo"=>"DharmaPracticePlaceResolutionNo",
					
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
					
					//"MetaData" => "tbl.MetaData",
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
					
					"CoverImageFileName" => "fc.Name",
					"CoverImageHashName" => "fc.HashName",
					"CoverImageFileType" => "fc.FileType",
					"CoverImageFileSize" => "fc.FileSize",
					"CoverImageFileExtension" => "fc.Extension",
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
			->from(array('tbl' => 'wat_item_info'), '')
			->joinLeft(array('d' => 'wat_item_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->joinLeft(array('s' => "wat_sect_info"), "tbl.WatSectID = s.ID", "")
			->joinLeft(array('sd' => "wat_sect_detail"), "s.ID = sd.ID and sd.Lang = '{$lang}' ", "")
			->joinLeft(array('p' => "wat_province_info"), "tbl.WatProvinceCode = p.Code", "")
			->joinLeft(array('pd' => "wat_province_detail"), "p.Code = pd.Code and pd.Lang = '{$lang}' ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"IntroImageID" => "tbl.IntroImageID",
					"TemplateType" => "tbl.TemplateType",
					"WatTypeID" => "tbl.WatTypeID",
					"WatSectID" => "tbl.WatSectID",
					"WatSectTitle" => "sd.Title",
					"WatKindID" => "tbl.WatKindID",
					"WatClassID" => "tbl.WatClassID",
					"WatSubDistrictCode" => "tbl.WatSubDistrictCode",
					"WatDistrictCode" => "tbl.WatDistrictCode",
					"WatProvinceCode" => "tbl.WatProvinceCode",
					"WatProvinceName" => "pd.Name",
					"PostCode" => "tbl.PostCode",
					"Latitude" => "tbl.Latitude",
					"Longitude" => "tbl.Longitude",
					"Telephone" => "tbl.Telephone",
					"Fax" => "tbl.Fax",
					"Mobile" => "tbl.Mobile",
					"Email" => "tbl.Email",
					"Website" => "tbl.Website",
					"FacebookUrl" => "tbl.FacebookUrl",
					"LineID" => "tbl.LineID",
					"Twitter" => "tbl.Twitter",
					"IsForTravel" => "tbl.IsForTravel",
					"HasHistoricSite" => "tbl.HasHistoricSite",
					"IsDharmaPracticePlace"=>"IsDharmaPracticePlace",
					"DharmaPracticePlaceNo"=>"DharmaPracticePlaceNo",
					"DharmaPracticePlaceResolutionNo"=>"DharmaPracticePlaceResolutionNo",
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
					
					//"MetaData" => "tbl.MetaData",
					"SocialNetworkFacebook" => "tbl.SocialNetworkFacebook",
					"SocialNetworkTwitter" => "tbl.SocialNetworkTwitter",
					"SocialNetworkGooglePlus" => "tbl.SocialNetworkGooglePlus",
					"ShowQrcode" => "tbl.ShowQrcode",
					"ShowPrint" => "tbl.ShowPrint",

					"Lang" => "d.Lang",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Detail" => "d.Detail",
					"Source" => "d.Source",
					"Address" => "d.Address",
					"Moo" => "d.Moo",
					"Soi" => "d.Soi",
					"Road" => "d.Road",
					"AbbotName" => "d.AbbotName",
					"MetaData" => "d.MetaData",
					"HistoricSiteDetail" => "d.HistoricSiteDetail",
					"BuddhaStatueDetail" => "d.BuddhaStatueDetail",
					"OpenHourRemark" => "d.OpenHourRemark",
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
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['CoverImageID'] = $params['CoverImageID'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['ViewAmount'] = 0;
			$form['TemplateType'] = $params['TemplateType'];
			
			$form['WatSectID'] = empty($param[$defaultLanguageCode]['Sect']) ? null : $param[$defaultLanguageCode]['Sect'];
			$form['WatKindID'] = empty($param[$defaultLanguageCode]['Kind']) ? null : $param[$defaultLanguageCode]['Kind'];
			$form['WatClassID'] = empty($param[$defaultLanguageCode]['WatClass']) ? null : $param[$defaultLanguageCode]['WatClass'];
			//$form['WatTypeID'] = $param[$defaultLanguageCode]['WatType'];
			$form['IsForTravel'] = $param[$defaultLanguageCode]['IsForTravel'];
			$form['HasHistoricSite'] = $param[$defaultLanguageCode]['HasHistoricSite'];
			$form['IsDharmaPracticePlace'] = $param[$defaultLanguageCode]['IsDharmaPracticePlace'];
			$form['DharmaPracticePlaceNo'] = empty($param[$defaultLanguageCode]['DharmaPracticePlaceNo']) ? null : $param[$defaultLanguageCode]['DharmaPracticePlaceNo'];
			$form['DharmaPracticePlaceResolutionNo'] = empty($param[$defaultLanguageCode]['DharmaPracticePlaceResolutionNo']) ? null : $param[$defaultLanguageCode]['DharmaPracticePlaceResolutionNo'];

			$form['WatSubDistrictCode'] = $param[$defaultLanguageCode]['SubDistrict'];
			$form['WatDistrictCode'] = $param[$defaultLanguageCode]['District'];
			$form['WatProvinceCode'] = $param[$defaultLanguageCode]['Province'];
			$form['PostCode'] = $param[$defaultLanguageCode]['PostCode'];
			$form['Latitude'] = $param[$defaultLanguageCode]['Latitude'] == "" ? null : $param[$defaultLanguageCode]['Latitude'];
			$form['Longitude'] = $param[$defaultLanguageCode]['Longitude'] == "" ? null : $param[$defaultLanguageCode]['Longitude'];
			$form['Telephone'] = $param[$defaultLanguageCode]['Telephone'];
			$form['Fax'] = $param[$defaultLanguageCode]['Fax'];
			$form['Mobile'] = $param[$defaultLanguageCode]['Mobile'];
			$form['Email'] = $param[$defaultLanguageCode]['Email'];
			$form['Website'] = $param[$defaultLanguageCode]['Website'];
			$form['FacebookUrl'] = $param[$defaultLanguageCode]['FacebookUrl'];
			$form['LineID'] = $param[$defaultLanguageCode]['LineID'];
			$form['Twitter'] = $param[$defaultLanguageCode]['Twitter'];
			
			//$form['CanComment'] = $param[$defaultLanguageCode]['CanCommentType'] == 'C' ? $param[$defaultLanguageCode]['CanComment'] : $param[$defaultLanguageCode]['CanCommentType'];			
			//$form['MetaData'] = $param[$defaultLanguageCode]['MetaData'];
			$form['SocialNetworkFacebook'] = $param[$defaultLanguageCode]['SocialNetworkFacebook'];
			$form['SocialNetworkTwitter'] = $param[$defaultLanguageCode]['SocialNetworkTwitter'];
			$form['SocialNetworkGooglePlus'] = $param[$defaultLanguageCode]['SocialNetworkGooglePlus'];
			$form['ShowQrcode'] = $param[$defaultLanguageCode]['ShowQrcode'];
			$form['ShowPrint'] = $param[$defaultLanguageCode]['ShowPrint'];
			
			$form['Created'] = $modified;
			$form['CreatedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("wat_item_info", $form);
			$id = $this->db->lastInsertId();

			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['WatItemID'] = $id;
					$formDetail['WatCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$this->db->insert("wat_category_item", $formDetail);					
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
					//$formDetail['Source'] = $value['Source'];
					//$formDetail['AbbotName'] = $value['AbbotName'];
					$formDetail['Address'] = $value['Address'];
					$formDetail['Moo'] = $value['Moo'];
					$formDetail['Soi'] = $value['Soi'];
					$formDetail['Road'] = $value['Road'];
					$formDetail['MetaData'] = $value['MetaData'];
					$formDetail['HistoricSiteDetail'] = $param[$defaultLanguageCode]['HasHistoricSite'] == "Y" ? $value['HistoricSiteDetail'] : null;
					$formDetail['BuddhaStatueDetail'] = $value['BuddhaStatueDetail'];
					$formDetail['OpenHourRemark'] = $value['OpenHourRemark'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$this->db->insert("wat_item_detail", $formDetail);
				}
			}
			
			// Keyword
			if(count($params['hdfTag'])>0){
				foreach ($params['hdfTag'] as $lang => $tags) {
					
					if (count($tags) > 0) {
						foreach ($tags as $tag) {
							// Find Tag ID
							$select = $this->db->select()->from(array("t" => "wat_tags"), array("ID"))->where("t.Lang = ?", $lang)->where("t.Title = ?", $tag);
							$stmt = $this->db->query($select);
							$dataTag = $stmt->fetchObject();
							$tagID = $dataTag->ID;
							if (empty($tagID)) {
								$formTag = array();
								$formTag["Title"] = $tag;
								$formTag["Lang"] = $lang;
								$formTag['Created'] = $modified;
								$formTag['CreatedBy']= $this->UserID;
								$formTag['IsEnable'] = 'Y';
								$formTag['IsDeleted'] = 'N';
								$this->db->insert("wat_tags", $formTag);
								$tagID = $this->db->lastInsertId();
							}
							
							$formWatTag = array();
							$formWatTag["WatItemID"] = $id;
							$formWatTag["WatTagID"] = $tagID;
							$formWatTag["Lang"] = $lang;
							$formWatTag['Created'] = $modified;
							$formWatTag['CreatedBy']= $this->UserID;
							$formWatTag['IsEnable'] = 'Y';
							$formWatTag['IsDeleted'] = 'N';
							
							$this->db->insert("wat_item_tags", $formWatTag);
						}
					}
				}
			}
			
			// WatType
			if(count($param[$defaultLanguageCode]['WatType'])>0){
				foreach ($param[$defaultLanguageCode]['WatType'] as $watTypeID) {
					$formType = array();
					$formType['WatItemID'] = $id;
					$formType['WatTypeID']= $watTypeID;
					$formType['Created'] = $modified;
					$formType['CreatedBy']= $this->UserID;
					$formType['IsEnable'] = 'Y';
					$formType['IsDeleted'] = 'N';
					$this->db->insert("wat_item_type", $formType);
				}
			}			
			
			// Open Hour
			if (count($param[$defaultLanguageCode]['OpenHour']) > 0) {
				foreach ($param[$defaultLanguageCode]['OpenHour'] as $valueJson) {
					$val = json_decode($valueJson, true);
					$form = array();
					$form['WatItemID'] = $id;
					$form['DayID'] = $val["day"];
					$form['StartTime'] = $val["startHour"] . ":" . $val["startMinute"];
					$form['EndTime'] = ($val["endHour"] == '24' ? '00' : $val["endHour"]) . ":" . $val["endMinute"];
					$form['Created'] = $modified;
					$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['IsEnable'] = 'Y';
					$form['IsDeleted'] = 'N';
					$this->db->insert("wat_item_openhour", $form);
				}
			}
			
			// Activity
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$where['IsDeleted = ?'] = 'N';
			$where['WatItemID = ?'] = $id;
			$this->db->update("wat_item_activity", $form, $where);
			if (count($param[$defaultLanguageCode]['Activity']) > 0) {
				$i = 0;
				foreach ($param[$defaultLanguageCode]['Activity'] as $key => $val) {
					$obj = json_decode($val, true);
					$i ++;
					$form = array();
					$where = array();
					$form['WatActivityID'] = $obj['TypeID'];
					$form['Ordering'] = $i;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['IsEnable'] = 'Y';
					$form['IsDeleted'] = 'N';
					$where["WatItemID = ?"] = $id;
					$where["DataCode = ?"] = $obj['ID'];
					$affectedRows = $this->db->update("wat_item_activity", $form, $where);
					if ($affectedRows == 0) {
						$form['DataCode'] = $obj['ID'];
						$form['WatItemID'] = $id;
						$form['Created'] = $modified;
						$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$affectedRows = $this->db->insert("wat_item_activity", $form);
					}
					
					if (($affectedRows > 0) && (count($obj['Lang']) > 0)) {
						foreach ($obj['Lang'] as $l => $v) {
							$formDetail = array();
							$whereDetail = array();
							$formDetail['Detail'] = $v['Detail'];
							$formDetail['Modified'] = $modified;
							$formDetail['IsEnable'] = 'Y';
							$formDetail['IsDeleted'] = 'N';
							$whereDetail["WatItemID = ?"] = $id;
							$whereDetail["DataCode = ?"] = $obj['ID'];
							$whereDetail["Lang = ?"] = $l;
							$affectedDetailRows = $this->db->update("wat_item_activity_detail", $formDetail, $whereDetail);
							if ($affectedDetailRows == 0) {
								$formDetail['WatItemID'] = $id;
								$formDetail['DataCode'] = $obj['ID'];
								$formDetail['Lang'] = $l;
								$formDetail['Created'] = $modified;
								$this->db->insert("wat_item_activity_detail", $formDetail);
							}
						}
					}
				}
			}
			
			// Rescue
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$where['IsDeleted = ?'] = 'N';
			$where['WatItemID = ?'] = $id;
			$this->db->update("wat_item_rescue", $form, $where);
			if (count($param[$defaultLanguageCode]['Rescue']) > 0) {
				$i = 0;
				foreach ($param[$defaultLanguageCode]['Rescue'] as $key => $val) {
					$obj = json_decode($val, true);
					$i ++;
					$form = array();
					$where = array();
					$form['WatRescueID'] = $obj['TypeID'];
					$form['Ordering'] = $i;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['IsEnable'] = 'Y';
					$form['IsDeleted'] = 'N';
					$where["WatItemID = ?"] = $id;
					$where["DataCode = ?"] = $obj['ID'];
					$affectedRows = $this->db->update("wat_item_rescue", $form, $where);
					if ($affectedRows == 0) {
						$form['DataCode'] = $obj['ID'];
						$form['WatItemID'] = $id;
						$form['Created'] = $modified;
						$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$affectedRows = $this->db->insert("wat_item_rescue", $form);
					}
					
					if (($affectedRows > 0) && (count($obj['Lang']) > 0)) {
						foreach ($obj['Lang'] as $l => $v) {
							$formDetail = array();
							$whereDetail = array();
							$formDetail['Detail'] = $v['Detail'];
							$formDetail['Modified'] = $modified;
							$formDetail['IsEnable'] = 'Y';
							$formDetail['IsDeleted'] = 'N';
							$whereDetail["WatItemID = ?"] = $id;
							$whereDetail["DataCode = ?"] = $obj['ID'];
							$whereDetail["Lang = ?"] = $l;
							$affectedDetailRows = $this->db->update("wat_item_rescue_detail", $formDetail, $whereDetail);
							if ($affectedDetailRows == 0) {
								$formDetail['WatItemID'] = $id;
								$formDetail['DataCode'] = $obj['ID'];
								$formDetail['Lang'] = $l;
								$formDetail['Created'] = $modified;
								$this->db->insert("wat_item_rescue_detail", $formDetail);
							}
						}
					}
				}
			}

			// Bank
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$where['IsDeleted = ?'] = 'N';
			$where['WatItemID = ?'] = $id;
			$this->db->update("wat_item_bankaccount", $form, $where);
			if (count($param[$defaultLanguageCode]['Bank']) > 0) {
				$i = 0;
				$fileModel = new System_Model_Fileinfo();
				foreach ($param[$defaultLanguageCode]['Bank'] as $key => $val) {
					$obj = json_decode($val, true);
					$QRCodeImage = null;
					if($obj['QRCodeImageHash']){
						$fileData = $fileModel->itemByHashName($obj['QRCodeImageHash']);
						$QRCodeImage = empty($fileData->ID) ? null : $fileData->ID;
					}
					$i ++;
					$form = array();
					$where = array();
					$form['WatBankID'] = $obj['WatBankID'];
					$form['WatBakaccounttypeID'] = $obj['WatBakaccounttypeID'];
					$form['BranchName'] = $obj['BranchName'];
					$form['AccountName'] = $obj['AccountName'];
					$form['AccountNo'] = $obj['AccountNo'];
					$form['QRCodeImageID'] = $QRCodeImage;
					$form['Ordering'] = $i;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['IsEnable'] = 'Y';
					$form['IsDeleted'] = 'N';
					$where["WatItemID = ?"] = $id;
					$where["DataCode = ?"] = $obj['ID'];
					$affectedRows = $this->db->update("wat_item_bankaccount", $form, $where);
					if ($affectedRows == 0) {
						$form['DataCode'] = $obj['ID'];
						$form['WatItemID'] = $id;
						$form['Created'] = $modified;
						$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$affectedRows = $this->db->insert("wat_item_bankaccount", $form);
					}
				}
			}

			// PromptPay
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'kittinan' . DS . 'php-promptpay-qr' . DS . 'src' . DS . 'PromptPay.php';
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'kittinan' . DS . 'php-crc' . DS . 'src' . DS . 'CRCInterface.php';
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'kittinan' . DS . 'php-crc' . DS . 'src' . DS . 'AbstractCRC.php';
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'kittinan' . DS . 'php-crc' . DS . 'src' . DS . 'CRC16.php';
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'kittinan' . DS . 'php-crc' . DS . 'src' . DS . 'CRC16CCITT.php';
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'bacon' . DS . 'bacon-qr-code' . DS . 'autoload_register.php';
			$pp = new \KS\PromptPay();

			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$where['IsDeleted = ?'] = 'N';
			$where['WatItemID = ?'] = $id;
			$this->db->update("wat_item_promptpayaccount", $form, $where);
			if (count($param[$defaultLanguageCode]['PromptPay']) > 0) {
				$i = 0;
				$fileModel = new System_Model_Fileinfo();
				foreach ($param[$defaultLanguageCode]['PromptPay'] as $key => $val) {
					$obj = json_decode($val, true);

					$imgPath = APPLICATION_ROOT . DS .'tmp' . DS . 'qrcode' . DS . 'PromptPay-' . $obj['PromptPayNo'] . '.png';
					$pp->generateQrCode($imgPath, $obj['PromptPayNo'], null, '300');
					$resultfile = $this->uploadfile($imgPath);
					if($resultfile->result){
						$QRCodeImage = $resultfile->data->id;
					}

					$i ++;
					$form = array();
					$where = array();
					$form['PromptPayName'] = $obj['PromptPayName'];
					$form['PromptPayNo'] = $obj['PromptPayNo'];
					$form['QRCodeImageID'] = empty($QRCodeImage) ? null : $QRCodeImage;
					$form['Ordering'] = $i;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['IsEnable'] = 'Y';
					$form['IsDeleted'] = 'N';
					$where["WatItemID = ?"] = $id;
					$where["DataCode = ?"] = $obj['ID'];
					$affectedRows = $this->db->update("wat_item_promptpayaccount", $form, $where);
					if ($affectedRows == 0) {
						$form['DataCode'] = $obj['ID'];
						$form['WatItemID'] = $id;
						$form['Created'] = $modified;
						$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$affectedRows = $this->db->insert("wat_item_promptpayaccount", $form);
					}
				}
			}
			
			// Gallery
			if(count($params['GalleryIds'])>0){
				foreach ($params['GalleryIds'] as $fileID){
					$formFile = array();
					$formFile['WatItemID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'image';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("wat_item_file", $formFile);
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
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['CoverImageID'] = $params['CoverImageID'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['TemplateType'] = $params['TemplateType'];
			
			$form['WatSectID'] = empty($param[$defaultLanguageCode]['Sect']) ? null : $param[$defaultLanguageCode]['Sect'];
			$form['WatKindID'] = empty($param[$defaultLanguageCode]['Kind']) ? null : $param[$defaultLanguageCode]['Kind'];
			$form['WatClassID'] = empty($param[$defaultLanguageCode]['WatClass']) ? null : $param[$defaultLanguageCode]['WatClass'];
			//$form['WatTypeID'] = $param[$defaultLanguageCode]['WatType'];
			$form['IsForTravel'] = $param[$defaultLanguageCode]['IsForTravel'];
			$form['HasHistoricSite'] = $param[$defaultLanguageCode]['HasHistoricSite'];
			$form['IsDharmaPracticePlace'] = $param[$defaultLanguageCode]['IsDharmaPracticePlace'];
			$form['DharmaPracticePlaceNo'] = empty($param[$defaultLanguageCode]['DharmaPracticePlaceNo']) ? null : $param[$defaultLanguageCode]['DharmaPracticePlaceNo'];
			$form['DharmaPracticePlaceResolutionNo'] = empty($param[$defaultLanguageCode]['DharmaPracticePlaceResolutionNo']) ? null : $param[$defaultLanguageCode]['DharmaPracticePlaceResolutionNo'];

			$form['WatSubDistrictCode'] = $param[$defaultLanguageCode]['SubDistrict'];
			$form['WatDistrictCode'] = $param[$defaultLanguageCode]['District'];
			$form['WatProvinceCode'] = $param[$defaultLanguageCode]['Province'];
			$form['PostCode'] = $param[$defaultLanguageCode]['PostCode'];
			$form['Latitude'] = $param[$defaultLanguageCode]['Latitude'] == "" ? null : $param[$defaultLanguageCode]['Latitude'];
			$form['Longitude'] = $param[$defaultLanguageCode]['Longitude'] == "" ? null : $param[$defaultLanguageCode]['Longitude'];
			$form['Telephone'] = $param[$defaultLanguageCode]['Telephone'];
			$form['Fax'] = $param[$defaultLanguageCode]['Fax'];
			$form['Mobile'] = $param[$defaultLanguageCode]['Mobile'];
			$form['Email'] = $param[$defaultLanguageCode]['Email'];
			$form['Website'] = $param[$defaultLanguageCode]['Website'];
			$form['FacebookUrl'] = $param[$defaultLanguageCode]['FacebookUrl'];
			$form['LineID'] = $param[$defaultLanguageCode]['LineID'];
			$form['Twitter'] = $param[$defaultLanguageCode]['Twitter'];
			
			//$form['CanComment'] = $param[$defaultLanguageCode]['CanCommentType'] == 'C' ? $param[$defaultLanguageCode]['CanComment'] : $param[$defaultLanguageCode]['CanCommentType'];
			//$form['MetaData'] = $param[$defaultLanguageCode]['MetaData'];

			$form['SocialNetworkFacebook'] = $param[$defaultLanguageCode]['SocialNetworkFacebook'];
			$form['SocialNetworkTwitter'] = $param[$defaultLanguageCode]['SocialNetworkTwitter'];
			$form['SocialNetworkGooglePlus'] = $param[$defaultLanguageCode]['SocialNetworkGooglePlus'];
			$form['ShowQrcode'] = $param[$defaultLanguageCode]['ShowQrcode'];
			$form['ShowPrint'] = $param[$defaultLanguageCode]['ShowPrint'];
			
			$form['Modified'] = $modified;
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$where['ID = ? '] = $id;
			$result = $this->db->update("wat_item_info", $form, $where);
			
			// Delete Category
			$formCategoryDeleted = array();
			$whereCategoryDeleted = array();
			$formCategoryDeleted['Modified'] = $modified;
			$formCategoryDeleted['ModifiedBy']= $this->UserID;
			$formCategoryDeleted["IsDeleted"] = "Y";
			$whereCategoryDeleted['IsDeleted = ? '] = 'N';
			$whereCategoryDeleted['WatItemID = ? '] = $id;
			$this->db->update("wat_category_item", $formCategoryDeleted, $whereCategoryDeleted);

			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$whereDetail = array();
					$formDetail['Modified'] = $modified;
					$formDetail['ModifiedBy']= $this->UserID;
					$formDetail['IsDeleted'] = 'N';
					$whereDetail["WatItemID = ?"] = $id;
					$whereDetail["WatCategoryID = ?"] = $cId;
					$affRow = $this->db->update("wat_category_item", $formDetail, $whereDetail);
					if ($affRow == 0) {
						$formDetail['WatItemID'] = $id;
						$formDetail['WatCategoryID'] = $cId;
						$formDetail['Created'] = $modified;
						$formDetail['CreatedBy']= $this->UserID;
						$formDetail['IsEnable'] = 'Y';
						$this->db->insert("wat_category_item", $formDetail);
					}
				}
			}
			
			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$whereDetail = array();
					$formDetail['Title'] = $value['Title'];
					//$formDetail['Source'] = $value['Source'];
					$formDetail['IntroText'] = $value['IntroText'];
					$formDetail['Detail'] = $value['Detail'];
					//$formDetail['AbbotName'] = $value['AbbotName'];
					$formDetail['Address'] = $value['Address'];
					$formDetail['Moo'] = $value['Moo'];
					$formDetail['Soi'] = $value['Soi'];
					$formDetail['Road'] = $value['Road'];
					$formDetail['MetaData'] = $value['MetaData'];
					$formDetail['HistoricSiteDetail'] = $param[$defaultLanguageCode]['HasHistoricSite'] == "Y" ? $value['HistoricSiteDetail'] : null;
					$formDetail['BuddhaStatueDetail'] = $value['BuddhaStatueDetail'];
					$formDetail['OpenHourRemark'] = $value['OpenHourRemark'];
					$formDetail['Modified'] = $modified;
					$formDetail['ModifiedBy']= $this->UserID;
					$whereDetail['ID = ? '] = $id;
					$whereDetail['Lang = ? '] = $lang;
					$affRow = $this->db->update("wat_item_detail", $formDetail, $whereDetail);
					if ($affRow == 0) {
						$formDetail['ID'] = $id;
						$formDetail['Lang'] = $lang;
						$formDetail['Created'] = $modified;
						$formDetail['CreatedBy']= $this->UserID;
						$formDetail['IsEnable'] = 'Y';
						$formDetail['IsDeleted'] = 'N';
						$this->db->insert("wat_item_detail", $formDetail);
					}
				}
			}
			
			// Keyword
			$formWatTag = array();
			$whereWatTag = array();
			$formWatTag["IsDeleted"] = 'Y';
			$formWatTag['Modified'] = $modified;
			$whereWatTag['IsDeleted = ?'] = 'N';
			$whereWatTag['WatItemID = ?'] = $id;
			$this->db->update("wat_item_tags", $formWatTag, $whereWatTag);
			if(count($params['hdfTag'])>0){
				foreach ($params['hdfTag'] as $lang => $tags) {
					
					if (count($tags) > 0) {
						foreach ($tags as $tag) {
							// Find Tag ID
							$select = $this->db->select()->from(array("t" => "wat_tags"), array("ID"))->where("t.Lang = ?", $lang)->where("t.Title = ?", $tag);
							$stmt = $this->db->query($select);
							$dataTag = $stmt->fetchObject();
							$tagID = $dataTag->ID;
							if (empty($tagID)) {
								$formTag = array();
								$formTag["Title"] = $tag;
								$formTag["Lang"] = $lang;
								$formTag['Created'] = $modified;
								$formTag['CreatedBy']= $this->UserID;
								$formTag['IsEnable'] = 'Y';
								$formTag['IsDeleted'] = 'N';
								$this->db->insert("wat_tags", $formTag);
								$tagID = $this->db->lastInsertId();
							}
							
							$formWatTag = array();
							$whereWatTag = array();
							$formWatTag['Modified'] = $modified;
							$formWatTag['ModifiedBy']= $this->UserID;
							$formWatTag['IsDeleted'] = 'N';
							$whereWatTag["WatItemID = ?"] = $id;
							$whereWatTag["WatTagID = ?"] = $tagID;
							$whereWatTag["Lang = ?"] = $lang;
							$affRow = $this->db->update("wat_item_tags", $formWatTag, $whereWatTag);
							if ($affRow == 0) {
								$formWatTag["WatItemID"] = $id;
								$formWatTag["WatTagID"] = $tagID;
								$formWatTag["Lang"] = $lang;
								$formWatTag['Created'] = $modified;
								$formWatTag['CreatedBy']= $this->UserID;
								$formWatTag['IsEnable'] = 'Y';
								$this->db->insert("wat_item_tags", $formWatTag);
							}
						}
					}
				}
			}
			
			// WatType
			$formWatType = array();
			$whereWatType = array();
			$formWatType["IsDeleted"] = 'Y';
			$formWatType['Modified'] = $modified;
			$whereWatType['IsDeleted = ?'] = 'N';
			$whereWatType['WatItemID = ?'] = $id;
			$this->db->update("wat_item_type", $formWatType, $whereWatType);
			if(count($param[$defaultLanguageCode]['WatType'])>0){
				foreach ($param[$defaultLanguageCode]['WatType'] as $watTypeID) {
					$formType = array();
					$whereWatType = array();
					$formType['Modified'] = $modified;
					$formType['ModifiedBy']= $this->UserID;
					$formType['IsDeleted'] = 'N';
					$whereWatType['WatItemID = ?'] = $id;
					$whereWatType['WatTypeID = ?']= $watTypeID;
					$affRow = $this->db->update("wat_item_type", $formType, $whereWatType);
					if ($affRow == 0) {
						$formType['WatItemID'] = $id;
						$formType['WatTypeID']= $watTypeID;
						$formType['Created'] = $modified;
						$formType['CreatedBy']= $this->UserID;
						$formType['IsEnable'] = 'Y';
						$this->db->insert("wat_item_type", $formType);
					}
				}
			}
			
			// Open Hour
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$where['WatItemID = ?'] = $id;
			$this->db->update("wat_item_openhour", $form, $where);
			if (count($param[$defaultLanguageCode]['OpenHour']) > 0) {
				foreach ($param[$defaultLanguageCode]['OpenHour'] as $valueJson) {
					$val = json_decode($valueJson, true);
					$form = array();
					$form['WatItemID'] = $id;
					$form['DayID'] = $val["day"];
					$form['StartTime'] = $val["startHour"] . ":" . $val["startMinute"];
					$form['EndTime'] = ($val["endHour"] == '24' ? '00' : $val["endHour"]) . ":" . $val["endMinute"];
					$form['Created'] = $modified;
					$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['IsEnable'] = 'Y';
					$form['IsDeleted'] = 'N';
					$this->db->insert("wat_item_openhour", $form);
				}
			}
			
			// Activity
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$where['IsDeleted = ?'] = 'N';
			$where['WatItemID = ?'] = $id;
			$this->db->update("wat_item_activity", $form, $where);
			if (count($param[$defaultLanguageCode]['Activity']) > 0) {
				$i = 0;
				foreach ($param[$defaultLanguageCode]['Activity'] as $key => $val) {
					$obj = json_decode($val, true);
					$i ++;
					$form = array();
					$where = array();
					$form['WatActivityID'] = $obj['TypeID'];
					$form['Ordering'] = $i;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['IsEnable'] = 'Y';
					$form['IsDeleted'] = 'N';
					$where["WatItemID = ?"] = $id;
					$where["DataCode = ?"] = $obj['ID'];
					$affectedRows = $this->db->update("wat_item_activity", $form, $where);
					if ($affectedRows == 0) {
						$form['DataCode'] = $obj['ID'];
						$form['WatItemID'] = $id;
						$form['Created'] = $modified;
						$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$affectedRows = $this->db->insert("wat_item_activity", $form);
					}
					
					if (($affectedRows > 0) && (count($obj['Lang']) > 0)) {
						foreach ($obj['Lang'] as $l => $v) {
							$formDetail = array();
							$whereDetail = array();
							$formDetail['Detail'] = $v['Detail'];
							$formDetail['Modified'] = $modified;
							$formDetail['IsEnable'] = 'Y';
							$formDetail['IsDeleted'] = 'N';
							$whereDetail["WatItemID = ?"] = $id;
							$whereDetail["DataCode = ?"] = $obj['ID'];
							$whereDetail["Lang = ?"] = $l;
							$affectedDetailRows = $this->db->update("wat_item_activity_detail", $formDetail, $whereDetail);
							if ($affectedDetailRows == 0) {
								$formDetail['WatItemID'] = $id;
								$formDetail['DataCode'] = $obj['ID'];
								$formDetail['Lang'] = $l;
								$formDetail['Created'] = $modified;
								$this->db->insert("wat_item_activity_detail", $formDetail);
							}
						}
					}
				}
			}
			
			// Rescue
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$where['IsDeleted = ?'] = 'N';
			$where['WatItemID = ?'] = $id;
			$this->db->update("wat_item_rescue", $form, $where);
			if (count($param[$defaultLanguageCode]['Rescue']) > 0) {
				$i = 0;
				foreach ($param[$defaultLanguageCode]['Rescue'] as $key => $val) {
					$obj = json_decode($val, true);
					$i ++;
					$form = array();
					$where = array();
					$form['WatRescueID'] = $obj['TypeID'];
					$form['Ordering'] = $i;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['IsEnable'] = 'Y';
					$form['IsDeleted'] = 'N';
					$where["WatItemID = ?"] = $id;
					$where["DataCode = ?"] = $obj['ID'];
					$affectedRows = $this->db->update("wat_item_rescue", $form, $where);
					if ($affectedRows == 0) {
						$form['DataCode'] = $obj['ID'];
						$form['WatItemID'] = $id;
						$form['Created'] = $modified;
						$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$affectedRows = $this->db->insert("wat_item_rescue", $form);
					}
					
					if (($affectedRows > 0) && (count($obj['Lang']) > 0)) {
						foreach ($obj['Lang'] as $l => $v) {
							$formDetail = array();
							$whereDetail = array();
							$formDetail['Detail'] = $v['Detail'];
							$formDetail['Modified'] = $modified;
							$formDetail['IsEnable'] = 'Y';
							$formDetail['IsDeleted'] = 'N';
							$whereDetail["WatItemID = ?"] = $id;
							$whereDetail["DataCode = ?"] = $obj['ID'];
							$whereDetail["Lang = ?"] = $l;
							$affectedDetailRows = $this->db->update("wat_item_rescue_detail", $formDetail, $whereDetail);
							if ($affectedDetailRows == 0) {
								$formDetail['WatItemID'] = $id;
								$formDetail['DataCode'] = $obj['ID'];
								$formDetail['Lang'] = $l;
								$formDetail['Created'] = $modified;
								$this->db->insert("wat_item_rescue_detail", $formDetail);
							}
						}
					}
				}
			}
			
			// Bank
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$where['IsDeleted = ?'] = 'N';
			$where['WatItemID = ?'] = $id;
			$this->db->update("wat_item_bankaccount", $form, $where);
			if (count($param[$defaultLanguageCode]['Bank']) > 0) {
				$i = 0;
				$fileModel = new System_Model_Fileinfo();
				foreach ($param[$defaultLanguageCode]['Bank'] as $key => $val) {
					$obj = json_decode($val, true);
					$QRCodeImage = null;
					if($obj['QRCodeImageHash']){
						$fileData = $fileModel->itemByHashName($obj['QRCodeImageHash']);
						$QRCodeImage = empty($fileData->ID) ? null : $fileData->ID;
					}
					$i ++;
					$form = array();
					$where = array();
					$form['WatBankID'] = $obj['WatBankID'];
					$form['WatBakaccounttypeID'] = $obj['WatBakaccounttypeID'];
					$form['BranchName'] = $obj['BranchName'];
					$form['AccountName'] = $obj['AccountName'];
					$form['AccountNo'] = $obj['AccountNo'];
					$form['QRCodeImageID'] = $QRCodeImage;
					$form['Ordering'] = $i;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['IsEnable'] = 'Y';
					$form['IsDeleted'] = 'N';
					$where["WatItemID = ?"] = $id;
					$where["DataCode = ?"] = $obj['ID'];
					$affectedRows = $this->db->update("wat_item_bankaccount", $form, $where);
					if ($affectedRows == 0) {
						$form['DataCode'] = $obj['ID'];
						$form['WatItemID'] = $id;
						$form['Created'] = $modified;
						$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$affectedRows = $this->db->insert("wat_item_bankaccount", $form);
					}
				}
			}

			// PromptPay
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'kittinan' . DS . 'php-promptpay-qr' . DS . 'src' . DS . 'PromptPay.php';
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'kittinan' . DS . 'php-crc' . DS . 'src' . DS . 'CRCInterface.php';
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'kittinan' . DS . 'php-crc' . DS . 'src' . DS . 'AbstractCRC.php';
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'kittinan' . DS . 'php-crc' . DS . 'src' . DS . 'CRC16.php';
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'kittinan' . DS . 'php-crc' . DS . 'src' . DS . 'CRC16CCITT.php';
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'bacon' . DS . 'bacon-qr-code' . DS . 'autoload_register.php';
			$pp = new \KS\PromptPay();

			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$where['IsDeleted = ?'] = 'N';
			$where['WatItemID = ?'] = $id;
			$this->db->update("wat_item_promptpayaccount", $form, $where);
			if (count($param[$defaultLanguageCode]['PromptPay']) > 0) {
				$i = 0;
				$fileModel = new System_Model_Fileinfo();
				foreach ($param[$defaultLanguageCode]['PromptPay'] as $key => $val) {
					$obj = json_decode($val, true);

					$select = $this->db->select()
					->from(array('tbl' => 'wat_item_promptpayaccount'), array("PromptPayNo","QRCodeImageID"))
					->where("DataCode = ?", $obj['ID'])
					;
					$stmt = $this->db->query($select);
					$data = $stmt->fetchObject();

					if($data->PromptPayNo != $obj['PromptPayNo'] || empty($data->QRCodeImageID)){
						$imgPath = APPLICATION_ROOT . DS .'tmp' . DS . 'qrcode' . DS . 'PromptPay-' . $obj['PromptPayNo'] . '.png';
						$pp->generateQrCode($imgPath, $obj['PromptPayNo'], null, '300');
						$resultfile = $this->uploadfile($imgPath);
						if($resultfile->result){
							$QRCodeImage = $resultfile->data->id;
						}
					}else{
						$QRCodeImage = $data->QRCodeImageID;
					}

					$i ++;
					$form = array();
					$where = array();
					$form['PromptPayName'] = $obj['PromptPayName'];
					$form['PromptPayNo'] = $obj['PromptPayNo'];
					$form['QRCodeImageID'] = empty($QRCodeImage) ? null : $QRCodeImage;
					$form['Ordering'] = $i;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['IsEnable'] = 'Y';
					$form['IsDeleted'] = 'N';
					$where["WatItemID = ?"] = $id;
					$where["DataCode = ?"] = $obj['ID'];
					$affectedRows = $this->db->update("wat_item_promptpayaccount", $form, $where);
					if ($affectedRows == 0) {
						$form['DataCode'] = $obj['ID'];
						$form['WatItemID'] = $id;
						$form['Created'] = $modified;
						$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$affectedRows = $this->db->insert("wat_item_promptpayaccount", $form);
					}
				}
			}

			// Delete All File
            $formFile = array();
            $whereIdDelete = array();
            $formFile['Modified'] = $modified;
            $formFile['ModifiedBy']= $this->UserID;
            $formFile['IsDeleted']= 'Y';
            $whereIdDelete['IsDeleted = ? '] = 'N';
            $whereIdDelete['WatItemID = ? '] = $id;
            $this->db->update("wat_item_file", $formFile, $whereIdDelete);

            if(count($params['GalleryIds'])>0){
                foreach ($params['GalleryIds'] as $fileID){
                    $formFile = array();
                    $whereFile = array();
                    $formFile['Modified'] = $modified;
                    $formFile['ModifiedBy']= $this->UserID;
                    $formFile['IsDeleted']= 'N';
                    $whereFile['FileInfoID = ? '] = $fileID;
                    $whereFile['WatItemID = ? '] = $id;
                    $updateResult = $this->db->update("wat_item_file", $formFile, $whereFile);
                    if(!$updateResult){
                        $formFile = array();
                        $whereFile = array();
                        $formFile['WatItemID'] = $id;
                        $formFile['FileInfoID']= $fileID;
                        $formFile['FileType']= 'image';
                        $formFile['Created'] = $modified;
                        $formFile['CreatedBy']= $this->UserID;
                        $formFile['IsEnable'] = 'Y';
                        $formFile['IsDeleted'] = 'N';
                        $this->db->insert("wat_item_file", $formFile);
                    }
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
			$result = $this->db->update("wat_item_info", $form, $where);
			
			// Category
			$formCategory = array();
			$whereCategory = array();
			$formCategory['IsDeleted'] = 'Y';
			$formCategory['Modified'] = $modified;
			$formCategory['ModifiedBy'] = $this->UserID;
			$whereCategory['IsDeleted = ? '] = 'N';
			$whereCategory['WatItemID = ? '] = $params['id'];
			$this->db->update("wat_category_item", $formCategory, $whereCategory);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	public function getOption($lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'wat_item_info'), array( 'ID'))
		->joinInner(array('d' => 'wat_item_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'=>'Title'))
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
	
	function getCategories($watItemID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'wat_category_item'), '')
			->columns(array(
					"WatCategoryID" => "tbl.WatCategoryID"
			))
			->where("tbl.WatItemID = ? ", $watItemID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getFiles($watItemID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'wat_item_file'), '')
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
			->where("tbl.WatItemID = ? ", $watItemID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getAllKeyword($searchText, $lang) {
				
		// set query
		$select = $this->db->select()->distinct()
		->from(array('tbl' => 'wat_tags'), array('ID', 'Title'))
		->where("tbl.IsEnable = 'Y' ")
		->where("tbl.IsDeleted = 'N' ")
		->limitPage("1", "10")
		;
		if (!empty($lang)) {
			$select = $select->where("tbl.Lang = ?", $lang);
		}
		if(!empty($searchText)){
			$select = $select->where("tbl.Title like '%" . $searchText . "%'");
			$select = $select->order("INSTR(tbl.Title, '" . $searchText . "')");
		}else{
			$select = $select->where("tbl.ID = '-1' ");
		}
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		 
		return $data;
	}
	
	function getAllKeywordByItem($watItemID, $lang) {
		
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'wat_item_tags'), '')
			->joinInner(array('t' => 'wat_tags'), "tbl.WatTagID = t.ID and tbl.Lang = t.Lang", '')
			->columns(array(
					"Title" => "t.Title",
					"Lang"=>"t.Lang",
					"ID"=>"t.ID"
			))
			->where("tbl.WatItemID = ? ", $watItemID)
			->where("tbl.Lang = ? ", $lang)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getWatTypes($watItemID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'wat_item_type'), array("*"))
			->where("tbl.WatItemID = ? ", $watItemID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	public function getOpenHours($id, $lang) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'wat_item_openhour'), array("ID", "WatItemID", "DayID", "StartTime", "EndTime"))
			->where("tbl.WatItemID = ? ", $id)
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->order("tbl.DayID")
			->order("tbl.StartTime")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["StartTime"] = empty($data[$i]["StartTime"]) ? null : new DateTime($data[$i]["StartTime"]);
					$data[$i]["EndTime"] = empty($data[$i]["EndTime"]) ? null : new DateTime($data[$i]["EndTime"]);
				}
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	public function getActivitiesByLanguage($watItemID, $lang) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('m' => 'wat_item_info'), "")
			->joinInner(array("aa" => "wat_item_activity"), "m.ID = aa.WatItemID", "")
			->joinInner(array("aad" => "wat_item_activity_detail"), "aa.DataCode = aad.DataCode and aad.Lang = '{$lang}' ", "")
			->joinLeft(array("aat" => "wat_activity_info"), "aa.WatActivityID = aat.ID", "")
			->joinLeft(array("aatd" => "wat_activity_detail"), "aat.ID = aatd.ID and aatd.Lang = '{$lang}' ", "")
			->columns(array(
					"aa.WatItemID",
					"aa.DataCode",
					"aa.WatActivityID",
					"aa.Ordering",
					"aad.Lang",
					"aad.Detail",
					"ActivityName" => "aatd.Name",
			))
			->where("m.ID = ?", $watItemID)
			->where("aa.IsDeleted = 'N' ")
			->order("aa.Ordering")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	public function getRescuesByLanguage($watItemID, $lang) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('m' => 'wat_item_info'), "")
			->joinInner(array("aa" => "wat_item_rescue"), "m.ID = aa.WatItemID", "")
			->joinInner(array("aad" => "wat_item_rescue_detail"), "aa.DataCode = aad.DataCode and aad.Lang = '{$lang}' ", "")
			->joinLeft(array("aat" => "wat_rescue_info"), "aa.WatRescueID = aat.ID", "")
			->joinLeft(array("aatd" => "wat_rescue_detail"), "aat.ID = aatd.ID and aatd.Lang = '{$lang}' ", "")
			->columns(array(
					"aa.WatItemID",
					"aa.DataCode",
					"aa.WatRescueID",
					"aa.Ordering",
					"aad.Lang",
					"aad.Detail",
					"RescueName" => "aatd.Name",
			))
			->where("m.ID = ?", $watItemID)
			->where("aa.IsDeleted = 'N' ")
			->order("aa.Ordering")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	public function getBankAccountByLanguage($watItemID, $lang) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'wat_item_info'), "")
			->joinLeft(array("b" => "wat_item_bankaccount"), "tbl.ID = b.WatItemID", "")
			->joinLeft(array("f" => "file_info"), "b.QRCodeImageID = f.ID ", "")
			->columns(array(
					"b.DataCode",
					"b.WatItemID",
					"b.WatBankID",
					"b.WatBakaccounttypeID",
					"b.BranchName",
					"b.AccountName",
					"b.AccountNo",
					"b.QRCodeImageID",
					"b.Ordering",

					"QRCodeImageFileName" => "f.Name",
					"QRCodeImageHashName" => "f.HashName",
					"QRCodeImageFileType" => "f.FileType",
					"QRCodeImageFileSize" => "f.FileSize",
					"QRCodeImageFileExtension" => "f.Extension",
			))
			->where("tbl.ID = ?", $watItemID)
			->where("b.IsDeleted = 'N' ")
			->order("b.Ordering")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	public function getPromptPayAccountByLanguage($watItemID, $lang) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'wat_item_info'), "")
			->joinLeft(array("b" => "wat_item_promptpayaccount"), "tbl.ID = b.WatItemID", "")
			->joinLeft(array("f" => "file_info"), "b.QRCodeImageID = f.ID ", "")
			->columns(array(
					"b.DataCode",
					"b.WatItemID",
					"b.PromptPayName",
					"b.PromptPayNo",
					"b.QRCodeImageID",
					"b.Ordering",

					"QRCodeImageFileName" => "f.Name",
					"QRCodeImageHashName" => "f.HashName",
					"QRCodeImageFileType" => "f.FileType",
					"QRCodeImageFileSize" => "f.FileSize",
					"QRCodeImageFileExtension" => "f.Extension",
			))
			->where("tbl.ID = ?", $watItemID)
			->where("b.IsDeleted = 'N' ")
			->order("b.Ordering")
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
					$affRow += $this->db->update("wat_item_info", $form, $where);
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
					->from(array('tbl'=>'wat_item_info'), array(
						'WatSectID',
						'WatProvinceCode',
						'IntroImageID',
						'TemplateType',
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
					$form['IntroImageID'] = empty($dataInfo->IntroImageID) ? null : $dataInfo->IntroImageID;
					$form['WatSectID'] = empty($dataInfo->WatSectID) ? null : $dataInfo->WatSectID;
					$form['WatProvinceCode'] = empty($dataInfo->WatProvinceCode) ? null : $dataInfo->WatProvinceCode;
					$form['TemplateType'] = empty($dataInfo->TemplateType) ? 1 : $dataInfo->TemplateType;
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
					$data->result = $this->db->insert("wat_item_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'wat_item_detail'), array('Lang','Title','IntroText','Detail','Source'))
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
								$form['Source'] = empty($d['Source']) ? null : $d['Source'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("wat_item_detail", $form);
							}
						}

						// Category Article
						$selectCatArt = $this->db->select ()
						->from(array('tbl'=>'wat_category_item'), array('WatCategoryID'))
						->where("tbl.WatItemID = ? ", $id)
						;
						$stmtCatArt = $this->db->query($selectCatArt);
						$dataCatArt = $stmtCatArt->fetchAll();

						if(count($dataCatArt)>0){
							foreach($dataCatArt as $d){
								$form = array();
								$form['WatItemID'] = $tmpID;
								$form['WatCategoryID'] = empty($d['WatCategoryID']) ? null : $d['WatCategoryID'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("wat_category_item", $form);
							}
						}

						// // Tag
						// $selectTag = $this->db->select ()
						// ->from(array('tbl'=>'content_article_tags'), array('Title'))
						// ->where("tbl.ContentArticleID = ? ", $id)
						// ;
						// $stmtTag = $this->db->query($selectTag);
						// $dataTag = $stmtTag->fetchAll();
						// if(count($dataTag)>0){
						// 	foreach($dataTag as $d){
						// 		$form = array();
						// 		$form['ContentArticleID'] = $tmpID;
						// 		$form['Title'] = empty($d['Title']) ? null : $d['Title'];
						// 		$form['Created'] = $modified;
						// 		$form['CreatedBy'] = $this->UserID;
						// 		$this->db->insert("content_article_tags", $form);
						// 	}
						// }

						// File
						$selectFile = $this->db->select ()
						->from(array('tbl'=>'wat_item_file'), array('FileInfoID','FileType'))
						->where("tbl.WatItemID = ? ", $id)
						;
						$stmtFile = $this->db->query($selectFile);
						$dataFile = $stmtFile->fetchAll();

						if(count($dataFile)>0){
							foreach($dataFile as $d){
								$form = array();
								$form['WatItemID'] = $tmpID;
								$form['FileInfoID'] = empty($d['FileInfoID']) ? null : $d['FileInfoID'];
								$form['FileType'] = empty($d['FileType']) ? null : $d['FileType'];
								$form['DownloadAmount'] = 0;
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("wat_item_file", $form);
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
					$affRow += $this->db->update("wat_item_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	private function uploadfile($imgPath) {
		$result = new stdClass();
		$result->result = false;
		$result->message = '';
		$result->data = null;

		if(file_exists($imgPath)){
			
			$uploadRootPath = APPLICATION_ROOT . DS . 'files';
			$storePath = '/' . date('Y') . '/' . date('m') . '/' . date('d');
			
			$pathToUploads = $uploadRootPath . $storePath;
			$pathToUploads = str_replace('/', DS, $pathToUploads);
			$pathToUploads = str_replace("\\", DS, $pathToUploads);
			
			if ($this->createDirectory($pathToUploads)) {

				$imgInfo = pathinfo($imgPath);
				$baseName = $imgInfo['basename'];
				$fileSize = filesize($imgPath);
				$fileType = Emwork_Utility_File::returnMIMEType($baseName);
				$fileName = $imgInfo['filename'];
				$ext = $imgInfo['extension'];
				
				$hashFileName = date('Ymd') . md5($fileName) . date('His');
				$hashBaseName = $hashFileName . '.' . $ext;
				
				$resultFile = rename($imgPath, $pathToUploads . DS . $hashBaseName);
				if ($resultFile) {
					$modelFile = new System_Model_Fileinfo();
					$data = $modelFile->save($baseName, $hashBaseName, $storePath, $fileSize, $ext, $fileType);
					if ($data->result) {
						$dataResult = new stdClass();
						$dataResult->id = $data->id;
						$dataResult->name = $baseName;
						$dataResult->hashname = $hashBaseName;
						$dataResult->filesize = $fileSize;
						$dataResult->filetype = $fileType;
						
						$result->result = true;
						$result->data = $dataResult;						
					} else {
						$result->message = 'Save file fail';
					}
				} else {
					$result->message = 'Transfer file fail';
				}				
			} else {
				$result->message = 'Create folder fail';
			}
		}else{
			$result->message = 'File not exists';
		}
		return $result;
	}

	private function createDirectory($pathToUploads) {
		$result = false;
		if (file_exists($pathToUploads)) {
			$result = true;
		} else {
			$result = mkdir($pathToUploads, 0777, true);
		}
		return $result;
	}
}

?>
