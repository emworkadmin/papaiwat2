<?php

class Story_Model_Article {
	
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
			->from(array('tbl' => 'story_article_info'), '')
			->joinLeft(array('d' => 'story_article_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('c' => 'story_category_info') , "tbl.StoryCategoryID = c.ID" , '')
			->joinLeft(array('cd' => 'story_category_detail') , "c.ID = cd.ID and cd.Lang = '{$lang}' " , '')
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
				$select = $select->distinct()
				->joinInner(array('ca' => "story_category_article"), "tbl.ID = ca.StoryArticleID", '')
				->where("ca.StoryCategoryID = ? ", $advancesearch['isCat'])
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
			->from(array('tbl' => 'story_article_info'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"StoryCategoryID" => "tbl.StoryCategoryID",
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
			->from(array('tbl' => 'story_article_info'), '')
			->joinLeft(array('d' => 'story_article_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"StoryCategoryID" => "tbl.StoryCategoryID",
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
					
					"MetaData" => "tbl.MetaData",
					"SocialNetworkFacebook" => "tbl.SocialNetworkFacebook",
					"SocialNetworkTwitter" => "tbl.SocialNetworkTwitter",
					"SocialNetworkGooglePlus" => "tbl.SocialNetworkGooglePlus",
					"ShowQrcode" => "tbl.ShowQrcode",
					"ShowPrint" => "tbl.ShowPrint",

					"Lang" => "d.Lang",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Detail" => "d.Detail"
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

	function getInfoData($StoryArticleID) {
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
	        ->where("tbl.StoryArticleID = ?", $StoryArticleID)
	        ->where("tbl.IsEnable = 'Y'")
			->where("tbl.IsDeleted = 'N'")
			->order("tbl.Lang ASC")
	        ->order("tbl.Ordering ASC")
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
	                    $resultGallery = $stmt->fetchAll();
	                    
	                    if(count($resultGallery)>0) {
	                       $resultInfoData[$info_key]['Data'] = $resultGallery;
	                    } else {
	                        unset($resultInfoData[$info_key]);
	                    }

	                } else if($info_value['DataType'] == 'D') {
	                    
	                    $select = $this->db->select()
	                    ->from(array("tbl" => "story_article_data_description"), "Detail")
	                    ->where("tbl.StoryArticleID = ?", $info_value['StoryArticleID'])
						->where("tbl.Lang = ?", $info_value['Lang'])
						->where("tbl.DataCode = ?", $info_value['DataCode'])
	                    ->where("tbl.IsEnable = 'Y' ")
	                    ->where("tbl.IsDeleted = 'N' ")
						;
	                    $stmt = $this->db->query($select);
	                    $resultDescription = $stmt->fetchAll();
	                    
	                    $resultInfoData[$info_key]['Data'] = $resultDescription;
	                    
	                } else if($info_value['DataType'] == 'M') {
	                    
	                    $select = $this->db->select()
						->from(array("tbl" => "story_article_data_multimedia"), "")
						->joinLeft(array("f" => "file_info"), "tbl.FileInfoID = f.ID", "")
	                    ->columns(array(
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
	                    
	                    $resultInfoData[$info_key]['Data'] = $resultMedia;
	                    
	                }
	                
	            }
	        }
	    } catch (Exception $e) {
	        
	    }
	    return $resultInfoData;
	}
	
	public function getCoverByLanguage($storyArticleID, $lang) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('m' => 'story_article_info'), "")
			->joinInner(array("c" => "story_article_cover"), "m.ID = c.StoryArticleID", "")
			->joinInner(array("cd" => "story_article_cover_detail"), "c.StoryArticleID = cd.StoryArticleID and c.DataCode = cd.DataCode and cd.Lang = '{$lang}' ", "")
			->columns(array(
					"c.StoryArticleID",
					"c.DataCode",
					"c.FileInfoID",
					"cd.Lang",
					"cd.Title",
					"cd.IntroText"
			))
			->where("m.ID = ?", $storyArticleID)
			->where("c.IsDeleted = 'N' ")
			->order("c.Ordering")
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
			$StoryArticleID = '';
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
			$form['StoryCategoryID'] = count($params['CategoryIds']) > 0 ? $params['CategoryIds'][0] : null;
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['ViewAmount'] = 0;
			$form['TemplateType'] = $params['TemplateType'];
			$form['CanComment'] = "N"; //$param[$defaultLanguageCode]['CanCommentType'] == 'C' ? $param[$defaultLanguageCode]['CanComment'] : $param[$defaultLanguageCode]['CanCommentType'];			
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
			$result = $this->db->insert("story_article_info", $form);
			$StoryArticleID = $this->db->lastInsertId();

			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['StoryArticleID'] = $StoryArticleID;
					$formDetail['StoryCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("story_category_article", $formDetail);					
				}
			}
			
			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$formDetail['ID'] = $StoryArticleID;
					$formDetail['Lang'] = $lang;
					$formDetail['Title'] = $value['Title'];
					$formDetail['IntroText'] = $value['IntroText'];
					// $formDetail['Detail'] = $value['Detail'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("story_article_detail", $formDetail);
				}
			}

