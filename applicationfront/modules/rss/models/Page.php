<?php
class Rss_Model_Page {
	
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

	public function getCategoryByLanguage($categoryID, $lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'rss_category_info' ), array ('ID'))
		->joinInner(array('d' => 'rss_category_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}'", array ('Title'))
		->columns(array(
			'ID' => 'tbl.ID',
			'Settings' => 'tbl.Settings',
			'Title' => 'd.Title',
			'IntroText' => 'd.IntroText',
			'IsEnable' => 'd.IsEnable',
			'IsDeleted' => 'd.IsDeleted',
		))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.ID = ?", $categoryID)
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();

		return $data;
	}

	public function getItemListByCategory($categoryID, $lang, $setting) {
		$select = $this->db->select()
		->from(array('tbl' => 'rss_item_info' ), '')
		->joinLeft(array('d' => 'rss_item_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}'" , '')
		->columns(array(
			'ID' => 'tbl.ID',
			"Title" => "d.Title",
			"RSSCategoryID" => "tbl.RSSCategoryID",
			"DataType" => "tbl.DataType",
			"DataCategoryID" => "tbl.DataCategoryID",
		))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.RssCategoryID = ?", $categoryID)
		;

		if($setting->ItemPerPage){
			$select = $select->limit("$setting->ItemPerPage");
		}
		if($setting->ItemSortType){
			switch ($setting->ItemSortType) {
				case 'name' :
					$sidx = 'd.Title';
					break;
				case 'created' :
					$sidx = 'tbl.Created';
					break;
				// case 'publishdate' :
				// 	$sidx = 'tbl.PublishDate';
				// 	break;
				case 'viewamount' :
					$sidx = 'tbl.ViewAmount';
					break;
			}
			$sord = $setting->ItemSortDirection;
			if(empty($sord)){ 
				$sord = 'ASC';
			}
			$select = $select->order("$sidx $sord");
		}

		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();

		return $data;
	}

	public function getItemSetting($ID, $lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'rss_item_info' ), '')
		->joinLeft(array('d' => 'rss_item_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}'" , '')
		->columns(array(
			'ID' => 'tbl.ID',
			'Settings' => 'tbl.Settings',
			'IsEnable' => 'd.IsEnable',
			'IsDeleted' => 'd.IsDeleted',
		))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.ID = ?", $ID)
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();

		return $data;
	}

	// --------------------------------------- Content --------------------------------------- //

	public function getContent($categoryID, $lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'content_category_info' ), array ('ID'))
		->joinInner(array('d' => 'content_category_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}'", array ('Title'))
		->columns(array(
			'ID' => 'tbl.ID',
			'Title' => 'd.Title',
			'IntroText' => 'd.IntroText',
			'Settings' => 'tbl.Settings',
			'IsEnable' => 'd.IsEnable',
			'IsDeleted' => 'd.IsDeleted',
		))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.ID = ?", $categoryID)
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();

		return $data;
	}

	public function getContentList($categoryID, $lang, $setting) {
		$select = $this->db->select()
		->from(array('tbl' => 'content_article_info' ), '')
		->joinLeft(array('d' => 'content_article_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}'" , '')
		->joinLeft(array('f' => 'file_info') , "tbl.IntroImageID = f.ID" , '')
		->columns(array(
			'ID' => 'tbl.ID',
			'PublishDate' => 'tbl.PublishDate',
			"Title" => "d.Title",
			"Detail" => "d.Detail",
			"IntroImageID" => "tbl.IntroImageID",
			"IntroImageHashName" => "f.HashName",
			"IntroImageFileType" => "f.FileType",
		))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.ContentCategoryID = ?", $categoryID)
		->where("d.Title is not null")
		->where("d.Title <> '' ")
		;

		if($setting->Limit){
			$select = $select->limit("$setting->Limit");
		}
		if($setting->Sidx){
			switch ($setting->Sidx) {
				case 'name' :
					$sidx = 'd.Title';
					break;
				case 'created' :
					$sidx = 'tbl.Created';
					break;
				case 'publishdate' :
					$sidx = 'tbl.PublishDate';
					break;
				case 'viewamount' :
					$sidx = 'tbl.ViewAmount';
					break;
			}
			$sord = $setting->Sord;
			if(empty($sord)){ 
				$sord = 'ASC';
			}
			$select = $select->order("$sidx $sord");
		}

		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();

		return $data;
	}

	// --------------------------------------- Gallery --------------------------------------- //

	public function getGallery($categoryID, $lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'gallery_category_info' ), array ('ID'))
		->joinInner(array('d' => 'gallery_category_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}'", array ('Title'))
		->columns(array(
			'ID' => 'tbl.ID',
			'Title' => 'd.Title',
			'IntroText' => 'd.IntroText',
			'Settings' => 'tbl.Settings',
			'IsEnable' => 'd.IsEnable',
			'IsDeleted' => 'd.IsDeleted',
		))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.ID = ?", $categoryID)
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();

		return $data;
	}

	public function getGalleryList($categoryID, $lang, $setting) {
		$select = $this->db->select()
		->from(array('tbl' => 'gallery_article_info' ), '')
		->joinLeft(array('d' => 'gallery_article_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}'" , '')
		->joinLeft(array('f' => 'file_info') , "tbl.IntroImageID = f.ID" , '')
		->columns(array(
			'ID' => 'tbl.ID',
			'PublishDate' => 'tbl.PublishDate',
			"Title" => "d.Title",
			"Detail" => "d.Detail",
			"IntroImageID" => "tbl.IntroImageID",
			"IntroImageHashName" => "f.HashName",
			"IntroImageFileType" => "f.FileType",
		))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.GalleryCategoryID = ?", $categoryID)
		->where("d.Title is not null")
		->where("d.Title <> '' ")
		;
		
		if($setting->Limit){
			$select = $select->limit("$setting->Limit");
		}
		if($setting->Sidx){
			switch ($setting->Sidx) {
				case 'name' :
					$sidx = 'd.Title';
					break;
				case 'created' :
					$sidx = 'tbl.Created';
					break;
				case 'publishdate' :
					$sidx = 'tbl.PublishDate';
					break;
				case 'viewamount' :
					$sidx = 'tbl.ViewAmount';
					break;
			}
			$sord = $setting->Sord;
			if(empty($sord)){ 
				$sord = 'ASC';
			}
			$select = $select->order("$sidx $sord");
		}

		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();

		return $data;
	}

	// --------------------------------------- Calendar --------------------------------------- //

	public function getCalendar($categoryID, $lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'calendar_category_info' ), array ('ID'))
		->joinInner(array('d' => 'calendar_category_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}'", array ('Title'))
		->columns(array(
			'ID' => 'tbl.ID',
			'Title' => 'd.Title',
			'IntroText' => 'd.IntroText',
			'Settings' => 'tbl.Settings',
			'IsEnable' => 'd.IsEnable',
			'IsDeleted' => 'd.IsDeleted',
		))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.ID = ?", $categoryID)
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();

		return $data;
	}

	public function getCalendarList($categoryID, $lang, $setting) {
		$select = $this->db->select()
		->from(array('tbl' => 'calendar_article_info' ), '')
		->joinLeft(array('d' => 'calendar_article_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}'" , '')
		->joinLeft(array('f' => 'file_info') , "tbl.IntroImageID = f.ID" , '')
		->columns(array(
			'ID' => 'tbl.ID',
			'PublishDate' => 'tbl.PublishDate',
			"Title" => "d.Title",
			"Detail" => "d.Detail",
			"IntroImageID" => "tbl.IntroImageID",
			"IntroImageHashName" => "f.HashName",
			"IntroImageFileType" => "f.FileType",
		))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.CalendarCategoryID = ?", $categoryID)
		->where("d.Title is not null")
		->where("d.Title <> '' ")
		;

		if($setting->Limit){
			$select = $select->limit("$setting->Limit");
		}
		if($setting->Sidx){
			switch ($setting->Sidx) {
				case 'name' :
					$sidx = 'd.Title';
					break;
				case 'created' :
					$sidx = 'tbl.Created';
					break;
				case 'publishdate' :
					$sidx = 'tbl.PublishDate';
					break;
				case 'viewamount' :
					$sidx = 'tbl.ViewAmount';
					break;
			}
			$sord = $setting->Sord;
			if(empty($sord)){ 
				$sord = 'ASC';
			}
			$select = $select->order("$sidx $sord");
		}

		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();

		return $data;
	}

	// --------------------------------------- Story --------------------------------------- //

	public function getStory($categoryID, $lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'story_category_info' ), array ('ID'))
		->joinInner(array('d' => 'story_category_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}'", array ('Title'))
		->columns(array(
			'ID' => 'tbl.ID',
			'Title' => 'd.Title',
			'IntroText' => 'd.IntroText',
			'Settings' => 'tbl.Settings',
			'IsEnable' => 'd.IsEnable',
			'IsDeleted' => 'd.IsDeleted',
		))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.ID = ?", $categoryID)
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();

		return $data;
	}

	public function getStoryList($categoryID, $lang, $setting) {
		$select = $this->db->select()
		->from(array('tbl' => 'story_article_info' ), '')
		->joinLeft(array('d' => 'story_article_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}'" , '')
		->joinLeft(array('f' => 'file_info') , "tbl.IntroImageID = f.ID" , '')
		->columns(array(
			'ID' => 'tbl.ID',
			'PublishDate' => 'tbl.PublishDate',
			"Title" => "d.Title",
			"Detail" => "d.Detail",
			"IntroImageID" => "tbl.IntroImageID",
			"IntroImageHashName" => "f.HashName",
			"IntroImageFileType" => "f.FileType",
		))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.StoryCategoryID = ?", $categoryID)
		->where("d.Title is not null")
		->where("d.Title <> '' ")
		;

		if($setting->Limit){
			$select = $select->limit("$setting->Limit");
		}
		if($setting->Sidx){
			switch ($setting->Sidx) {
				case 'name' :
					$sidx = 'd.Title';
					break;
				case 'created' :
					$sidx = 'tbl.Created';
					break;
				case 'publishdate' :
					$sidx = 'tbl.PublishDate';
					break;
				case 'viewamount' :
					$sidx = 'tbl.ViewAmount';
					break;
			}
			$sord = $setting->Sord;
			if(empty($sord)){ 
				$sord = 'ASC';
			}
			$select = $select->order("$sidx $sord");
		}

		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();

		return $data;
	}

}

?>
