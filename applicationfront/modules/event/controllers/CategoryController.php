<?php
class Event_CategoryController extends Zend_Controller_Action
{
    protected $auth;
    protected $lang;
    
    public function init()
	{
		// Auth
		$auth = Zend_Auth::getInstance();
		$authData = $auth->getIdentity();
		$this->auth = $authData;

		// Get Languages
	    $lang = Zend_Registry::isRegistered("lang") ? Zend_Registry::get("lang") : "";
	    if (empty($lang)) {
	        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
	        $lang = $defaultLanguage["Code"];
	    }
		$this->lang = $lang;
		
		// Send data to view
		$this->view->lang = $lang;
	}
	
	protected function findMenu($url) {
		$menuID = null;
		$menus = System_Model_Menu::findMenuByUrl($url);
		if (count($menus) > 0) {
			$menuID = $menus[0]["ID"];
		}
		return $menuID;
	}	
    
    public function indexAction()
    {
		// Log Access
		System_Model_Log::access();

		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));
		
		// Get Info
		$model = new Event_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
				
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;

		$menuID = $this->findMenu("/event/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}
	
	public function getlistAction() {
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';

		// Category Setting
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		if(empty($cid)){
			$cid = $id;
		}
		$categoryModel = new Event_Model_Category();
		$categoryData = $categoryModel->getSettings($cid);
		$categorySetting = json_decode($categoryData->Settings);

		$CategoryPerPageSetting = empty($categorySetting->CategoryPerPageSetting) ? "D" : $categorySetting->CategoryPerPageSetting;
		$CategoryPerPage = $categorySetting->CategoryPerPage;
		$CategoryAmountColumnSetting = empty($categorySetting->CategoryAmountColumnSetting) ? "D" : $categorySetting->CategoryAmountColumnSetting;
		$CategoryAmountColumn = $categorySetting->CategoryAmountColumn;
		$CategoryShowImageSetting = empty($categorySetting->CategoryShowImageSetting) ? "D" : $categorySetting->CategoryShowImageSetting;
		$CategoryShowImage = $categorySetting->CategoryShowImage;
		$CategoryShowCategorySetting = empty($categorySetting->CategoryShowCategorySetting) ? "D" : $categorySetting->CategoryShowCategorySetting;
		$CategoryShowCategory = $categorySetting->CategoryShowCategory;
		$CategoryShowIntroTextSetting = empty($categorySetting->CategoryShowIntroTextSetting) ? "D" : $categorySetting->CategoryShowIntroTextSetting;
		$CategoryShowIntroText = $categorySetting->CategoryShowIntroText;

		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General"); 

		// Setting
		$eventSettingCategoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Event");
		$eventSettingCategoryPerPage = $CategoryPerPageSetting == "D" ? $eventSettingCategoryPerPage : $CategoryPerPage;

		$eventSettingCategoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Event");
		$eventSettingCategoryAmountColumn = $CategoryAmountColumnSetting == "D" ? $eventSettingCategoryAmountColumn : $CategoryAmountColumn;
		$eventSettingCategoryAmountColumn = empty($eventSettingCategoryAmountColumn) || $eventSettingCategoryAmountColumn <= 1 ? 1 : ($eventSettingCategoryAmountColumn >= 4 ? 4 : $eventSettingCategoryAmountColumn);

		$eventSettingCategoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Event");
		$eventSettingCategoryShowImage = $CategoryShowImageSetting == "D" ? $eventSettingCategoryShowImage : $CategoryShowImage;
		
		$eventSettingCategoryShowCategory = System_Model_Setting::getValue("CategoryShowCategory", "Event");
		$eventSettingCategoryShowCategory = $CategoryShowCategorySetting == "D" ? $eventSettingCategoryShowCategory : $CategoryShowCategory;

		$eventSettingCategoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Event");
		$eventSettingCategoryShowIntroText = $CategoryShowIntroTextSetting == "D" ? $eventSettingCategoryShowIntroText : $CategoryShowIntroText;
		
		$sortType = 'Ordering';
		$sortDirection = 'asc';
				
		// Init
		$pageSize = empty($eventSettingCategoryPerPage) ? 10 : $eventSettingCategoryPerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
	
		$advancesearch = array();
		if(!empty($cid)){
			$advancesearch["parent_id"] = $cid;
		}else{
			$advancesearch["parent_id"] = $id;
		}
		
		$model = new Event_Model_Category();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->eventSettingCategoryAmountColumn = $eventSettingCategoryAmountColumn;
		$this->view->eventSettingCategoryShowImage = $eventSettingCategoryShowImage == 'Y';
		$this->view->eventSettingCategoryShowCategory = $eventSettingCategoryShowCategory == 'Y';
		$this->view->eventSettingCategoryShowIntroText = $eventSettingCategoryShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->items = $items;

		if ($eventSettingCategoryAmountColumn == 1) {
			$responce["html"] = $this->view->render('category/_crows.phtml');
		} else {
			$responce["html"] = $this->view->render('category/_ccards.phtml');
		}
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
	}


	public function geteventsAction() {
		$mainCategoryID = $this->mainCategoryID;
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';

		// Category
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		if(empty($cid)){
			$cid = $id;
		}
		$categoryModel = new Event_Model_Category();
		$categoryData = $categoryModel->getSettings($cid);
		$categorySetting = json_decode($categoryData->Settings);

		$EventPerPageSetting = empty($categorySetting->EventPerPageSetting) ? "D" : $categorySetting->EventPerPageSetting;
		$EventPerPage = $categorySetting->EventPerPage;
		$EventSortSetting = empty($categorySetting->EventSortSetting) ? "D" : $categorySetting->EventSortSetting;
		$EventSortType = $categorySetting->EventSortType;
		$EventSortDirection = $categorySetting->EventSortDirection;
		$EventAmountColumnSetting = empty($categorySetting->EventAmountColumnSetting) ? "D" : $categorySetting->EventAmountColumnSetting;
		$EventAmountColumn = $categorySetting->EventAmountColumn;
		$EventShowImageSetting = empty($categorySetting->EventShowImageSetting) ? "D" : $categorySetting->EventShowImageSetting;
		$EventShowImage = $categorySetting->EventShowImage;
		$EventShowCategorySetting = empty($categorySetting->EventShowCategorySetting) ? "D" : $categorySetting->EventShowCategorySetting;
		$EventShowCategory = $categorySetting->EventShowCategory;
		$EventShowStartDateSetting = empty($categorySetting->EventShowStartDateSetting) ? "D" : $categorySetting->EventShowStartDateSetting;
		$EventShowStartDate = $categorySetting->EventShowStartDate;
		$EventShowEndDateSetting = empty($categorySetting->EventShowEndDateSetting) ? "D" : $categorySetting->EventShowEndDateSetting;
		$EventShowEndDate = $categorySetting->EventShowEndDate;
		$EventShowPublishDateSetting = empty($categorySetting->EventShowPublishDateSetting) ? "D" : $categorySetting->EventShowPublishDateSetting;
		$EventShowPublishDate = $categorySetting->EventShowPublishDate;		
		$EventShowPlaceSetting = empty($categorySetting->EventShowPlaceSetting) ? "D" : $categorySetting->EventShowPlaceSetting;
		$EventShowPlace = $categorySetting->EventShowPlace;
		$EventShowCreatorSetting = empty($categorySetting->EventShowCreatorSetting) ? "D" : $categorySetting->EventShowCreatorSetting;
		$EventShowCreator = $categorySetting->EventShowCreator;

		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");

		// Setting
		$eventSettingEventPerPage = System_Model_Setting::getValue("EventPerPage", "Event");
		$eventSettingEventPerPage = $EventPerPageSetting == "D" ? $eventSettingEventPerPage : $EventPerPage;

		$eventSettingEventSortType = System_Model_Setting::getValue("EventSortType", "Event");
		$eventSettingEventSortType = $EventSortSetting == "D" ? $eventSettingEventSortType : $EventSortType;

		$eventSettingEventSortDirection = System_Model_Setting::getValue("EventSortDirection", "Event");
		$eventSettingEventSortDirection = $EventSortSetting == "D" ? $eventSettingEventSortDirection : $EventSortDirection;

		$eventSettingEventAmountColumn = System_Model_Setting::getValue("EventAmountColumn", "Event");
		$eventSettingEventAmountColumn = $EventAmountColumnSetting == "D" ? $eventSettingEventAmountColumn : $EventAmountColumn;
		$eventSettingEventAmountColumn = empty($eventSettingEventAmountColumn) || $eventSettingEventAmountColumn <= 1 ? 1 : ($eventSettingEventAmountColumn >= 4 ? 4 : $eventSettingEventAmountColumn);

		$eventSettingEventShowAmount = System_Model_Setting::getValue("EventShowAmount", "Event");
		$eventSettingEventShowAmount = $EventShowAmountSetting == "D" ? $eventSettingEventShowAmount : $EventShowAmount;

		$eventSettingEventShowImage = System_Model_Setting::getValue("EventShowImage", "Event");
		$eventSettingEventShowImage = $EventShowImageSetting == "D" ? $eventSettingEventShowImage : $EventShowImage;

		$eventSettingEventShowCategory = System_Model_Setting::getValue("EventShowCategory", "Event");
		$eventSettingEventShowCategory = $EventShowCategorySetting == "D" ? $eventSettingEventShowCategory : $EventShowCategory;

		$eventSettingEventShowStartDate = System_Model_Setting::getValue("EventShowStartDate", "Event");
		$eventSettingEventShowStartDate = $EventShowStartDateSetting == "D" ? $eventSettingEventShowStartDate : $EventShowStartDate;
		
		$eventSettingEventShowEndDate = System_Model_Setting::getValue("EventShowEndDate", "Event");
		$eventSettingEventShowEndDate = $EventShowEndDateSetting == "D" ? $eventSettingEventShowEndDate : $EventShowEndDate;

		$eventSettingEventShowPublishDate = System_Model_Setting::getValue("EventShowPublishDate", "Event");
		$eventSettingEventShowPublishDate = $EventShowPublishDateSetting == "D" ? $eventSettingEventShowPublishDate : $EventShowPublishDate;		
		
		$eventSettingEventShowPlace = System_Model_Setting::getValue("EventShowPlace", "Event");
		$eventSettingEventShowPlace = $EventShowPlaceSetting == "D" ? $eventSettingEventShowPlace : $EventShowPlace;
		
		$eventSettingEventShowCreator = System_Model_Setting::getValue("EventShowCreator", "Event");
		$eventSettingEventShowCreator = $EventShowCreatorSetting == "D" ? $eventSettingEventShowCreator : $EventShowCreator;		

		$sortType = 'Created';
		switch ($eventSettingEventSortType) {
			case 'created' :
				$sortType = 'Created';
				break;
			case 'title' :
			case 'name' :
				$sortType = 'Title';
				break;
			case 'publishdate' :
				$sortType = 'PublishDate';
				break;
		}
		$sortDirection = in_array($eventSettingEventSortDirection, array('asc', 'desc')) ? $eventSettingEventSortDirection : 'desc';
		
		// Init
		$pageSize = empty($eventSettingEventPerPage) ? 10 : $eventSettingEventPerPage;
		
		// Search
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		
		$advancesearch = array();
		$advancesearch['isCat'] = $id;
		if($cid){
			$advancesearch['isCat'] = $cid;
		}

		$model = new Event_Model_Event();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->eventSettingEventAmountColumn = $eventSettingEventAmountColumn;
		$this->view->eventSettingEventShowImage = $eventSettingEventShowImage == 'Y';
		$this->view->eventSettingEventShowCategory = $eventSettingEventShowCategory == 'Y';
		$this->view->eventSettingEventShowStartDate = $eventSettingEventShowStartDate == 'Y';
		$this->view->eventSettingEventShowEndDate = $eventSettingEventShowEndDate == 'Y';
		$this->view->eventSettingEventShowPublishDate = $eventSettingEventShowPublishDate == 'Y';
		$this->view->eventSettingEventShowPlace = $eventSettingEventShowPlace == 'Y';
		$this->view->eventSettingEventShowCreator = $eventSettingEventShowCreator == 'Y';		
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->items = $items;

		$responce["html"] = $this->view->render('category/_arows.phtml');
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;

		$this->_helper->json($responce);
	}

	public function eventsAction()
	{
		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		
		// Log Access
		System_Model_Log::access();
		
		// Get Param
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		
		if (empty($id) || empty($cid)) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		$model = new Event_Model_Category();
		$mainCategoryData = $model->getDataByLanguage($id, $this->lang);
		$categoryData = $model->getDataByLanguage($cid, $this->lang);

		$parent[0] =  $categoryData->ParentID;
		$parentName[0] = $categoryData->Title;

		$checkParen = 1 ;

		while($checkParen != null){
			$dataParen = $model->getDataByLanguage($parent[$checkParen-1], $this->lang);
			$parent[$checkParen] = $dataParen->ParentID;
			$parentName[$checkParen] = $dataParen->Title;
			if($parent[$checkParen] != null){
				$checkParen++ ;
			}else{
				$checkParen = null ;
			}
		}
		// Send Data to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->parentMenu = $mainCategoryData->Title;
		$this->view->pageTitle = $categoryData->Title;
		
		// Set Global
		$menuID = $this->findMenu("/event/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparentt = $num - 2 ;
		
		while($sumparentt > -1){
			$extraBreadcrumb[] = array('url' => '/event/category/events/id/' . $id . '/cid/' . $parent[$sumparentt-1], 'name' => $parentName[$sumparentt]);
			$sumparentt-- ;
			if($sumparentt == -1){
				$sumparentt = null ;
			}
		}

		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}

	public function detailAction() {
		// Log Access
		System_Model_Log::access ();
		
		$filterInt = new Zend_Filter_Int ();
		$id = $this->getRequest ()->getParam ( "id", "" );
		$cid = $this->getRequest ()->getParam ( "cid","" );
		$iID = $this->getRequest ()->getParam ( "iid", "" );
		$id = $filterInt->filter ( $id );
		$iID = $filterInt->filter ( $iID );
		
		if (empty ( $id ) || empty ( $iID )) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		// Save View
		$accessFrom = "Web";
		$memberID = null;
		$userID = null;
		Event_Model_Event::saveView($iID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Event_Model_Event();
		$dataInfo = $model->getInfo( $iID );
		$data = $model->getDataByLanguage( $iID, $this->lang);
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
				
		$categoryModel = new Event_Model_Category();
		$dataDetailCategory = $categoryModel->getDataByLanguage($id, $this->lang);

		
		// Check Data: Can Access
		if (!$dataInfo || $dataInfo->IsEnable != 'Y' || $dataInfo->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		if (empty ( $data->Title)) {
			// Show "Not found data in this LANG, อยู่ระหว่างจัดทำข้อมูล"
			$this->renderScript('index/_alertmaintenance.phtml');
			return;
		}
		
		// Intro Image
		$dataIntroImages = array();
		if(!empty($data->IntroImageID)) {
			$dataIntroImages[] = array(
				'file_name'=> $data->IntroImageFileName, 
				'size'=> $data->IntroImageFileSize, 
				'type'=> $data->IntroImageFileType, 
				'hash_name'=> $data->IntroImageHashName
			);
		}

		$dataFiles = $model->getFiles($data->ID);
		$dataFileImages = array();
		$dataFileDocuments = array();
		$dataFileMultiMedias = array();
		if (count($dataFiles) > 0) {
			foreach ($dataFiles as $dataFile) {
				if ($dataFile["EventFileType"] == "image") {
					$dataFileImages[] = array(
						'file_name'=> $dataFile['FileName'], 
						'size'=> $dataFile['FileSize'], 
						'type'=> $dataFile['FileType'], 
						'hash_name'=> $dataFile['FileHashName']
					);
				} elseif ($dataFile["EventFileType"] == "document") {
					$dataFileDocuments[] = array(
						'file_name'=> $dataFile['FileName'], 
						'size'=> $dataFile['FileSize'], 
						'type'=> $dataFile['FileType'], 
						'hash_name'=> $dataFile['FileHashName']
					);
				} elseif ($dataFile["EventFileType"] == "multimedia") {
					$dataFileMultiMedias[] = array(
						'file_name'=> $dataFile['FileName'], 
						'size'=> $dataFile['FileSize'], 
						'type'=> $dataFile['FileType'], 
						'hash_name'=> $dataFile['FileHashName']
					);
				}
			}
		}

		// Other Setup
		$menuName = $dataDetailCategory->Title;
		$menuUrl = $this->view->url(array("module" => "event", "controller" => "events", "action" => "list"), null, true);
		$this->view->menuName = $menuName;
		$this->view->menuUrl = $menuUrl;
		$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->view->currentUrl = $currentUrl;
		
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
		$this->view->currentUrlQrCodeUrl = $this->view->QrcodeCurrentUrl($currentUrl, null, $siteName);

		$categoryData = $categoryModel->getDataByLanguage($cid, $this->lang);

		$parent[0] =  $categoryData->ParentID;
		$parentName[0] = $categoryData->Title;
		$checkParen = 1 ;

		while($checkParen != null){
			$dataParen = $categoryModel->getDataByLanguage($parent[$checkParen-1], $this->lang);
			$parent[$checkParen] = $dataParen->ParentID;
			$parentName[$checkParen] = $dataParen->Title;
			if($parent[$checkParen] != null){
				$checkParen++ ;
			}else{
				$checkParen = null ;
			}
		}	

		// Send To View
		$this->view->data = $data;
		$this->view->dataInfo = $dataInfo;
		$this->view->dataIntroImages = $dataIntroImages;
		$this->view->dataFileImages = $dataFileImages;
		$this->view->dataFileDocuments = $dataFileDocuments;
		$this->view->dataFileMultiMedias = $dataFileMultiMedias;

		// Set Global
		$menuID = $this->findMenu("/event/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparent = $num - 2 ;

		while($sumparent > -1){
			if($sumparent <= 0){
				$extraBreadcrumb[] = array('url' => '/event/category/events/id/' . $id . '/cid/' . $cid, 'name' => $parentName[$sumparent]);
				$sumparent = null ;
			}else{
				$extraBreadcrumb[] = array('url' => '/event/category/events/id/' . $id . '/cid/' . $parent[$sumparent-1],'name' => $parentName[$sumparent]);
				$sumparent-- ;
			}
		}

		$extraBreadcrumb[] = array('url' => '/event/category/detail/id/' . $id . '/cid/' . $cid . '/iid/' . $iID, 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}	
		

}
?>