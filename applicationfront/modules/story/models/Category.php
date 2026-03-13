<?php
class Story_Model_Category {
	
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
			$this->db = Zend_Db_Table::getDefaultAdapter ();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance ();
			$this->auth = $authobj->getIdentity ();
			$this->UserID = isset ( $this->auth->UserID ) ? $this->auth->UserID : '';
		} catch ( Exception $e ) {
			
		}
	}
	public function getOption($lang, $parentID = null) {
		
		$select = $this->db->select()
		->from ( array ('tbs' => 'story_category_info' ), array ('ID') )
		->joinInner ( array ('d' => 'story_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->order ( "tbs.Ordering ASC" )
		->order ( "convert(d.Title USING TIS620)  ASC" );
		
		if (!empty($parentID)) {
			$select = $select->where("tbs.ParentID = ?", $parentID);
		}
		
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array ();
		
		foreach ( $data as $value ) {
			$result [$value ["ID"]] = $value ["Title"];
		}
		return $result;
	}
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array ();
		try {
			
			// set query
			$select = $this->db->select ()
			->from ( array ('tbs' => 'story_category_info'), '')
			->joinLeft ( array ("d" => "story_category_detail" ), "tbs.ID = d.ID and d.Lang = '{$lang}' ", '')
			->joinLeft(array("fi" => "file_info"), "tbs.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbs.ID",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Ordering" => "tbs.Ordering",
					"Created" => "tbs.Created",
					"CreatedBy" => "tbs.CreatedBy",
					"Modified" => "tbs.Modified",
					"ModifiedBy" => "tbs.ModifiedBy",
					"IsEnable" => "tbs.IsEnable",
					"IsDeleted" => "tbs.IsDeleted",
					
					"IntroImageID" => "tbs.IntroImageID",
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where ( "tbs.IsEnable = 'Y' " )
			->where ( "tbs.IsDeleted = 'N' " )
			;
			
			// set criteria			
			if (! empty ( $advancesearch ['parent_id'] )) {
				$select = $select->where ( "tbs.ParentID = ? ", $advancesearch ['parent_id'] );
			} else {
				$select = $select->where ( "tbs.ParentID is null ");
			}
			
			// find total
			$records = $this->getListCount ( $select );
			
			// set ordering and total
			if (empty ( $sidx )) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select ()
			->from ( array ('tbs' => $select ), array ('*'))
			->columns ( array ("RECORD" => "abs($records)" ) )
			->limitPage ( $page, $rows )
			;
			if (in_array($rowSort, array('Title'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchAll ();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
				}
			}

		} catch ( Exception $e ) {
			echo $e->getMessage ();
		}
		return $data;
	}
	function getListCount($select) {
		$select = $this->db->select ()
		->from ( array ('T' => $select), array ("RECORDS" => "count(T.ID)") )
		;
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchObject ();
		return $data->RECORDS;
	}

	function getInfo($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbs' => 'story_category_info'), array (
					"ID",
					"Ordering",
					"IsDeleted",
					"IsEnable"
			))
			->where ( "tbs.IsDeleted = 'N' " )
			->where ( "tbs.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
			
		}
		return $data;
	}

	function getDetails($id) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbs' => 'story_category_info'), "")
			->joinLeft(array("d" => "story_category_detail"), "tbs.ID = d.ID", "")
			->columns(array(
					"d.ID",
					"d.Name" ,
					"d.IsEnable",
					"d.Lang"
			))
			->where ( "tbs.IsDeleted = 'N'" )
			->where ( "tbs.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchAll ();
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	
	public function getDataByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('m' => 'story_category_info'), "")
			->joinLeft(array("md" => "story_category_detail"), "m.ID = md.ID and md.Lang = '{$lang}' ", "")
			->joinLeft(array("fi" => "file_info"), "m.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "m.ID",
					"Lang" => 'md.Lang',
					"ParentID" => 'm.ParentID',
					"Title" => "md.Title",
					"IntroText" => "md.IntroText",
					"Detail" => "md.Detail",
					"Ordering" => "m.Ordering",
					"IsDeleted" => "m.IsDeleted",
					"IsEnable" => "m.IsEnable",
					
					"IntroImageID" => "m.IntroImageID",
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where("m.ID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	
	/******************** Setting ***********************/	
	function getSettings($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbs' => 'story_category_info'), array (
					"Settings"
			))
			->where ( "tbs.IsDeleted = 'N' " )
			->where ( "tbs.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
			
		}
		return $data;
	}

	function getInfoStory($iid, $lang = 'th') {
		$data = new stdClass();
		try {

			$select = $this->db->select()
			->from(array('tbl' => 'story_article_info'), '')
			->joinLeft(array('d' => 'story_article_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('c' => 'story_category_info'),"tbl.StoryCategoryID = c.ID ", '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"ViewAmount" => "tbl.ViewAmount",
					"TemplateType" => "tbl.TemplateType",
					"Ordering" => "tbl.Ordering",
					"CanComment" => "tbl.CanComment",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
					"tbl.SocialNetworkFacebook",
					"tbl.SocialNetworkTwitter",
					"tbl.SocialNetworkGoogleplus",
					"tbl.ShowQrcode",
					"tbl.ShowPrint",
					
					"FileName" => "fi.Name",
					"FileHashName" => "fi.HashName",
					"FileType" => "fi.FileType",
					"FileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",

					"StoryCategoryID" => "tbl.StoryCategoryID",
					"StoryCategoryParentID" => "c.ParentID",
					"Settings" => "c.Settings"
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted ='N'")
			->where("tbl.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("case when tbl.PublishDown is null then 1 else (case when tbl.PublishDown >= ? then 1 else 0 end) end = 1", date('Y-m-d H:i:s'))
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			->where("tbl.ID = ? " , $iid)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();

			if (count($data) > 0) {
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
			}

		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getInfoDataStory($iid, $lang) {
		$resultInfoData = array();
		try {
			
			$select = $this->db->select()
			->from(array("tbl" => "story_article_data"), "")
			->columns(array(
				"tbl.StoryArticleID",
				"tbl.Lang",
				"tbl.DataCode",
				"tbl.DataType",
				"tbl.Ordering",
			))
			->where("tbl.StoryArticleID = ?", $iid)
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.IsDeleted = 'N'")
			->order("tbl.Ordering ASC")
			->where("tbl.Lang =". '"'.$lang.'"')
			;
			
			$stmt = $this->db->query($select);
			$resultInfoData = $stmt->fetchAll();
			
			if(count($resultInfoData)>0) {


				foreach ($resultInfoData as $info_key => $info_value) {
					
					if($info_value['DataType'] == 'G') {
						
						$select = $this->db->select()
						->from(array("tbl" => "story_article_data_gallery"), "")
						->joinLeft(array("f" => "file_info"), "tbl.FileInfoID = f.ID", "")
						->columns(array(
								"f.Name",
								"f.HashName",
								"Size" => "f.FileSize",
								"Type" => "f.FileType",
								"FilePath" => "f.Path"
						))
						->where("tbl.StoryArticleID = ?", $info_value['StoryArticleID'])
						->where("tbl.Lang = ?", $info_value['Lang'])
						->where("tbl.DataCode = ?", $info_value['DataCode'])
						->where("tbl.IsEnable = 'Y' ")
						->where("tbl.IsDeleted = 'N' ")
						;
						$stmt = $this->db->query($select);
						$resultGallery = $stmt->fetchAll();
						
						if(count($resultGallery)>0) {
							$resultGalleries = array();
							$i = 0;
							foreach ($resultGallery as $gallery) {
								$i ++;
								$resultGalleries[$i]['FileData'] = new stdClass();
								$resultGalleries[$i]['FileData']->Name = $gallery["Name"];
								$resultGalleries[$i]['FileData']->HashName = $gallery["HashName"];
								$resultGalleries[$i]['FileData']->Type = $gallery["Type"];
								$resultGalleries[$i]['FileData']->Size = $gallery["Size"];
								$resultGalleries[$i]['FileData']->FilePath = $gallery["FilePath"];
							}
							$resultInfoData[$info_key]['Data'] = $resultGalleries;
						} else {
							unset($resultInfoData[$info_key]);
						}

					} else if($info_value['DataType'] == 'D') {
						
						$select = $this->db->select()
						->from(array("tbl" => "story_article_data_description"), "Detail")
						->where("tbl.DataCode = ?", $info_value['DataCode'])
						->where("tbl.IsEnable = 'Y' ")
						->where("tbl.IsDeleted = 'N' ")
						->where("tbl.Lang =". '"'.$lang.'"')
						;
						$stmt = $this->db->query($select);
						$resultDescription = $stmt->fetchAll();
						
						$resultInfoData[$info_key]['Data'] = $resultDescription;
						
					} else if($info_value['DataType'] == 'M') {
						
						$select = $this->db->select()
						->from(array("tbl" => "story_article_data_multimedia"), "")
						->joinLeft(array("f" => "file_info"), "tbl.FileInfoID = f.ID", "")
						->columns(array(
								"tbl.DataCode",
								"tbl.Type",
								"tbl.YoutubeUrl",
								"f.Name",
								"f.HashName",
								"f.FileSize",
								"f.FileType"
						))
						->where("tbl.StoryArticleID = ?", $info_value['StoryArticleID'])
						->where("tbl.Lang = ?", $info_value['Lang'])
						->where("tbl.DataCode = ?", $info_value['DataCode'])
						->where("tbl.IsEnable = 'Y' ")
						->where("tbl.IsDeleted = 'N' ")
						;
						$stmt = $this->db->query($select);
						$resultMedia = $stmt->fetchAll();
						
						if(count($resultMedia)>0) {
							$resultMedias = array();
							$i = 0;
							foreach ($resultMedia as $media) {
								$i ++;
								$resultMedias[$i]["Type"] = $media["Type"];
								$resultMedias[$i]["YoutubeUrl"] = $media["YoutubeUrl"];
								$resultMedias[$i]["DataCode"] = $media["DataCode"];
								$resultMedias[$i]['FileData'] = new stdClass();
								$resultMedias[$i]['FileData']->Name = $media["Name"];
								$resultMedias[$i]['FileData']->HashName = $media["HashName"];
								$resultMedias[$i]['FileData']->Type = $media["FileType"];
								$resultMedias[$i]['FileData']->Size = $media["FileSize"];
							}
							$resultInfoData[$info_key]['Data'] = $resultMedias;
						} else {
							unset($resultInfoData[$info_key]);
						}
						
					}
					
				}
			}
		} catch (Exception $e) {
			
		}
		return $resultInfoData;
	}

	function getCommentList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			$fullname = 'concat(ifnull(u.TitleName,""), ifnull(u.FirstName,""), " ", ifnull(u.LastName,""))';
			$select = $this->db->select()
			->from(array('tbl' => 'story_article_comment'), "")
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
			->where("tbl.StoryArticleID = ?", $advancesearch['StoryArticleID'])
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
			$form["StoryArticleID"] = $params['hdfID'];
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
			$data->result = $this->db->insert("story_article_comment", $form);

		} catch (Exception $e) {

		}
		return $data;
	}

	public function getCoverListByLanguage($storyArticleID, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('m' => 'story_article_cover'), "")
			->joinLeft(array(
				"md" => "story_article_cover_detail"), 
				"m.StoryArticleID = md.StoryArticleID and m.DataCode = md.DataCode and md.Lang = '{$lang}' ", 
				"")
			->joinLeft(array("fi" => "file_info"), "m.FileInfoID = fi.ID ", "")
			->columns(array(
					"StoryArticleID" => "m.StoryArticleID",
					"DataCode" => "m.DataCode",
					"Lang" => 'md.Lang',
					"Title" => "md.Title",
					"IntroText" => "md.IntroText",
					"Ordering" => "m.Ordering",
					"IsDeleted" => "m.IsDeleted",
					"IsEnable" => "m.IsEnable",
					
					"FileInfoID" => "m.FileInfoID",
					"FileInfoFileName" => "fi.Name",
					"FileInfoHashName" => "fi.HashName",
					"FileInfoFileType" => "fi.FileType",
					"FileInfoFileSize" => "fi.FileSize",
					"FileInfoFileExtension" => "fi.Extension"
			))
			->where("m.StoryArticleID = ?", $storyArticleID)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	// File Document
	function getFiles($storyArticleID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'story_article_file'), '')
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
			->where("tbl.StoryArticleID = ? ", $storyArticleID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
}

?>