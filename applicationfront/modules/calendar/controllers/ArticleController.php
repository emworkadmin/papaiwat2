<?php 


class Calendar_ArticleController extends Zend_Controller_Action
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
		$type = $this->_request->getParam('type');
		$filter = $this->_request->getParam('filter');

		if($type=='date'){
			$checkFormatDate = $this->checkFormatDate($filter);
			if(!$checkFormatDate){
				$this->_helper->redirector('index', 'article', 'calendar');
				//$this->renderScript('index/_alertnotfound.phtml');
				//return;
			}
		}
		
		$model = new Calendar_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;
		$this->view->type = ($type == 'date' ? 'date' : '');
		$this->view->filter = ($type == 'date' ? $filter : '');
		
		$menuID = $this->findMenu("/calendar/article/index/id/" . $id);
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
		$categoryModel = new Calendar_Model_Category();
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
		$calendarSettingArticleAmountColumn = System_Model_Setting::getValue("ArticleAmountColumn", "Calendar");
		$calendarSettingArticleAmountColumn = $cArticleAmountColumnSetting == "D" ? $calendarSettingArticleAmountColumn : $cArticleAmountColumn;
		$calendarSettingArticleAmountColumn = empty($calendarSettingArticleAmountColumn) ? 0 : ($calendarSettingArticleAmountColumn >= 4 ? 4 : $calendarSettingArticleAmountColumn);
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
		$calendarSettingArticleSortDirection = $cArticleSortSetting == "D" ? $calendarSettingArticleSortDirection : $cArticleSortDirection;
		
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
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$type = $this->getRequest()->getParam('type');
		$filter = $this->getRequest()->getParam('filter');
		
		$advancesearch = array();
		$advancesearch["isCat"] = $id;
		
		$model = new Calendar_Model_Article();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		$model = new Calendar_Model_Category();
		$calendarCategoryOption = $model->getOption($this->lang);
		
		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->calendarSettingArticleAmountColumn = $calendarSettingArticleAmountColumn;
		$this->view->calendarSettingArticleShowImage = $calendarSettingArticleShowImage == 'Y';
		$this->view->calendarSettingArticleShowIntroText = $calendarSettingArticleShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->items = $items;
		$this->view->type = $type;
		$this->view->filter = $filter;
		// '-- Please Select --'
		$this->view->calendarCategoryOption = Emwork_Utility::addEmptyOption($calendarCategoryOption, Emwork_Translate::text($this->lang, 'general', 'emptyoption'));

		if (empty($calendarSettingArticleAmountColumn)) {
			$responce["html"] = $this->view->render('index/_calendar.phtml');
		} else if ($calendarSettingArticleAmountColumn == 1) {
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
		Calendar_Model_Article::saveView($iID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Calendar_Model_Article();
		$dataInfo = $model->getInfo( $iID );
		$data = $model->getDataByLanguage( $iID, $this->lang);
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
				
		$categoryModel = new Calendar_Model_Category();
		$dataDetailCategory = $categoryModel->getDataByLanguage($id, $this->lang);
		$data->CategoryName = $dataDetailCategory->Title;
		
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
		$this->view->ControllerName = "article";
		$this->view->ArticleCanComment = $ArticleCanComment;
		$this->view->ArticlePublicComment = $ArticlePublicComment;
		$this->view->ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");

		// Set Global
		$menuID = $this->findMenu("/calendar/article/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();
		$extraBreadcrumb[] = array('url' => '/calendar/article/detail/id/' . $id . '/iid/' . $iID, 'name' => $data->Title);
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
		$advancesearch['CalendarArticleID'] = $this->getRequest()->getParam('id');
        
        $model = new Calendar_Model_Article();
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
        
        $model = new Calendar_Model_Article();
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

	public function getcalendarAction() {
		// Zend Filter
		$filterInt = new Zend_Filter_Int ();

		// Init
		$sortType = 'StartDate';
		$sortDirection = 'ASC';
		$pageSize = 1000;
		$pageIndex = 1;
		
		// Search
		$_id = $filterInt->filter($this->getRequest()->getParam('id'));
		$_start = $this->getRequest()->getParam('start');
		$_end = $this->getRequest()->getParam('end');

		$advancesearch = array();
		$advancesearch["StartDate"] = str_replace('T00:00:00',' 00:00:00',$_start);
		$advancesearch["EndDate"] = str_replace('T00:00:00',' 00:00:00',$_end);
		$advancesearch["isCat"] = $_id;

		// Option For Widget
		$_categoryIds = $this->getRequest()->getParam('categoryIds');
		$_sidx = $this->getRequest()->getParam('sidx');
		$_sord = $this->getRequest()->getParam('sord');
		$_limit = $this->getRequest()->getParam('limit');
		$_filter = $this->getRequest()->getParam('filter');

		$advancesearch["CategoryIds"] = $_categoryIds;
		$advancesearch["FilterDate"] = $_filter;
		if($_sidx){ $sortType = $_sidx; }
		if($_sord){ $sortDirection = $_sord; }
		if($_limit){ $pageSize = $_limit; }

		if($_id){
			$categoryModel = new Calendar_Model_Category();
			$dataDetailCategory = $categoryModel->getDataByLanguage($_id, $this->lang);
		}
		
		$model = new Calendar_Model_Article();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		if (count($items) > 0) {
			foreach ($items as $event) {
				$i = array();
				$i["id"] = $event['ID'];
				$i["title"] = $event['Title'];
				$i["url"] = $this->view->url(array("module" => "calendar", "controller" => "article", "action" => "detail", "id" => $event['CalendarCategoryID'], "iid" => $event['ID']), null, true);
				$i["start"] = str_replace(' ','T',$event['StartDate']->format('Y-m-d H:i:s'));
				$i["end"] = str_replace(' ','T',$event['EndDate']->format('Y-m-d H:i:s'));
				$i["date-d"] = $event['StartDate']->format('d');
				$i["date-m"] = $event['StartDate']->format('m');
				$i["date-y"] = $event['StartDate']->format('Y') + ($this->lang == 'th' ? 543 : 0);
				$i["place"] = $event['Place'];
				$i["image-url"] = '/images/other/no-image.jpg';
				if($event['IntroImageHashName']){
					$i["image-url"] = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $event['IntroImageHashName']), null, true);
				}
				$i["starttime"] = $event['EndDate']->format('H:i');
				if($this->lang == 'th') {
					$i["startdate"] = Emwork_Utility_DateTime::MSSqlToDateThai($event['StartDate'],'DT_Nd') . ' - ' . Emwork_Utility_DateTime::MSSqlToDateThai($event['EndDate'],'DT_Nd') ;
					$i["startdate"] = str_replace('วันที่ ','',$i["startdate"]);
					$i["publish-date"] = Emwork_Utility_DateTime::MSSqlToDateThai($event['PublishDate'],'DT_Nd');
					$i["publish-date"] = str_replace('วันที่ ','',$i["publish-date"]);
				} else {
					$i["startdate"] = Emwork_Utility_DateTime::MSSqlToDateEng($event['StartDate'],'DT_Nd') . ' - ' . Emwork_Utility_DateTime::MSSqlToDateEng($event['EndDate'],'DT_Nd') ;
					$i["startdate"] = str_replace('วันที่ ','',$i["startdate"]);
					$i["publish-date"] = Emwork_Utility_DateTime::MSSqlToDateEng($event['PublishDate'],'DT_Nd');
					$i["publish-date"] = str_replace('วันที่ ','',$i["publish-date"]);
				}
				$i["publish-by"] = $event['AuthorName'];
				$i["introtext"] = nl2br($event['IntroText']);

				$i["color"] = empty($_id) ? $event['Color'] : $dataDetailCategory->Color;
				$responce[] = $i;
			}
		}

		$this->_helper->json($responce);
	}

	public function searchAction()
	{
		// Log Access
		System_Model_Log::access();

		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
		// Get Param
		$params = $this->_request->getParams();
		$text = $params['text'];
		$cid = $filterInt->filter($params['cid']);

		// Get Data
		$data = $this->getCalendarSearch($text,$cid);

		// Get Option
		$model = new Calendar_Model_Category();
		$calendarCategoryOption = $model->getOption($this->lang);
		
		// Send Data to view
		$this->view->data = $data;
		$this->view->calendarSearchText = $text;
		$this->view->calendarSearchCategory = $cid;
		$this->view->calendarCategoryOption = Emwork_Utility::addEmptyOption($calendarCategoryOption, Emwork_Translate::text($this->lang, 'general', 'emptyoption'));
		
		$this->view->pageTitle = Emwork_Translate::text($this->lang, 'calendar', 'search_title');
		$this->view->calendarSettingArticleShowImage = $calendarSettingArticleShowImage == 'Y';
		$this->view->calendarSettingArticleShowIntroText = $calendarSettingArticleShowIntroText == 'Y';
		
		// $menuID = $this->findMenu("/calendar/article/index/id/" . $id);
		// Zend_Registry::set("MenuID", $menuID);
	}

	private function getCalendarSearch($text,$cid) {
		// // Zend Filter
		// $filterInt = new Zend_Filter_Int ();

		// Init
		$sortType = 'StartDate';
		$sortDirection = 'ASC';
		$pageSize = 1000;
		$pageIndex = 1;
		
		// Search
		// $_id = $filterInt->filter($this->getRequest()->getParam('id'));
		// $_start = $this->getRequest()->getParam('start');
		// $_end = $this->getRequest()->getParam('end');

		$advancesearch = array();
		$advancesearch["searchText"] = $text;
		$advancesearch["isCat"] = $cid;

		// $advancesearch["StartDate"] = str_replace('T00:00:00',' 00:00:00',$_start);
		// $advancesearch["EndDate"] = str_replace('T00:00:00',' 00:00:00',$_end);
		// $advancesearch["isCat"] = $cid;

		// Option For Widget
		// $_categoryIds = $this->getRequest()->getParam('categoryIds');
		// $_sidx = $this->getRequest()->getParam('sidx');
		// $_sord = $this->getRequest()->getParam('sord');
		// $_limit = $this->getRequest()->getParam('limit');
		// $_filter = $this->getRequest()->getParam('filter');

		// $advancesearch["CategoryIds"] = $_categoryIds;
		// $advancesearch["FilterDate"] = $_filter;
		// if($_sidx){ $sortType = $_sidx; }
		// if($_sord){ $sortDirection = $_sord; }
		// if($_limit){ $pageSize = $_limit; }

		// if($_id){
		// 	$categoryModel = new Calendar_Model_Category();
		// 	$dataDetailCategory = $categoryModel->getDataByLanguage($_id, $this->lang);
		// }
		
		$model = new Calendar_Model_Article();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		if (count($items) > 0) {
			foreach ($items as $event) {
				$i = array();
				$i["id"] = $event['ID'];
				$i["title"] = $event['Title'];
				$i["url"] = $this->view->url(array("module" => "calendar", "controller" => "article", "action" => "detail", "id" => $event['CalendarCategoryID'], "iid" => $event['ID']), null, true);
				// $i["start"] = str_replace(' ','T',$event['StartDate']->format('Y-m-d H:i:s'));
				// $i["end"] = str_replace(' ','T',$event['EndDate']->format('Y-m-d H:i:s'));
				// $i["date-d"] = $event['StartDate']->format('d');
				// $i["date-m"] = $event['StartDate']->format('m');
				// $i["date-y"] = $event['StartDate']->format('Y') + 543;
				$i["place"] = $event['Place'];
				$i["image-url"] = '/images/other/no-image.jpg';
				if($event['IntroImageHashName']){
					$i["image-url"] = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $event['IntroImageHashName']), null, true);
				}
				$i["starttime"] = $event['EndDate']->format('H:i');
				$i["startdate"] = Emwork_Utility_DateTime::MSSqlToDateThai($event['StartDate'],'DT_Nd') . ' - ' . Emwork_Utility_DateTime::MSSqlToDateThai($event['EndDate'],'DT_Nd') ;
				$i["startdate"] = str_replace('วันที่ ','',$i["startdate"]);
				$i["introtext"] = nl2br($event['IntroText']);
				$i["publish-date"] = Emwork_Utility_DateTime::MSSqlToDateThai($event['PublishDate'],'DT_Nd');
				$i["publish-date"] = str_replace('วันที่ ','',$i["publish-date"]);
				$i["publish-by"] = $event['AuthorName'];

				// $i["color"] = empty($_id) ? $event['Color'] : $dataDetailCategory->Color;
				$responce[] = $i;
			}
		}

		return $responce;
	}
	
	private function checkFormatDate($date){
		if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$date)) {
			return true;
		} else {
			return false;
		}
	}
}
?>