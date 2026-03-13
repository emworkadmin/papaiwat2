<?php 


class Story_CategoryController extends Zend_Controller_Action
{
	protected $auth;
	protected $lang;
	protected $mainCategoryID;

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
		$model = new Story_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
				
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;	
		$this->view->dataDetail = $dataDetail;	
		
		// Send Data to Template
		$this->view->bodyClass = "story-body-categoies";
		
		$menuID = $this->findMenu("/story/category/index/id/" . $id);
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

		$categoryModel = new Story_Model_Category();
		$categoryData = $categoryModel->getSettings($cid);
		$categorySetting = json_decode($categoryData->Settings);
		$cCategoryPerPageSetting = empty($categorySetting->CategoryPerPageSetting) ? "D" : $categorySetting->CategoryPerPageSetting;
		$cCategoryPerPage = empty($categorySetting->CategoryPerPage) ? 10 : $categorySetting->CategoryPerPage;
		$cCategoryAmountColumnSetting = empty($categorySetting->CategoryAmountColumnSetting) ? "D" : $categorySetting->CategoryAmountColumnSetting;
		$cCategoryAmountColumn = $categorySetting->CategoryAmountColumn;
		$cCategoryShowImageSetting = empty($categorySetting->CategoryShowImageSetting) ? "D" :  $categorySetting->CategoryShowImageSetting;
		$cCategoryShowImage = $categorySetting->CategoryShowImage;
		$cCategoryShowIntroTextSetting = empty($categorySetting->CategoryShowIntroTextSetting) ? "D" : $categorySetting->CategoryShowIntroTextSetting;
		$cCategoryShowIntroText = $categorySetting->CategoryShowIntroText;
		
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General"); 
		
		// Setting
		$storySettingCategoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Story");
		$storySettingCategoryAmountColumn = $cCategoryAmountColumnSetting == "D" ? $storySettingCategoryAmountColumn : $cCategoryAmountColumn;
		$storySettingCategoryAmountColumn = empty($storySettingCategoryAmountColumn) || $storySettingCategoryAmountColumn <= 1 ? 1 : ($storySettingCategoryAmountColumn >= 4 ? 4 : $storySettingCategoryAmountColumn);
		$storySettingCategoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Story");
		$storySettingCategoryPerPage = $cCategoryPerPageSetting == "D" ? $storySettingCategoryPerPage : $cCategoryPerPage;
		$storySettingCategoryPerPage = empty($storySettingCategoryPerPage) ? 10 : $storySettingCategoryPerPage;
		$storySettingCategoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Story");
		$storySettingCategoryShowImage = $cCategoryShowImageSetting == "D" ? $storySettingCategoryShowImage : $cCategoryShowImage;
		$storySettingCategoryShowImage = empty($storySettingCategoryShowImage) ? "Y" : $storySettingCategoryShowImage;
		$storySettingCategoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Story");
		$storySettingCategoryShowIntroText = $cCategoryShowIntroTextSetting == "D" ? $storySettingCategoryShowIntroText : $cCategoryShowIntroText;
		$storySettingCategoryShowIntroText = empty($storySettingCategoryShowIntroText) ? "Y" : $storySettingCategoryShowIntroText;
		
		$sortType = 'Ordering';
		$sortDirection = 'asc';
				
		// Init
		$pageSize = empty($storySettingCategoryPerPage) ? 10 : $storySettingCategoryPerPage;
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
		
		$model = new Story_Model_Category();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->storySettingCategoryAmountColumn = $storySettingCategoryAmountColumn;
		$this->view->storySettingCategoryShowImage = $storySettingCategoryShowImage == 'Y';
		$this->view->storySettingCategoryShowIntroText = $storySettingCategoryShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->items = $items;
		if ($storySettingCategoryAmountColumn == 1) {
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
		// id = cid when id = 0

		if (empty($id) || empty($cid)) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}

		$model = new Story_Model_Category();
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
		$this->view->dataDetail = $categoryData;
		
		// Send Data to Template
		$this->view->bodyClass = "story-body-articles";
		
