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

	function updateIcon($limitupdate , $lang = 'th'){
		$data = array();

		try {
			$select = $this->db->select()
				->from(array('tbl' => 'content_article_info'), '')
				->columns(array(
						"ID" => "tbl.ID",
						// "Created" => "tbl.Created",
						"Modified" => "tbl.Modified",
				))
				->where("tbl.IsEnable = 'Y' ")
				->where("tbl.IsDeleted ='N'")
				->order('tbl.Modified desc')
				->limit($limitupdate)
				;
				$stmt = $this->db->query($select);
				$data = $stmt->fetchAll();

			}catch (Exception $e) {
				echo $e->getMessage();
			}
			return $data;
	}

	function newIcon($limitnew , $lang = 'th'){
		$data = array();

		try {
			$select = $this->db->select()
				->from(array('tbl' => 'content_article_info'), '')
				->columns(array(
						"ID" => "tbl.ID",
						"Created" => "tbl.Created",
						// "Modified" => "tbl.Modified",
				))
				->where("tbl.IsEnable = 'Y' ")
				->where("tbl.IsDeleted ='N'")
				->order('tbl.Created desc')
				->limit($limitnew)
				;

				$stmt = $this->db->query($select);
				$data = $stmt->fetchAll();
			}catch (Exception $e) {
				echo $e->getMessage();
			}
			return $data;
	}
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			
			$select = $this->db->select()
			->from(array('tbl' => 'wat_item_info'), '')
			->joinLeft(array('d' => 'wat_item_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('s' => "wat_sect_info"), "tbl.WatSectID = s.ID", "")
			->joinLeft(array('sd' => "wat_sect_detail"), "s.ID = sd.ID and sd.Lang = '{$lang}' ", "")
			->joinLeft(array('dd' => "wat_district_info"), "tbl.WatDistrictCode = dd.Code", "")
			->joinLeft(array('ddd' => "wat_district_detail"), "dd.Code = ddd.Code and ddd.Lang = '{$lang}' ", "")
			->joinLeft(array('p' => "wat_province_info"), "tbl.WatProvinceCode = p.Code", "")
			->joinLeft(array('pd' => "wat_province_detail"), "p.Code = pd.Code and pd.Lang = '{$lang}' ", "")
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
					"PublishDate" => "tbl.PublishDate",
					"IsEnable" => "tbl.IsEnable",
					"AuthorName" => "concat(u.FirstName, ' ', u.LastName)",
					"SectTitle" => "sd.Title",
					"WatProvinceCode",
					"ProvinceName" => "pd.Name",
					"WatDistrictCode",
					"DistrictName" => "ddd.Name",
					
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
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			//->order('tbl.Created desc')
			;
			
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("d.Title like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['isCat'])) {
				$select = $select->distinct()
				->joinInner(array('ca' => "wat_category_item"), "tbl.ID = ca.WatItemID", '') 
				->where("ca.WatCategoryID = ? ", $advancesearch['isCat'])
				->where("ca.IsDeleted = 'N' ")
				;
			}
			if (!empty($advancesearch['RegionID'])) {
				$val = $advancesearch['RegionID'];
				$select = $select->where("p.WatRegionID = " . $this->db->quote($val));
			}
			if (!empty($advancesearch['ProvinceCode'])) {
				$val = $advancesearch['ProvinceCode'];
				$select = $select->where("tbl.WatProvinceCode = " . $this->db->quote($val));
			}
			if (!empty($advancesearch['WatTypeID'])) {
				$val = $advancesearch['WatTypeID'];
				$select = $select->distinct()
				->joinInner(array('wit' => "wat_item_type"), "tbl.ID = wit.WatItemID", '')
				->where("wit.WatTypeID = ? ", $val)
				->where("wit.IsDeleted = 'N' ")
				;
			}

			$records = $this->getListCount($select);
			
			if (empty($sidx)) {
				$sidx = "Created";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			;
			if ($rows > 0) {
				$select = $select->limitPage($page, $rows);
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
					$data[$i]["PublishDate"] = empty($data[$i]["PublishDate"]) ? null : new DateTime($data[$i]["PublishDate"]);
				}
			}

		} catch (Exception $e) {
			
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
					//"MetaData" => "tbl.MetaData",
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

					"IsDharmaPracticePlace"=>"tbl.IsDharmaPracticePlace",
					"DharmaPracticePlaceNo"=>"tbl.DharmaPracticePlaceNo",
					"DharmaPracticePlaceResolutionNo"=>"tbl.DharmaPracticePlaceResolutionNo",
					
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
			->joinLeft(array('sdd' => "wat_subdistrict_info"), "tbl.WatSubDistrictCode = sdd.Code", "")
			->joinLeft(array('sddd' => "wat_subdistrict_detail"), "sdd.Code = sddd.Code and sddd.Lang = '{$lang}' ", "")
			->joinLeft(array('dd' => "wat_district_info"), "tbl.WatDistrictCode = dd.Code", "")
			->joinLeft(array('ddd' => "wat_district_detail"), "dd.Code = ddd.Code and ddd.Lang = '{$lang}' ", "")
			->joinLeft(array('p' => "wat_province_info"), "tbl.WatProvinceCode = p.Code", "")
			->joinLeft(array('pd' => "wat_province_detail"), "p.Code = pd.Code and pd.Lang = '{$lang}' ", "")
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->joinLeft(array("fc" => "file_info"), "tbl.CoverImageID = fc.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"IntroImageID" => "tbl.IntroImageID",
					"TemplateType" => "tbl.TemplateType",
					//"WatTypeID" => "tbl.WatTypeID",
					"WatSectID" => "tbl.WatSectID",
					"WatSectTitle" => "sd.Title",
					"WatKindID" => "tbl.WatKindID",
					"WatClassID" => "tbl.WatClassID",
					"WatSubDistrictCode" => "tbl.WatSubDistrictCode",
					"WatSubDistrictName" => "sddd.Name",
					"WatDistrictCode" => "tbl.WatDistrictCode",
					"WatDistrictName" => "ddd.Name",
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

					"IsDharmaPracticePlace"=>"tbl.IsDharmaPracticePlace",
					"DharmaPracticePlaceNo"=>"tbl.DharmaPracticePlaceNo",
					"DharmaPracticePlaceResolutionNo"=>"tbl.DharmaPracticePlaceResolutionNo",
					
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
	
	function getFiles($watItemID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'wat_item_file'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
			->columns(array(
					"WatItemFileID" => "tbl.ID",
					"DownloadAmount" => "tbl.DownloadAmount",
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
	
	function getWatTypes($watItemID, $lang) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'wat_item_type'), array("*"))
			->joinInner(array("t" => "wat_type_info"), "tbl.WatTypeID = t.ID", "")
			->joinInner(array("td" => "wat_type_detail"), "t.ID = td.ID and td.Lang = '{$lang}' ", "")
			->columns(array(
					"WatTypeName" => "td.Name"
			))
			->where("tbl.WatItemID = ? ", $watItemID)
			->where("tbl.IsDeleted = 'N' ")
			->where("t.IsDeleted = 'N' ")
			->where("t.IsEnable = 'Y' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	public function getOpenHours($watItemID, $lang) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'wat_item_openhour'), array("ID", "WatItemID", "DayID", "StartTime", "EndTime"))
			->where("tbl.WatItemID = ? ", $watItemID)
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
			->joinLeft(array("bi" => "wat_bank_info"), "b.WatBankID = bi.ID", "")
			->joinLeft(array("bid" => "wat_bank_detail"), "bi.ID = bid.ID and bid.Lang = '{$lang}' ", "")
			->joinLeft(array("fb" => "file_info"), "bi.LogoImageID = fb.ID ", "")
			->columns(array(
					"b.DataCode",
					"b.AccountName",
					"b.AccountNo",
					"b.Ordering",

					"QRCodeImageHashName" => "f.HashName",

					"BankColor" => "bi.Color",
					"BankName" => "bid.Name",

					"BankLogoHashName" => "fb.HashName",
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
					"b.PromptPayName",
					"b.PromptPayNo",
					"b.Ordering",

					"QRCodeImageHashName" => "f.HashName",
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
	
	public static function saveView($watItemID, $memberID, $userID, $lang, $accessFrom) {
		$data = new stdClass();
		$data->result = false;
		try {
			$db = Zend_Db_Table::getDefaultAdapter();
			$request = Zend_Controller_Front::getInstance()->getRequest();
			
			$created = date("Y-m-d H:i:s");
			$form = array();
			$form["WatItemID"] = $watItemID;
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
			$affectedRows = $db->insert("wat_item_view", $form);
			
			if ($affectedRows) {
				$select = $db->select()->from(array("tbl" => "wat_item_view"), array("TotalView" => "count(tbl.ID)"))
				->where("WatItemID = ?", $watItemID)
				;
				$stmt = $db->query($select);
				$dataTotal = $stmt->fetchObject();
				
				$formSummary = array();
				$whereSummary = array();
				$formSummary["ViewAmount"] = $dataTotal->TotalView;
				$whereSummary["ID = ?"] = $watItemID;
				$affectedRows = $db->update("wat_item_info", $formSummary, $whereSummary);
			}
			$data->result = true;
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	public function saveDownload($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$selectFile = $this->db->select()
			->from(array('tbl' => 'wat_item_file'), array('ID','DownloadAmount'))
			->where("tbl.ID = ?", $params['WatItemFileID'])
			;
			$stmtFile = $this->db->query($selectFile);
			$dataFile = $stmtFile->fetchObject();
			$DownloadAmount = empty($dataFile->DownloadAmount) ? 1 : ($dataFile->DownloadAmount+1);
			
			$modified = date("Y-m-d H:i:s");
			$form = array();
			$form["DownloadAmount"] = $DownloadAmount;
			$form["Modified"] = $modified;
			$form["ModifiedBy"] = empty($this->UserID) ? null : $this->UserID;
			$where['ID = ? '] = $params['WatItemFileID'];
			$data->result = $this->db->update("wat_item_file", $form, $where);
			$data->download_amount = $DownloadAmount;
			
		} catch (Exception $e) {
			
		}
		return $data;
	}
}

?>