			// Data Item
			if(count($params['sortable'])>0){
				foreach ($params['sortable'] as $lang=>$dataByLanguage){

					if(count($dataByLanguage)>0){
						foreach ($dataByLanguage as $k=>$v){
					
							$formInfoData = array();
							$formInfoData['StoryArticleID'] = $StoryArticleID;
							$formInfoData['Lang'] = $lang;
							$formInfoData['DataCode'] = $v['id'];
							$formInfoData['DataType'] = $v['type'];
							$formInfoData['Ordering'] = $k;
							$formInfoData['Created'] = date('Y-m-d H:i:s');
							$formInfoData['CreatedBy'] = $this->UserID;
							$this->db->insert('story_article_data', $formInfoData);
							
							if($v['type']=='D'){
								$formDescription = array();
								$formDescription['StoryArticleID'] = $StoryArticleID;
								$formDescription['Lang'] = $lang;
								$formDescription['DataCode'] =$v['id'];
								$formDescription['Detail'] = $v['data'];
								$formDescription['Created'] = date('Y-m-d H:i:s');
								$formDescription['CreatedBy'] = $this->UserID;
								$this->db->insert('story_article_data_description', $formDescription);
							}
							
							if($v['type']=='G'){
								if(count($v['data'])>0){
									foreach($v['data'] as $gID){
										$formGallery = array();
										$formGallery['StoryArticleID'] = $StoryArticleID;
										$formGallery['Lang'] = $lang;
										$formGallery['DataCode'] = $v['id'];
										$formGallery['FileInfoID'] = $gID;
										$formGallery['Created'] = date('Y-m-d H:i:s');
										$formGallery['CreatedBy'] = $this->UserID;
										$this->db->insert('story_article_data_gallery', $formGallery);
									}
								}
							}
							
							if($v['type']=='M'){
								$formMultimedia = array();
								$formMultimedia['StoryArticleID'] = $StoryArticleID;
								$formMultimedia['Lang'] = $lang;
								$formMultimedia['DataCode'] =$v['id'];
								$formMultimedia['Type'] = $v['data']['type'];
								if($v['data']['type']=='Y'){
									$formMultimedia['YoutubeUrl'] = $v['data']['url'];
								}
								if($v['data']['type']=='U'){
									$formMultimedia['FileInfoID'] = $v['data']['fileId'];
								}
								$formMultimedia['Created'] = date('Y-m-d H:i:s');
								$formMultimedia['CreatedBy'] = $this->UserID;
								$this->db->insert('story_article_data_multimedia', $formMultimedia);
							}
					
						}
					}
				}
			}

