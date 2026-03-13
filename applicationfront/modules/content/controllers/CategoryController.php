<?php 


class Content_CategoryController extends Zend_Controller_Action
{
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
		$model = new Content_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
				
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;	
		$this->view->dataDetail = $dataDetail;	
		$this->view->cssClass = $dataDetail->CssClass;	
		
		$menuID = $this->findMenu("/content/category/index/id/" . $id);
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
		$contentSettingCategoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Content");
		$contentSettingCategoryAmountColumn = $cCategoryAmountColumnSetting == "D" ? $contentSettingCategoryAmountColumn : $cCategoryAmountColumn;
		$contentSettingCategoryAmountColumn = empty($contentSettingCategoryAmountColumn) || $contentSettingCategoryAmountColumn <= 1 ? 1 : ($contentSettingCategoryAmountColumn >= 4 ? 4 : $contentSettingCategoryAmountColumn);
		$contentSettingCategoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Content");
		$contentSettingCategoryPerPage = $cCategoryPerPageSetting == "D" ? $contentSettingCategoryPerPage : $cCategoryPerPage;
		$contentSettingCategoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Content");
		$contentSettingCategoryShowImage = $cCategoryShowImageSetting == "D" ? $contentSettingCategoryShowImage : $cCategoryShowImage;
		$contentSettingCategoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Content");
		$contentSettingCategoryShowIntroText = $cCategoryShowIntroTextSetting == "D" ? $calendarSettingCategoryShowIntroText : $cCategoryShowIntroText;
		
		$sortType = 'Ordering';
		$sortDirection = 'asc';
				
		// Init
		$pageSize = empty($contentSettingCategoryPerPage) ? 10 : $contentSettingCategoryPerPage;
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
		
