<?php 


class Calendar_CategoryController extends Zend_Controller_Action
{
	protected $auth;
	protected $lang;
	protected $mainCategoryID = 2;

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
	
	public function indexAction()
	{
		// Log Access
		System_Model_Log::access();

		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));
		
		// Get Info
		$model = new Calendar_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
				
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;		
		
		$menuID = $this->findMenu("/calendar/category/index/id/" . $id);
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
		$categoryModel = new Calendar_Model_Category();
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
		$calendarSettingCategoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Calendar");
		$calendarSettingCategoryAmountColumn = $cCategoryAmountColumnSetting == "D" ? $calendarSettingCategoryAmountColumn : $cCategoryAmountColumn;
		$calendarSettingCategoryAmountColumn = empty($calendarSettingCategoryAmountColumn) || $calendarSettingCategoryAmountColumn <= 1 ? 1 : ($calendarSettingCategoryAmountColumn >= 4 ? 4 : $calendarSettingCategoryAmountColumn);
		$calendarSettingCategoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Calendar");
		$calendarSettingCategoryPerPage = $cCategoryPerPageSetting == "D" ? $calendarSettingCategoryPerPage : $cCategoryPerPage;
		$calendarSettingCategoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Calendar");
		$calendarSettingCategoryShowImage = $cCategoryShowImageSetting == "D" ? $calendarSettingCategoryShowImage : $cCategoryShowImage;
		$calendarSettingCategoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Calendar");
		$calendarSettingCategoryShowIntroText = $cCategoryShowIntroTextSetting == "D" ? $calendarSettingCategoryShowIntroText : $cCategoryShowIntroText;
		
		$sortType = 'Ordering';
		$sortDirection = 'asc';
				
		// Init
		$pageSize = empty($calendarSettingCategoryPerPage) ? 10 : $calendarSettingCategoryPerPage;
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
		
		$model = new Calendar_Model_Category();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->calendarSettingCategoryAmountColumn = $calendarSettingCategoryAmountColumn;
		$this->view->calendarSettingCategoryShowImage = $calendarSettingCategoryShowImage == 'Y';
		$this->view->calendarSettingCategoryShowIntroText = $calendarSettingCategoryShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->items = $items;
		if ($calendarSettingCategoryAmountColumn == 1) {
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

	// Article List	
	public function articlesAction()
	{	
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		// Get Param
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		
		if (empty($id) || empty($cid)) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		$model = new Calendar_Model_Category();
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
		$menuID = $this->findMenu("/calendar/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);

		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			//echo($sumparen);
			$extraBreadcrumb[] = array('url' => '/calendar/category/articles/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
			$sumparen-- ;
			if($sumparen == -1){
				$sumparen = null ;
			}
		}

		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}
	
	public function getarticlesAction() {
		
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
		$categoryModel = new Calendar_Model_Category();
		$categoryData = $categoryModel->getSettings($cid);
		$categorySetting = json_decode($categoryData->Settings);
		$cArticleAmountColumnSetting = $categorySetting->ArticleAmountColumnSetting;
		$cArticleAmountColumn = $categorySetting->ArticleAmountColumn;
		$cArticlePerPageSetting = $categorySetting->ArticlePerPageSetting;
		$cArticlePerPage = $categorySetting->ArticlePerPage;
		$cArticleShowAmountSetting = $categorySetting->ArticleShowAmountSetting;
		$cArticleShowAmount = $categorySetting->ArticleShowAmount;
		$cArticleShowImageSetting = $categorySetting->ArticleShowImageSetting;
		$cArticleShowImage = $categorySetting->ArticleShowImage;
		$cArticleShowIntroTextSetting = $categorySetting->ArticleShowIntroTextSetting;
		$cArticleShowIntroText = $categorySetting->ArticleShowIntroText;
		$cArticleSortSetting = $categorySetting->ArticleSortSetting;
		$cArticleSortType = $categorySetting->ArticleSortType;
		$cArticleSortDirection = $categorySetting->ArticleSortDirection;
		
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
		
		// Setting
		$calendarSettingArticleAmountColumn = System_Model_Setting::getValue("ArticleAmountColumn", "Calendar");
		$calendarSettingArticleAmountColumn = $cArticleAmountColumnSetting == "D" ? $calendarSettingArticleAmountColumn : $cArticleAmountColumn;
		$calendarSettingArticleAmountColumn = empty($calendarSettingArticleAmountColumn) || $calendarSettingArticleAmountColumn <= 1 ? 1 : ($calendarSettingArticleAmountColumn >= 4 ? 4 : $calendarSettingArticleAmountColumn);
		$calendarSettingArticlePerPage = System_Model_Setting::getValue("ArticlePerPage", "Calendar");
		$calendarSettingArticlePerPage = $cArticlePerPageSetting == "D" ? $calendarSettingArticlePerPage : $cArticlePerPage;
		$calendarSettingArticleShowAmount = System_Model_Setting::getValue("ArticleShowAmount", "Calendar");
		$calendarSettingArticleShowAmount = $cArticleShowAmountSetting == "D" ? $calendarSettingArticleShowAmount : $cArticleShowAmount;
		$calendarSettingArticleShowImage = System_Model_Setting::getValue("ArticleShowImage", "Calendar");
		$calendarSettingArticleShowImage = $cArticleShowImageSetting == "D" ? $calendarSettingArticleShowImage : $cArticleShowImage;
		$calendarSettingArticleShowIntroText = System_Model_Setting::getValue("ArticleShowIntroText", "Calendar");
		$calendarSettingArticleShowIntroText = $cArticleShowIntroTextSetting == "D" ? $calendarSettingArticleShowIntroText : $cArticleShowIntroText;
		$calendarSettingArticleSortType = System_Model_Setting::getValue("ArticleSortType", "Calendar");
		$calendarSettingArticleSortType = $cArticleSortSetting == "D" ? $calendarSettingArticleSortType : $cArticleSortType;
		$calendarSettingArticleSortDirection = System_Model_Setting::getValue("ArticleSortDirection", "Calendar");
		$calendarSettingArticleSortDirection = $cArticleSortSetting == "D" ? $contentSettingArticleSortDirection : $cArticleSortDirection;
		
		$sortType = 'Created';
		switch ($calendarSettingArticleSortType) {
			case 'created' :
				$sortType = 'Created';
				break;
			case 'title' :
			case 'name' :
				$sortType = 'Title';
				break;
			case 'modified' :
				$sortType = 'Modified';
				break;
		}
		$sortDirection = in_array($calendarSettingArticleSortDirection, array('asc', 'desc')) ? $calendarSettingArticleSortDirection : 'desc';
		
		// Init
		$pageSize = empty($calendarSettingArticlePerPage) ? 10 : $calendarSettingArticlePerPage;
		
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

		$model = new Calendar_Model_Article();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		// Set Setting to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		//$this->view->currentCategoryID = $currentCategoryID;
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->calendarSettingArticleAmountColumn = $calendarSettingArticleAmountColumn;
		$this->view->calendarSettingArticleShowImage = $calendarSettingArticleShowImage == 'Y';
		$this->view->calendarSettingArticleShowIntroText = $calendarSettingArticleShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->items = $items;
		if ($calendarSettingArticleAmountColumn == 1) {
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
	
	// Article Detail
	public function detailAction() {

		// Log Access
		System_Model_Log::access ();

		$filterInt = new Zend_Filter_Int ();
		$id = $this->getRequest ()->getParam ( "id", "" );
		$iID = $this->getRequest ()->getParam ( "iid", "" );
		$cid = $this->getRequest ()->getParam ( "cid", "" );
		$cid = $filterInt->filter ( $cid );
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
		Calendar_Model_Article::saveView($iID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Calendar_Model_Article();
		$dataInfo = $model->getInfo( $iID );
		$data = $model->getDataByLanguage( $iID, $this->lang);
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
				
		$categoryModel = new Calendar_Model_Category();
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
			$dataIntroImages[] = array('file_name'=> $data->IntroImageFileName, 'size'=> $data->IntroImageFileSize, 'type'=> $data->IntroImageFileType, 'hash_name'=> $data->IntroImageHashName);
		}
		
		$dataFiles = $model->getFiles($data->ID);
		$dataFileImages = array();
		$dataFileDocuments = array();
		$dataFileMultiMedias = array();
		if (count($dataFiles) > 0) {
			foreach ($dataFiles as $dataFile) {
				if ($dataFile["ArticleFileType"] == "image") {
					$dataFileImages[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				} elseif ($dataFile["ArticleFileType"] == "document") {
					$dataFileDocuments[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				} elseif ($dataFile["ArticleFileType"] == "multimedia") {
					$dataFileMultiMedias[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				}
			}
		}
		
		// Other Setup
		$menuName = $dataDetailCategory->Title;
		$menuUrl = $this->view->url(array("module" => "calendar", "controller" => "article", "action" => "list"), null, true);
		$this->view->menuName = $menuName;
		$this->view->menuUrl = $menuUrl;
		$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->view->currentUrl = $currentUrl;
		
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
		$this->view->currentUrlQrCodeUrl = $this->view->QrcodeCurrentUrl($currentUrl, null, $siteName);
 
		$Model = new Calendar_Model_Category();
		$categoryData = $Model->getDataByLanguage($cid, $this->lang);

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

		$cSettings = json_decode($data->Settings);
		$cArticleCanCommentSetting = $cSettings->ArticleCanCommentSetting;
		$cArticleCanComment = $cSettings->ArticleCanComment;
		$cArticlePublicCommentSetting = $cSettings->ArticlePublicCommentSetting;
		$cArticlePublicComment = $cSettings->ArticlePublicComment;

		$dArticleCanComment = System_Model_Setting::getValue("ArticleCanComment", "Calendar");
		$ArticleCanComment = $cArticleCanCommentSetting == "D" ? $dArticleCanComment : $cArticleCanComment;
		$dArticlePublicComment = System_Model_Setting::getValue("ArticlePublicComment", "Calendar");
		$ArticlePublicComment = $cArticlePublicCommentSetting == "D" ? $dArticlePublicComment : $cArticlePublicComment;

		if(in_array($data->CanComment,array('Y','N'))){
			$ArticleCanComment = $data->CanComment;
		}

		// Send To View
		$this->view->data = $data;
		$this->view->dataInfo = $dataInfo;
		$this->view->dataIntroImages = $dataIntroImages;
		$this->view->dataFileImages = $dataFileImages;
		$this->view->dataFileDocuments = $dataFileDocuments;
		$this->view->dataFileMultiMedias = $dataFileMultiMedias;

		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;
		$this->view->ControllerName = "category";
		$this->view->ArticleCanComment = $ArticleCanComment;
		$this->view->ArticlePublicComment = $ArticlePublicComment;
		$this->view->ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");

		$dataParam = new stdClass();
		$dataParam->id = $id;
		$dataParam->cid = $cid;
		$dataParam->iid = $iID;
		$this->view->dataParam = $dataParam;
		
		// Set Global
		$menuID = $this->findMenu("/calendar/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);

		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			if($sumparen <= 0){
				$extraBreadcrumb[] = array('url' => '/calendar/category/articles/id/' . $id . '/cid/' . $cid, 'name' => $parentName[$sumparen] );
				$sumparen = null ;
			}else{
				$extraBreadcrumb[] = array('url' => '/calendar/category/articles/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
				$sumparen-- ;
			}
		}

		$extraBreadcrumb[] = array('url' => '/calendar/category/detail/id/' . $id . '/cid/' . $cid . '/iid/' . $iID, 'name' => $data->Title);
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