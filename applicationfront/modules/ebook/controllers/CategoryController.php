<?php 


class Ebook_CategoryController extends Zend_Controller_Action
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
	
	public function indexAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));

		// Get Info
		$model = new Ebook_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);

		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;		
	
		$menuID = $this->findMenu("/ebook/category/index/id/" . $id);
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
		
		$categoryModel = new Ebook_Model_Category();
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
		$ebookSettingCategoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Ebook");
		$ebookSettingCategoryAmountColumn = $cCategoryAmountColumnSetting == "D" ? $ebookSettingCategoryAmountColumn : $cCategoryAmountColumn;
		$ebookSettingCategoryAmountColumn = empty($ebookSettingCategoryAmountColumn) || $ebookSettingCategoryAmountColumn <= 1 ? 1 : ($ebookSettingCategoryAmountColumn >= 4 ? 4 : $ebookSettingCategoryAmountColumn);
		$ebookSettingCategoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Ebook");
		$ebookSettingCategoryPerPage = $cCategoryPerPageSetting == "D" ? $ebookSettingCategoryPerPage : $cCategoryPerPage;
		$ebookSettingCategoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Ebook");
		$ebookSettingCategoryShowImage = $cCategoryShowImageSetting == "D" ? $ebookSettingCategoryShowImage : $cCategoryShowImage;
		$ebookSettingCategoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Ebook");
		$ebookSettingCategoryShowIntroText = $cCategoryShowIntroTextSetting == "D" ? $ebookSettingCategoryShowIntroText : $cCategoryShowIntroText;

		
		// $mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General"); 
		
		// // Setting
		// $ebookSettingCategoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Ebook");
		// $ebookSettingCategoryAmountColumn = empty($ebookSettingCategoryAmountColumn) || $ebookSettingCategoryAmountColumn <= 1 ? 1 : ($ebookSettingCategoryAmountColumn >= 4 ? 4 : $ebookSettingCategoryAmountColumn);
		// $ebookSettingCategoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Ebook");
		// $ebookSettingCategoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Ebook");
		// $ebookSettingCategoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Ebook");


		$sortType = 'Ordering';
		$sortDirection = 'asc';
				
		// Init
		$pageSize = empty($ebookSettingCategoryPerPage) ? 10 : $ebookSettingCategoryPerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$id = $this->getRequest()->getParam('id');
		$cid = $this->getRequest()->getParam('cid');

		
		$advancesearch = array();
		if($cid != null){
			$advancesearch["parent_id"] = $cid;
		}else{
			$advancesearch["parent_id"] = $id;

		}
		
		$model = new Ebook_Model_Category();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->ebookSettingCategoryAmountColumn = $ebookSettingCategoryAmountColumn;
		$this->view->ebookSettingCategoryShowImage = $ebookSettingCategoryShowImage;
		$this->view->ebookSettingCategoryShowIntroText = $ebookSettingCategoryShowIntroText;
		
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->items = $items;

		if ($ebookSettingCategoryAmountColumn == 1) {
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

	// Book List	
	public function booksAction()
	{
		
		// Log Access
		System_Model_Log::access();

		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));

		// Model 
		$model = new Ebook_Model_Category();

		
		if (empty($id) || empty($cid)) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}

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
		$menuID = $this->findMenu("/ebook/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			//echo($sumparen);
			$extraBreadcrumb[] = array('url' => '/ebook/category/books/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
			$sumparen-- ;
			if($sumparen == -1){
				$sumparen = null ;
			}
		}

		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);

	}

	public function getbooksAction() {

		$mainCategoryID = $this->mainCategoryID;
		$filterInt = new Zend_Filter_Int();

		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
				
		// Category
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		$categoryModel = new Ebook_Model_Category();
		$categoryData = $categoryModel->getSettings($cid);
		$categorySetting = json_decode($categoryData->Settings);
		$cBookAmountColumnSetting = $categorySetting->BookAmountColumnSetting;
		$cBookAmountColumn = $categorySetting->BookAmountColumn;
		$cBookPerPageSetting = $categorySetting->BookPerPageSetting;
		$cBookPerPage = $categorySetting->BookPerPage;
		$cBookShowAmountSetting = $categorySetting->BookShowAmountSetting;
		$cBookShowAmount = $categorySetting->BookShowAmount;
		$cBookShowImageSetting = $categorySetting->BookShowImageSetting;
		$cBookShowImage = $categorySetting->BookShowImage;
		$cBookShowIntroTextSetting = $categorySetting->BookShowIntroTextSetting;
		$cBookShowIntroText = $categorySetting->BookShowIntroText;
		$cBookSortSetting = $categorySetting->BookSortSetting;
		$cBookSortType = $categorySetting->BookSortType;
		$cBookSortDirection = $categorySetting->BookSortDirection;
		
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");		
		// Setting
		$ebookSettingBookAmountColumn = System_Model_Setting::getValue("EbookAmountColumn", "Ebook");
		$ebookSettingBookAmountColumn = $cBookAmountColumnSetting == "D" ? $ebookSettingBookAmountColumn : $cBookAmountColumn;
		$ebookSettingBookAmountColumn = empty($ebookSettingBookAmountColumn) || $ebookSettingBookAmountColumn <= 1 ? 1 : ($ebookSettingBookAmountColumn >= 4 ? 4 : $ebookSettingBookAmountColumn);
		$ebookSettingBookPerPage = System_Model_Setting::getValue("EbookPerPage", "Ebook");
		$ebookSettingBookPerPage = $cBookPerPageSetting == "D" ? $ebookSettingBookPerPage : $cBookPerPage;
		$ebookSettingBookShowAmount = System_Model_Setting::getValue("EbookShowAmount", "Ebook");
		$ebookSettingBookShowAmount = $cBookShowAmountSetting == "D" ? $ebookSettingBookShowAmount : $cBookShowAmount;
		$ebookSettingBookShowImage = System_Model_Setting::getValue("EbookShowImage", "Ebook");
		$ebookSettingBookShowImage = $cBookShowImageSetting == "D" ? $ebookSettingBookShowImage : $cBookShowImage;
		$ebookSettingBookShowIntroText = System_Model_Setting::getValue("EbookShowIntroText", "Ebook");
		$ebookSettingBookShowIntroText = $cBookShowIntroTextSetting == "D" ? $ebookSettingBookShowIntroText : $cBookShowIntroText;
		$ebookSettingBookSortType = System_Model_Setting::getValue("EbookSortType", "Ebook");
		$ebookSettingBookSortType = $cBookSortSetting == "D" ? $ebookSettingBookSortType : $cBookSortType;
		$ebookSettingBookSortDirection = System_Model_Setting::getValue("EbookSortDirection", "Ebook");
		$ebookSettingBookSortDirection = $cBookSortSetting == "D" ? $ebookSettingBookSortDirection : $cBookSortDirection;
		
		$sortType = 'Created';
		switch ($ebookSettingBookSortType) {
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
		$sortDirection = in_array($ebookSettingBookSortDirection, array('asc', 'desc')) ? $ebookSettingBookSortDirection : 'desc';
		
		// Init
		$pageSize = empty($ebookSettingBookPerPage) ? 10 : $ebookSettingBookPerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		
		$advancesearch = array();
		$advancesearch["parent_id"] = $cid;

		
		$model = new Ebook_Model_Category();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		// Set Setting to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		//$this->view->currentCategoryID = $currentCategoryID;
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->ebookSettingBookAmountColumn = $ebookSettingBookAmountColumn;
		$this->view->ebookSettingBookShowImage = $ebookSettingBookShowImage == 'Y';
		$this->view->ebookSettingBookShowIntroText = $ebookSettingBookShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->items = $items;
		if ($ebookSettingBookAmountColumn == 1) {
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

	public function detailAction() {
		// Log Access
		System_Model_Log::access ();
		
		$filterInt = new Zend_Filter_Int ();
		$id = $this->getRequest ()->getParam ( "id", "" );
		$id = $filterInt->filter ( $id );
		$cid = $this->getRequest ()->getParam ( "cid" );
		//$cid = $filterInt->filter ( $id );
		$iid = $this->getRequest ()->getParam ( "iid", "" );
		$iid = $filterInt->filter ( $iid );

		// Save View
		$accessFrom = "Web";
		$memberID = null;
		$userID = null;
		Ebook_Model_Book::saveView($iid, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Ebook_Model_Book();
		$dataInfo = $model->getInfo( $iid );
		$data = $model->getDataByLanguage( $iid, $this->lang);

		$this->view->SizeType = $data->SizeType;
		
		if($data->SizeType == 'C'){
			$this->view->Height = $data->Height;
			$this->view->Width = $data->Width;
		}

		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");

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
		
		$dataFiles = $model->getFiles($data->ID);
		$dataFileImages = array();
		
		if (count($dataFiles) > 0) {
			foreach ($dataFiles as $dataFile) {
				$dataFileImages[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
			}
		}
		
		// Other Setup
		$menuName = "ข่าวประชาสัมพันธ์";
		$menuUrl = $this->view->url(array("module" => "ebook", "controller" => "book", "action" => "list"), null, true);
		$this->view->menuName = $menuName;
		$this->view->menuUrl = $menuUrl;
		$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->view->currentUrl = $currentUrl;
		
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
		$this->view->currentUrlQrCodeUrl = $this->view->QrcodeCurrentUrl($currentUrl, null, $siteName);
		
		$model = new Ebook_Model_Category();
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

		$modelCat = new Ebook_Model_Category();
		$settingData = $modelCat->getSettings($data->EbookCategoryID);

		$cSettings = json_decode($settingData->Settings);
		$cEbookCanCommentSetting = $cSettings->EbookCanCommentSetting;
		$cEbookCanComment = $cSettings->EbookCanComment;
		$cEbookPublicCommentSetting = $cSettings->EbookPublicCommentSetting;
		$cEbookPublicComment = $cSettings->EbookPublicComment;

		$dEbookCanComment = System_Model_Setting::getValue("EbookCanComment", "Ebook");
		$EbookCanComment = $cEbookCanCommentSetting == "D" ? $dEbookCanComment : $cEbookCanComment;
		$dEbookPublicComment = System_Model_Setting::getValue("EbookPublicComment", "Ebook");
		$EbookPublicComment = $cEbookPublicCommentSetting == "D" ? $dEbookPublicComment : $cEbookPublicComment;

		if($data->CanComment == 'D') {
			$data->CanComment = 'Y';
		}
		if(in_array($data->CanComment,array('Y','N'))){
			$CanComment = $data->CanComment;
		}

		$this->view->CanComment = $CanComment;
		$this->view->EbookPublicComment = $EbookPublicComment;
		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;

		// Send To View
		$this->view->dataInfo = $dataInfo;
		$this->view->dataFileImages = $dataFileImages;
		$this->view->data = $data;

		$dataParam = new stdClass();
		$dataParam->id = $id;
		$dataParam->cid = $cid;
		$dataParam->iid = $iid;
		$this->view->dataParam = $dataParam;
		$this->view->ControllerName = "category";
		$this->view->ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");
		
		// Set Global
		$menuID = $this->findMenu("/ebook/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			if($sumparen <= 0){
				$extraBreadcrumb[] = array('url' => '/ebook/category/books/id/' . $id . '/cid/' . $cid, 'name' => $parentName[$sumparen] );
				$sumparen = null ;
			}else{
				$extraBreadcrumb[] = array('url' => '/ebook/category/books/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
				$sumparen-- ;
			}
		}

		$extraBreadcrumb[] = array('url' => '/ebook/category/detail/id/' . $id . '/cid/' . $cid . '/iid/' . $iID, 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
		
		// Send To Layout
		$this->view->pageDescription = $dataInfo->MetaData;
		
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