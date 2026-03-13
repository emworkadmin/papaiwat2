<?php 


class Ebook_BookController extends Zend_Controller_Action
{
	protected $lang;
	protected $auth;
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
		
		$model = new Ebook_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;
		
		$menuID = $this->findMenu("/ebook/book/index/id/" . $id);
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
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));

		if(empty($cid)){
			$cid = $id;
		}

		$categoryModel = new Ebook_Model_Category();
		$categoryData = $categoryModel->getSettings($cid);
		$categorySetting = json_decode($categoryData->Settings);
		$cBookAmountColumnSetting = $categorySetting->EbookAmountColumnSetting;
		$cBookAmountColumn = $categorySetting->EbookAmountColumn;
		$cBookPerPageSetting = $categorySetting->EbookPerPageSetting;
		$cBookPerPage = $categorySetting->EbookPerPage;
		$cBookShowAmountSetting = $categorySetting->EbookShowAmountSetting;
		$cBookShowAmount = $categorySetting->EbookShowAmount;
		$cBookShowImageSetting = $categorySetting->EbookShowImageSetting;
		$cBookShowImage = $categorySetting->EbookShowImage;
		$cBookShowIntroTextSetting = $categorySetting->EbookShowIntroTextSetting;
		$cBookShowIntroText = $categorySetting->EbookShowIntroText;		
		$cBookSortSetting = $categorySetting->EbookSortSetting;
		$cBookSortType = $categorySetting->EbookSortType;
		$cBookSortDirection = $categorySetting->EbookSortDirection;

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
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		
		$advancesearch = array();
		if($cid != null){
			$advancesearch["id"] = $cid;
		}else{
			$advancesearch["id"] = $id;
		}

		$model = new Ebook_Model_Book();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->ebookSettingBookAmountColumn = $ebookSettingBookAmountColumn;
		$this->view->ebookSettingBookShowImage = $ebookSettingBookShowImage == 'Y';
		$this->view->ebookSettingBookShowIntroText = $ebookSettingBookShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->items = $items;


		if ($ebookSettingBookAmountColumn == 1) {
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
		Ebook_Model_Book::saveView($iID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Ebook_Model_Book();
		$dataInfo = $model->getInfo( $iID );
		$data = $model->getDataByLanguage( $iID, $this->lang);
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
				
		$categoryModel = new Ebook_Model_Category();
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
				if ($dataFile["BookFileType"] == "image") {
					$dataFileImages[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				} elseif ($dataFile["BookFileType"] == "document") {
					$dataFileDocuments[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				} elseif ($dataFile["BookFileType"] == "multimedia") {
					$dataFileMultiMedias[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				}
			}
		}
		
		// Other Setup
		$menuName = $dataDetailCategory->Title;
		$menuUrl = $this->view->url(array("module" => "ebook", "controller" => "book", "action" => "list"), null, true);
		$this->view->menuName = $menuName;
		$this->view->menuUrl = $menuUrl;
		$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->view->currentUrl = $currentUrl;
		
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
		$this->view->currentUrlQrCodeUrl = $this->view->QrcodeCurrentUrl($currentUrl, null, $siteName);
		
		// Send To View
		$this->view->data = $data;
		$this->view->dataInfo = $dataInfo;
		$this->view->dataIntroImages = $dataIntroImages;
		$this->view->dataFileImages = $dataFileImages;
		$this->view->dataFileDocuments = $dataFileDocuments;
		$this->view->dataFileMultiMedias = $dataFileMultiMedias;
		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;
		$this->view->ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");
		
		// Set Global
		$menuID = $this->findMenu("/ebook/book/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();
		$extraBreadcrumb[] = array('url' => '/ebook/book/detail/id/' . $id . '/iid/' . $iID, 'name' => $data->Title);
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
		$advancesearch['EbookBookID'] = $this->getRequest()->getParam('id');
        
        $model = new Ebook_Model_Book();
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
        
        $model = new Ebook_Model_Book();
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
            $result['message'] = Emwork_Translate::text($this->lang, 'ebook', 'validate_error');
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