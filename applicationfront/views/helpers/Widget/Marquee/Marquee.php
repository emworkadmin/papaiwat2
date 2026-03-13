<?php

class Zend_View_Helper_Widget_Marquee_Marquee extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Marquee_Marquee($params)
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
		->where("tbl.WidgetType = 'marquee' ")
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

		$Direction = empty($DisplaySettings['settingDirection']) ? 'left' : $DisplaySettings['settingDirection'];
		$Duration = empty($DisplaySettings['settingDuration']) ? '20000' : $DisplaySettings['settingDuration'];

		$Duplicated = 'true'; //$DisplaySettings['settingDuplicated'] == 'Y' ? 'true' : 'false';
		$PauseOnHover = $DisplaySettings['settingPauseOnHover'] == 'Y' ? 'true' : 'false';

		$ShowLink = $DisplaySettings['settingShowLink'] == 'Y' ? 'true' : 'false';
		$LinkTarget = empty($DisplaySettings['settingTarget']) ? '_blank' : $DisplaySettings['settingTarget'];
		$BackgroundColor = empty($DisplaySettings['settingBackgroundColor']) ? '#999999' : $DisplaySettings['settingBackgroundColor'];
		$FontColor = empty($DisplaySettings['settingFontColor']) ? '#FFFFFF' : $DisplaySettings['settingFontColor'];

		$ShowTitle = $DisplaySettings['settingShowTitle'] == 'Y' ? 'true' : 'false';
		$Title = empty($DisplaySettings['settingTitle']) ? '' : $DisplaySettings['settingTitle'];
		$TitleBackgroundColor = empty($DisplaySettings['settingTitleBackgroundColor']) ? '#226BB2' : $DisplaySettings['settingTitleBackgroundColor'];
		$TitleFontColor = empty($DisplaySettings['settingTitleFontColor']) ? '#FFFFFF' : $DisplaySettings['settingTitleFontColor'];

		$HtmlClass = empty($DisplaySettings['settingHtmlClass']) ? '' : $DisplaySettings['settingHtmlClass'];

		$CustomCSS = empty($DisplaySettings['settingCustomCSS']) ? '' : $DisplaySettings['settingCustomCSS'];

		// -------------------------------------------- Get Data Content -------------------------------------------- //

		if($dataType=='content'){
			$select = $db->select()
			->from(array('i' => 'content_article_info'), '')
			->joinLeft(array('d' => 'content_article_detail') , "i.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array("fi" => "file_info"), "i.IntroImageID = fi.ID ", "")
			->columns(array(
					"IID" => "i.ID",
					"ID" => "i.ContentCategoryID",
					"Title" => "d.Title",
					// "IntroText" => "d.IntroText",
					// "IntroImageHashName" => "fi.HashName",
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
					// "IntroText" => "d.IntroText",
					// "IntroImageHashName" => "fi.HashName",
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
					// "IntroText" => "d.IntroText",
					// "IntroImageHashName" => "fi.HashName",
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
					// "IntroText" => "d.IntroText",
					// "IntroImageHashName" => "fi.HashName",
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
					// "IntroText" => "d.IntroText",
					// "IntroImageHashName" => "fi.HashName",
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
					// "IntroText" => "d.IntroText",
					// "IntroImageHashName" => "fi.HashName",
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
					// "IntroText" => "d.IntroText",
					// "IntroImageHashName" => "fi.HashName",
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
					// "IntroImageHashName" => "fi.HashName",
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

		echo '<link rel="stylesheet" type="text/css" href="/assets/wmarquee/css/styles.css">';
		echo '<script src="/assets/wmarquee/js/jquery.marquee.min.js"></script>';
		echo '<script src="/assets/wmarquee/js/jquery.easing.min.js"></script>';
		echo '<script src="/assets/wmarquee/js/jquery.pause.js"></script>';

		if($TitleBackgroundColor){
			echo '<style>.widget-kit-marquee .marquee-title { background-color: '.$TitleBackgroundColor.'; }</style>';	
		}
		if($TitleFontColor){
			echo '<style>.widget-kit-marquee .marquee-title { color: '.$TitleFontColor.'; }</style>';	
		}
		if($BackgroundColor){
			echo '<style>.widget-kit-marquee .marquee-slide { background-color: '.$BackgroundColor.'; }</style>';	
		}
		if($FontColor){
			echo '<style>.widget-kit-marquee .marquee-slide .lnkMarquee { color: '.$FontColor.'; }</style>';	
		}
		
		echo '<style>'.$CustomCSS.'</style>';

		echo '<div class="widget-kit-marquee '.$HtmlClass.'">';

		if($ShowTitle=='true'){
			echo '	<div class="marquee-title">';
			echo '		'.$Title;
			echo '	</div>';

			echo '<div class="marquee-slide has-title marquee-'.$UID.'">';
		}else{
			echo '<div class="marquee-slide marquee-'.$UID.'">';	
		}
		
		if(count($data)>0){
			foreach($data as $k=>$v){

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

				if($ShowLink=='true'){
					echo '	<a class="lnkMarquee ' . $Direction . '" href="' . $linkUrl . '" target="' . $LinkTarget . '">';
					echo '		' . $v["Title"];
					echo '	</a>';
				}else{
					echo '	<span class="lnkMarquee ' . $Direction . '">';
					echo '		' . $v["Title"];
					echo '	</span>';
				}
				
				// echo '</div>';

			} // End Foreach
		} // End Count

		echo '</div>';
		echo '</div>';
		$script = '
        <script>
		$(function(){
			$(".marquee-'.$UID.'").marquee({
		  
				//If you wish to always animate using jQuery
				allowCss3Support: true,
			
				//works when allowCss3Support is set to true - for full list see http://www.w3.org/TR/2013/WD-css3-transitions-20131119/#transition-timing-function
				css3easing: "linear",
			
				//requires jQuery easing plugin. Default is "linear"
				easing: "linear",
			
				//pause time before the next animation turn in milliseconds
				delayBeforeStart: 1000,

				//"left", "right", "up" or "down"
				direction: "'.$Direction.'",

				//true or false - should the marquee be duplicated to show an effect of continues flow
				duplicated: '.$Duplicated.',
			
				//speed in milliseconds of the marquee in milliseconds
				duration: '.$Duration.',
			
				//gap in pixels between the tickers
				gap: 20,
			
				//on cycle pause the marquee
				pauseOnCycle: false,
			
				//on hover pause the marquee - using jQuery plugin https://github.com/tobia/Pauses
				pauseOnHover: '.$PauseOnHover.',
			
				//the marquee is visible initially positioned next to the border towards it will be moving
				startVisible: true
				
				});
			});
		</script>
		' ;
		echo $script;

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