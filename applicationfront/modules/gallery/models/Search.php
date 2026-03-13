<?php

class Gallery_Model_Search {
	
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

			->joinLeft(array("gc" => "gallery_article_cover"), "tbl.ID = gc.GalleryArticleID", "")
			->joinLeft(array("gcd" => "gallery_article_cover_detail"), "gc.GalleryArticleID = gcd.GalleryArticleID  and gc.DataCode = gcd.DataCode and gcd.Lang = '{$lang}' ", "")

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
				"IsEnable" => "tbl.IsEnable",
				"AuthorName" => "concat(u.FirstName, ' ', u.LastName)",
				"tbl.PublishDate",
				
				"IntroImageFileName" => "fi.Name",
				"IntroImageHashName" => "fi.HashName",
				"IntroImageFileType" => "fi.FileType",
				"IntroImageFileSize" => "fi.FileSize",
				"IntroImageFileExtension" => "fi.Extension"
			))
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.IsDeleted ='N'")
			->where("tbl.IsTrash ='N'")
			->where("tbl.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			->where("gc.IsEnable = 'Y' ")
			->where("gc.IsDeleted = 'N' ")
			->where("gcd.Title like " . $this->db->quote("%" . $advancesearch['searchText'] . "%") )
			->group("tbl.ID")
			;

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
			if(!empty($advancesearch['infoID'])){
				$select = $select->order('RAND()');
			} else if (in_array($rowSort, array('Title'))) {
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
    
}

?>