<?php 


class Gallery_CategoryController extends Zend_Controller_Action
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
		$model = new Gallery_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);

		// Send Data to view
		$this->view->controllerSearchName = 'category';
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;		

		$menuID = $this->findMenu("/gallery/category/index/id/" . $id);
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
		if(empty($cid)) {
			$cid = $id;
		}
		$categoryModel = new Gallery_Model_Category();
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
		$cCategorySortType = $categorySetting->CategorySortType;
		$cCategorySortDirection = $categorySetting->CategorySortDirection;
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General"); 

		// Setting
		$gallerySettingCategoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Gallery");
		$gallerySettingCategoryAmountColumn = $cCategoryAmountColumnSetting == "D" ? $gallerySettingCategoryAmountColumn : $cCategoryAmountColumn;
		$gallerySettingCategoryAmountColumn = empty($gallerySettingCategoryAmountColumn) || $gallerySettingCategoryAmountColumn <= 1 ? 1 : ($gallerySettingCategoryAmountColumn >= 4 ? 4 : $gallerySettingCategoryAmountColumn);
		$gallerySettingCategoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Gallery");
		$gallerySettingCategoryPerPage = $cCategoryPerPageSetting == "D" ? $gallerySettingCategoryPerPage : $cCategoryPerPage;
		$gallerySettingCategoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Gallery");
		$gallerySettingCategoryShowImage = $cCategoryShowImageSetting == "D" ? $gallerySettingCategoryShowImage : $cCategoryShowImage;
		$gallerySettingCategoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Gallery");
		$gallerySettingCategoryShowIntroText = $cCategoryShowIntroTextSetting == "D" ? $gallerySettingCategoryShowIntroText : $cCategoryShowIntroText;
		$gallerySettingCategorySortType = System_Model_Setting::getValue("CategorySortType", "Gallery");
		$gallerySettingCategorySortType = $cCategorySortSetting == "D" ? $gallerySettingCategorySortType : $cCategorySortType;
		$gallerySettingCategorySortDirection = System_Model_Setting::getValue("CategorySortDirection", "Gallery");
		$gallerySettingCategorySortDirection = $cCategorySortSetting == "D" ? $gallerySettingCategorySortDirection : $cCategorySortDirection;
		
		$sortType = 'Ordering';
		switch ($gallerySettingCategorySortType) {
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
		$sortDirection = in_array($gallerySettingCategorySortDirection, array('asc', 'desc')) ? $gallerySettingCategorySortDirection : 'desc';
				
		// Init
		$pageSize = empty($gallerySettingCategoryPerPage) ? 10 : $gallerySettingCategoryPerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);

		// Search
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->_request->getParam('id'));
		$cid = $filterInt->filter($this->_request->getParam('cid'));
		
		$advancesearch = array();
		if(!empty($cid)){
			$advancesearch["parent_id"] = $cid;
		}else{
			$advancesearch["parent_id"] = $id;
		}
		
		$model = new Gallery_Model_Category();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->gallerySettingCategoryAmountColumn = $gallerySettingCategoryAmountColumn;
		$this->view->gallerySettingCategoryShowImage = $gallerySettingCategoryShowImage == 'Y';
		$this->view->gallerySettingCategoryShowIntroText = $gallerySettingCategoryShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->items = $items;
		if ($gallerySettingCategoryAmountColumn == 1) {
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
		
		$model = new Gallery_Model_Category();
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
		$this->view->controllerSearchName = 'category';
		
		// Set Global
		$menuID = $this->findMenu("/gallery/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;
		
		while($sumparen > -1){
			//echo($sumparen);
			$extraBreadcrumb[] = array('url' => '/gallery/category/articles/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
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
		$categoryModel = new Gallery_Model_Category();
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
		$gallerySettingArticleAmountColumn = System_Model_Setting::getValue("ArticleAmountColumn", "Gallery");
		$gallerySettingArticleAmountColumn = $cArticleAmountColumnSetting == "D" ? $gallerySettingArticleAmountColumn : $cArticleAmountColumn;
		$gallerySettingArticleAmountColumn = empty($gallerySettingArticleAmountColumn) || $gallerySettingArticleAmountColumn <= 1 ? 1 : ($gallerySettingArticleAmountColumn >= 4 ? 4 : $gallerySettingArticleAmountColumn);
		$gallerySettingArticlePerPage = System_Model_Setting::getValue("ArticlePerPage", "Gallery");
		$gallerySettingArticlePerPage = $cArticlePerPageSetting == "D" ? $gallerySettingArticlePerPage : $cArticlePerPage;
		$gallerySettingArticleShowAmount = System_Model_Setting::getValue("ArticleShowAmount", "Gallery");
		$gallerySettingArticleShowAmount = $cArticleShowAmountSetting == "D" ? $gallerySettingArticleShowAmount : $cArticleShowAmount;
		$gallerySettingArticleShowImage = System_Model_Setting::getValue("ArticleShowImage", "Gallery");
		$gallerySettingArticleShowImage = $cArticleShowImageSetting == "D" ? $gallerySettingArticleShowImage : $cArticleShowImage;
		$gallerySettingArticleShowIntroText = System_Model_Setting::getValue("ArticleShowIntroText", "Gallery");
		$gallerySettingArticleShowIntroText = $cArticleShowIntroTextSetting == "D" ? $gallerySettingArticleShowIntroText : $cArticleShowIntroText;
		$gallerySettingArticleSortType = System_Model_Setting::getValue("ArticleSortType", "Gallery");
		$gallerySettingArticleSortType = $cArticleSortSetting == "D" ? $gallerySettingArticleSortType : $cArticleSortType;
		$gallerySettingArticleSortDirection = System_Model_Setting::getValue("ArticleSortDirection", "Gallery");
		$gallerySettingArticleSortDirection = $cArticleSortSetting == "D" ? $gallerySettingArticleSortDirection : $cArticleSortDirection;

		$sortType = 'Created';
		switch ($gallerySettingArticleSortType) {
			case 'name' :
			case 'title' :
				$sortType = 'Title';
				break;
			case 'created' :
				$sortType = 'Created';
				break;
			case 'publishdate' :
				$sortType = 'PublishDate';
				break;
			case 'viewamount' :
				$sortType = 'ViewAmount';
				break;
			case 'ordering' :
				$sortType = 'Ordering';
				break;
		}
		$sortDirection = in_array($gallerySettingArticleSortDirection, array('asc', 'desc')) ? $gallerySettingArticleSortDirection : 'desc';

		// Init
		$pageSize = empty($gallerySettingArticlePerPage) ? 10 : $gallerySettingArticlePerPage;
		
		// Search
		$pageIndex = $this->getRequest()->getParam('pageIndex',1);
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		
		$advancesearch = array();
		$advancesearch['isCat'] = $id;
		if($cid){
			$advancesearch['isCat'] = $cid;
		}

		$model = new Gallery_Model_Article();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		// Set Setting to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		//$this->view->currentCategoryID = $currentCategoryID;
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->gallerySettingArticleAmountColumn = $gallerySettingArticleAmountColumn;
		$this->view->gallerySettingArticleShowImage = $gallerySettingArticleShowImage == 'Y';
		$this->view->gallerySettingArticleShowIntroText = $gallerySettingArticleShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->items = $items;
		if ($gallerySettingArticleAmountColumn == 1) {
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
		Gallery_Model_Article::saveView($iID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Gallery_Model_Article();
		$dataInfo = $model->getInfo( $iID );
		$data = $model->getDataByLanguage( $iID, $this->lang);
		$datatag= $model->getAllKeywordByArticle($iID);
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
				
		$categoryModel = new Gallery_Model_Category();
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

		// $dataFiles = $model->getFiles($data->ID,$data->ImagePerPage,$data->TemplateType);
		// $dataFileImages = array();
		// $dataFileDocuments = array();
		// $dataFileMultiMedias = array();
		// if (count($dataFiles) > 0) {
		// 	foreach ($dataFiles as $dataFile) {
		// 		if ($dataFile["ArticleFileType"] == "image") {
		// 			$dataFileImages[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
		// 		} elseif ($dataFile["ArticleFileType"] == "document") {
		// 			$dataFileDocuments[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
		// 		} elseif ($dataFile["ArticleFileType"] == "multimedia") {
		// 			$dataFileMultiMedias[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
		// 		}
		// 	}
		// }

		$dataFiles = $model->getFile($data->ID,$data->ImagePerPage,$data->TemplateType);
		$dataFileImages = array();
		if (count($dataFiles) > 0) {
			foreach ($dataFiles as $dataFile) {
					$dataFileImages[] = array('file_id' => $dataFile['FileInfoID'], 'file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName'],'Description'=>$dataFile['Description']);
			}
		}

		// Other Setup
		$menuName = $dataDetailCategory->Title;
		$menuUrl = $this->view->url(array("module" => "gallery", "controller" => "article", "action" => "list"), null, true);
		$this->view->menuName = $menuName;
		$this->view->menuUrl = $menuUrl;
		$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->view->currentUrl = $currentUrl;
		
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
		$this->view->currentUrlQrCodeUrl = $this->view->QrcodeCurrentUrl($currentUrl, null, $siteName);

		$model = new Gallery_Model_Category();
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

		$cSettings = json_decode($data->Settings);
		$cArticleCanCommentSetting = $cSettings->ArticleCanCommentSetting;
		$cArticleCanComment = $cSettings->ArticleCanComment;
		$cArticlePublicCommentSetting = $cSettings->ArticlePublicCommentSetting;
		$cArticlePublicComment = $cSettings->ArticlePublicComment;

		$dArticleCanComment = System_Model_Setting::getValue("ArticleCanComment", "Gallery");
		$ArticleCanComment = $cArticleCanCommentSetting == "D" ? $dArticleCanComment : $cArticleCanComment;
		$dArticlePublicComment = System_Model_Setting::getValue("ArticlePublicComment", "Gallery");
		$ArticlePublicComment = $cArticlePublicCommentSetting == "D" ? $dArticlePublicComment : $cArticlePublicComment;

		if(in_array($data->CanComment,array('Y','N'))){
			$ArticleCanComment = $data->CanComment;
		}

		// Send To View
		$this->view->controller = 'category';
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->iid = $iID;

		$this->view->data = $data;
		$this->view->datatag = $datatag;
		$this->view->dataInfo = $dataInfo;
		$this->view->dataIntroImages = $dataIntroImages;
		$this->view->dataFileImages = $dataFileImages;
		$this->view->dataFilesNew =$dataFilesNew ;
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
		$menuID = $this->findMenu("/gallery/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			if($sumparen <= 0){
				$extraBreadcrumb[] = array('url' => '/gallery/category/articles/id/' . $id . '/cid/' . $cid, 'name' => $parentName[$sumparen]);
				$sumparen = null ;
			}else{
				$extraBreadcrumb[] = array('url' => '/gallery/category/articles/id/' . $id . '/cid/' . $parent[$sumparen-1],'name' => $parentName[$sumparen]);
				$sumparen-- ;
			}
		}

		$extraBreadcrumb[] = array('url' => '/gallery/category/detail/id/' . $id . '/cid/' . $cid . '/iid/' . $iID, 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}
	
	public function viewimageAction()
	{
		// Log Access
		System_Model_Log::access ();
		
		$filterInt = new Zend_Filter_Int ();
		$id = $this->getRequest()->getParam("id","");
		$cid = $this->getRequest ()->getParam ( "cid","" );
		$iID = $this->getRequest()->getParam("iid","");
		$img = $this->getRequest()->getParam("img", "");
		$id = $filterInt->filter($id);
		$cid = $filterInt->filter($cid);
		$iID = $filterInt->filter($iID);
		$img = $filterInt->filter($img);

		$model = new Gallery_Model_Article();
		$dataInfo = $model->getInfo($iID) ;

		// Check Data: Can Access
		if (!$dataInfo || $dataInfo->IsEnable != 'Y' || $dataInfo->IsDeleted != 'N' || $dataInfo->IsTrash != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		$data = $model->getDataByLanguage($iID, $this->lang);

		if (empty ( $data->Title)) {
			// Show "Not found data in this LANG, อยู่ระหว่างจัดทำข้อมูล"
			$this->renderScript('index/_alertmaintenance.phtml');
			return;
		}

		$cSettings = json_decode($data->Settings);
		$cArticleCanCommentSetting = $cSettings->ArticleCanCommentSetting;
		$cArticleCanComment = $cSettings->ArticleCanComment;
		$dArticleCanComment = System_Model_Setting::getValue("ArticleCanComment", "Gallery");
		$ArticleCanComment = $cArticleCanCommentSetting == "D" ? $dArticleCanComment : $cArticleCanComment;
		if(in_array($dataInfo->CanComment,array('Y','N'))){
			$ArticleCanComment = $dataInfo->CanComment;
		}
		$this->view->ArticleCanComment = $ArticleCanComment;

		$modelArticle = new Gallery_Model_Article();
		$dataFile = $modelArticle->getFileCoverDetail($dataInfo->ID, $img, $this->lang);

		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;

		$this->view->dataInfo = $dataInfo;
		$this->view->data = $data;
		$this->view->dataFile = $dataFile;
		$this->view->imgUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $dataFile->FileHashName), "default", true);
		$this->view->ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");

		$model = new Gallery_Model_Category();
		$categoryData = $model->getDataByLanguage($cid, $this->lang);

		$parent[0] =  $categoryData->ParentID;
		$parentName[0] = $categoryData->Title;
		$checkParen = 1 ;

		while($checkParen != null) {
			$dataParen = $model->getDataByLanguage($parent[$checkParen-1], $this->lang);
			$parent[$checkParen] = $dataParen->ParentID;
			$parentName[$checkParen] = $dataParen->Title;
			if($parent[$checkParen] != null){
				$checkParen++ ;
			}else{
				$checkParen = null ;
			}
		}

		// Set Global
		$menuID = $this->findMenu("/gallery/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			if($sumparen <= 0){
				$extraBreadcrumb[] = array('url' => '/gallery/category/articles/id/' . $id . '/cid/' . $cid, 'name' => $parentName[$sumparen]);
				$sumparen = null ;
			}else{
				$extraBreadcrumb[] = array('url' => '/gallery/category/articles/id/' . $id . '/cid/' . $parent[$sumparen-1],'name' => $parentName[$sumparen]);
				$sumparen-- ;
			}
		}

		$extraBreadcrumb[] = array('url' => '/gallery/category/detail/id/' . $id . '/cid/' . $cid . '/iid/' . $iID, 'name' => $data->Title);
		$extraBreadcrumb[] = array('url' => '/gallery/category/detail/id/' . $id . '/iid/' . $iID . '/img/' . $img, 'name' => $dataFile->Name);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
		$this->renderScript('article/viewimage.phtml');
	}

	public function searchAction()
	{	
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		
		// Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));
		$cid = $filterInt->filter($this->_request->getParam('cid'));
		$search = $this->getRequest()->getParam("search");

		$model = new Gallery_Model_Category();
		$categoryData = $model->getDataByLanguage($cid, $this->lang);

		$parent[0] =  $categoryData->ParentID;
		$parentName[0] = $categoryData->Title;

		
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

		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->controllerSearchName = 'category';
		$this->view->search = $search;
		$this->view->pageTitle = Emwork_Translate::text($this->lang, 'gallery', 'search_list');

		$menuID = $this->findMenu("/gallery/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);

		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			if($sumparen <= 0){
				$extraBreadcrumb[] = array('url' => '/gallery/category/articles/id/' . $id . '/cid/' . $cid, 'name' => $parentName[$sumparen]);
				$sumparen = null ;
			}else{
				$extraBreadcrumb[] = array('url' => '/gallery/category/articles/id/' . $id . '/cid/' . $parent[$sumparen-1],'name' => $parentName[$sumparen]);
				$sumparen-- ;
			}
		}

		$extraBreadcrumb[] = array('url' => '/gallery/category/detail/id/' . $id . '/cid/' . $cid . '/iid/' . $iID, 'name' => $data->Title);
		$extraBreadcrumb[] = array('url' => '/gallery/category/index/id' . $id, 'name' => Emwork_Translate::text($this->lang, 'gallery', 'search_list'));
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}

	public function getarticlessearchAction() {

		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';

		// Init
		$pageSize = 10;
		
		// Search
		$pageIndex = $this->getRequest()->getParam('pageIndex',1);
		$textstr = $this->getRequest()->getParam('search');
		$params = $this->getRequest()->getParams();

		$advancesearch = array();
		$advancesearch['searchText'] = $textstr;

		$model = new Gallery_Model_Search();
		$items = $model->getList("Title", "ASC", $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		$id = $filterInt->filter($this->_request->getParam('id'));
		$this->view->id = $id;
		$this->view->cid = '-1';

		$this->view->search = $textstr;
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->gallerySettingArticleShowImage = 'Y';
		$this->view->gallerySettingArticleShowIntroText = 'Y';
		
		// Send Data to view
		$this->view->items = $items;
			$responce["html"] = $this->view->render('category/_arows.phtml');

		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
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