		$model = new Content_Model_Category();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->contentSettingCategoryAmountColumn = $contentSettingCategoryAmountColumn;
		$this->view->contentSettingCategoryShowImage = $contentSettingCategoryShowImage == 'Y';
		$this->view->contentSettingCategoryShowIntroText = $contentSettingCategoryShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->items = $items;
		if ($contentSettingCategoryAmountColumn == 1) {
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
		$model = new Content_Model_Category();
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
		
		// Set Global
		$menuID = $this->findMenu("/content/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			//echo($sumparen);
			$extraBreadcrumb[] = array('url' => '/content/category/articles/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
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

		$categoryModel = new Content_Model_Category();
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
		$contentSettingArticleAmountColumn = System_Model_Setting::getValue("ArticleAmountColumn", "Content");
		$contentSettingArticleAmountColumn = $cArticleAmountColumnSetting == "D" ? $contentSettingArticleAmountColumn : $cArticleAmountColumn;
		$contentSettingArticleAmountColumn = empty($contentSettingArticleAmountColumn) || $contentSettingArticleAmountColumn <= 1 ? 1 : ($contentSettingArticleAmountColumn >= 4 ? 4 : $contentSettingArticleAmountColumn);
		$contentSettingArticlePerPage = System_Model_Setting::getValue("ArticlePerPage", "Content");
		$contentSettingArticlePerPage = $cArticlePerPageSetting == "D" ? $contentSettingArticlePerPage : $cArticlePerPage;
		$contentSettingArticleShowAmount = System_Model_Setting::getValue("ArticleShowAmount", "Content");
		$contentSettingArticleShowAmount = $cArticleShowAmountSetting == "D" ? $contentSettingArticleShowAmount : $cArticleShowAmount;
		$contentSettingArticleShowImage = System_Model_Setting::getValue("ArticleShowImage", "Content");
		$contentSettingArticleShowImage = $cArticleShowImageSetting == "D" ? $contentSettingArticleShowImage : $cArticleShowImage;
		$contentSettingArticleShowIntroText = System_Model_Setting::getValue("ArticleShowIntroText", "Content");
		$contentSettingArticleShowIntroText = $cArticleShowIntroTextSetting == "D" ? $contentSettingArticleShowIntroText : $cArticleShowIntroText;
		$contentSettingArticleSortType = System_Model_Setting::getValue("ArticleSortType", "Content");
		$contentSettingArticleSortType = $cArticleSortSetting == "D" ? $contentSettingArticleSortType : $cArticleSortType;
		$contentSettingArticleSortDirection = System_Model_Setting::getValue("ArticleSortDirection", "Content");
		$contentSettingArticleSortDirection = $cArticleSortSetting == "D" ? $contentSettingArticleSortDirection : $cArticleSortDirection;

		// Setting News and Update
		$setting->contentSettingNewlimitdata = System_Model_Setting::getValue("Newlimitdata", "Content");
		$setting->contentSettingNewlimitdata=empty($setting->contentSettingNewlimitdata) ? 10:$setting->contentSettingNewlimitdata;
		$setting->contentSettingNewdistancedate = System_Model_Setting::getValue("Newdistancedate", "Content");
		$setting->contentSettingNewdistancedate=empty($setting->contentSettingNewdistancedate) ? 10:$setting->contentSettingNewdistancedate;
		$setting->contentSettingUpdatelimitdata = System_Model_Setting::getValue("Updatelimitdata", "Content");
		$setting->contentSettingUpdatelimitdata=empty($setting->contentSettingUpdatelimitdata) ? 10:$setting->contentSettingUpdatelimitdata;
		$setting->contentSettingUpdatedistancedate = System_Model_Setting::getValue("Updatedistancedate", "Content");
		$setting->contentSettingUpdatedistancedate=empty($setting->contentSettingUpdatedistancedate) ? 10:$setting->contentSettingUpdatedistancedate;

		$sortType = 'Created';
		switch ($contentSettingArticleSortType) {
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
		$sortDirection = in_array($contentSettingArticleSortDirection, array('asc', 'desc')) ? $contentSettingArticleSortDirection : 'desc';
		
		// Init
		$pageSize = empty($contentSettingArticlePerPage) ? 10 : $contentSettingArticlePerPage;


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

		$model = new Content_Model_Article();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);		

		$datanewforicon = $model->newIcon($setting->contentSettingNewlimitdata , $this->lang);
		$dataupdateforicon = $model->updateIcon($setting->contentSettingUpdatelimitdata , $this->lang);

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
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->contentSettingArticleAmountColumn = $contentSettingArticleAmountColumn;
		$this->view->contentSettingArticleShowImage = $contentSettingArticleShowImage == 'Y';
		$this->view->contentSettingArticleShowIntroText = $contentSettingArticleShowIntroText == 'Y';

		// Send Data to view
		$this->view->items = $items;
		if ($contentSettingArticleAmountColumn == 1) {
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
		Content_Model_Article::saveView($iID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Content_Model_Article();
		$dataInfo = $model->getInfo( $iID );
		$data = $model->getDataByLanguage( $iID, $this->lang);
		$datatag= $model->getAllKeywordByArticle($iID);
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
				
		$categoryModel = new Content_Model_Category();
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
					$dataFileImages[] = array('ContentArticleFileID'=>$dataFile['ContentArticleFileID'], 'DownloadAmount'=>$dataFile['DownloadAmount'], 'file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				} elseif ($dataFile["ArticleFileType"] == "document") {
					$dataFileDocuments[] = array('ContentArticleFileID'=>$dataFile['ContentArticleFileID'], 'DownloadAmount'=>$dataFile['DownloadAmount'], 'file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName'],'Extension'=>$dataFile['FileExtension']);
				} elseif ($dataFile["ArticleFileType"] == "multimedia") {
					$dataFileMultiMedias[] = array('ContentArticleFileID'=>$dataFile['ContentArticleFileID'], 'DownloadAmount'=>$dataFile['DownloadAmount'], 'file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				}
			}
		}

		// Other Setup
		$menuName = $dataDetailCategory->Title;
		$menuUrl = $this->view->url(array("module" => "content", "controller" => "article", "action" => "list"), null, true);
		$this->view->menuName = $menuName;
		$this->view->menuUrl = $menuUrl;
		$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->view->currentUrl = $currentUrl;
		
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
		$this->view->currentUrlQrCodeUrl = $this->view->QrcodeCurrentUrl($currentUrl, null, $siteName);
		
		$Model = new Content_Model_Category();
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

		$dArticleCanComment = System_Model_Setting::getValue("ArticleCanComment", "Content");
		$ArticleCanComment = $cArticleCanCommentSetting == "D" ? $dArticleCanComment : $cArticleCanComment;
		$dArticlePublicComment = System_Model_Setting::getValue("ArticlePublicComment", "Content");
		$ArticlePublicComment = $cArticlePublicCommentSetting == "D" ? $dArticlePublicComment : $cArticlePublicComment;

		if(in_array($data->CanComment,array('Y','N'))){
			$ArticleCanComment = $data->CanComment;
		}

		// Send To View
		$this->view->cssClass = $dataDetailCategory->CssClass;
		$this->view->data = $data;
		$this->view->datatag = $datatag;
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
		$menuID = $this->findMenu("/content/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			//echo($sumparen);
			if($sumparen <= 0){
				$extraBreadcrumb[] = array('url' => '/content/category/articles/id/' . $id . '/cid/' . $cid, 'name' => $parentName[$sumparen] );
				$sumparen = null ;
			}else{
				$extraBreadcrumb[] = array('url' => '/content/category/articles/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
				$sumparen-- ;
			}
		}
		$extraBreadcrumb[] = array('url' => '/content/category/detail/id/' . $id . '/cid/' . $cid . '/iid/' . $iID, 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
		
		// Send To Layout
		$this->view->pageTitle = $data->Title;
		$this->view->pageDescription = $data->IntroText;
		$this->view->pageKeyword = $dataInfo->MetaData;
		if(!empty($data->IntroImageID)) {
			$fileModel = new System_Model_Fileinfo();
			$fileObj = $fileModel->item($data->IntroImageID);
			if (!empty($fileObj->HashName)) {
				$this->view->ogImage = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $fileObj->HashName), null, true);
			}
		}
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