			// Cover
			if (count($param[$defaultLanguageCode]['Cover']) > 0) {
				$i = 0;
				$fileModel = new System_Model_Fileinfo();
				foreach ($param[$defaultLanguageCode]['Cover'] as $val) {
					$obj = json_decode($val, true);
					$coverImageData = $fileModel->itemByHashName($obj['CoverImage']);
					$i ++;
					$form = array();
					$where = array();
					$form['FileInfoID'] = $coverImageData->ID;
					$form['Ordering'] = $i;
					$form['IsEnable'] = 'Y';
					$form['IsDeleted'] = 'N';
					$form['Created'] = date('Y-m-d H:i:s');
					$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['StoryArticleID'] = $StoryArticleID;
					$form['DataCode'] = $obj["ID"];
					$affectedRows = $this->db->insert("story_article_cover", $form);
					
					if (($affectedRows > 0) && (count($obj['Lang']) > 0)) {
						foreach ($obj['Lang'] as $l => $v) {
							$formDetail = array();
							$whereDetail = array();
							$formDetail['Title'] = $v['Title'];
							$formDetail['IntroText'] = $v['IntroText'];
							$formDetail['IsEnable'] = 'Y';
							$formDetail['IsDeleted'] = 'N';
							$formDetail['StoryArticleID'] = $StoryArticleID;
							$formDetail['DataCode'] = $obj['ID'];
							$formDetail['Lang'] = $l;
							$formDetail['Created'] = date('Y-m-d H:i:s');
							$this->db->insert("story_article_cover_detail", $formDetail);
						}
					}
				}
			}			
			
			// File Document
			if(count($params['DocumentIds'])>0){
				foreach ($params['DocumentIds'] as $fileID){
					$formFile = array();
					$formFile['StoryArticleID'] = $StoryArticleID;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'document';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("story_article_file", $formFile);
				}
			}

