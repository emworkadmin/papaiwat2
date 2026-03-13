<?php 


class Content_ArticleController extends Zend_Controller_Action
{
	protected $auth;
	protected $lang;
	protected $mainCategoryID = '1';

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
		
		$model = new Content_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;
		$this->view->dataDetail = $dataDetail;
		
		$menuID = $this->findMenu("/content/article/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}
	
	public function getlistAction() {
		
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
		
		// Category
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$categoryModel = new Content_Model_Category();
		$categoryData = $categoryModel->getSettings($id);
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
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$advancesearch = array();
		$advancesearch["isCat"] = $id;
		
		$model = new Content_Model_Article();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->contentSettingArticleAmountColumn = $contentSettingArticleAmountColumn;
		$this->view->contentSettingArticleShowImage = $contentSettingArticleShowImage == 'Y';
		$this->view->contentSettingArticleShowIntroText = $contentSettingArticleShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->items = $items;
		if ($contentSettingArticleAmountColumn == 1) {
			$responce["html"] = $this->view->render('index/_rows.phtml');
		} else {
			$responce["html"] = $this->view->render('index/_cards.phtml');
		}
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
	}
	
	// detail
	public function detailAction() {

		// Log Access
		System_Model_Log::access ();

		$filterInt = new Zend_Filter_Int ();
		$id = $this->getRequest ()->getParam ( "id", "" );
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
		$this->view->data = $data;
		$this->view->datatag=$datatag;
		$this->view->dataInfo = $dataInfo;
		$this->view->dataIntroImages = $dataIntroImages;
		$this->view->dataFileImages = $dataFileImages;
		$this->view->dataFileDocuments = $dataFileDocuments;
		$this->view->dataFileMultiMedias = $dataFileMultiMedias;

		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;
		$this->view->ControllerName = "article";
		$this->view->ArticleCanComment = $ArticleCanComment;
		$this->view->ArticlePublicComment = $ArticlePublicComment;
		$this->view->ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");
		
		// Set Global
		$menuID = $this->findMenu("/content/article/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();
		$extraBreadcrumb[] = array('url' => '/content/article/detail/id/' . $id . '/iid/' . $iID, 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
		
		// Send To Layout
		$this->view->pageDescription = $dataInfo->MetaData;
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
		$advancesearch['ContentArticleID'] = $this->getRequest()->getParam('id');
        
        $model = new Content_Model_Article();
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
        
        $model = new Content_Model_Article();
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

	public function savedownloadAction()
    {
        // Init
        $result = array();
        $result['result'] = false;
        $result['id'] = null;
        $result['message'] = '';
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Content_Model_Article();
        $params = $this->getRequest()->getParams();
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
		
		$data = $model->saveDownload($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['download_amount'] = $data->download_amount;
        } else {
            $result['result'] = false;
            $result['message'] = Emwork_Translate::text($this->lang, 'content', 'validate_error');
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