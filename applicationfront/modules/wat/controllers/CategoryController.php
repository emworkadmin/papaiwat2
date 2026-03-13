<?php 

class Wat_CategoryController extends Zend_Controller_Action
{
	protected $lang;
	protected $extensionName;
	protected $extensionCode;

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
		$this->extensionName = Wat_Service_Info::getExtensionName();
		$this->extensionCode = Wat_Service_Info::getExtensionCode();
	}
	
	public function indexAction()
	{
		if(!System_Service_Element::isActive($this->extensionCode)) {
			throw new Zend_Controller_Action_Exception('This page does not exist', 404);
			return;
		}
		
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));

		// Get Info
		$model = new Wat_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
		
		// Send Data to Template
		$this->view->bodyClass = "wat-body-categoies";
				
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;	
		$this->view->dataDetail = $dataDetail;	
		$this->view->cssClass = $dataDetail->CssClass;	
		
		$menuID = $this->findMenu("/wat/category/index/id/" . $id);
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
		$categoryModel = new Content_Model_Category();
		$categoryData = $categoryModel->getSettings($cid);
		$categorySetting = json_decode($categoryData->Settings);
		$cCategoryPerPageSetting = $categorySetting->CategoryPerPageSetting;
		$cCategoryPerPage = $categorySetting->CategoryPerPage;
		$cCategoryAmountColumnSetting = $categorySetting->CategoryAmountColumnSetting;
		$cCategoryAmountColumn = $categorySetting->CategoryAmountColumn;
		$cCategoryShowImageSetting = $categorySetting->CategoryShowImageSetting;
		$cCategoryShowImage = $categorySetting->CategoryShowImage;
		$cCategoryShowIntroTextSetting = $categorySetting->CategoryShowIntroTextSetting;
		$cCategoryShowIntroText = $categorySetting->CategoryShowIntroText;
		
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General"); 
		
		// Setting
		$watSettingCategoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Wat");
		$watSettingCategoryAmountColumn = $cCategoryAmountColumnSetting != "C" ? $watSettingCategoryAmountColumn : $cCategoryAmountColumn;
		$watSettingCategoryAmountColumn = empty($watSettingCategoryAmountColumn) || $watSettingCategoryAmountColumn <= 1 ? 1 : ($watSettingCategoryAmountColumn >= 4 ? 4 : $watSettingCategoryAmountColumn);
		$watSettingCategoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Wat");
		$watSettingCategoryPerPage = $cCategoryPerPageSetting != "C" ? $watSettingCategoryPerPage : $cCategoryPerPage;
		$watSettingCategoryPerPage = $watSettingCategoryPerPage == null ? 0 : $watSettingCategoryPerPage;
		$watSettingCategoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Wat");
		$watSettingCategoryShowImage = $cCategoryShowImageSetting != "C" ? $watSettingCategoryShowImage : $cCategoryShowImage;
		$watSettingCategoryShowImage = $watSettingCategoryShowImage == null ? "Y" : $watSettingCategoryShowImage;
		$watSettingCategoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Wat");
		$watSettingCategoryShowIntroText = $cCategoryShowIntroTextSetting != "C" ? $calendarSettingCategoryShowIntroText : $cCategoryShowIntroText;
		$watSettingCategoryShowIntroText = $watSettingCategoryShowIntroText == null ? "Y" : $watSettingCategoryShowIntroText;
		
		$sortType = 'Ordering';
		$sortDirection = 'asc';
				
		// Init
		//$pageSize = empty($watSettingCategoryPerPage) ? 10 : $watSettingCategoryPerPage;
		$pageSize = $watSettingCategoryPerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->getrequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		
		$advancesearch = array();
		if(!empty($cid)){
			$advancesearch["parent_id"] = $cid;
		}else{
			$advancesearch["parent_id"] = $id;
		}
		
		$model = new Wat_Model_Category();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->watSettingCategoryAmountColumn = $watSettingCategoryAmountColumn;
		$this->view->watSettingCategoryShowImage = $watSettingCategoryShowImage == 'Y';
		$this->view->watSettingCategoryShowIntroText = $watSettingCategoryShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->items = $items;
		if ($watSettingCategoryAmountColumn == 1) {
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
	
	// Item List	
	public function articlesAction()
	{
		if(!System_Service_Element::isActive($this->extensionCode)) {
			throw new Zend_Controller_Action_Exception('This page does not exist', 404);
			return;
		}
		
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		// id = cid when id = 0
		if (empty($id) || empty($cid)) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		$model = new Wat_Model_Category();
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
		$this->view->cssClass = $mainCategoryData->CssClass;
		$this->view->subCssClass = $categoryData->CssClass;
		$this->view->pageTitle = $categoryData->Title;
		$this->view->dataDetail = $categoryData;
		
		// Send Data to Template
		$this->view->bodyClass = "wat-body-articles";
		
		// Set Global
		$menuID = $this->findMenu("/wat/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			//echo($sumparen);
			$extraBreadcrumb[] = array('url' => '/wat/category/articles/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
			$sumparen-- ;
			if($sumparen == -1){
				$sumparen = null ;
			}
		}

		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}
	
	public function getarticlesAction() {
		
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

		$categoryModel = new Wat_Model_Category();
		$categoryData = $categoryModel->getSettings($cid);
		$categorySetting = json_decode($categoryData->Settings);
		$cItemAmountColumnSetting = $categorySetting->ItemAmountColumnSetting;
		$cItemAmountColumn = $categorySetting->ItemAmountColumn;
		$cItemPerPageSetting = $categorySetting->ItemPerPageSetting;
		$cItemPerPage = $categorySetting->ItemPerPage;
		$cItemShowAmountSetting = $categorySetting->ItemShowAmountSetting;
		$cItemShowAmount = $categorySetting->ItemShowAmount;
		$cItemShowImageSetting = $categorySetting->ItemShowImageSetting;
		$cItemShowImage = $categorySetting->ItemShowImage;
		$cItemShowIntroTextSetting = $categorySetting->ItemShowIntroTextSetting;
		$cItemShowIntroText = $categorySetting->ItemShowIntroText;
		$cItemSortSetting = $categorySetting->ItemSortSetting;
		$cItemSortType = $categorySetting->ItemSortType;
		$cItemSortDirection = $categorySetting->ItemSortDirection;
		
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");		
		
		// Setting
		$watSettingItemAmountColumn = System_Model_Setting::getValue("ItemAmountColumn", "Wat");
		$watSettingItemAmountColumn = $cItemAmountColumnSetting != "C" ? $watSettingItemAmountColumn : $cItemAmountColumn;
		$watSettingItemAmountColumn = empty($watSettingItemAmountColumn) || $watSettingItemAmountColumn <= 1 ? 1 : ($watSettingItemAmountColumn >= 4 ? 4 : $watSettingItemAmountColumn);
		$watSettingItemPerPage = System_Model_Setting::getValue("ItemPerPage", "Wat");
		$watSettingItemPerPage = $cItemPerPageSetting != "C" ? $watSettingItemPerPage : $cItemPerPage;
		$watSettingItemShowAmount = System_Model_Setting::getValue("ItemShowAmount", "Wat");
		$watSettingItemShowAmount = $cItemShowAmountSetting != "C" ? $watSettingItemShowAmount : $cItemShowAmount;
		$watSettingItemShowImage = System_Model_Setting::getValue("ItemShowImage", "Wat");
		$watSettingItemShowImage = $cItemShowImageSetting != "C" ? $watSettingItemShowImage : $cItemShowImage;
		$watSettingItemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "Wat");
		$watSettingItemShowIntroText = $cItemShowIntroTextSetting != "C" ? $watSettingItemShowIntroText : $cItemShowIntroText;
		$watSettingItemShowAddress = "Y";
		$watSettingItemSortType = System_Model_Setting::getValue("ItemSortType", "Wat");
		$watSettingItemSortType = $cItemSortSetting != "C" ? $watSettingItemSortType : $cItemSortType;
		$watSettingItemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Wat");
		$watSettingItemSortDirection = $cItemSortSetting != "C" ? $watSettingItemSortDirection : $cItemSortDirection;
		
		$fileModel = new System_Model_Fileinfo();
		$watSettingDefaultCoverImageHashName = '';
		$watSettingDefaultCoverImageID = System_Model_Setting::getValue("CoverImage", "Wat");
		if (!empty($watSettingDefaultCoverImageID)) {
			$fileInfo = $fileModel->item($watSettingDefaultCoverImageID);
			if (!empty($fileInfo->ID)) {
				$watSettingDefaultCoverImageHashName = $fileInfo->HashName;
			}
		}
		$watSettingDefaultIntroImageHashName = '';
		$watSettingDefaultIntroImageID = System_Model_Setting::getValue("IntroImage", "Wat");
		if (!empty($watSettingDefaultIntroImageID)) {
			$fileInfo = $fileModel->item($watSettingDefaultIntroImageID);
			if (!empty($fileInfo->ID)) {
				$watSettingDefaultIntroImageHashName = $fileInfo->HashName;
			}
		}

		// Setting News and Update
		$setting->watSettingNewlimitdata = System_Model_Setting::getValue("Newlimitdata", "Content");
		$setting->watSettingNewlimitdata=empty($setting->watSettingNewlimitdata) ? 10:$setting->watSettingNewlimitdata;
		$setting->watSettingNewdistancedate = System_Model_Setting::getValue("Newdistancedate", "Content");
		$setting->watSettingNewdistancedate=empty($setting->watSettingNewdistancedate) ? 10:$setting->watSettingNewdistancedate;
		$setting->watSettingUpdatelimitdata = System_Model_Setting::getValue("Updatelimitdata", "Content");
		$setting->watSettingUpdatelimitdata=empty($setting->watSettingUpdatelimitdata) ? 10:$setting->watSettingUpdatelimitdata;
		$setting->watSettingUpdatedistancedate = System_Model_Setting::getValue("Updatedistancedate", "Content");
		$setting->watSettingUpdatedistancedate=empty($setting->watSettingUpdatedistancedate) ? 10:$setting->watSettingUpdatedistancedate;

		$sortType = 'Created';
		switch ($watSettingItemSortType) {
			case 'created' :
				$sortType = 'Created';
				break;
			case 'publishdate' :
				$sortType = 'PublishDate';
				break;
			case 'title' :
			case 'name' :
				$sortType = 'Title';
				break;
			case 'modified' :
				$sortType = 'Modified';
				break;
		}
		$sortDirection = in_array($watSettingItemSortDirection, array('asc', 'desc')) ? $watSettingItemSortDirection : 'desc';
		
		// Init
		$pageSize = empty($watSettingItemPerPage) ? 10 : $watSettingItemPerPage;


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

		$model = new Wat_Model_Item();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);		

		$datanewforicon = $model->newIcon($setting->watSettingNewlimitdata , $this->lang);
		$dataupdateforicon = $model->updateIcon($setting->watSettingUpdatelimitdata , $this->lang);

		// if(!empty($items)){
		// 	for($i=0;$i<count($items);$i++){
		// 		if($items[$i]['ID']==$datanewforicon[$i]['ID']){
					
		// 			$items[$i]['status']='new';
		// 		}
		// 	}
		// }
		// echo "<pre>";print_r($items);exit;
		// Set Setting to view
		$this->view->id = $id;
		$this->view->cid = $cid;

		//$this->view->currentCategoryID = $currentCategoryID;
		$this->view->datanewforicon = $datanewforicon;
		$this->view->dataupdateforicon = $dataupdateforicon;
		$this->view->setting = $setting;
		$this->view->controllerName = "category";
		$this->view->watSettingItemAmountColumn = $watSettingItemAmountColumn;
		$this->view->watSettingItemShowImage = $watSettingItemShowImage == 'Y';
		$this->view->watSettingItemShowIntroText = $watSettingItemShowIntroText == 'Y';
		$this->view->watSettingItemShowAddress = $watSettingItemShowAddress == 'Y';
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		if (!empty($watSettingDefaultIntroImageHashName)) {
			$this->view->defaultImageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $watSettingDefaultIntroImageHashName), null, true);
		}

		// Send Data to view
		$this->view->items = $items;
		if ($watSettingItemAmountColumn == 1) {
			$responce["html"] = $this->view->render('category/_arows.phtml');
		} else {
			$responce["html"] = $this->view->render('category/_acards.phtml');
		}
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
	}
	
	// Item Detail
	public function detailAction() {
		
		if(!System_Service_Element::isActive($this->extensionCode)) {
			throw new Zend_Controller_Action_Exception('This page does not exist', 404);
			return;
		}
		// Log Access
		System_Model_Log::access ();
		
		$filterInt = new Zend_Filter_Int ();
		$id = $this->getRequest ()->getParam ( "id", "" );
		$iID = $this->getRequest ()->getParam ( "iid", "" );
		$cid = $this->getRequest ()->getParam ( "cid", "" );
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
		Wat_Model_Item::saveView($iID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Wat_Model_Item();
		$fileModel = new System_Model_Fileinfo(); 
		$dataInfo = $model->getInfo( $iID );
		$data = $model->getDataByLanguage( $iID, $this->lang);
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
				
		$categoryModel = new Wat_Model_Category();
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
		$data->IntroImage = '';
		if (!empty($data->IntroImageHashName)) {
			$data->IntroImage = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $data->IntroImageHashName), null, true);
		} else {
			$defaultIntroImageID = System_Model_Setting::getValue("IntroImage", "Wat");
			if (!empty($defaultIntroImageID)) {
				$fileObj = $fileModel->item($defaultIntroImageID);
				$data->IntroImage = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $fileObj->HashName), null, true);
			}
		}
		// Cover Image
		$data->CoverImage = '';
		if (!empty($data->CoverImageHashName)) {
			$data->CoverImage = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $data->CoverImageHashName), null, true);
		} else {
			$defaultCoverImageID = System_Model_Setting::getValue("CoverImage", "Wat");
			if (!empty($defaultCoverImageID)) {
				$fileObj = $fileModel->item($defaultCoverImageID);
				$data->CoverImage = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $fileObj->HashName), null, true);
			}
		}
		
		// File / Gallery
		$dataFiles = $model->getFiles($data->ID);
		$dataFileImages = array();
		$dataFileDocuments = array();
		$dataFileMultiMedias = array();
		if (count($dataFiles) > 0) {
			foreach ($dataFiles as $dataFile) {
				if ($dataFile["ItemFileType"] == "image") {
					$dataFileImages[] = array('WatItemFileID'=>$dataFile['WatItemFileID'], 'DownloadAmount'=>$dataFile['DownloadAmount'], 'file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				} elseif ($dataFile["ItemFileType"] == "document") {
					$dataFileDocuments[] = array('WatItemFileID'=>$dataFile['WatItemFileID'], 'DownloadAmount'=>$dataFile['DownloadAmount'], 'file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName'],'Extension'=>$dataFile['FileExtension']);
				} elseif ($dataFile["ItemFileType"] == "multimedia") {
					$dataFileMultiMedias[] = array('WatItemFileID'=>$dataFile['WatItemFileID'], 'DownloadAmount'=>$dataFile['DownloadAmount'], 'file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				}
			}
		}
		
		// Activity
		$dataWatActivityList = $model->getActivitiesByLanguage($data->ID, $this->lang);
		
		// Rescue
		$dataWatRescueList = $model->getRescuesByLanguage($data->ID, $this->lang);
		
		// WatType
		$dataWatTypeList = $model->getWatTypes($data->ID, $this->lang);
		
		// Tag
		$dataWatTagList = $model->getAllKeywordByItem($data->ID, $this->lang);
		
		// Tag
		$dataOpenHourList = $model->getOpenHours($data->ID, $this->lang);

		// BankAccount
		$dataBankAccountList = $model->getBankAccountByLanguage($data->ID, $this->lang);

		// PromptPayAccount
		$dataPromptPayAccountList = $model->getPromptPayAccountByLanguage($data->ID, $this->lang);

		// Other Setup
		$menuName = $dataDetailCategory->Title;
		$menuUrl = $this->view->url(array("module" => "wat", "controller" => "article", "action" => "list"), null, true);
		$this->view->menuName = $menuName;
		$this->view->menuUrl = $menuUrl;
		$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->view->currentUrl = $currentUrl;
		
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
		$this->view->currentUrlQrCodeUrl = $this->view->QrcodeCurrentUrl($currentUrl, null, $siteName);
		
		$Model = new Wat_Model_Category();
		$categoryData = $Model->getDataByLanguage($cid, $this->lang);
		
		$parent[0] =  $categoryData->ParentID;
		$parentName[0] = $categoryData->Title;
		$checkParen = 1 ;

		while($checkParen != null){
			$dataParent = $model->getDataByLanguage($parent[$checkParen-1], $this->lang);
			$parent[$checkParen] = $dataParent->ParentID;
			$parentName[$checkParen] = $dataParent->Title;
			if($parent[$checkParen] != null){
				$checkParen++ ;
			}else{
				$checkParen = null ;
			}
		}
		
		// Option
		$watClassOption = Wat_Service_Info::getWatClassOption($this->lang);
		$dayOption = Wat_Service_Info::getDayOption($this->lang);
		$watKindOption = Wat_Service_Info::getWatKindOption($this->lang);

		// Send To View
		$this->view->cssClass = $dataDetailCategory->CssClass;
		$this->view->data = $data;
		$this->view->datatag = $datatag;
		$this->view->dataInfo = $dataInfo;
		$this->view->dataIntroImages = $dataIntroImages;
		$this->view->dataFileImages = $dataFileImages;
		$this->view->dataFileDocuments = $dataFileDocuments;
		$this->view->dataFileMultiMedias = $dataFileMultiMedias;
		$this->view->dataWatActivityList = $dataWatActivityList;
		$this->view->dataWatRescueList = $dataWatRescueList;
		$this->view->dataWatTagList = $dataWatTagList;
		$this->view->dataWatTypeList = $dataWatTypeList;
		$this->view->dataOpenHourList = $dataOpenHourList;
		$this->view->dataBankAccountList = $dataBankAccountList;
		$this->view->dataPromptPayAccountList = $dataPromptPayAccountList;
		
		$this->view->watKindOption = $watKindOption;
		$this->view->watClassOption = $watClassOption;
		$this->view->dayOption = $dayOption;

		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;
		$this->view->ControllerName = "category";
		$this->view->ItemCanComment = $ItemCanComment;
		$this->view->ItemPublicComment = $ItemPublicComment;
		$this->view->ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");
		$this->view->facebookAppID = System_Model_Setting::getValue("AppID", "Facebook");
		$this->view->googleMapApiKey = System_Model_Setting::getValue("ApiKey", "GoogleMap");

		$dataParam = new stdClass();
		$dataParam->id = $id;
		$dataParam->cid = $cid;
		$dataParam->iid = $iID;
		$this->view->dataParam = $dataParam;
		
		// Send Data to Template
		$this->view->bodyClass = "wat-body-detail";
		$this->view->pageTitle = $data->Title;
		$this->view->pageDescription = $data->IntroText;
		$this->view->pageKeyword = $data->MetaData;
		$this->view->ogImage = $data->IntroImage;

		// Set Global
		$menuID = $this->findMenu("/wat/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			//echo($sumparen);
			if($sumparen <= 0){
				$extraBreadcrumb[] = array('url' => '/wat/category/articles/id/' . $id . '/cid/' . $cid, 'name' => $parentName[$sumparen] );
				$sumparen = null ;
			}else{
				$extraBreadcrumb[] = array('url' => '/wat/category/articles/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
				$sumparen-- ;
			}
		}
		$extraBreadcrumb[] = array('url' => '/wat/category/detail/id/' . $id . '/cid/' . $cid . '/iid/' . $iID, 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}
	
	protected function findMenu($url) {
		$menuID = null;
		$menus = System_Model_Menu::findMenuByUrl($url);
		if (count($menus) > 0) {
			$menuID = $menus[0]["ID"];
		}
		return $menuID;
	}
}
?>