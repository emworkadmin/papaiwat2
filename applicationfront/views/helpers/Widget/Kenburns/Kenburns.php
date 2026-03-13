<?php
class Zend_View_Helper_Widget_Kenburns_Kenburns extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Kenburns_Kenburns($params)
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
		->where("tbl.WidgetType = 'kenburns' ")
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
		$kenburnDuration = empty($DisplaySettings['settingKenburnDuration']) ? '20' : $DisplaySettings['settingKenburnDuration'];
		$Height = empty($DisplaySettings['settingHeight']) ? '40vw' : $DisplaySettings['settingHeight'];
        $Animation = empty($DisplaySettings['settingAnimation']) ? 'left' : $DisplaySettings['settingAnimation'];
        $SlideDuration = empty($DisplaySettings['settingSlideDuration']) ? '4000' : $DisplaySettings['settingSlideDuration'];
		$TitleShow = empty($DisplaySettings['settingTitleShow']) ? 'Y' : $DisplaySettings['settingTitleShow'];
		$TitleSize = empty($DisplaySettings['settingTitleSize']) ? 'h1' : $DisplaySettings['settingTitleSize'];
		$TitleTextAlign = empty($DisplaySettings['settingTextAlign']) ? 'left' : $DisplaySettings['settingTextAlign'];
		$IntroTextShow = empty($DisplaySettings['settingIntroTextShow']) ? 'Y' : $DisplaySettings['settingIntroTextShow'];
		$IntroTextSize = empty($DisplaySettings['settingIntroTextSize']) ? 'h4' : $DisplaySettings['settingIntroTextSize'];
        $IntroTextTextAlign = empty($DisplaySettings['settingIntroTextTextAlign']) ? 'left' : $DisplaySettings['settingIntroTextTextAlign'];
		$ShowLink = empty($DisplaySettings['settingShowLink']) ? 'Y' : $DisplaySettings['settingShowLink'];
		$Target = empty($DisplaySettings['settingTarget']) ? '_blank' : $DisplaySettings['settingTarget'];
		$HtmlClass = empty($DisplaySettings['settingHtmlClass']) ? '' : $DisplaySettings['settingHtmlClass'];
        $CustomCSS = empty($DisplaySettings['settingCustomCSS']) ? '' : $DisplaySettings['settingCustomCSS'];

		// -------------------------------------------- Get Data Content -------------------------------------------- //

		if($dataType=='content') {
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
					$select = $select->where("i.ContentCategoryID in (". implode(",",$categoryIds). ")");
				}else{
					$select = $select->where("i.ContentCategoryID = ?", $Category);
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

        if($dataType=='gallery') {
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
					$select = $select->where("i.GalleryCategoryID in (". implode(",",$categoryIds). ")");
				}else{
					$select = $select->where("i.GalleryCategoryID = ?", $Category);
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
					$select = $select->where("i.CalendarCategoryID in (". implode(",",$categoryIds). ")");
				}else{
					$select = $select->where("i.CalendarCategoryID = ?", $Category);
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
					$categoryIds = $this->getRecursiveStoryCategoryIds($Category);
					$categoryIds[] = $Category;
					$select = $select->where("i.StoryCategoryID in (". implode(",",$categoryIds). ")");
				}else{
					$select = $select->where("i.StoryCategoryID = ?", $Category);
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
				$select = $select->where("i.EventCategoryID = ?", $Category);
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

		if($dataType=='custom') {
			$data = $ContentSettings;
        }
        
        // ---------------------------------------------- Present Data ---------------------------------------------- //

        echo '<style>'.$CustomCSS.'</style>' . "\n";
        echo '<style>' . "\n";
        echo '  #kenburns_show'.$UID.' {' . "\n";
        echo '      position: relative;' . "\n";
        echo '      width: 100%;' . "\n";
        echo '      height: '.$Height.';' . "\n";
        echo '      overflow: hidden;' . "\n";
        echo '  }' . "\n";
        echo '  #kenburns_show'.$UID.' img {' . "\n";
        echo '      max-width: none;' . "\n";
        echo '      position:absolute;' . "\n";
        echo '      width: calc(100% + 90px);' . "\n";
        echo '      height: '.$Height.';' . "\n";
        echo '      top:0%;' . "\n";
        echo '      left:0%;' . "\n";
        echo '      margin-left: -30px;' . "\n";
        echo '      margin-top:0px;' . "\n";
        echo '      opacity:0;' . "\n";
        echo '      transition-property: opacity, transform;' . "\n";
        echo '      transition-duration: 3s, '.$kenburnDuration.'s;' . "\n";
        echo '  }' . "\n";
        echo '  #kenburns_show'.$UID.' .fx_z-1 {' . "\n";
        echo '      z-index: -1;' . "\n";
        echo '      opacity:1;' . "\n";
        echo '      transform: scale(1.5) translate(15px);' . "\n";
        echo '  }' . "\n";
        echo '  #kenburns_show'.$UID.' .fx {' . "\n";
        echo '      opacity:1;' . "\n";
        echo '      transform: scale(1.5) translate(15px);' . "\n";
		echo '  }' . "\n";
		echo '  #kenburns_show'.$UID.' .ffx {' . "\n";
		echo '      opacity:1;' . "\n";
		echo '  }' . "\n";		
		echo '  #kenburns_show'.$UID.' #divText {' . "\n";
        echo '      position: absolute;' . "\n";
		echo '      top: 50%;' . "\n";
		echo '      left: 50%;' . "\n";
        echo '      z-index: 0;' . "\n";
        echo '      color: white;' . "\n";
        echo '      font-size: 3.6em;' . "\n";
        echo '      transform: translate(-50%, -50%);' . "\n";
        echo '      transition-property: opacity;' . "\n";
        echo '      transition-duration: 1s;' . "\n";
        echo '      opacity: 0;' . "\n";
        echo '  }' . "\n";
        echo '  #kenburns_show'.$UID.' #divText>div {' . "\n";
        echo '      color: white;' . "\n";
        echo '  }' . "\n";
        echo '  #kenburns_show'.$UID.' #divText.tfx {' . "\n";
        echo '      opacity:1;' . "\n";
        echo '      z-index: 1;' . "\n";
        echo '  }' . "\n";
        echo '  #kenburns_show'.$UID.' #divText.tfx_z-1 {' . "\n";
        echo '      opacity:1;' . "\n";
        echo '      z-index: -1;' . "\n";
		echo '  }' . "\n";
		echo ' #kenburns_show'.$UID.'>.next-on, #kenburns_show'.$UID.'>.prev-on { ' . "\n";
		echo '		position: absolute;' . "\n";
		echo '		z-index: 1;' . "\n";
		echo '		right: 5px;' . "\n";
		echo '		bottom: 5px;' . "\n";
		echo '		color: #3333;' . "\n";
		echo '		background: #fff;' . "\n";
		echo '		cursor: pointer;' . "\n";
		echo '		text-align: center;' . "\n";
		echo '		width: 50px;' . "\n";
		echo '		height: 45px;' . "\n";
		echo '		line-height: 50px;' . "\n";
		echo '		border-radius: 3px;' . "\n";
		echo '		opacity: 0.5;' . "\n";
		echo '		font-size: 15px;' . "\n";
		echo ' }' . "\n";
		echo ' #kenburns_show'.$UID.'>.next-on:hover, #kenburns_show'.$UID.'>.prev-on:hover { ' . "\n";
		echo '		opacity: 0.8;' . "\n";
		echo '		color: black;' . "\n";		
		echo ' }' . "\n";		
		echo ' #kenburns_show'.$UID.'>.prev-on { ' . "\n";
		echo '		left: 5px;' . "\n";
		echo ' }' . "\n";
		echo ' #kenburns_show'.$UID.'>.next-on { ' . "\n";
		echo '		right: 5px;' . "\n";
		echo ' }' . "\n";
        echo '  </style>' . "\n";
        
        echo '<div id="kenburns_show'.$UID.'" class="' . $HtmlClass . '">' . "\n";
        
        if(count($data)>0) {

            foreach($data as $k=>$v) {

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

                if($ShowLink=='Y') {
                echo '<a class="lnkClickBanner-'.$UID.'-'.$v["ID"].'" href="' . $linkUrl . '" target="' . $Target . '">' . "\n";
                }
                echo '  <img alt="image" src="'. $imageUrl .'">' . "\n";
                echo '  <div id="divText" class="">' . "\n";
                echo '      <div>' . "\n";
                if($TitleShow=='Y') {
                echo '          <'. $TitleSize .' style="text-align: ' . $TitleTextAlign . ';">' . $v['Title'] . '</'. $TitleSize .'>' . "\n";
                }
                if($IntroTextShow=='Y') {
                echo '          <'. $IntroTextSize .' style="text-align: ' . $IntroTextTextAlign . '; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">' . nl2br($v['IntroText']) . '</'. $IntroTextSize .'>' . "\n";
                }
                echo '      </div>' . "\n";
                echo '  </div>' . "\n";
                // }
                if($ShowLink=='Y') {
                echo '</a>' . "\n";
				}

				if ($dataType == 'banner') {
				echo '<script>';
				echo '	$(".lnkClickBanner-'.$UID.'-'.$v["ID"].'").click(function() {';
				echo '		$.post("'.$this->view->url(array('module' => 'banner', 'controller' => 'item', 'action' => 'click'), null, true).'", {id:"'.$v['ID'].'"}, function(data) {';
				echo '			console.log(data);';
				echo '		});';
				echo '	});';
				echo '</script>';
				}
			}
			
			if(count($data)>3) {
				echo '<a class="next-on"> ►</a>';
				echo '<a class="prev-on">◄ </a>';
			}

        }

        echo '</div>';

        echo '<script>' . "\n";
        echo '$(function(){' . "\n";
        echo '  loadKenburn'.$UID.'();' . "\n";
        echo '      function loadKenburn'.$UID.'() {' . "\n";
		echo '			if(document.getElementById("kenburns_show'.$UID.'").getElementsByTagName("img")[0]) {' . "\n";
		echo '          document.getElementById("kenburns_show'.$UID.'").getElementsByTagName("img")[0].className = "fx";' . "\n";
        echo '          let images = document.getElementById("kenburns_show'.$UID.'").getElementsByTagName("img"),' . "\n";
        echo '              numberOfImages = images.length,' . "\n";
        echo '              i = 0;' . "\n";

        if($Animation == 'square') {
		echo '			let square = 1;' . "\n";
		echo '			$.each(images, function (i, e) {' . "\n";
		echo '          	if(square == 1) { $(e).css("transform-origin", "left") };' . "\n";
		echo '          	if(square == 2) { $(e).css("transform-origin", "right") };' . "\n";
		echo '          	if(square == 3) { $(e).css("transform-origin", "top") };' . "\n";
		echo '          	if(square == 4) { $(e).css("transform-origin", "bottom") };' . "\n";
		echo '          	square++; if(square==5){square=1};' . "\n";
		echo '          });' . "\n";
        } elseif($Animation == 'corner') {
		echo '          let corner = 1;' . "\n";
		echo '          $.each(images, function (i, e) {' . "\n";
		echo '          	if(corner == 1) { $(e).css("transform-origin", "bottom right") };' . "\n";
		echo '          	if(corner == 2) { $(e).css("transform-origin", "bottom left") };' . "\n";
		echo '          	if(corner == 3) { $(e).css("transform-origin", "top right") };' . "\n";
		echo '          	if(corner == 4) { $(e).css("transform-origin", "top left") };' . "\n";
		echo '          	corner++; if(corner==5){corner=1};' . "\n";
		echo '          });' . "\n";
        } else {
		echo '          $.each(images, function (i, e) {' . "\n";
		echo '              $(e).css("transform-origin", "' . $Animation . '")'. "\n";
		echo '          });' . "\n";            
        }

        echo '          let text = [];' . "\n";
        echo '          text = $("#kenburns_show'.$UID.' #divText");' . "\n";
        echo '			if(text.length>0) { text[0].className = "container tfx"; }' . "\n";

		// There should be no less than 4 images.
		if(count($data)>=3) {

		echo '          var playInterval = setInterval(kenBurns'.$UID.', '.$SlideDuration.');' . "\n";
		echo '          function kenBurns'.$UID.'() {' . "\n";
        echo '              i++;' . "\n";
        echo '              if(i==numberOfImages) {' . "\n";
        echo '                  images[i-1].className = "fx_z-1";' . "\n";
        echo '                  if(text.length>0) { text[i-1].className = "container tfx_z-1"; }' . "\n";
        echo '                  i = 0;' . "\n";
        echo '              }' . "\n";
        echo '              images[i].className = "fx";' . "\n";
        echo '              if(text.length>0) { text[i].className = "container tfx"; }' . "\n";
        echo '              if(i===0) {' . "\n";
        echo '                  images[numberOfImages-2].className = "";' . "\n";
        echo '                  if(text.length>0) { text[numberOfImages-1].className = "container"; }' . "\n";
        echo '              }' . "\n";
        echo '              if(i===1) {' . "\n";
        echo '                  images[numberOfImages-1].className = "";' . "\n";
        echo '                  if(text.length>0) { text[0].className = "container"; }' . "\n";
        echo '              }' . "\n";
        echo '              if(i>1) {' . "\n";
        echo '                  images[i-2].className = "";' . "\n";
        echo '                  if(text.length>0) { text[i-1].className = "container"; }' . "\n";
        echo '              }' . "\n";
		echo '          };' . "\n";

		echo '			$("#kenburns_show'.$UID.' .next-on").click(function() {' . "\n";
		echo '				clearInterval(playInterval); console.log(1)' . "\n";
		echo '				i++;' . "\n";
        echo '              if(i==numberOfImages) {' . "\n";
		echo '                  i = 0;' . "\n";
		echo '              }' . "\n";
		echo '				$.each(images, function(k , v) {' . "\n";
		echo '					images[k].className = ""' . "\n";
		echo '				});' . "\n";
		echo '				images[i].className="ffx"' . "\n";
		echo '				$.each(text, function(k , v) {' . "\n";
		echo '					text[k].className = "container"' . "\n";
		echo '				});' . "\n";
		echo '				text[i].className="container tfx"' . "\n";		
		echo '          	playInterval = setInterval(kenBurns'.$UID.', '.$SlideDuration.');' . "\n";
		echo '			});' . "\n";

		echo '			$("#kenburns_show'.$UID.' .prev-on").click(function() {' . "\n";
		echo '				clearInterval(playInterval);' . "\n";
		echo ' 				i = i - 1;' . "\n";
		echo ' 				if(i<0) {' . "\n";
		echo '					i = numberOfImages - 1';
		echo '				}' . "\n";
		echo '				$.each(images, function(k , v) {' . "\n";
		echo '					images[k].className = ""' . "\n";
		echo '				});' . "\n";
		echo '				images[i].className="ffx"' . "\n";
		echo '				$.each(text, function(k , v) {' . "\n";
		echo '					text[k].className = "container"' . "\n";
		echo '				});' . "\n";
		echo '				text[i].className="container tfx"' . "\n";			
		echo '          	playInterval = setInterval(kenBurns'.$UID.', '.$SlideDuration.');' . "\n";
		echo '			});' . "\n";
		
		} elseif(count($data)>1) {

		echo '          window.setInterval(kenBurns, '.$SlideDuration.');' . "\n";			
		echo '          function kenBurns() {' . "\n";
		echo '              if(i==numberOfImages) {' . "\n";
		echo '                  images[i-1].className = "fx_z-1";' . "\n";
		echo '                  if(text.length>0) { text[i-1].className = "container tfx_z-1"; }' . "\n";
		echo '                  i = 0;' . "\n";
		echo '              }' . "\n";
		echo '              images[i].className = "fx";' . "\n";
		echo '              if(text.length>0) { text[i].className = "container tfx"; }' . "\n";
		echo '              if(i===0) {' . "\n";
		echo '                  images[numberOfImages-1].className = "";' . "\n";
		echo '                  if(text.length>0) { text[numberOfImages-1].className = "container"; }' . "\n";
		echo '              }' . "\n";
		echo '              if(i===1) {' . "\n";
		echo '                  images[0].className = "";' . "\n";
		echo '                  if(text.length>0) { text[0].className = "container"; }' . "\n";
		echo '              }' . "\n";
		echo '              if(i>1) {' . "\n";
		echo '                  images[i-1].className = "";' . "\n";
		echo '                  if(text.length>0) { text[i-1].className = "container"; }' . "\n";
		echo '              }' . "\n";
		echo '              i++;' . "\n";
		echo '          };' . "\n";

		} else {

		}

		echo '		};' . "\n"; // End function 
		echo '	};' . "\n";
        echo '});' . "\n";
        echo '</script>' . "\n";
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
				if(!in_array($value["ID"],$this->recursiveIds)){ 
					$this->recursiveIds[] = $value["ID"]; 
				}
			}
		}
		return $this->recursiveIds;
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
				if(!in_array($value["ID"],$this->recursiveIds)){ 
					$this->recursiveIds[] = $value["ID"]; 
				}
			}
		}
		return $this->recursiveIds;
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
				if(!in_array($value["ID"],$this->recursiveIds)){ 
					$this->recursiveIds[] = $value["ID"]; 
				}
			}
		}
		return $this->recursiveIds;
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
				if(!in_array($value["ID"],$this->recursiveIds)){ 
					$this->recursiveIds[] = $value["ID"]; 
				}
			}
		}
		return $this->recursiveIds;
	}
        
}
?>