			if($result){
				$data->id = $StoryArticleID;
				$data->result = true;
			}

		} catch (Exception $e) {
			// print_r($e->getMessage()); exit;
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
			$StoryArticleID = $params['hdfID'];
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
			$form['StoryCategoryID'] = count($params['CategoryIds']) > 0 ? $params['CategoryIds'][0] : null;
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['TemplateType'] = $params['TemplateType'];
			$form['CanComment'] = "N"; // $param[$defaultLanguageCode]['CanCommentType'] == 'C' ? $param[$defaultLanguageCode]['CanComment'] : $param[$defaultLanguageCode]['CanCommentType'];
			$form['MetaData'] = $param[$defaultLanguageCode]['MetaData'];
			$form['SocialNetworkFacebook'] = $param[$defaultLanguageCode]['SocialNetworkFacebook'];
			$form['SocialNetworkTwitter'] = $param[$defaultLanguageCode]['SocialNetworkTwitter'];
			$form['SocialNetworkGooglePlus'] = $param[$defaultLanguageCode]['SocialNetworkGooglePlus'];
			$form['ShowQrcode'] = $param[$defaultLanguageCode]['ShowQrcode'];
			$form['ShowPrint'] = $param[$defaultLanguageCode]['ShowPrint'];
			$form['Modified'] = $modified;
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$where['ID = ? '] = $StoryArticleID;
			$result = $this->db->update("story_article_info", $form, $where);
			
			// Delete All Category
			$whereIdDelete = array();
			$whereIdDelete['StoryArticleID = ? '] = $StoryArticleID;
			$this->db->delete("story_category_article",$whereIdDelete);
			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['StoryArticleID'] = $StoryArticleID;
					$formDetail['StoryCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("story_category_article", $formDetail);
				}
			}
			
			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$whereDetail = array();
					$formDetail['Title'] = $value['Title'];
					$formDetail['IntroText'] = $value['IntroText'];
					// $formDetail['Detail'] = $value['Detail'];
					$formDetail['Modified'] = $modified;
					$whereDetail['ID = ? '] = $StoryArticleID;
					$whereDetail['Lang = ? '] = $lang;
					$affRow = $this->db->update("story_article_detail", $formDetail, $whereDetail);
					if ($affRow == 0) {
						$formDetail['ID'] = $StoryArticleID;
						$formDetail['Lang'] = $lang;
						$formDetail['Created'] = $modified;
						$formDetail['IsEnable'] = 'Y';
						$formDetail['IsDeleted'] = 'N';
						$this->db->insert("story_article_detail", $formDetail);
					}
				}
			}

			$formInfoData = array();
			$whereInfoData = array();
			$formInfoData['Modified'] = date('Y-m-d H:i:s');
			$formInfoData['ModifiedBy'] = $this->UserID;
			$formInfoData['IsDeleted'] = 'Y';
			$whereInfoData['StoryArticleID = ? '] = $StoryArticleID;
			$this->db->update("story_article_data", $formInfoData, $whereInfoData);
			
			$this->db->update("story_article_data_description", $formInfoData, $whereInfoData);
			
			$this->db->update("story_article_data_gallery", $formInfoData, $whereInfoData);
			
			$this->db->update("story_article_data_multimedia", $formInfoData, $whereInfoData);
			

			if(count($params['sortable'])>0){
				foreach ($params['sortable'] as $lang=>$dataByLanguage){

					if(count($dataByLanguage)>0){
						foreach ($dataByLanguage as $k=>$v){

							$formInfoData = array();
							$whereInfoData = array();
							$formInfoData['StoryArticleID'] = $StoryArticleID;
							$formInfoData['Lang'] = $lang;
							$formInfoData['DataCode'] = $v['id'];
							$formInfoData['DataType'] = $v['type'];
							$formInfoData['Ordering'] = $k;
							$formInfoData['Modified'] = date('Y-m-d H:i:s');
							$formInfoData['ModifiedBy'] = $this->UserID;
							$formInfoData['IsDeleted'] = 'N';
							$whereInfoData['StoryArticleID = ? '] = $StoryArticleID;
							$whereInfoData['Lang = ? '] = $lang;
							$whereInfoData['DataCode = ? '] = $v['id'];
							$tmpResult = $this->db->update("story_article_data", $formInfoData, $whereInfoData);
							if ($tmpResult == 0) {
								$formInfoData['Created'] = date('Y-m-d H:i:s');
								$formInfoData['CreatedBy'] = $this->UserID;
								$this->db->insert('story_article_data', $formInfoData);
							}
							
							if($v['type']=='D'){
								$formDescription = array();
								$whereDescription = array();
								$formDescription['StoryArticleID'] = $StoryArticleID;
								$formDescription['Lang'] = $lang;
								$formDescription['DataCode'] =$v['id'];
								$formDescription['Detail'] = $v['data'];
								$formDescription['Modified'] = date('Y-m-d H:i:s');
								$formDescription['ModifiedBy'] = $this->UserID;
								$formDescription['IsDeleted'] = 'N';
								$whereDescription['StoryArticleID = ? '] = $StoryArticleID;
								$whereDescription['Lang = ? '] = $lang;
								$whereDescription['DataCode = ? '] = $v['id'];
								$tmpResult = $this->db->update("story_article_data_description", $formDescription, $whereDescription);
								if ($tmpResult == 0) {
									$formDescription['Created'] = date('Y-m-d H:i:s');
									$formDescription['CreatedBy'] = $this->UserID;
									$this->db->insert('story_article_data_description', $formDescription);
								}
							}
							
							if($v['type']=='G'){
								if(count($v['data'])>0){
									foreach($v['data'] as $gID){
										$formGallery = array();
										$whereGallery = array();
										$formGallery['StoryArticleID'] = $StoryArticleID;
										$formGallery['Lang'] = $lang;
										$formGallery['DataCode'] = $v['id'];
										$formGallery['FileInfoID'] = $gID;
										$formGallery['Modified'] = date('Y-m-d H:i:s');
										$formGallery['ModifiedBy'] = $this->UserID;
										$formGallery['IsDeleted'] = 'N';
										$whereGallery['StoryArticleID = ? '] = $StoryArticleID;
										$whereGallery['Lang = ? '] = $lang;
										$whereGallery['DataCode = ? '] = $v['id'];
										$whereGallery['FileInfoID = ? '] = $gID;
										$tmpResult = $this->db->update("story_article_data_gallery", $formGallery, $whereGallery);
										if ($tmpResult == 0) {
											$formGallery['Created'] = date('Y-m-d H:i:s');
											$formGallery['CreatedBy'] = $this->UserID;
											$this->db->insert('story_article_data_gallery', $formGallery);
										}
									}
								}
							}
							
							if($v['type']=='M'){
								$formMultimedia = array();
								$whereMultimedia = array();
								$formMultimedia['StoryArticleID'] = $StoryArticleID;
								$formMultimedia['Lang'] = $lang;
								$formMultimedia['DataCode'] =$v['id'];
								$formMultimedia['Type'] = $v['data']['type'];
								if($v['data']['type']=='Y'){
									$formMultimedia['YoutubeUrl'] = $v['data']['url'];
								}
								if($v['data']['type']=='U'){
									$formMultimedia['FileInfoID'] = $v['data']['fileId'];
								}
								$formMultimedia['Modified'] = date('Y-m-d H:i:s');
								$formMultimedia['ModifiedBy'] = $this->UserID;
								$formMultimedia['IsDeleted'] = 'N';
								$whereMultimedia['StoryArticleID = ? '] = $StoryArticleID;
								$whereMultimedia['Lang = ? '] = $lang;
								$whereMultimedia['DataCode = ? '] = $v['id'];
								$tmpResult = $this->db->update("story_article_data_multimedia", $formMultimedia, $whereMultimedia);
								if ($tmpResult == 0) {
									$formMultimedia['Created'] = date('Y-m-d H:i:s');
									$formMultimedia['CreatedBy'] = $this->UserID;
									$this->db->insert('story_article_data_multimedia', $formMultimedia);
								}
							}
					
						}
					}
				}
			}
			
			// CoverImage
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$where['StoryArticleID = ?'] = $StoryArticleID;
			$this->db->update("story_article_cover", $form, $where);
			if (count($param[$defaultLanguageCode]['Cover']) > 0) {
				$i = 0;
				$fileModel = new System_Model_Fileinfo();
				foreach ($param[$defaultLanguageCode]['Cover'] as $val) {
					$obj = json_decode($val, true);
					$coverImageData = $fileModel->itemByHashName($obj['CoverImage']);
					$i ++;
					$form = array();
					$where = array();
					$form['FileInfoID'] = $coverImageData->ID;
					$form['Ordering'] = $i;
					$form['Modified'] = date('Y-m-d H:i:s');
					$form['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
					$form['IsEnable'] = 'Y';
					$form['IsDeleted'] = 'N';
					$where["StoryArticleID = ?"] = $StoryArticleID;
					$where["DataCode = ?"] = $obj["ID"];
					$affectedRows = $this->db->update("story_article_cover", $form, $where);
					if ($affectedRows <= 0) {
						$form['Created'] = date('Y-m-d H:i:s');
						$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$form['StoryArticleID'] = $StoryArticleID;
						$form['DataCode'] = $obj["ID"];
						$affectedRows = $this->db->insert("story_article_cover", $form);
					}
					
					if (($affectedRows > 0) && (count($obj['Lang']) > 0)) {
						foreach ($obj['Lang'] as $l => $v) {
							$formDetail = array();
							$whereDetail = array();
							$formDetail['Title'] = $v['Title'];
							$formDetail['IntroText'] = $v['IntroText'];
							$formDetail['Modified'] = date('Y-m-d H:i:s');
							$formDetail['IsEnable'] = 'Y';
							$formDetail['IsDeleted'] = 'N';
							$whereDetail["StoryArticleID = ?"] = $StoryArticleID;
							$whereDetail["DataCode = ?"] = $obj['ID'];
							$whereDetail["Lang = ?"] = $l;
							$affectedDetailRows = $this->db->update("story_article_cover_detail", $formDetail, $whereDetail);
							if ($affectedDetailRows == 0) {
								$formDetail['StoryArticleID'] = $StoryArticleID;
								$formDetail['DataCode'] = $obj['ID'];
								$formDetail['Lang'] = $l;
								$formDetail['Created'] = date('Y-m-d H:i:s');
								$this->db->insert("story_article_cover_detail", $formDetail);
							}
						}
					}
				}
			}
			
			// Delete All File
			$whereIdDelete = array();
			$whereIdDelete['StoryArticleID = ? '] = $StoryArticleID;
			$this->db->delete("story_article_file",$whereIdDelete);
			if(count($params['DocumentIds'])>0){
				foreach ($params['DocumentIds'] as $fileID){
					$formFile = array();
					$formFile['StoryArticleID'] = $StoryArticleID;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'document';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("story_article_file", $formFile);
				}
			}
						
			if($result){
				$data->id = $StoryArticleID;
				$data->result = true;
			}
		} catch (Exception $e) {
			// print_r($e->getMessage()); exit;
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
			$result = $this->db->update("story_article_info", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	public function getOption($lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'story_article_info'), array( 'ID'))
		->joinInner(array('d' => 'story_article_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'=>'Title'))
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
	
	function getFile($id) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'dbo.FileInfo'), '')
			->columns(array(
					"tbl.Name",
					"tbl.HashName",
					"tbl.FileSize",
					"tbl.FileType",
			))
			->where("tbl.ID = ? " ,$id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getCategories($storyArticleID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'story_category_article'), '')
			->columns(array(
					"StoryCategoryID" => "tbl.StoryCategoryID"
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
	
	function getIdfile($id) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'dbo.StoryInfoFile'), array(""))
			->columns(array(
					"tbl.FileInfoID",
					"tbl.FileType"
			))
			->where("tbl.StoryInfoID = ? " ,$id)
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
					$affRow += $this->db->update("story_article_info", $form, $where);
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
					->from(array('tbl'=>'story_article_info'), array(
						'StoryCategoryID',
						'IntroImageID',
						'MainTitle',
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
						'ShowPrint',
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['StoryCategoryID'] = empty($dataInfo->StoryCategoryID) ? null : $dataInfo->StoryCategoryID;
					$form['IntroImageID'] = empty($dataInfo->IntroImageID) ? null : $dataInfo->IntroImageID;
					$form['MainTitle'] = empty($dataInfo->MainTitle) ? null : $dataInfo->MainTitle;
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
					$data->result = $this->db->insert("story_article_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Article Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'story_article_detail'), array('Lang','Title','IntroText','Detail'))
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
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("story_article_detail", $form);
							}
						}

						// Category Article
						$selectFile = $this->db->select ()
						->from(array('tbl'=>'story_category_article'), array('StoryCategoryID'))
						->where("tbl.StoryArticleID = ? ", $id)
						;
						$stmtFile = $this->db->query($selectFile);
						$dataFile = $stmtFile->fetchAll();

						if(count($dataFile)>0){
							foreach($dataFile as $d){
								$form = array();
								$form['StoryArticleID'] = $tmpID;
								$form['StoryCategoryID'] = empty($d['StoryCategoryID']) ? null : $d['StoryCategoryID'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("story_category_article", $form);
							}
						}

						// Article File
						$selectFile = $this->db->select ()
						->from(array('tbl'=>'story_article_file'), array('FileInfoID','FileType'))
						->where("tbl.StoryArticleID = ? ", $id)
						;
						$stmtFile = $this->db->query($selectFile);
						$dataFile = $stmtFile->fetchAll();

						if(count($dataFile)>0){
							foreach($dataFile as $d){
								$form = array();
								$form['StoryArticleID'] = $tmpID;
								$form['FileInfoID'] = empty($d['FileInfoID']) ? null : $d['FileInfoID'];
								$form['FileType'] = empty($d['FileType']) ? null : $d['FileType'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("story_article_file", $form);
							}
						}

						// Article Cover
						$selectCover = $this->db->select ()
						->from(array('tbl'=>'story_article_cover'), array('DataCode','FileInfoID','Ordering'))
						->where("tbl.StoryArticleID = ? ", $id)
						;
						$stmtCover = $this->db->query($selectCover);
						$dataCover = $stmtCover->fetchAll();

						if(count($dataCover)>0){
							foreach($dataCover as $cover){

								$tmpDataCode = $this->gencode();

								$form = array();
								$form['StoryArticleID'] = $tmpID;
								$form['DataCode'] = $tmpDataCode;
								$form['FileInfoID'] = empty($cover['FileInfoID']) ? null : $cover['FileInfoID'];
								$form['Ordering'] = empty($cover['Ordering']) ? null : $cover['Ordering'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("story_article_cover", $form);

								// Article Cover Detail
								$selectCoverDetail = $this->db->select ()
								->from(array('tbl'=>'story_article_cover_detail'), array('Lang','Title','IntroText'))
								->where("tbl.StoryArticleID = ? ", $id)
								->where("tbl.DataCode = ? ", $cover['DataCode'])
								;
								$stmtCoverDetail = $this->db->query($selectCoverDetail);
								$dataCoverDetail = $stmtCoverDetail->fetchAll();

								if(count($dataCoverDetail)>0){
									foreach($dataCoverDetail as $coverDetail){
										$form = array();
										$form['StoryArticleID'] = $tmpID;
										$form['DataCode'] = $tmpDataCode;
										$form['Lang'] = empty($coverDetail['Lang']) ? null : $coverDetail['Lang'];
										$form['Title'] = empty($coverDetail['Title']) ? null : $coverDetail['Title'];
										$form['IntroText'] = empty($coverDetail['IntroText']) ? null : $coverDetail['IntroText'];
										$form['Created'] = $modified;
										$form['CreatedBy'] = $this->UserID;
										$this->db->insert("story_article_cover_detail", $form);
									}
								}
							}
						}

						// Article Data
						$selectArtData = $this->db->select ()
						->from(array('tbl'=>'story_article_data'), array('Lang','DataCode','DataType','Ordering'))
						->joinInner(array("info" => "story_article_info"), "tbl.StoryArticleID = info.ID ", "")
						->where("tbl.StoryArticleID = ? ", $id)
						->where("tbl.IsDeleted = 'N' ")
						->where("info.IsDeleted = 'N' ")
						;
						$stmtArtData = $this->db->query($selectArtData);
						$dataArtData = $stmtArtData->fetchAll();

						if(count($dataArtData)>0){
							foreach($dataArtData as $artData){

								$tmpDataCode = $this->gencode();

								$form = array();
								$form['StoryArticleID'] = $tmpID;
								$form['Lang'] = empty($artData['Lang']) ? null : $artData['Lang'];
								$form['DataCode'] = $tmpDataCode;
								$form['DataType'] = empty($artData['DataType']) ? null : $artData['DataType'];
								$form['Ordering'] = empty($artData['Ordering']) ? null : $artData['Ordering'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("story_article_data", $form);

								// Data Description
								$selectArtDataDesc = $this->db->select ()
								->from(array('tbl'=>'story_article_data_description'), array('Lang','Detail'))
								->where("tbl.StoryArticleID = ? ", $id)
								->where("tbl.Lang = ? ", $artData['Lang'])
								->where("tbl.DataCode = ? ", $artData['DataCode'])
								;
								$stmtArtDataDesc = $this->db->query($selectArtDataDesc);
								$dataArtDataDesc = $stmtArtDataDesc->fetchAll();

								if(count($dataArtDataDesc)>0){
									foreach($dataArtDataDesc as $artDataDesc){
										$form = array();
										$form['StoryArticleID'] = $tmpID;
										$form['Lang'] = empty($artDataDesc['Lang']) ? null : $artDataDesc['Lang'];
										$form['DataCode'] = $tmpDataCode;
										$form['Detail'] = empty($artDataDesc['Detail']) ? null : $artDataDesc['Detail'];
										$form['Created'] = $modified;
										$form['CreatedBy'] = $this->UserID;
										$this->db->insert("story_article_data_description", $form);
									}
								}

								// Data Gallery
								$selectArtDataGallery = $this->db->select ()
								->from(array('tbl'=>'story_article_data_gallery'), array('Lang','FileInfoID'))
								->where("tbl.StoryArticleID = ? ", $id)
								->where("tbl.Lang = ? ", $artData['Lang'])
								->where("tbl.DataCode = ? ", $artData['DataCode'])
								;
								$stmtArtDataGallery = $this->db->query($selectArtDataGallery);
								$dataArtDataGallery = $stmtArtDataGallery->fetchAll();

								if(count($dataArtDataGallery)>0){
									foreach($dataArtDataGallery as $artDataGallery){
										$form = array();
										$form['StoryArticleID'] = $tmpID;
										$form['Lang'] = empty($artDataGallery['Lang']) ? null : $artDataGallery['Lang'];
										$form['DataCode'] = $tmpDataCode;
										$form['FileInfoID'] = empty($artDataGallery['FileInfoID']) ? null : $artDataGallery['FileInfoID'];
										$form['Created'] = $modified;
										$form['CreatedBy'] = $this->UserID;
										$this->db->insert("story_article_data_gallery", $form);
									}
								}

								// Data Multimedia
								$selectArtDataMulti = $this->db->select ()
								->from(array('tbl'=>'story_article_data_multimedia'), array('Lang','Type','YoutubeUrl','FileInfoID'))
								->where("tbl.StoryArticleID = ? ", $id)
								->where("tbl.Lang = ? ", $artData['Lang'])
								->where("tbl.DataCode = ? ", $artData['DataCode'])
								;
								$stmtArtDataMulti = $this->db->query($selectArtDataMulti);
								$dataArtDataMulti = $stmtArtDataMulti->fetchAll();

								if(count($dataArtDataMulti)>0){
									foreach($dataArtDataMulti as $artDataMulti){
										$form = array();
										$form['StoryArticleID'] = $tmpID;
										$form['Lang'] = empty($artDataMulti['Lang']) ? null : $artDataMulti['Lang'];
										$form['DataCode'] = $tmpDataCode;
										$form['Type'] = empty($artDataMulti['Type']) ? null : $artDataMulti['Type'];
										$form['YoutubeUrl'] = empty($artDataMulti['YoutubeUrl']) ? null : $artDataMulti['YoutubeUrl'];
										$form['FileInfoID'] = empty($artDataMulti['FileInfoID']) ? null : $artDataMulti['FileInfoID'];
										$form['Created'] = $modified;
										$form['CreatedBy'] = $this->UserID;
										$this->db->insert("story_article_data_multimedia", $form);
									}
								}

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
					$affRow += $this->db->update("story_article_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function gencode(){
		$code  = '';
		$code .= substr(md5(uniqid(mt_rand(), true)) , 0, 8);
		$code .= '-';
		$code .= substr(md5(uniqid(mt_rand(), true)) , 0, 4);
		$code .= '-';
		$code .= substr(md5(uniqid(mt_rand(), true)) , 0, 4);
		$code .= '-';
		$code .= substr(md5(uniqid(mt_rand(), true)) , 0, 4);
		$code .= '-';
		$code .= substr(md5(uniqid(mt_rand(), true)) , 0, 12);
		return $code;
	}

}

?>