		// Set Global
		$menuID = $this->findMenu("/story/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			//echo($sumparen);
			$extraBreadcrumb[] = array('url' => '/story/category/articles/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
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

		$categoryModel = new Story_Model_Category();
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
		$storySettingArticleAmountColumn = System_Model_Setting::getValue("ArticleAmountColumn", "Story");
		$storySettingArticleAmountColumn = $cArticleAmountColumnSetting == "D" ? $storySettingArticleAmountColumn : $cArticleAmountColumn;
		$storySettingArticleAmountColumn = empty($storySettingArticleAmountColumn) ? 1 : $storySettingArticleAmountColumn;
		$storySettingArticleAmountColumn = empty($storySettingArticleAmountColumn) || $storySettingArticleAmountColumn <= 1 ? 1 : ($storySettingArticleAmountColumn >= 4 ? 4 : $storySettingArticleAmountColumn);
		
		$storySettingArticlePerPage = System_Model_Setting::getValue("ArticlePerPage", "Story");
		$storySettingArticlePerPage = $cArticlePerPageSetting == "D" ? $storySettingArticlePerPage : $cArticlePerPage;
		$storySettingArticlePerPage = empty($storySettingArticlePerPage) ? 10 : $storySettingArticlePerPage;

		$storySettingArticleShowAmount = System_Model_Setting::getValue("ArticleShowAmount", "Story");
		$storySettingArticleShowAmount = $cArticleShowAmountSetting == "D" ? $storySettingArticleShowAmount : $cArticleShowAmount;
		$storySettingArticleShowAmount = empty($storySettingArticleShowAmount) ? "Y" : $storySettingArticleShowAmount;

		$storySettingArticleShowImage = System_Model_Setting::getValue("ArticleShowImage", "Story");
		$storySettingArticleShowImage = $cArticleShowImageSetting == "D" ? $storySettingArticleShowImage : $cArticleShowImage;
		$storySettingArticleShowImage = empty($storySettingArticleShowImage) ? "Y" : $storySettingArticleShowImage;

		$storySettingArticleShowIntroText = System_Model_Setting::getValue("ArticleShowIntroText", "Story");
		$storySettingArticleShowIntroText = $cArticleShowIntroTextSetting == "D" ? $storySettingArticleShowIntroText : $cArticleShowIntroText;
		$storySettingArticleShowIntroText = empty($storySettingArticleShowIntroText) ? "Y" : $storySettingArticleShowIntroText;

		$storySettingArticleSortType = System_Model_Setting::getValue("ArticleSortType", "Story");
		$storySettingArticleSortType = $cArticleSortSetting == "D" ? $storySettingArticleSortType : $cArticleSortType;
		$storySettingArticleSortType = empty($storySettingArticleSortType) ? 'title' : $storySettingArticleSortType;

		$storySettingArticleSortDirection = System_Model_Setting::getValue("ArticleSortDirection", "Story");
		$storySettingArticleSortDirection = $cArticleSortSetting == "D" ? $storySettingArticleSortDirection : $cArticleSortDirection;
		$storySettingArticleSortDirection = empty($storySettingArticleSortDirection) ? 'asc' : $storySettingArticleSortDirection;
		
		$sortType = 'Created';
		switch ($storySettingArticleSortType) {
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
		$sortDirection = in_array($storySettingArticleSortDirection, array('asc', 'desc')) ? $storySettingArticleSortDirection : 'desc';
		
		// Init
		$pageSize = empty($storySettingArticlePerPage) ? 10 : $storySettingArticlePerPage;


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

		$model = new Story_Model_Article();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		// Set Setting to view
		$this->view->id = $id;
		$this->view->cid = $cid;

		//$this->view->currentCategoryID = $currentCategoryID;
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->storySettingArticleAmountColumn = $storySettingArticleAmountColumn;
		$this->view->storySettingArticleShowImage = $storySettingArticleShowImage == 'Y';
		$this->view->storySettingArticleShowIntroText = $storySettingArticleShowIntroText == 'Y';

		
		// Send Data to view
		$this->view->items = $items;
		if ($storySettingArticleAmountColumn == 1) {
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
	public function detailAction()
	{
		// Log Access
		System_Model_Log::access();
		
		$filterInt = new Zend_Filter_Int();
		
		$id = $this->getRequest()->getParam("id", "");
		$cid = $this->getRequest ()->getParam ( "cid", "" );
		$iid = $this->getRequest()->getParam("iid", "");

		$id = $filterInt->filter($id);
		$iid = $filterInt->filter($iid);
		
		if (empty($id)) {
			$this->_helper->redirector('index', 'index', 'default');
			return;
		}elseif(empty($iid)){
			$this->_helper->redirector('index', 'index', 'default');
			return;
		}

		// Save View
		$accessFrom = "Web";
		$memberID = null;
		$userID = null;
		Story_Model_Article::saveView($iid, $memberID, $userID, $this->lang, $accessFrom);

		// Other Setup
		$menuUrl = $this->view->url(array("module" => "story", "controller" => "category", "action" => "detail", "id" => $id, "cid" => $cid, "iid" => $iid), null, true);
		$this->view->menuName = $menuName;
		$this->view->menuUrl = $menuUrl;
		$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->view->currentUrl = $currentUrl;
		
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
		$this->view->currentUrlQrCodeUrl = $this->view->QrcodeCurrentUrl($currentUrl, null, $siteName);
				
		$model = new Story_Model_Category();
		$dataInfo = $model->getInfoStory($iid,$this->lang);
		
		// Check Data: Can Access
		if (!$dataInfo || $dataInfo->IsEnable != 'Y' || $dataInfo->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}

		if (empty($dataInfo->Title)) {
			// Show "Not found data in this LANG, อยู่ระหว่างจัดทำข้อมูล"
			$this->renderScript('index/_alertmaintenance.phtml');
			return;
		}

		$this->view->dataInfoStory = $dataInfo;
		$this->view->ViewAmount = $dataInfo->ViewAmount ;
		$this->view->Created = $dataInfo->Created ;

		$dataDetail = $model->getInfoDataStory($dataInfo->ID,$this->lang);
		$dataCover = $model->getCoverListByLanguage($dataInfo->ID,$this->lang);

		// File Document
		$dataFiles = $model->getFiles($dataInfo->ID);
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
		
		$cSettings = json_decode($dataInfo->Settings);
		$cArticleCanCommentSetting = $cSettings->ArticleCanCommentSetting;
		$cArticleCanComment = $cSettings->ArticleCanComment;
		$cArticlePublicCommentSetting = $cSettings->ArticlePublicCommentSetting;
		$cArticlePublicComment = $cSettings->ArticlePublicComment;

		$dArticleCanComment = System_Model_Setting::getValue("ArticleCanComment", "Story");
		$ArticleCanComment = $cArticleCanCommentSetting == "D" ? $dArticleCanComment : $cArticleCanComment;
		$dArticlePublicComment = System_Model_Setting::getValue("ArticlePublicComment", "Story");
		$ArticlePublicComment = $cArticlePublicCommentSetting == "D" ? $dArticlePublicComment : $cArticlePublicComment;
		
		$dArticleUseFacebookComment = System_Model_Setting::getValue("ArticleUseFacebookComment", "Story");
		$ArticleUseFacebookComment = $dArticleUseFacebookComment;
		
		$facebookAppID = System_Model_Setting::getValue("AppID", "Facebook");
		if (empty($facebookAppID)) {
			$ArticleUseFacebookComment = 'N';
		}
		

		if(in_array($dataInfo->CanComment,array('Y','N'))){
			$ArticleCanComment = $dataInfo->CanComment;
		}
		
		$this->view->dataInfo = $dataInfo;
		$this->view->dataDetail = $dataDetail;
		$this->view->dataCover = $dataCover;

		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;
		$this->view->ControllerName = "category";
		$this->view->ArticleCanComment = $ArticleCanComment;
		$this->view->ArticlePublicComment = $ArticlePublicComment;
		$this->view->ArticleUseFacebookComment = $ArticleUseFacebookComment;
		$this->view->facebookAppID = $facebookAppID;
		$this->view->dataFileImages = $dataFileImages;
		$this->view->dataFileDocuments = $dataFileDocuments;
		$this->view->dataFileMultiMedias = $dataFileMultiMedias;
		$this->view->ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");

		$dataParam = new stdClass();
		$dataParam->id = $id;
		$dataParam->cid = $cid;
		$dataParam->iid = $iid;
		$this->view->dataParam = $dataParam;
		
		// Send Data to Template
		$this->view->bodyClass = "story-body-detail";
		$this->view->pageTitle = $dataInfo->Title;
		$this->view->pageDescription = $dataInfo->IntroText;
		$this->view->pageKeyword = ''; 
		if(!empty($dataInfo->FileHashName)) {
			$this->view->ogImage = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $dataInfo->FileHashName), null, true);
		}
		
		// Set Global
		$menuID = $this->findMenu("/story/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		// Set ExtraBreadcrumd
		$extraBreadcrumb = array();
		$tmpBreadcrumb = array();
		if (!empty($cid)) {
			$tempCID = $cid;
			$categoryTempData = $model->getDataByLanguage($tempCID, $this->lang);
			$tmpBreadcrumb[] = array('url' => $this->view->url(array('module' => "story", "controller" => "category", "action" => "articles", "id" => $id, "cid" => $categoryTempData->ID), null, true), 'name' => $categoryTempData->Title);
			while (!empty($categoryTempData->ParentID) && ($categoryTempData->ParentID != $id)) {
				$tempCID = $categoryTempData->ParentID;
				$categoryTempData = $model->getDataByLanguage($tempCID, $this->lang);
				$tmpBreadcrumb[] = array('url' => $this->view->url(array('module' => "story", "controller" => "category", "action" => "articles", "id" => $id, "cid" => $categoryTempData->ID), null, true), 'name' => $categoryTempData->Title);
			}
			$extraBreadcrumb = array_reverse($tmpBreadcrumb);
		}
		$extraBreadcrumb[] = array('url' => $this->view->url(array('module' => "story", "controller" => "category", "action" => "detail", "id" => $id, "cid" => $categoryTempData->ID, "iid" => $iid), null, true), 'name' => $dataInfo->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}

	public function getcommentlistAction()     {
        $filterInt = new Zend_Filter_Int();
        $canView = true;
        $responce = array();
        $responce['draw'] = $_draw;
        $responce['recordsTotal'] = 0;
        $responce['recordsFiltered'] = 0;
        $responce['data'] = array();
        
        // Check Action Rights
        $canView = true; // $canView && $sUser->isAllow('intropage.info.view');
        
		// Advance Search
		$row = 5;
		$page = $this->getRequest()->getParam('pageIndex');
		$advancesearch['StoryArticleID'] = $this->getRequest()->getParam('id');
        
        $model = new Story_Model_Category();
		$data = $model->getCommentList('ActionDate', 'DESC', $row, $page, null, null, $advancesearch);

        $i=$_start;
        if ($canView && $data) {
            $responce['recordsTotal'] = $data[0]["RECORD"];
            $responce['recordsFiltered'] = $data[0]["RECORD"] - ($row * $page);
            foreach($data as $item){
                $i++;
                $d = array();
                $d['NO']=$i;
                $d['ID'] = $item["ID"];
				$d['Message'] = nl2br($item["Message"]);
				$d['ProfileImageUrl'] = $this->view->baseUrl('images/logos/user.png');
				if(!empty($item['ProfileImageHashName'])){
					$d['ProfileImageUrl'] = $this->view->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get', 'file' => $item['ProfileImageHashName']), null, true);
				}
				if($this->lang=='th'){
					$d['ActionDate'] = Emwork_Utility_DateTime::MySQLToDateThai($item["ActionDate"], 'DT_F');
				}else{
					$d['ActionDate'] = Emwork_Utility_DateTime::MySQLToDateEng($item["ActionDate"], 'DT_F');
				}
                $d['ActionFullname'] = $item["ActionFullname"];
                $responce['data'][] = $d;
            }
        }
        $this->_helper->json($responce);
	}
	
	public function savecommentAction()
    {
        // Init
        $result = array();
        $result['result'] = false;
        $result['id'] = null;
        $result['message'] = '';

		$reCaptcha = $this->getRequest()->getParam('g-recaptcha-response');
		if($reCaptcha) {
			$recaptchaSiteVerify = 'https://www.google.com/recaptcha/api/siteverify';
			$recaptchaReq = array();
			$recaptchaReq["secret"] = System_Model_Setting::getValue("ReCaptchaSecretKey", "General");
			$recaptchaReq["response"] = $reCaptcha;
			$recaptchaRes = Emwork_Utility::callAPI("POST", $recaptchaSiteVerify, $recaptchaReq);
			$recaptchaResArray = json_decode($recaptchaRes, true);
			if (!$recaptchaResArray["success"]) {
				$result['message'] = Emwork_Translate::text($this->lang, 'auth', 'validate_error_3');
				$this->_helper->json($result);
			}
		}

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Story_Model_Category();
        $params = $this->getRequest()->getParams();
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
		
		$data = $model->saveComment($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
        } else {
            $result['result'] = false;
            $result['message'] = Emwork_Translate::text($this->lang, "general", "comment_warning_send_fail");
        }
        
        $this->_helper->json($result);
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