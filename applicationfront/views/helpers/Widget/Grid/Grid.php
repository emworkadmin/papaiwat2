<?php

class Zend_View_Helper_Widget_Grid_Grid extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Grid_Grid($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');

		// --------------------------------------------- Get Widget Item -------------------------------------------- //

		$selectWI = $db->select()
		->from(array('tbl' => 'widget_item'), '')
		->columns(array(
				"ID" => "tbl.ID",
				"Name" => "tbl.Name",
				"DataType" => "tbl.DataType",
				"WidgetType" => "tbl.WidgetType",
				"ContentSettings" => "tbl.ContentSettings",
				"DisplaySettings" => "tbl.DisplaySettings",
		))
		->where("tbl.WidgetType = 'grid' ")
		->where("tbl.IsEnable = 'Y' ")
		->where("tbl.IsDeleted ='N' ")
		->where("tbl.ID = ? ", $this->params['item'])
		;
		$stmtWI = $db->query($selectWI);
		$dataWI = $stmtWI->fetchObject();

		// ------------------------------------------------- Setting ------------------------------------------------ //

		$UID = uniqid();

		$ContentSettings = !empty($dataWI->ContentSettings) ? json_decode($dataWI->ContentSettings,true) : null;
		$DisplaySettings = !empty($dataWI->DisplaySettings) ? json_decode($dataWI->DisplaySettings,true) : null;

		$dataType = $dataWI->DataType; // content, vrv, gallery, calendar, banner, weblink, event, custom

		// Content Setting ------------

		$Category = empty($ContentSettings['settingCategory']) ? null : $ContentSettings['settingCategory'];
		$UseCategoryParent = empty($ContentSettings['settingUseCategoryParent']) ? 'N' : $ContentSettings['settingUseCategoryParent'];
		$Sidx = empty($ContentSettings['settingSidx']) ? 'Created' : $ContentSettings['settingSidx'];
		$Sord = empty($ContentSettings['settingSord']) ? 'ASC' : $ContentSettings['settingSord'];
		$Limit = empty($ContentSettings['settingLimit']) ? '1000' : $ContentSettings['settingLimit'];

		// Display Setting ------------

		$Grid = empty($DisplaySettings['settingShowColumn']) ? 6 : $DisplaySettings['settingShowColumn'];

		$ImageShow = empty($DisplaySettings['settingShowImage']) ? 'Y' : $DisplaySettings['settingShowImage'];
		$ImageShowLink = empty($DisplaySettings['settingImageShowLink']) ? 'Y' : $DisplaySettings['settingImageShowLink'];
		$ImageTarget = empty($DisplaySettings['settingImageTarget']) ? '_blank' : $DisplaySettings['settingImageTarget'];

		$TitleShow = empty($DisplaySettings['settingShowTitle']) ? 'Y' : $DisplaySettings['settingShowTitle'];
		$TitleShowLink = empty($DisplaySettings['settingTitleShowLink']) ? 'Y' : $DisplaySettings['settingTitleShowLink'];
		$TitleTarget = empty($DisplaySettings['settingTitleTarget']) ? '_blank' : $DisplaySettings['settingTitleTarget'];

		$IntroTextShow = empty($DisplaySettings['settingShowIntroText']) ? 'Y' : $DisplaySettings['settingShowIntroText'];
		$IntroTextShowLink = empty($DisplaySettings['settingIntroTextShowLink']) ? 'Y' : $DisplaySettings['settingIntroTextShowLink'];
		$IntroTextTarget = empty($DisplaySettings['settingIntroTextTarget']) ? '_blank' : $DisplaySettings['settingIntroTextTarget'];

		$ImageAspectRatio = empty($DisplaySettings['settingImageAspectRatio']) ? 'ratio-1-1' : $DisplaySettings['settingImageAspectRatio'];

		$ShowOverlay = empty($DisplaySettings['settingShowOverlay']) ? 'N' : $DisplaySettings['settingShowOverlay'];

		$HtmlClass = empty($DisplaySettings['settingHtmlClass']) ? '' : $DisplaySettings['settingHtmlClass'];

		$CustomCSS = empty($DisplaySettings['settingCustomCSS']) ? '' : $DisplaySettings['settingCustomCSS'];

		// -------------------------------------------- Get Data Content -------------------------------------------- //

		if($dataType=='content'){

			// Setting New and Update Icon Content
			$setting = new stdClass();
			$setting->contentSettingNewlimitdata = System_Model_Setting::getValue("Newlimitdata", "Content");
			$setting->contentSettingNewdistancedate = System_Model_Setting::getValue("Newdistancedate", "Content");
			$setting->contentSettingUpdatelimitdata = System_Model_Setting::getValue("Updatelimitdata", "Content");
			$setting->contentSettingUpdatedistancedate = System_Model_Setting::getValue("Updatedistancedate", "Content");
			
			$dataupdate = array();
			$datanew = array();
			
			$selectupdate = $db->select()
			->from(array('tbl' => 'content_article_info'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"Modified" => "tbl.Modified",
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted ='N'")
			->order('tbl.Modified desc')
			->limit($setting->contentSettingUpdatelimitdata)
			;
			
			$stmtupdate = $db->query($selectupdate);
			$dataupdate = $stmtupdate->fetchAll();
			
			$selectnew = $db->select()
			->from(array('tbl' => 'content_article_info'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"Created" => "tbl.Created",
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted ='N'")
			->order('tbl.Created desc')
			->limit($setting->contentSettingNewlimitdata)
			;
			
			$stmtnew = $db->query($selectnew);
			$datanew = $stmtnew->fetchAll();
			
			
			$select = $db->select()
			->from(array('i' => 'content_article_info'), '')
			->joinLeft(array('d' => 'content_article_detail') , "i.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array("fi" => "file_info"), "i.IntroImageID = fi.ID ", "")
			->columns(array(
					"IID" => "i.ID",
					"ID" => "i.ContentCategoryID",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"IntroImageHashName" => "fi.HashName",
					"Created" => "i.Created",
					"Modified" => "i.Modified",
					"PublishDate" => "i.PublishDate",
					"ViewAmount" => "i.ViewAmount",
			))
			->where("i.IsEnable = 'Y' ")
			->where("i.IsDeleted ='N' ")
			->where("i.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("case when i.PublishDown is null then 1 else (case when i.PublishDown >= ? then 1 else 0 end) end = 1", date('Y-m-d H:i:s'))
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			;
			if(!empty($Category)){
				if($UseCategoryParent=='Y'){
					$categoryIds = $this->getRecursiveContentCategoryIds($Category);
					$categoryIds[] = $Category;
					$select = $select
					->joinInner(array("ca" => "content_category_article"), "i.ID = ca.ContentArticleID", "")
					->where("ca.ContentCategoryID in (". implode(",",$categoryIds). ")")
					->group("i.ID");
				}else{
					$select = $select
					->joinInner(array("ca" => "content_category_article"), "i.ID = ca.ContentArticleID", "")
					->where("ca.ContentCategoryID = ?", $Category);
				}
			}
			if(!empty($Sidx)){
				switch ($Sidx) {
					case 'name' :
						$sortType = 'd.Title';
						break;
					case 'created' :
						$sortType = 'i.Created';
						break;
					case 'publishdate' :
						$sortType = 'i.PublishDate';
						break;
					case 'viewamount' :
						$sortType = 'i.ViewAmount';
						break;
				}
				$sortDirection = empty($Sord) ? 'ASC' : $Sord;
				$select = $select->order("$sortType $sortDirection");
			}
			if($Limit){
				$select = $select->limit("$Limit");
			}
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
		}
		 
		// -------------------------------------------- Get Data VRV -------------------------------------------- //

		if($dataType=='vrv'){
			$select = $db->select()
			->from(array('i' => 'vrv_item_info'), '')
			->joinLeft(array('d' => 'vrv_item_detail') , "i.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array("fi" => "file_info"), "i.IntroImageID = fi.ID ", "")
			->columns(array(
					"IID" => "i.ID",
					"ID" => "i.VrvCategoryID",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"IntroImageHashName" => "fi.HashName",
					"Created" => "i.Created",
					"PublishDate" => "i.PublishDate",
					"ViewAmount" => "i.ViewAmount",
			))
			->where("i.IsEnable = 'Y' ")
			->where("i.IsDeleted ='N' ")
			->where("i.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("case when i.PublishDown is null then 1 else (case when i.PublishDown >= ? then 1 else 0 end) end = 1", date('Y-m-d H:i:s'))
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			;
			if(!empty($Category)){
				if($UseCategoryParent=='Y'){
					$categoryIds = $this->getRecursiveVrvCategoryIds($Category);
					$categoryIds[] = $Category;
					$select = $select
					->joinInner(array("ga" => "vrv_category_item"), "i.ID = ga.VrvItemID", "")
					->where("ga.VrvCategoryID in (". implode(",",$categoryIds). ")")
					->group("i.ID");
				}else{
					$select = $select
					->joinInner(array("ga" => "vrv_category_item"), "i.ID = ga.VrvItemID", "")
					->where("ga.VrvCategoryID = ?", $Category);
				}
			}
			if(!empty($Sidx)){
				switch ($Sidx) {
					case 'name' :
						$sortType = 'd.Title';
						break;
					case 'created' :
						$sortType = 'i.Created';
						break;
					case 'publishdate' :
						$sortType = 'i.PublishDate';
						break;
					case 'viewamount' :
						$sortType = 'i.ViewAmount';
						break;
					case 'ordering' :
						$sortType = 'i.Ordering';
						break;
				}
				$sortDirection = empty($Sord) ? 'ASC' : $Sord;
				$select = $select->order("$sortType $sortDirection");
			}
			if($Limit){
				$select = $select->limit("$Limit");
			}
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
		}
		
		// -------------------------------------------- Get Data Gallery -------------------------------------------- //

		if($dataType=='gallery'){
			$select = $db->select()
			->from(array('i' => 'gallery_article_info'), '')
			->joinLeft(array('d' => 'gallery_article_detail') , "i.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array("fi" => "file_info"), "i.IntroImageID = fi.ID ", "")
			->columns(array(
					"IID" => "i.ID",
					"ID" => "i.GalleryCategoryID",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"IntroImageHashName" => "fi.HashName",
					"Created" => "i.Created",
					"PublishDate" => "i.PublishDate",
					"ViewAmount" => "i.ViewAmount",
			))
			->where("i.IsEnable = 'Y' ")
			->where("i.IsDeleted ='N' ")
			->where("i.IsTrash ='N' ")
			->where("i.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("case when i.PublishDown is null then 1 else (case when i.PublishDown >= ? then 1 else 0 end) end = 1", date('Y-m-d H:i:s'))
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			;
			if(!empty($Category)){
				if($UseCategoryParent=='Y'){
					$categoryIds = $this->getRecursiveGalleryCategoryIds($Category);
					$categoryIds[] = $Category;
					$select = $select
					->joinInner(array("ga" => "gallery_category_article"), "i.ID = ga.GalleryArticleID", "")
					->where("ga.GalleryCategoryID in (". implode(",",$categoryIds). ")")
					->group("i.ID");
				}else{
					$select = $select
					->joinInner(array("ga" => "gallery_category_article"), "i.ID = ga.GalleryArticleID", "")
					->where("ga.GalleryCategoryID = ?", $Category);
				}
			}
			if(!empty($Sidx)){
				switch ($Sidx) {
					case 'name' :
						$sortType = 'd.Title';
						break;
					case 'created' :
						$sortType = 'i.Created';
						break;
					case 'publishdate' :
						$sortType = 'i.PublishDate';
						break;
					case 'viewamount' :
						$sortType = 'i.ViewAmount';
						break;
					case 'ordering' :
						$sortType = 'i.Ordering';
						break;
				}
				$sortDirection = empty($Sord) ? 'ASC' : $Sord;
				$select = $select->order("$sortType $sortDirection");
			}
			if($Limit){
				$select = $select->limit("$Limit");
			}
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
		}

		// -------------------------------------------- Get Data Calendar ------------------------------------------- //

		if($dataType=='calendar'){
			$select = $db->select()
			->from(array('i' => 'calendar_article_info'), '')
			->joinLeft(array('d' => 'calendar_article_detail') , "i.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array("fi" => "file_info"), "i.IntroImageID = fi.ID ", "")
			->columns(array(
					"IID" => "i.ID",
					"ID" => "i.CalendarCategoryID",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"IntroImageHashName" => "fi.HashName",
					"Created" => "i.Created",
					"StartDate" => "i.StartDate",
					"EndDate" => "i.EndDate",
					"PublishDate" => "i.PublishDate",
					"ViewAmount" => "i.ViewAmount",
			))
			->where("i.IsEnable = 'Y' ")
			->where("i.IsDeleted ='N' ")
			->where("i.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("case when i.PublishDown is null then 1 else (case when i.PublishDown >= ? then 1 else 0 end) end = 1", date('Y-m-d H:i:s'))
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			;
			if(!empty($Category)){
				if($UseCategoryParent=='Y'){
					$categoryIds = $this->getRecursiveCalendarCategoryIds($Category);
					$categoryIds[] = $Category;
					$select = $select
					->joinInner(array("ca" => "calendar_category_article"), "i.ID = ca.CalendarArticleID", "")
					->where("ca.CalendarCategoryID in (". implode(",", $categoryIds) .")")
					->group("i.ID");
				}else{
					$select = $select
					->joinInner(array("ca" => "calendar_category_article"), "i.ID = ca.CalendarArticleID", "")
					->where("ca.CalendarCategoryID = ?", $Category);
				}
			}
			if(!empty($Sidx)){
				switch ($Sidx) {
					case 'name' :
						$sortType = 'd.Title';
						break;
					case 'created' :
						$sortType = 'i.Created';
						break;
					case 'publishdate' :
						$sortType = 'i.PublishDate';
						break;
					case 'viewamount' :
						$sortType = 'i.ViewAmount';
						break;
				}
				$sortDirection = empty($Sord) ? 'ASC' : $Sord;
				$select = $select->order("$sortType $sortDirection");
			}
			if($Limit){
				$select = $select->limit("$Limit");
			}
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();

			if(count($data)>0) {
				foreach($data as $k => $v) {
					$data[$k]['StartDate'] = new DateTime($data[$k]['StartDate']);
				}
			}
		}

		// -------------------------------------------- Get Data Story -------------------------------------------- //

		if($dataType=='story'){
			$select = $db->select()
			->from(array('i' => 'story_article_info'), '')
			->joinLeft(array('d' => 'story_article_detail') , "i.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array("fi" => "file_info"), "i.IntroImageID = fi.ID ", "")
			->columns(array(
					"IID" => "i.ID",
					"ID" => "i.StoryCategoryID",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"IntroImageHashName" => "fi.HashName",
					"Created" => "i.Created",
					"PublishDate" => "i.PublishDate",
					"ViewAmount" => "i.ViewAmount",
			))
			->where("i.IsEnable = 'Y' ")
			->where("i.IsDeleted ='N' ")
			->where("i.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("case when i.PublishDown is null then 1 else (case when i.PublishDown >= ? then 1 else 0 end) end = 1", date('Y-m-d H:i:s'))
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			;
			if(!empty($Category)){
				if($UseCategoryParent=='Y'){
					$categoryIds = $this->getRecursiveStoryCategoryIds($Category);
					$categoryIds[] = $Category;
					$select = $select
					->joinInner(array("ca" => "story_category_article") ,"i.ID = ca.StoryArticleID", "")
					->where("ca.StoryCategoryID in (". implode(",",$categoryIds). ")")
					->group("i.ID");
				}else{
					$select = $select
					->joinInner(array("ca" => "story_category_article") ,"i.ID = ca.StoryArticleID", "")
					->where("ca.StoryCategoryID = ?", $Category);
				}
			}
			if(!empty($Sidx)){
				switch ($Sidx) {
					case 'name' :
						$sortType = 'd.Title';
						break;
					case 'created' :
						$sortType = 'i.Created';
						break;
					case 'publishdate' :
						$sortType = 'i.PublishDate';
						break;
					case 'viewamount' :
						$sortType = 'i.ViewAmount';
						break;
				}
				$sortDirection = empty($Sord) ? 'ASC' : $Sord;
				$select = $select->order("$sortType $sortDirection");
			}
			if($Limit){
				$select = $select->limit("$Limit");
			}
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
		}

		// --------------------------------------------- Get Data Banner -------------------------------------------- //

		if($dataType=='banner'){
			$select = $db->select()
			->from(array('i' => 'banner_item_info'), '')
			->joinLeft(array('d' => 'banner_item_detail') , "i.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array("fi" => "file_info"), "i.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "i.ID",
					"Url" => "i.Url",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"IntroImageHashName" => "fi.HashName",
					"Created" => "i.Created",
					"PublishDate" => "i.PublishDate",
					"ViewAmount" => "i.ViewAmount",
					"Ordering" => "i.Ordering",
			))
			->where("i.IsEnable = 'Y' ")
			->where("i.IsDeleted ='N' ")
			->where("i.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("case when i.PublishDown is null then 1 else (case when i.PublishDown >= ? then 1 else 0 end) end = 1", date('Y-m-d H:i:s'))
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			;
			if(!empty($Category)){
				$select = $select->where("i.BannerCategoryID = ?", $Category);
			}
			if(!empty($Sidx)){
				switch ($Sidx) {
					case 'name' :
						$sortType = 'd.Title';
						break;
					case 'created' :
						$sortType = 'i.Created';
						break;
					case 'publishdate' :
						$sortType = 'i.PublishDate';
						break;
					case 'viewamount' :
						$sortType = 'i.ViewAmount';
						break;
					case 'ordering' :
						$sortType = 'i.Ordering';
						break;
				}
				$sortDirection = empty($Sord) ? 'ASC' : $Sord;
				$select = $select->order("$sortType $sortDirection");
			}
			if($Limit){
				$select = $select->limit("$Limit");
			}
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
		}

		// -------------------------------------------- Get Data Weblink -------------------------------------------- //

		if($dataType=='weblink') {
			$select = $db->select()
			->from(array('i' => 'weblink_item_info'), '')
			->joinLeft(array('d' => 'weblink_item_detail') , "i.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array("fi" => "file_info"), "i.IntroImageID = fi.ID ", "")
			->columns(array(
					"Url" => "i.Url",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"IntroImageHashName" => "fi.HashName",
					"Created" => "i.Created",
					"PublishDate" => "i.PublishDate",
					"ViewAmount" => "i.ViewAmount",
					"Ordering" => "i.Ordering",
			))
			->where("i.IsEnable = 'Y' ")
			->where("i.IsDeleted ='N' ")
			->where("i.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("case when i.PublishDown is null then 1 else (case when i.PublishDown >= ? then 1 else 0 end) end = 1", date('Y-m-d H:i:s'))
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			;
			if(!empty($Category)){
				$select = $select->where("i.WeblinkCategoryID = ?", $Category);
			}
			if(!empty($Sidx)){
				switch ($Sidx) {
					case 'name' :
						$sortType = 'd.Title';
						break;
					case 'created' :
						$sortType = 'i.Created';
						break;
					case 'publishdate' :
						$sortType = 'i.PublishDate';
						break;
					case 'viewamount' :
						$sortType = 'i.ViewAmount';
						break;
					case 'ordering' :
						$sortType = 'i.Ordering';
						break;
				}
				$sortDirection = empty($Sord) ? 'ASC' : $Sord;
				$select = $select->order("$sortType $sortDirection");
			}
			if($Limit) {
				$select = $select->limit("$Limit");
			}
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
		}

		// -------------------------------------------- Get Data Event -------------------------------------------- //

		if($dataType=='event') {
			$select = $db->select()
			->from(array('i' => 'event_event_info'), '')
			->joinLeft(array('d' => 'event_event_detail') , "i.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array("fi" => "file_info"), "i.IntroImageID = fi.ID ", "")
			->columns(array(
					"i.ID",
					"Title" => "d.Title",
					// "IntroText" => "d.Detail",
					"IntroImageHashName" => "fi.HashName",
					"Created" => "i.Created",
					"PublishDate" => "i.PublishDate",
					"ViewAmount" => "i.ViewAmount",
					"Ordering" => "i.Ordering",
			))
			->where("i.IsEnable = 'Y' ")
			->where("i.IsDeleted ='N' ")
			->where("i.PublishDate <= ? ", date('Y-m-d H:i:s'))
			->where("case when i.PublishDown is null then 1 else (case when i.PublishDown >= ? then 1 else 0 end) end = 1", date('Y-m-d H:i:s'))
			->where("d.Title is not null")
			->where("d.Title <> '' ")
			;
			if(!empty($Category)){
				$select = $select
				->joinInner(array("ca" => "event_category_event"), "i.ID = ca.EventEventID", "")
				->where("ca.EventCategoryID = ?", $Category);
			}
			if(!empty($Sidx)){
				switch ($Sidx) {
					case 'name' :
						$sortType = 'd.Title';
						break;
					case 'created' :
						$sortType = 'i.Created';
						break;
					case 'publishdate' :
						$sortType = 'i.PublishDate';
						break;
					case 'viewamount' :
						$sortType = 'i.ViewAmount';
						break;
					case 'ordering' :
						$sortType = 'i.Ordering';
						break;
				}
				$sortDirection = empty($Sord) ? 'ASC' : $Sord;
				$select = $select->order("$sortType $sortDirection");
			}
			if($Limit){
				$select = $select->limit("$Limit");
			}

			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
		}

		// ----------------------------------------------- Get Custom ----------------------------------------------- //

		if($dataType=='custom'){
			$data = $ContentSettings;
		}

		// ---------------------------------------------- Present Data ---------------------------------------------- //

		echo '<link rel="stylesheet" type="text/css" href="/assets/wgrid/css/styles.css">';
		echo '<style>'.$CustomCSS.'</style>';

		echo '<div class="widget-kit-grid '.$HtmlClass.'">';
		echo '<div class="row">';
		
		$colSize = 12;
		switch ($Grid) {
			case 2 :
				$colSize = 6;
				break;
			case 3 :
				$colSize = 4;
				break;
			case 4 :
				$colSize = 3;
				break;
			case 6 :
				$colSize = 2;
				break;
			case 1 :
				$colSize = 12;
				break;
		}
		
		if(count($data)>0){
			foreach($data as $k=>$v){

				// Icon Update and New
				if($v['Created']){
					$v["Created"] = empty($v["Created"]) ? null : new DateTime($v["Created"]);
					$datecontent = $v['Created']->format("Y-m-d H:i");
				}

				if($v['Modified']){
					$v["Modified"] = empty($v["Modified"]) ? null : new DateTime($v["Modified"]);
					$datemodifycontent = $v['Modified']->format("Y-m-d H:i");
				}
				if($this->setting->contentSettingNewdistancedate){
					$datenow = new Datetime($now);
					$datenow->sub(new DateInterval('P'.$setting->contentSettingNewdistancedate.'D'));	
					$now = date("Y-m-d H:i");
					$dataperiodnew = $datenow->format('Y-m-d H:i');
				}

				if($setting->contentSettingUpdatedistancedate){
					$datenowupdate = new Datetime($now);
					$datenowupdate->sub(new DateInterval('P'.$setting->contentSettingUpdatedistancedate.'D'));	
					$now = date("Y-m-d H:i");
					$dataperiodupdate = $datenowupdate->format('Y-m-d H:i');
				}

				// Prepare Data
				switch($dataType){
					case 'content':
						$linkUrl = $this->view->url(array("module" => "content", "controller" => "category", "action" => "detail", "id" => $v["ID"], "iid" => $v["IID"]), null, true);
						break;
					case 'vrv':
						$linkUrl = $this->view->url(array("module" => "vrv", "controller" => "category", "action" => "detail", "id" => $v["ID"], "iid" => $v["IID"]), null, true);
						break;
					case 'gallery':
						$linkUrl = $this->view->url(array("module" => "gallery", "controller" => "category", "action" => "detail", "id" => $v["ID"], "iid" => $v["IID"]), null, true);
						break;
					case 'calendar':
						$linkUrl = $this->view->url(array("module" => "calendar", "controller" => "category", "action" => "detail", "id" => $v["ID"], "iid" => $v["IID"]), null, true);
						break;
					case 'story':
						$linkUrl = $this->view->url(array("module" => "story", "controller" => "category", "action" => "detail", "id" => $v["ID"], "iid" => $v["IID"]), null, true);
						break;
					case 'banner':
						$linkUrl = $v['Url'];
						break;
					case 'weblink':
						$linkUrl = $v['Url'];
						break;
					case 'event' :
						$linkUrl = $this->view->url(array("module" => "event", "controller" => "event", "action" => "index", "id" => $v["ID"]), null, true);
						break;
					case 'custom':
						$linkUrl = $v['Url'];
					default:
						break;
				}

				switch($dataType){
					case 'content':
					case 'vrv':
					case 'gallery':
					case 'calendar':
					case 'story':
					case 'banner':
					case 'weblink':
					case 'event':
						$imageUrl = $this->view->baseUrl("/assets/wgrid/images/no-image.jpg");
						if (!empty($v["IntroImageHashName"])) {
							$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $v["IntroImageHashName"]), null, true);
						}
						break;
					case 'custom':
						$imageUrl = $v['ImageUrl'];
					default:
						break;
				}
				
				echo '<div class="col-md-' . $colSize . '">';
				echo '	<div class="infocard">';
				
				// Show Image
				if ($ImageShow == 'Y') {
					echo '		<div class="image '.$ImageAspectRatio.'">';
					// Show Image Link
					if($ImageShowLink=='Y'){
						echo '			<a class="lnkClickBanner-'.$UID.'-'.$v["ID"].'" href="' . $linkUrl . '" target="' . $ImageTarget . '"><img alt="image" src="' . $imageUrl . '" /></a>';
						if($ShowOverlay=='Y'){
							echo '			<a class="image-overlay lnkClickBanner-'.$UID.'-'.$v["ID"].'" href="' . $linkUrl . '" target="' . $ImageTarget . '">' . $v["Title"] . '</a>';
						}
					}else{
						echo '			<div><img alt="image" src="' . $imageUrl . '" /></div>';
						if($ShowOverlay=='Y'){
							echo '			<div class="image-overlay">' . $v["Title"] . '</div>';
						}
					} // End: Show Image Link
					echo '		</div>';
				} // End: Show Image
				
				if($dataType == 'calendar') {
					echo "<div class='calendar-grid-start'>";
					echo "<span class='Date'>" . $v['StartDate']->format('d') . "</span>";
					echo "<span class='Month'>" . $v['StartDate']->format('m') . "</span>";
					echo "<span class='Year'>" . (intval($v['StartDate']->format('Y')) + ($lang == 'th' ? 543 : 0)) . "</span>";
					echo "</div>";
				}

				// Show Title & Show IntroText
				if ($TitleShow == 'Y' || $IntroTextShow == 'Y') {
				echo '		<div class="detail">';	

				// Show Title
				if ($TitleShow == 'Y') {
					echo '			<div class="detail-title">';
					if($TitleShowLink=='Y'){
						echo '				<a class="lnkClickBanner-'.$UID.'-'.$v["ID"].'" href="' . $linkUrl . '" target="' . $TitleTarget . '">' . $v["Title"] . '</a>';
						
						if($dataType == "content"){
							if($setting->contentSettingNewdistancedate){
								foreach ($datanew as $new) {
									if($v['IID']==$new['ID']){
										if($datecontent > $dataperiodnew ){
									echo	'<span class="imgnew">';
									echo	'<img alt="image" src='.$this->view->baseUrl('/assets/content/icon/new.gif').'>';
									echo 	'</span>';
										}
									}
								}
							}

							if($setting->contentSettingUpdatedistancedate){
								foreach ($dataupdate as $update) {
									// echo $update['ID'];
									if($v['IID']==$update['ID']){
										if($datemodifycontent > $dataperiodupdate ){
									echo	'<span class="imgupdate">';
									echo	'<img alt="image" src='.$this->view->baseUrl('/assets/content/icon/update.gif').'>';
									echo 	'</span>';
										}
									}
								}
							}
						}

					}else{
						echo '				<div>' . $v["Title"] . '</div>';

						if($dataType == "content"){
							if($setting->contentSettingNewdistancedate){
								foreach ($datanew as $new) {
									if($v['IID']==$new['ID']){
										if($datecontent > $dataperiodnew ){
									echo	'<span class="imgnew">';
									echo	'<img alt="image" src='.$this->view->baseUrl('/assets/content/icon/new.gif').'>';
									echo 	'</span>';
										}
									}
								}
							}
	
							if($setting->contentSettingUpdatedistancedate){
								foreach ($dataupdate as $update) {
									if($v['IID']==$update['ID']){
										if($datemodifycontent > $dataperiodupdate ){
									echo	'<span class="imgupdate">';
									echo	'<img alt="image" src='.$this->view->baseUrl('/assets/content/icon/update.gif').'>';
									echo 	'</span>';
										}
									}
								}
							}
						}

					}
					echo '			</div>';
				}// End: Show Title	

				// Show View Amount
				if ($TitleShow == 'Y' && in_array($dataType,array('content','vrv','gallery','calendar','story'))) {
					echo '			<div class="detail-view">';
					if($TitleShowLink=='Y'){
						if($lang == 'th') {
							echo '				<a class="detail-publish" href="' . $linkUrl . '" target="' . $TitleTarget . '"><em class="fa fa-calendar"></em> ' . Emwork_Utility_DateTime::MySQLToDateThai($v["PublishDate"],'DT_F') . '</a>';
						} else {
							echo '				<a class="detail-publish" href="' . $linkUrl . '" target="' . $TitleTarget . '"><em class="fa fa-calendar"></em> ' . Emwork_Utility_DateTime::MySQLToDateEng($v["PublishDate"],'DT_F') . '</a>';
						}
						echo '				<a class="detail-view-amount" href="' . $linkUrl . '" target="' . $TitleTarget . '"><em class="fa fa-eye"></em> ' . $v["ViewAmount"] . '</a>';
					}else{
						if($lang == 'th') {
							echo '				<div class="detail-publish"><em class="fa fa-calendar"></em> ' . Emwork_Utility_DateTime::MySQLToDateThai($v["PublishDate"],'DT_F') . '</div>';
						} else {
							echo '				<div class="detail-publish"><em class="fa fa-calendar"></em> ' . Emwork_Utility_DateTime::MySQLToDateThai($v["PublishDate"],'DT_F') . '</div>';
						}
						echo '				<div class="detail-view-amount"><em class="fa fa-eye"></em> ' . $v["ViewAmount"] . '</div>';
					}
					echo '			</div>';
				}// End: Show View Amount	

				// Show IntroText
				if ($IntroTextShow == 'Y') {
					echo '			<div class="detail-row">';

					if($IntroTextShowLink=='Y') {
						echo '				<a class="lnkClickBanner-'.$UID.'-'.$v["ID"].'" href="' . $linkUrl . '" target="' . $IntroTextTarget . '">';
						if($dataType == 'event') {
							if($lang == 'th') {
								echo '<div>'; 
								echo '<span class="grid-startevent-text">' . Emwork_Translate::text($lang, 'wgrid', 'startdate') .' : </span><span class="grid-startevent-date">' . Emwork_Utility_DateTime::MySQLToDateThai($v['StartDate']) . '</span>';
								echo '<span class="grid-event-"> - </span>';
								echo '<span class="grid-endevent-text">'. Emwork_Translate::text($lang, 'wgrid', 'enddate') .' : </span><span class="grid-endevent-date">' . Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySQLToDateThai($v['EndDate'])) . '</span>';
								echo '</div>';
							} else {
								echo '<div>'; 
								echo '<span class="grid-startevent-text">' . Emwork_Translate::text($lang, 'wgrid', 'startdate') .' : </span><span class="grid-startevent-date">' . Emwork_Utility_DateTime::MySQLToDateEng($v['StartDate']) . '</span>';
								echo '<span class="grid-event-"> - </span>';
								echo '<span class="grid-endevent-text">'. Emwork_Translate::text($lang, 'wgrid', 'enddate') .' : </span><span class="grid-endevent-date">' . Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySQLToDateEng($v['EndDate'])) . '</span>';
								echo '</div>';
							}
						} else {
							echo nl2br($v["IntroText"]);
						}
						echo '              </a>';
					} else {
						if($dataType == 'event') {
							echo '<div>'; 
							echo '<span class="grid-startevent-text">' . Emwork_Translate::text($lang, 'wgrid', 'startdate') .' : </span><span class="grid-startevent-date">' . Emwork_Utility_DateTime::MySQLToDateThai($v['StartDate']) . '</span>';
							echo '<span class="grid-event-"> - </span>';
							echo '<span class="grid-endevent-text">'. Emwork_Translate::text($lang, 'wgrid', 'enddate') .' : </span><span class="grid-endevent-date">' . Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySQLToDateThai($v['EndDate'])) . '</span>';
							echo '</div>';
						} else {
							echo '				<div>' . nl2br($v["IntroText"]) . '</div>';
						}
					}					

					echo '			</div>';
				} // End: Show IntroText	

				echo '		</div>';
				}

				if($dataType == 'weblink') {
					if($DisplaySettings['settingWeblinkbuttonShowLink'] == 'Y') :
						echo '<a class="btn btn-info" href="' . $linkUrl . '">'. Emwork_Translate::text($lang, 'wgrid', 'viewdetail') .'</a>';
					endif;
				}
				
				echo '	</div>';
				echo '</div>';

				if ($dataType == 'banner') {
				echo '<script>';
				echo '	$(".lnkClickBanner-'.$UID.'-'.$v["ID"].'").click(function() {';
				echo '		$.post("'.$this->view->url(array('module' => 'banner', 'controller' => 'item', 'action' => 'click'), null, true).'", {id:"'.$v['ID'].'"}, function(data) {';
				echo '		});';
				echo '	});';
				echo '</script>';
				}

			} // End Foreach
		} // End Count

		echo '</div>';
		echo '</div>';

		$style = '
        <style>
			.widget-kit-grid .infocard .detail .detail-title {
				white-space: normal ;
			}
		</style>
		' ;
		echo $style;

	}

	public function getRecursiveContentCategoryIds($id = null) {

		$db = Zend_Db_Table::getDefaultAdapter();
		
		$select = $db->select()
		->from ( array ('tbs' => 'content_category_info' ), array ('ID') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->where ( "tbs.ParentID = ?", $id )
		->order ( "tbs.Ordering ASC" )
		;
		$stmt = $db->query ( $select );
		$data = $stmt->fetchAll ();

		foreach ( $data as $value ) {
			if (empty($id) || ($id != $value["ID"])) {
				$this->getRecursiveContentCategoryIds($value ["ID"]);
				if(!in_array($value["ID"],$recursiveIds)){ 
					$recursiveIds[] = $value["ID"]; 
				}
			}
		}
		return $recursiveIds;
	}

	public function getRecursiveVrvCategoryIds($id = null) {

		$db = Zend_Db_Table::getDefaultAdapter();
		
		$select = $db->select()
		->from ( array ('tbs' => 'vrv_category_info' ), array ('ID') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->where ( "tbs.ParentID = ?", $id )
		->order ( "tbs.Ordering ASC" )
		;
		$stmt = $db->query ( $select );
		$data = $stmt->fetchAll ();

		foreach ( $data as $value ) {
			if (empty($id) || ($id != $value["ID"])) {
				$this->getRecursiveVrvCategoryIds($value ["ID"]);
				if(!in_array($value["ID"],$recursiveIds)){ 
					$recursiveIds[] = $value["ID"]; 
				}
			}
		}
		return $recursiveIds;
	}

	public function getRecursiveGalleryCategoryIds($id = null) {

		$db = Zend_Db_Table::getDefaultAdapter();
		
		$select = $db->select()
		->from ( array ('tbs' => 'gallery_category_info' ), array ('ID') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->where ( "tbs.ParentID = ?", $id )
		->order ( "tbs.Ordering ASC" )
		;
		$stmt = $db->query ( $select );
		$data = $stmt->fetchAll ();

		foreach ( $data as $value ) {
			if (empty($id) || ($id != $value["ID"])) {
				$this->getRecursiveGalleryCategoryIds($value ["ID"]);
				if(!in_array($value["ID"],$recursiveIds)){ 
					$recursiveIds[] = $value["ID"]; 
				}
			}
		}
		return $recursiveIds;
	}

	public function getRecursiveCalendarCategoryIds($id = null) {

		$db = Zend_Db_Table::getDefaultAdapter();
		
		$select = $db->select()
		->from ( array ('tbs' => 'calendar_category_info' ), array ('ID') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->where ( "tbs.ParentID = ?", $id )
		->order ( "tbs.Ordering ASC" )
		;
		$stmt = $db->query ( $select );
		$data = $stmt->fetchAll ();

		foreach ( $data as $value ) {
			if (empty($id) || ($id != $value["ID"])) {
				$this->getRecursiveCalendarCategoryIds($value ["ID"]);
				if(!in_array($value["ID"],$recursiveIds)){ 
					$recursiveIds[] = $value["ID"]; 
				}
			}
		}
		return $recursiveIds;
	}

	public function getRecursiveStoryCategoryIds($id = null) {

		$db = Zend_Db_Table::getDefaultAdapter();
		
		$select = $db->select()
		->from ( array ('tbs' => 'story_category_info' ), array ('ID') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->where ( "tbs.ParentID = ?", $id )
		->order ( "tbs.Ordering ASC" )
		;
		$stmt = $db->query ( $select );
		$data = $stmt->fetchAll ();

		foreach ( $data as $value ) {
			if (empty($id) || ($id != $value["ID"])) {
				$this->getRecursiveStoryCategoryIds($value ["ID"]);
				if(!in_array($value["ID"],$recursiveIds)){ 
					$recursiveIds[] = $value["ID"]; 
				}
			}
		}
		return $recursiveIds;
	}
	
}