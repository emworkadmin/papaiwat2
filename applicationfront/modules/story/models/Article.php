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
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Title" => "d.Title",
					"Ordering" => "tbl.Ordering",
					"IntroText" => "d.IntroText",
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
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			;

			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("d.Title like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['isCat'])) {
				//$select = $select->where("tbl.content_category_id = ? ", $advancesearch['isCat']);
				$select = $select->distinct()
				->joinInner(array('ca' => "story_category_article"), "tbl.ID = ca.StoryArticleID", '') 
				->where("ca.StoryCategoryID = ? ", $advancesearch['isCat'])
				->where("ca.IsDeleted = 'N' ")
				;
			}
			if (isset($advancesearch['catIds']) && count($advancesearch['catIds']) > 0) {
				$select = $select->distinct()
				->joinInner(array('ca2' => "story_category_article"), "tbl.ID = ca2.StoryArticleID", '') 
				->where("ca2.StoryCategoryID in ('" . implode("','", $advancesearch['catIds']) . "')")
				->where("ca2.IsDeleted = 'N' ")
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
			$form["MemberID"] = $this->UserID;
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
			$form["CreatedBy"] = $this->UserID;
			$form["IsEnable"] = empty($params['hdfArticlePublicComment']) ? 'N' : $params['hdfArticlePublicComment'];
			$data->result = $this->db->insert("story_article_comment", $form);

		} catch (Exception $e) {

		}
		return $data;
	}

	public static function saveView($itemID, $memberID, $userID, $lang, $accessFrom) {


		$data = new stdClass();
		$data->result = false;
		try {
			$db = Zend_Db_Table::getDefaultAdapter();
			$request = Zend_Controller_Front::getInstance()->getRequest();
			
			$created = date("Y-m-d H:i:s");
			$form = array();
			$form["StoryArticleID"] = $itemID;
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
			$affectedRows = $db->insert("story_article_view", $form);
			
			if ($affectedRows) {
				$select = $db->select()->from(array("tbl" => "story_article_view"), array("TotalView" => "count(tbl.ID)"))
				->where("StoryArticleID = ?", $itemID)
				;
				$stmt = $db->query($select);
				$dataTotal = $stmt->fetchObject();
				
				$formSummary = array();
				$whereSummary = array();
				$formSummary["ViewAmount"] = $dataTotal->TotalView;
				$whereSummary["ID = ?"] = $itemID;
				$affectedRows = $db->update("story_article_info", $formSummary, $whereSummary);
			}
			$data->result = true;
		} catch (Exception $e) {

		}
		return $data;
	}

}

?>
