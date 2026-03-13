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
			->joinLeft(array('c' => 'gallery_category_info') , "tbl.GalleryCategoryID = c.ID" , '')
			->joinLeft(array('cd' => 'gallery_category_detail') , "c.ID = cd.ID and cd.Lang = '{$lang}' " , '')
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
			->where("tbl.IsTrash ='N'")
			;
			
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("d.Title like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['created'])) {
			    $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['created']);
			    $select = $select->where("tbl.Created >= ?", $value);
			}
			if (!empty($advancesearch['createdTo'])) {
			    $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['createdTo']);
			    $select = $select->where("tbl.Created <= ?", $value . " 23:59:59");
			}
			if (!empty($advancesearch['isEnable'])) {
				$select = $select->where("tbl.IsEnable = ? ", $advancesearch['isEnable']);
			}
			if (!empty($advancesearch['isCat'])) {
				//$select = $select->where("tbl.gallery_category_id = ? ", $advancesearch['isCat']);
				$select = $select->distinct()
				->joinInner(array('ca' => "gallery_category_article"), "tbl.ID = ca.GalleryArticleID", '')
				->where("ca.GalleryCategoryID = ? ", $advancesearch['isCat'])
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
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where("tbl.ID = ? " , $id)
			->where("tbl.IsDeleted ='N'")
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
			->from(array('tbl' => 'gallery_article_info'), '')
			->joinLeft(array('d' => 'gallery_article_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
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
			->where("tbl.IsDeleted ='N'")
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
			$form['GalleryCategoryID'] = count($params['CategoryIds']) > 0 ? $params['CategoryIds'][0] : null;
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['TemplateType'] = $params['TemplateType'];
			$form['Ordering'] = $param[$defaultLanguageCode]['Ordering'];
			$form['ImagePerPage'] = empty($params['ImagePerPage']) ? null:$params['ImagePerPage'];
			$form['ViewAmount'] = 0;
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

			$result = $this->db->insert("gallery_article_info", $form);
			$id = $this->db->lastInsertId();

			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['GalleryArticleID'] = $id;
					$formDetail['GalleryCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("gallery_category_article", $formDetail);					
				}
			}
			
			//Tag
			if (count($params['hdfTag']) > 0) {
				foreach ($params['hdfTag'] as $tag) {
					$formtag=array();
					$formtag['GalleryArticleID']=$id;
					$formtag['Title']= $tag;
					$formtag['Created'] = $modified;
					$formtag['CreatedBy']= $this->UserID;
					$formtag['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
					$formtag['IsDeleted'] = 'N';
					$result = $this->db->insert("gallery_article_tags", $formtag);
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
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("gallery_article_detail", $formDetail);
				}
			}
			
			if(count($params['GalleryIds'])>0){
				foreach ($params['GalleryIds'] as $fileID){
					$formFile = array();
					$formFile['GalleryArticleID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'image';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("gallery_article_file", $formFile);
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
					$form['GalleryArticleID'] = $id;
					$form['DataCode'] = $obj["ID"];
					$affectedRows = $this->db->insert("gallery_article_cover", $form);
					
					if (($affectedRows > 0) && (count($obj['Lang']) > 0)) {
						foreach ($obj['Lang'] as $l => $v) {
							$formDetail = array();
							$whereDetail = array();
							$formDetail['Title'] = $v['Title'];
							$formDetail['IntroText'] = $v['IntroText'];
							$formDetail['IsEnable'] = 'Y';
							$formDetail['IsDeleted'] = 'N';
							$formDetail['GalleryArticleID'] = $id;
							$formDetail['DataCode'] = $obj['ID'];
							$formDetail['Lang'] = $l;
							$formDetail['Created'] = date('Y-m-d H:i:s');
							$this->db->insert("gallery_article_cover_detail", $formDetail);
						}
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
			$form['GalleryCategoryID'] = count($params['CategoryIds']) > 0 ? $params['CategoryIds'][0] : null;
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['TemplateType'] = $params['TemplateType'];
			$form['Ordering'] = $param[$defaultLanguageCode]['Ordering'];
			$form['ImagePerPage'] = empty($params['ImagePerPage']) ? null : $params['ImagePerPage'];
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
			$result = $this->db->update("gallery_article_info", $form, $where);

			// Delete All Category
			$whereIdDelete = array();
			$whereIdDelete['GalleryArticleID = ? '] = $id;
			$this->db->delete("gallery_category_article",$whereIdDelete);
			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['GalleryArticleID'] = $id;
					$formDetail['GalleryCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("gallery_category_article", $formDetail);
				}
			}
			
			//Tag
			$whereTagDelete = array();
			$whereTagDelete['GalleryArticleID = ? '] = $id;
			$this->db->delete("gallery_article_tags",$whereTagDelete);
			if (count($params['hdfTag']) > 0) {
				foreach ($params['hdfTag'] as $tag) {
					$formtag=array();
					$formtag['GalleryArticleID'] = $id;
					$formtag['Title']= $tag;
					$formtag['Modified'] = $modified;
					$formtag['ModifiedBy']= $this->UserID;
					$formtag['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
					$formtag['IsDeleted'] = 'N';
					$result = $this->db->insert("gallery_article_tags", $formtag);
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
					$formDetail['Modified'] = $modified;
					$whereDetail['ID = ? '] = $id;
					$whereDetail['Lang = ? '] = $lang;
					$affRow = $this->db->update("gallery_article_detail", $formDetail, $whereDetail);
					if ($affRow == 0) {
						$formDetail['ID'] = $id;
						$formDetail['Lang'] = $lang;
						$formDetail['Created'] = $modified;
						$formDetail['IsEnable'] = 'Y';
						$formDetail['IsDeleted'] = 'N';
						$this->db->insert("gallery_article_detail", $formDetail);
					}
				}
			}
			
			// CoverImage
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$where['GalleryArticleID = ?'] = $id;
			$this->db->update("gallery_article_cover", $form, $where);
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
					$where["GalleryArticleID = ?"] = $id;
					$where["DataCode = ?"] = $obj["ID"];
					$affectedRows = $this->db->update("gallery_article_cover", $form, $where);
					if ($affectedRows <= 0) {
						$form['Created'] = date('Y-m-d H:i:s');
						$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$form['GalleryArticleID'] = $id;
						$form['DataCode'] = $obj["ID"];
						$affectedRows = $this->db->insert("gallery_article_cover", $form);
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
							$whereDetail["GalleryArticleID = ?"] = $id;
							$whereDetail["DataCode = ?"] = $obj['ID'];
							$whereDetail["Lang = ?"] = $l;
							$affectedDetailRows = $this->db->update("gallery_article_cover_detail", $formDetail, $whereDetail);
							if ($affectedDetailRows == 0) {
								$formDetail['GalleryArticleID'] = $id;
								$formDetail['DataCode'] = $obj['ID'];
								$formDetail['Lang'] = $l;
								$formDetail['Created'] = date('Y-m-d H:i:s');
								$this->db->insert("gallery_article_cover_detail", $formDetail);
							}
						}
					}
				}
			}
			// Delete All File
			$whereIdDelete = array();
			$whereIdDelete['GalleryArticleID = ? '] = $id;
			$this->db->delete("gallery_article_file",$whereIdDelete);
			
			if(count($params['GalleryIds'])>0){
				foreach ($params['GalleryIds'] as $fileID){
					$formFile = array();
					$formFile['GalleryArticleID'] = $id;
					$formFile['FileInfoID']= $fileID;
					$formFile['FileType']= 'image';
					$formFile['Created'] = $modified;
					$formFile['CreatedBy']= $this->UserID;
					$formFile['IsEnable'] = 'Y';
					$formFile['IsDeleted'] = 'N';
					$this->db->insert("gallery_article_file", $formFile);
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
			// $form['IsDeleted'] = 'Y';
			$form['IsTrash'] = 'Y';
			$form['TrashDate'] = $modified;
			$form['TrashBy'] = $this->UserID;;
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $params['id'];
			$result = $this->db->update("gallery_article_info", $form, $where);
			
			//Tag
			// $formTag = array();
			// $whereTag = array();
			// $formTag['IsDeleted'] = 'Y';
			// $whereTagDelete['GalleryArticleID = ? '] = $params['id'];
			// $result = $this->db->update("gallery_article_tags",$formTag,$whereTag);

			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	public function getCoverByLanguage($galleryArticleID, $lang) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('m' => 'gallery_article_info'), "")
			->joinInner(array("c" => "gallery_article_cover"), "m.ID = c.GalleryArticleID", "")
			->joinInner(array("cd" => "gallery_article_cover_detail"), "c.GalleryArticleID = cd.GalleryArticleID and c.DataCode = cd.DataCode and cd.Lang = '{$lang}' ", "")
			->columns(array(
					"c.GalleryArticleID",
					"c.DataCode",
					"c.FileInfoID",
					"cd.Lang",
					"cd.Title",
					"cd.IntroText"
			))
			->where("m.ID = ?", $galleryArticleID)
			->where("c.IsDeleted = 'N' ")
			->order("c.Ordering")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	public function getOption($lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'gallery_article_info'), array( 'ID'))
		->joinInner(array('d' => 'gallery_article_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'=>'Title'))
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsTrash = ?", "N")
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
	
	function getCategories($galleryArticleID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'gallery_category_article'), '')
			->columns(array(
					"GalleryCategoryID" => "tbl.GalleryCategoryID"
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
	
	function getFiles($galleryArticleID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'gallery_article_file'), '')
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
			->where("tbl.GalleryArticleID = ? ", $galleryArticleID)
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
			->from(array('tbl' => 'dbo.GalleryInfoFile'), array(""))
			->columns(array(
					"tbl.FileInfoID",
					"tbl.FileType"
			))
			->where("tbl.GalleryInfoID = ? " ,$id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getAllKeyword($searchText) {
		// set query
		$select = $this->db->select()->distinct()
		->from(array('tbl' => 'gallery_article_tags'), array('ID', 'Title'))
		->where("tbl.IsEnable = 'Y' ")
		->where("tbl.IsDeleted = 'N' ")
		->limitPage("1", "10")
		 ;
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
	
	function getAllKeywordByArticle($galleryArticleID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'gallery_article_tags'), '')
			->columns(array(
					"Title" => "tbl.Title",
					"ID"=>"tbl.ID"
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
					$affRow += $this->db->update("gallery_article_info", $form, $where);
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
					->from(array('tbl'=>'gallery_article_info'), array(
						'GalleryCategoryID',
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
						'ShowPrint'
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['GalleryCategoryID'] = empty($dataInfo->GalleryCategoryID) ? null : $dataInfo->GalleryCategoryID;
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
					$data->result = $this->db->insert("gallery_article_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'gallery_article_detail'), array('Lang','Title','IntroText','Detail'))
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
								$this->db->insert("gallery_article_detail", $form);
							}
						}

						// Category Article
						$selectCatArt = $this->db->select ()
						->from(array('tbl'=>'gallery_category_article'), array('GalleryCategoryID'))
						->where("tbl.GalleryArticleID = ? ", $id)
						;
						$stmtCatArt = $this->db->query($selectCatArt);
						$dataCatArt = $stmtCatArt->fetchAll();

						if(count($dataCatArt)>0){
							foreach($dataCatArt as $d){
								$form = array();
								$form['GalleryArticleID'] = $tmpID;
								$form['GalleryCategoryID'] = empty($d['GalleryCategoryID']) ? null : $d['GalleryCategoryID'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("gallery_category_article", $form);
							}
						}

						// Tag
						$selectTag = $this->db->select ()
						->from(array('tbl'=>'gallery_article_tags'), array('Title'))
						->where("tbl.GalleryArticleID = ? ", $id)
						;
						$stmtTag = $this->db->query($selectTag);
						$dataTag = $stmtTag->fetchAll();

						if(count($dataTag)>0){
							foreach($dataTag as $d){
								$form = array();
								$form['GalleryArticleID'] = $tmpID;
								$form['Title'] = empty($d['Title']) ? null : $d['Title'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("gallery_article_tags", $form);
							}
						}

						// File
						$selectFile = $this->db->select ()
						->from(array('tbl'=>'gallery_article_file'), array('FileInfoID','FileType'))
						->where("tbl.GalleryArticleID = ? ", $id)
						;
						$stmtFile = $this->db->query($selectFile);
						$dataFile = $stmtFile->fetchAll();

						if(count($dataFile)>0){
							foreach($dataFile as $d){
								$form = array();
								$form['GalleryArticleID'] = $tmpID;
								$form['FileInfoID'] = empty($d['FileInfoID']) ? null : $d['FileInfoID'];
								$form['FileType'] = empty($d['FileType']) ? null : $d['FileType'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("gallery_article_file", $form);
							}
						}

						// Cover
						$selectFile = $this->db->select ()
						->from(array('tbl'=>'gallery_article_cover'), array('DataCode','FileInfoID','Ordering'))
						->where("tbl.GalleryArticleID = ? ", $id)
						;
						$stmtFile = $this->db->query($selectFile);
						$dataFile = $stmtFile->fetchAll();

						if(count($dataFile)>0){
							foreach($dataFile as $d){
								$form = array();
								$form['GalleryArticleID'] = $tmpID;
								$form['DataCode'] = empty($d['DataCode']) ? null : $d['DataCode'];
								$form['FileInfoID'] = empty($d['FileInfoID']) ? null : $d['FileInfoID'];
								$form['Ordering'] = empty($d['Ordering']) ? null : $d['Ordering'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("gallery_article_cover", $form);
							}
						}

						// Cover Detail
						$selectFile = $this->db->select ()
						->from(array('tbl'=>'gallery_article_cover_detail'), array('DataCode','Lang','Title','IntroText'))
						->where("tbl.GalleryArticleID = ? ", $id)
						;
						$stmtFile = $this->db->query($selectFile);
						$dataFile = $stmtFile->fetchAll();

						if(count($dataFile)>0){
							foreach($dataFile as $d){
								$form = array();
								$form['GalleryArticleID'] = $tmpID;
								$form['DataCode'] = empty($d['DataCode']) ? null : $d['DataCode'];
								$form['Lang'] = empty($d['Lang']) ? null : $d['Lang'];
								$form['Title'] = empty($d['Title']) ? null : $d['Title'];
								$form['IntroText'] = empty($d['IntroText']) ? null : $d['IntroText'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("gallery_article_cover_detail", $form);
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
					$affRow += $this->db->update("gallery_article_info", $form, $where);
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
