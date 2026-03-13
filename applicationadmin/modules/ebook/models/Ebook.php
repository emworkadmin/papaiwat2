<?php

class Ebook_Model_Ebook {
	
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
			->from(array('tbl' => 'ebook_book_info'), '')
			->joinLeft(array('d' => 'ebook_book_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('c' => 'ebook_category_info') , "tbl.EbookCategoryID = c.ID" , '')
			->joinLeft(array('cd' => 'ebook_category_detail') , "c.ID = cd.ID and cd.Lang = '{$lang}' " , '')
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
				->joinInner(array('ca' => "ebook_category_book"), "tbl.ID = ca.EbookBookID", '')
				->where("ca.EbookCategoryID = ? ", $advancesearch['isCat'])
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
			->from(array('tbl' => 'ebook_book_info'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->joinLeft(array("fia" => "file_info"), "tbl.AuthorImageID = fia.ID ", "")
			->joinLeft(array("fi_doc" => "file_info"), "tbl.FileInfoID = fi_doc.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"EbookCategoryID" => "tbl.EbookCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					"TemplateType" => "tbl.TemplateType",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"DateOfCreation" => "tbl.DateOfCreation",
					"AuthorImageID" => "tbl.AuthorImageID",
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

					"SizeType" => "tbl.SizeType",
					"Height" => "tbl.Height",
					"Width" => "tbl.Width",
					
					"MetaData" => "tbl.MetaData",
					"SocialNetworkFacebook" => "tbl.SocialNetworkFacebook",
					"SocialNetworkTwitter" => "tbl.SocialNetworkTwitter",
					"SocialNetworkGooglePlus" => "tbl.SocialNetworkGooglePlus",
					"ShowQrcode" => "tbl.ShowQrcode",
					"ShowPrint" => "tbl.ShowPrint",
					
					"DocumentID" => "fi_doc.ID",
					"DocumentName" => "fi_doc.Name",
					"DocumentHashName" => "fi_doc.HashName",
					"DocumentType" => "fi_doc.FileType",
					"DocumentFileSize" => "fi_doc.FileSize",
					"DocumentFileExtension" => "fi_doc.Extension",

					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",

					"AuthorImageFileName" => "fia.Name",
					"AuthorImageHashName" => "fia.HashName",
					"AuthorImageFileType" => "fia.FileType",
					"AuthorImageFileSize" => "fia.FileSize",
					"AuthorImageFileExtension" => "fia.Extension",
			))
			->where("tbl.ID = ? " , $id)
			;
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->PublishDate = empty($data->PublishDate) ? null : new DateTime($data->PublishDate);
				$data->PublishDown = empty($data->PublishDown) ? null : new DateTime($data->PublishDown);
				$data->DateOfCreation = empty($data->DateOfCreation) ? null : new DateTime($data->DateOfCreation);
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
			->from(array('tbl' => 'ebook_book_info'), '')
			->joinLeft(array('d' => 'ebook_book_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
				"ID" => "tbl.ID",
				"EbookCategoryID" => "tbl.EbookCategoryID",
				"IntroImageID" => "tbl.IntroImageID",
				"TemplateType" => "tbl.TemplateType",
				"PublishDate" => "tbl.PublishDate",
				"PublishDown" => "tbl.PublishDown",
				"DateOfCreation" => "tbl.DateOfCreation",
				"AuthorImageID" => "tbl.AuthorImageID",
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
				"SizeType" => "tbl.SizeType",
				"Height" => "tbl.Height",
				"Width" => "tbl.Width",
				
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
				"AuthorName" => "d.AuthorName"
			))
			->where("tbl.ID = ? " ,$id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->PublishDate = empty($data->PublishDate) ? null : new DateTime($data->PublishDate);
				$data->PublishDown = empty($data->PublishDown) ? null : new DateTime($data->PublishDown);
				$data->DateOfCreation = empty($data->DateOfCreation) ? null : new DateTime($data->DateOfCreation);
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
			if($defaultLanguageCode == 'th') {
				$param[$defaultLanguageCode]['PublishDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
				$param[$defaultLanguageCode]['DateOfCreation'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['DateOfCreation']);
			}else {
				$param[$defaultLanguageCode]['PublishDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
				$param[$defaultLanguageCode]['DateOfCreation'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['DateOfCreation']);
			}

			$form = array();
			$form['EbookCategoryID'] = count($params['CategoryIds']) > 0 ? $params['CategoryIds'][0] : null;
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['DateOfCreation'] = $param[$defaultLanguageCode]['DateOfCreation'];
			$form['AuthorImageID'] = empty($params['AuthorImageID']) ? null : $params['AuthorImageID'];
			$form['ViewAmount'] = 0;
			$form['FileInfoID']= $params['DocumentID'];
			$form['TemplateType'] = $params['TemplateType'];
			$form['CanComment'] = $param[$defaultLanguageCode]['CanCommentType'] == 'C' ? $param[$defaultLanguageCode]['CanComment'] : $param[$defaultLanguageCode]['CanCommentType'];			
			$form['MetaData'] = $param[$defaultLanguageCode]['MetaData'];
			$form['SocialNetworkFacebook'] = $param[$defaultLanguageCode]['SocialNetworkFacebook'];
			$form['SocialNetworkTwitter'] = $param[$defaultLanguageCode]['SocialNetworkTwitter'];
			$form['SocialNetworkGooglePlus'] = $param[$defaultLanguageCode]['SocialNetworkGooglePlus'];
			$form['ShowQrcode'] = $param[$defaultLanguageCode]['ShowQrcode'];
			$form['ShowPrint'] = $param[$defaultLanguageCode]['ShowPrint'];

			$form['SizeType'] = $param[$defaultLanguageCode]['booksizeType'];
			$form['Height'] = empty($param[$defaultLanguageCode]['Height']) ? null : $param[$defaultLanguageCode]['Height'];
			$form['Width'] = empty($param[$defaultLanguageCode]['Width']) ? null : $param[$defaultLanguageCode]['Width'];
			
			$form['Created'] = $modified;
			$form['CreatedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("ebook_book_info", $form);
			$id = $this->db->lastInsertId();

			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['EbookBookID'] = $id;
					$formDetail['EbookCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("ebook_category_book", $formDetail);					
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
					$formDetail['AuthorName'] = $value['AuthorName'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("ebook_book_detail", $formDetail);
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
				$param[$defaultLanguageCode]['DateOfCreation'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['DateOfCreation']);
			} else {
				$param[$defaultLanguageCode]['PublishDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
				$param[$defaultLanguageCode]['DateOfCreation'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['DateOfCreation']);
			}
			$form = array();
			$where = array();
			$form['EbookCategoryID'] = count($params['CategoryIds']) > 0 ? $params['CategoryIds'][0] : null;
			$form['IntroImageID'] = $params['IntroImageID'];
			$form['FileInfoID']= $params['DocumentID'];
			$form['PublishDate'] = $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] = $param[$defaultLanguageCode]['PublishDown'];
			$form['DateOfCreation'] = $param[$defaultLanguageCode]['DateOfCreation'];
			$form['AuthorImageID'] = empty($params['AuthorImageID']) ? null : $params['AuthorImageID'];
			$form['TemplateType'] = $params['TemplateType'];
			$form['CanComment'] = $param[$defaultLanguageCode]['CanCommentType'] == 'C' ? $param[$defaultLanguageCode]['CanComment'] : $param[$defaultLanguageCode]['CanCommentType'];
			$form['MetaData'] = $param[$defaultLanguageCode]['MetaData'];
			$form['SocialNetworkFacebook'] = $param[$defaultLanguageCode]['SocialNetworkFacebook'];
			$form['SocialNetworkTwitter'] = $param[$defaultLanguageCode]['SocialNetworkTwitter'];
			$form['SocialNetworkGooglePlus'] = $param[$defaultLanguageCode]['SocialNetworkGooglePlus'];
			$form['ShowQrcode'] = $param[$defaultLanguageCode]['ShowQrcode'];
			$form['ShowPrint'] = $param[$defaultLanguageCode]['ShowPrint'];
			$form['SizeType'] = $param[$defaultLanguageCode]['booksizeType'];
			$form['Height'] = empty($param[$defaultLanguageCode]['Height']) ? null:$param[$defaultLanguageCode]['Height'];
			$form['Width'] = empty($param[$defaultLanguageCode]['Width']) ? null:$param[$defaultLanguageCode]['Width'];
			$form['Modified'] = $modified;
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$where['ID = ? '] = $id;
			
			$result = $this->db->update("ebook_book_info", $form, $where);
			
			// Delete All Category
			$whereIdDelete = array();
			$whereIdDelete['EbookBookID = ? '] = $id;
			$this->db->delete("ebook_category_book",$whereIdDelete);
			
			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['EbookBookID'] = $id;
					$formDetail['EbookCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("ebook_category_book", $formDetail);
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
					$formDetail['AuthorName'] = $value['AuthorName'];
					$formDetail['Modified'] = $modified;
					$whereDetail['ID = ? '] = $id;
					$whereDetail['Lang = ? '] = $lang;
					$affRow = $this->db->update("ebook_book_detail", $formDetail, $whereDetail);
					if ($affRow == 0) {
						$formDetail['ID'] = $id;
						$formDetail['Lang'] = $lang;
						$formDetail['Created'] = $modified;
						$formDetail['IsEnable'] = 'Y';
						$formDetail['IsDeleted'] = 'N';
						$this->db->insert("ebook_book_detail", $formDetail);
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
			$result = $this->db->update("ebook_book_info", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	public function getOption($lang = 'th') {
		$select = $this->db->select()
		->from(array('tbl' => 'ebook_book_info'), array( 'ID'))
		->joinInner(array('d' => 'ebook_book_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'=>'Title'))
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
	
	function getCategories($ebookBookID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'ebook_category_book'), '')
			->columns(array(
					"EbookCategoryID" => "tbl.EbookCategoryID"
			))
			->where("tbl.EbookBookID = ? ", $ebookBookID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getFiles($ebookBookID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'ebook_book_file'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
			->columns(array(
				"FileInfoID" => "tbl.FileInfoID",
				"BookFileType" => "tbl.FileType",
				
				"FileName" => "fi.Name",
				"FileHashName" => "fi.HashName",
				"FileType" => "fi.FileType",
				"FileSize" => "fi.FileSize",
				"FileExtension" => "fi.Extension"
			))
			->where("tbl.EbookBookID = ? ", $ebookBookID)
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
					$affRow += $this->db->update("ebook_book_info", $form, $where);
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
					->from(array('tbl'=>'ebook_book_info'), array(
						'EbookCategoryID',
						'IntroImageID',
						'AuthorImageID',
						'DateOfCreation',
						'TemplateType',
						'FileInfoID',
						'Width',
						'Height',
						'SizeType',
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
					$form['EbookCategoryID'] = empty($dataInfo->EbookCategoryID) ? null : $dataInfo->EbookCategoryID;
					$form['IntroImageID'] = empty($dataInfo->IntroImageID) ? null : $dataInfo->IntroImageID;
					$form['AuthorImageID'] = empty($dataInfo->AuthorImageID) ? null : $dataInfo->AuthorImageID;
					$form['DateOfCreation'] = empty($dataInfo->DateOfCreation) ? null : $dataInfo->DateOfCreation;
					$form['TemplateType'] = empty($dataInfo->TemplateType) ? 1 : $dataInfo->TemplateType;
					$form['FileInfoID'] = empty($dataInfo->FileInfoID) ? null : $dataInfo->FileInfoID;
					$form['Width'] = empty($dataInfo->Width) ? null : $dataInfo->Width;
					$form['Height'] = empty($dataInfo->Height) ? null : $dataInfo->Height;
					$form['SizeType'] = empty($dataInfo->SizeType) ? null : $dataInfo->SizeType;
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
					$data->result = $this->db->insert("ebook_book_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'ebook_book_detail'), array('Lang','Title','IntroText','Detail','AuthorName'))
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
								$form['AuthorName'] = empty($d['AuthorName']) ? null : $d['AuthorName'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("ebook_book_detail", $form);
							}
						}

						// Category Book
						$selectCatBook = $this->db->select ()
						->from(array('tbl'=>'ebook_category_book'), array('EbookCategoryID'))
						->where("tbl.EbookBookID = ? ", $id)
						;
						$stmtCatBook = $this->db->query($selectCatBook);
						$dataCatBook = $stmtCatBook->fetchAll();

						if(count($dataCatBook)>0){
							foreach($dataCatBook as $d){
								$form = array();
								$form['EbookBookID'] = $tmpID;
								$form['EbookCategoryID'] = empty($d['EbookCategoryID']) ? null : $d['EbookCategoryID'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("ebook_category_book", $form);
							}
						}

						// Page
						$selectPage = $this->db->select ()
						->from(array('tbl'=>'ebook_page_info'), array('PageIndex','FileInfoID','Title'))
						->where("tbl.EbookBookID = ? ", $id)
						;
						$stmtPage = $this->db->query($selectPage);
						$dataPage = $stmtPage->fetchAll();

						if(count($dataPage)>0){
							foreach($dataPage as $d){
								$form = array();
								$form['EbookBookID'] = $tmpID;
								$form['PageIndex'] = empty($d['PageIndex']) ? null : $d['PageIndex'];
								$form['FileInfoID'] = empty($d['FileInfoID']) ? null : $d['FileInfoID'];
								$form['Title'] = empty($d['Title']) ? null : $d['Title'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("ebook_page_info", $form);
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
					$affRow += $this->db->update("ebook_book_info", $form, $where);
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
