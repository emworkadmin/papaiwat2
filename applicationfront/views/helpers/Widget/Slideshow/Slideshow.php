<?php

class Zend_View_Helper_Widget_Slideshow_Slideshow extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Slideshow_Slideshow($params)
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
		->where("tbl.WidgetType = 'slideshow' ")
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
		$Sidx = empty($ContentSettings['settingSidx']) ? '' : $ContentSettings['settingSidx'];
		$Sord = empty($ContentSettings['settingSord']) ? 'ASC' : $ContentSettings['settingSord'];
		$Limit = empty($ContentSettings['settingLimit']) ? '1000' : $ContentSettings['settingLimit'];

		// Display Setting ------------
		$Height = empty($DisplaySettings['settingHeight']) ? '450px' : $DisplaySettings['settingHeight'];
		$NavigationArrowShow = empty($DisplaySettings['settingNavigationArrowShow']) ? 'Y' : $DisplaySettings['settingNavigationArrowShow'];
		$NavigationBulletShow = empty($DisplaySettings['settingNavigationBulletShow']) ? 'Y' : $DisplaySettings['settingNavigationBulletShow'];
		$NavigationDuration = empty($DisplaySettings['settingNavigationDuration']) ? '100' : $DisplaySettings['settingNavigationDuration'];

		$AutoPlay = $DisplaySettings['settingAutoPlay'] == 'Y' ? '1' : '0';
		$SlideDuration = empty($DisplaySettings['settingSlideDuration']) ? '100' : $DisplaySettings['settingSlideDuration'];

		$defaultAnimation = '{$Duration:1500,y:-4,$Zoom:11,$SlideOut:true,$Easing:{$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}';
		$Animation = empty($DisplaySettings['settingAnimation']) ? $defaultAnimation : $DisplaySettings['settingAnimation'];
		if(!empty($SlideDuration)){
			if(strpos($Animation, 'Duration:4000') !== false) { 
				$Animation = str_replace("4000",$SlideDuration,$Animation);
			}else if(strpos($Animation, 'Duration:1800') !== false) { 
				$Animation = str_replace("1800",$SlideDuration,$Animation);	
			}else if(strpos($Animation, 'Duration:1500') !== false) { 
				$Animation = str_replace("1500",$SlideDuration,$Animation);
			}else if(strpos($Animation, 'Duration:1200') !== false) { 
				$Animation = str_replace("1200",$SlideDuration,$Animation);
			}else if(strpos($Animation, 'Duration:1000') !== false) { 
				$Animation = str_replace("1000",$SlideDuration,$Animation);
			}else if(strpos($Animation, 'Duration:800') !== false) { 
				$Animation = str_replace("800",$SlideDuration,$Animation);
			}else if(strpos($Animation, 'Duration:700') !== false) { 
				$Animation = str_replace("700",$SlideDuration,$Animation);
			}else if(strpos($Animation, 'Duration:600') !== false) { 
				$Animation = str_replace("600",$SlideDuration,$Animation);
			}else if(strpos($Animation, 'Duration:500') !== false) { 
				$Animation = str_replace("500",$SlideDuration,$Animation);
			}else if(strpos($Animation, 'Duration:400') !== false) { 
				$Animation = str_replace("400",$SlideDuration,$Animation);
			}
		}

		$TitleShow = empty($DisplaySettings['settingTitleShow']) ? 'Y' : $DisplaySettings['settingTitleShow'];
		$TitleSize = empty($DisplaySettings['settingTitleSize']) ? 'h1' : $DisplaySettings['settingTitleSize'];
		$TitleTextAlign = empty($DisplaySettings['settingTextAlign']) ? 'center' : $DisplaySettings['settingTextAlign'];
		$IntroTextShow = empty($DisplaySettings['settingIntroTextShow']) ? 'Y' : $DisplaySettings['settingIntroTextShow'];
		$IntroTextSize = empty($DisplaySettings['settingIntroTextSize']) ? 'h2' : $DisplaySettings['settingIntroTextSize'];
		$IntroTextTextAlign = empty($DisplaySettings['settingIntroTextTextAlign']) ? 'center' : $DisplaySettings['settingIntroTextTextAlign'];

		$ShowLink = empty($DisplaySettings['settingShowLink']) ? 'N' : $DisplaySettings['settingShowLink'];
		$Target = empty($DisplaySettings['settingTarget']) ? '_blank' : $DisplaySettings['settingTarget'];

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
					$categoryIds = $this->getRecursiveContentCategoryIds($Category);
					$categoryIds[] = $Category;
					$select = $select
					->joinInner(array("ca" => "content_category_article"), "i.ID = ca.ContentArticleID", "")
					->where("ca.ContentCategoryID in (". implode(",",$categoryIds). ")")
					->group("i.ID");
				} else {
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
				if($UseCategoryParent=='Y') {
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
					$categoryIds = null;
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

		if($dataType=='weblink'){
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
			if($Limit){
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
					"StartDate" => "i.StartDate",
					"EndDate" => "i.EndDate",
					"PublishDate" => "i.PublishDate",
					"PublishDown" => "i.PublishDown",
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

		echo '<link rel="stylesheet" type="text/css" href="/assets/wslideshow/css/styles.css">';
		echo '<style>'.$CustomCSS.'</style>';

		echo '<div class="widget-kit-slideshow '.$HtmlClass.'">';

		if(count($data)>0) {
			
			$htmlScriptExtra = '';

			$html01 = '
			<div id="jssor_'.$UID.'" class="" style="position:relative;margin:0 auto;top:0px;left:0px;width:1300px;height:'.$Height.';overflow:hidden;visibility:hidden;">
				<!-- Loading Screen -->
				<div data-u="loading" class="jssorl-009-spin inner-silder" style="position:absolute;top:0px;left:0px;width:100%;height:100%;text-align:center;background-color:rgba(0,0,0,0.7);">
					<img alt="image" style="margin-top:-19px;position:relative;top:50%;width:38px;height:38px;" src="' . $this->view->baseUrl("plugins/jssor-slider/img/spin.svg") . '" />
				</div>
				<div data-u="slides" style="cursor:default;position:relative;top:0px;left:0px;width:1300px;height:'.$Height.';overflow:hidden;">
			';
			echo $html01;

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

				switch($dataType){
					case 'content':
					case 'vrv':
					case 'gallery':
					case 'calendar':
					case 'story':
					case 'banner':
					case 'weblink':
					case 'event':
						$imageUrl = $this->view->baseUrl("/assets/wslideshow/images/no-image.jpg");
						if (!empty($v["IntroImageHashName"])) {
							$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $v["IntroImageHashName"]), null, true);
						}
						break;
					case 'custom':
						$imageUrl = $v['ImageUrl'];
					default:
						break;
				}

				// HTML Show Link
				$html02_1 = '';
				$html02_1 .= '		<div>';
				$html02_1 .= '			<a class="lnkClickBanner-'.$UID.'-'.$v["ID"].'" href="' . $linkUrl . '" target="' . $Target . '">';
				$html02_1 .= '				<img alt="image" class="" data-u="image" src="' . $imageUrl . '" />';

				if($TitleShow=='Y' || $IntroTextShow=='Y') {

					$html02_1 .= '				<div class="divTitle">';
	
					$html02_1 .= '				' . ($TitleShow=='Y' ? ('<div class="title-hilight-01 '.$TitleSize.' text-'.$TitleTextAlign.'">'. $v['Title'] .'</div>') : '') . '';
	
					if($dataType == 'event') {
						if($lang == 'th') {
							$html02_1 .= '				' . ($IntroTextShow=='Y' ? ('<div class="title-hilight-02 '.$IntroTextSize.' text-'.$IntroTextTextAlign.'"><span class="slideshow-startevent-text">'. Emwork_Translate::text($lang, 'wslideshow', 'startdate') .' : </span><span class="slideshow-startevent-date">' . Emwork_Utility_DateTime::MySQLToDateThai($v['StartDate']) . '</span><span class="slideshow-event-"> - </span><span class="slideshow-endevent-text"> ' . Emwork_Translate::text($lang, 'wslideshow', 'enddate') . ' : </span><span class="slideshow-endevent-date">' . Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySQLToDateThai($v['EndDate'])) . '</span></div>') : '') . '';
						} else {
							$html02_1 .= '				' . ($IntroTextShow=='Y' ? ('<div class="title-hilight-02 '.$IntroTextSize.' text-'.$IntroTextTextAlign.'"><span class="slideshow-startevent-text">'. Emwork_Translate::text($lang, 'wslideshow', 'startdate') .' : </span><span class="slideshow-startevent-date">' . Emwork_Utility_DateTime::MySQLToDateThai($v['StartDate']) . '</span><span class="slideshow-event-"> - </span><span class="slideshow-endevent-text"> ' . Emwork_Translate::text($lang, 'wslideshow', 'enddate') . ' : </span><span class="slideshow-endevent-date">' . Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySQLToDateEng($v['EndDate'])) . '</span></div>') : '') . '';
						}
					} else {
						$html02_1 .= '				' . ($IntroTextShow=='Y' ? ('<div class="title-hilight-02 '.$IntroTextSize.' text-'.$IntroTextTextAlign.'">'. nl2br($v['IntroText']) .'</div>') : '') . '';
					}
					
					$html02_1 .= '				</div>';
				} 
				$html02_1 .= '			</a>';
				$html02_1 .= '		</div>';
				// END: HTML Show Link

				// HTML Not Show Link
				$html02_2 = '';
				$html02_2 .= '		<div>';
				$html02_2 .= '			<div>';
				$html02_2 .= '				<img alt="image" data-u="image" src="' . $imageUrl . '" />';
				if($TitleShow=='Y' || $IntroTextShow=='Y') {
				$html02_2 .= '				<div class="divTitle">';
				$html02_2 .= '				' . ($TitleShow=='Y' ? ('<div class="title-hilight-01 '.$TitleSize.' text-'.$TitleTextAlign.'">'. $v['Title'] .'</div>') : '') . '';

				if($dataType == 'event') {
					if($lang == 'th') {
						$html02_2 .= '				' . ($IntroTextShow=='Y' ? ('<div class="title-hilight-02 '.$IntroTextSize.' text-'.$IntroTextTextAlign.'"><span class="slideshow-startevent-text">'. Emwork_Translate::text($lang, 'wslideshow', 'startdate') .' : </span><span class="slideshow-startevent-date">' . Emwork_Utility_DateTime::MySQLToDateThai($v['StartDate']) . '</span><span class="slideshow-event-"> - </span><span class="slideshow-endevent-text"> ' . Emwork_Translate::text($lang, 'wslideshow', 'enddate') . ' : </span><span class="slideshow-endevent-date">' . Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySQLToDateThai($v['EndDate'])) . '</span></div>') : '') . '';
					} else {
						$html02_2 .= '				' . ($IntroTextShow=='Y' ? ('<div class="title-hilight-02 '.$IntroTextSize.' text-'.$IntroTextTextAlign.'"><span class="slideshow-startevent-text">'. Emwork_Translate::text($lang, 'wslideshow', 'startdate') .' : </span><span class="slideshow-startevent-date">' . Emwork_Utility_DateTime::MySQLToDateThai($v['StartDate']) . '</span><span class="slideshow-event-"> - </span><span class="slideshow-endevent-text"> ' . Emwork_Translate::text($lang, 'wslideshow', 'enddate') . ' : </span><span class="slideshow-endevent-date">' . Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySQLToDateEng($v['EndDate'])) . '</span></div>') : '') . '';
					}
				} else {
					$html02_2 .= '				' . ($IntroTextShow=='Y' ? ('<div class="title-hilight-02 '.$IntroTextSize.' text-'.$IntroTextTextAlign.'">'. nl2br($v['IntroText']) .'</div>') : '') . '';
				}

				$html02_2 .= '				</div>';
				}
				$html02_2 .= '			</div>';
				$html02_2 .= '		</div>';
				// END: HTML Not Show Link

				
				if($ShowLink=='Y'){
					echo $html02_1;	
				}else{
					echo $html02_2;
				}

				if ($dataType == 'banner') {
					$htmlScriptExtra .= '<script>';
					$htmlScriptExtra .= '	$(".lnkClickBanner-'.$UID.'-'.$v["ID"].'").click(function() {';
					$htmlScriptExtra .= '		$.post("'.$this->view->url(array('module' => 'banner', 'controller' => 'item', 'action' => 'click'), null, true).'", {id:"'.$v['ID'].'"}, function(data) {';
					$htmlScriptExtra .= '		});';
					$htmlScriptExtra .= '	});';
					$htmlScriptExtra .= '</script>';
				}

			} // End Foreach

			$html03 = '
				</div>
				' . ($NavigationBulletShow=='Y' ? ('
				<!-- Bullet Navigator -->
				<div data-u="navigator" class="jssorb032" style="position:absolute;bottom:12px;right:12px;" data-autocenter="1" data-scale="0.5" data-scale-bottom="0.75">
					<div data-u="prototype" class="i" style="width:16px;height:16px;">
						<svg viewbox="0 0 16000 16000" style="position:absolute;top:0;left:0;width:100%;height:100%;">
							<circle class="b" cx="8000" cy="8000" r="5800"></circle>
						</svg>
					</div>
				</div>
				') : '') . '
				' . ($NavigationArrowShow=='Y' ? ('
				<!-- Arrow Navigator -->
				<div data-u="arrowleft" class="jssora051" style="width:65px;height:65px;top:0px;left:25px;" data-autocenter="2" data-scale="0.75" data-scale-left="0.75">
					<svg viewbox="0 0 16000 16000" style="position:absolute;top:0;left:0;width:100%;height:100%;">
						<polyline class="a" points="11040,1920 4960,8000 11040,14080 "></polyline>
					</svg>
				</div>
				<div data-u="arrowright" class="jssora051" style="width:65px;height:65px;top:0px;right:25px;" data-autocenter="2" data-scale="0.75" data-scale-right="0.75">
					<svg viewbox="0 0 16000 16000" style="position:absolute;top:0;left:0;width:100%;height:100%;">
						<polyline class="a" points="4960,1920 11040,8000 4960,14080 "></polyline>
					</svg>
				</div>
				') : '') . '
			</div>

			<script>
			jQuery(document).ready(function ($) {

				var jssor_'.$UID.'_SlideoTransitions = ['.$Animation.'];

				var jssor_'.$UID.'_options = {
					$AutoPlay: '.$AutoPlay.',
					$Idle: 1500,
					$SlideshowOptions: {
						$Class: $JssorSlideshowRunner$,
						$Transitions: jssor_'.$UID.'_SlideoTransitions,
						$TransitionsOrder: 1
					},
					$SlideDuration: '.$SlideDuration.',
					' . ($NavigationArrowShow=='Y' ? ('$ArrowNavigatorOptions: { $Class: $JssorArrowNavigator$ },') : '') . '
					' . ($NavigationBulletShow=='Y' ? ('$BulletNavigatorOptions: { $Class: $JssorBulletNavigator$ },') : '') . '
				};

				var jssor_'.$UID.'_slider = new $JssorSlider$("jssor_'.$UID.'", jssor_'.$UID.'_options);

				/*#region responsive code begin*/

				var MAX_WIDTH = 3000;

				function ScaleSlider() {
					var containerElement = jssor_'.$UID.'_slider.$Elmt.parentNode;
					var containerWidth = containerElement.clientWidth;

					if (containerWidth) {

						var expectedWidth = Math.min(MAX_WIDTH || containerWidth, containerWidth);

						jssor_'.$UID.'_slider.$ScaleWidth(expectedWidth);
					}
					else {
						window.setTimeout(ScaleSlider, 30);
					}
				}

				ScaleSlider();

				$(window).bind("load", ScaleSlider);
				$(window).bind("resize", ScaleSlider);
				$(window).bind("orientationchange", ScaleSlider);
				/*#endregion responsive code end*/
			});
			</script>
			';
			echo $html03;
			echo $htmlScriptExtra;

		} // End Count

		echo '</div>';
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