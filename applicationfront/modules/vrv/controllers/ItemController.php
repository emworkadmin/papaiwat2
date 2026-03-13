<?php 


class Vrv_ItemController extends Zend_Controller_Action
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
		
		$model = new Vrv_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;
		$this->view->dataDetail = $dataDetail;
		
		$menuID = $this->findMenu("/vrv/item/index/id/" . $id);
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
		$categoryModel = new Vrv_Model_Category();
		$categoryData = $categoryModel->getSettings($id);
		$categorySetting = json_decode($categoryData->Settings);
		$cItemAmountColumnSetting = empty($categorySetting->ItemAmountColumnSetting) ? 'D' : $categorySetting->ItemAmountColumnSetting;
		$cItemAmountColumn = $categorySetting->ItemAmountColumn;
		$cItemPerPageSetting = empty($categorySetting->ItemPerPageSetting) ? 'D' : $categorySetting->ItemPerPageSetting;
		$cItemPerPage = $categorySetting->ItemPerPage;
		$cItemShowAmountSetting = empty($categorySetting->ItemShowAmountSetting) ? 'D' : $categorySetting->ItemShowAmountSetting;
		$cItemShowAmount = $categorySetting->ItemShowAmount;
		$cItemShowImageSetting = empty($categorySetting->ItemShowImageSetting) ? 'D' : $categorySetting->ItemShowImageSetting;
		$cItemShowImage = $categorySetting->ItemShowImage;
		$cItemShowIntroTextSetting = empty($categorySetting->ItemShowIntroTextSetting) ? 'D' : $categorySetting->ItemShowIntroTextSetting;
		$cItemShowIntroText = $categorySetting->ItemShowIntroText;		
		$cItemSortSetting = empty($categorySetting->ItemSortSetting) ? 'D' : $categorySetting->ItemSortSetting;
		$cItemSortType = $categorySetting->ItemSortType;
		$cItemSortDirection = $categorySetting->ItemSortDirection;
		
		// Setting
		$vrvSettingItemAmountColumn = System_Model_Setting::getValue("ItemAmountColumn", "VRV");
		$vrvSettingItemAmountColumn = $cItemAmountColumnSetting == "D" ? $vrvSettingItemAmountColumn : $cItemAmountColumn;
		$vrvSettingItemAmountColumn = empty($vrvSettingItemAmountColumn) || $vrvSettingItemAmountColumn <= 1 ? 1 : ($vrvSettingItemAmountColumn >= 4 ? 4 : $vrvSettingItemAmountColumn);
		$vrvSettingItemPerPage = System_Model_Setting::getValue("ItemPerPage", "VRV");
		$vrvSettingItemPerPage = $cItemPerPageSetting == "D" ? $vrvSettingItemPerPage : $cItemPerPage;
		$vrvSettingItemShowAmount = System_Model_Setting::getValue("ItemShowAmount", "VRV");
		$vrvSettingItemShowAmount = $cItemShowAmountSetting == "D" ? $vrvSettingItemShowAmount : $cItemShowAmount;
		$vrvSettingItemShowImage = System_Model_Setting::getValue("ItemShowImage", "VRV");
		$vrvSettingItemShowImage = $cItemShowImageSetting == "D" ? $vrvSettingItemShowImage : $cItemShowImage;
		$vrvSettingItemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "VRV");
		$vrvSettingItemShowIntroText = $cItemShowIntroTextSetting == "D" ? $vrvSettingItemShowIntroText : $cItemShowIntroText;
		$vrvSettingItemSortType = System_Model_Setting::getValue("ItemSortType", "VRV");
		$vrvSettingItemSortType = $cItemSortSetting == "D" ? $vrvSettingItemSortType : $cItemSortType;
		$vrvSettingItemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "VRV");
		$vrvSettingItemSortDirection = $cItemSortSetting == "D" ? $vrvSettingItemSortDirection : $cItemSortDirection;
		
		$sortType = 'Created';
		switch ($vrvSettingItemSortType) {
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
		$sortDirection = in_array($vrvSettingItemSortDirection, array('asc', 'desc')) ? $vrvSettingItemSortDirection : 'desc';
				
		// Init
		$pageSize = empty($vrvSettingItemPerPage) ? 10 : $vrvSettingItemPerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$advancesearch = array();
		$advancesearch["isCat"] = $id;
		
		$model = new Vrv_Model_Item();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->vrvSettingItemAmountColumn = $vrvSettingItemAmountColumn;
		$this->view->vrvSettingItemShowImage = $vrvSettingItemShowImage == 'Y';
		$this->view->vrvSettingItemShowIntroText = $vrvSettingItemShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->items = $items;
		if ($vrvSettingItemAmountColumn == 1) {
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
		Vrv_Model_Item::saveView($iID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Vrv_Model_Item();
		$dataInfo = $model->getInfo( $iID );
		
		// Check Data: Can Access
		if (!$dataInfo || $dataInfo->IsEnable != 'Y' || $dataInfo->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		// if (empty ( $data->Title)) {
		// 	// Show "Not found data in this LANG, อยู่ระหว่างจัดทำข้อมูล"
		// 	$this->renderScript('index/_alertmaintenance.phtml');
		// 	return;
		// }

		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'init');
        $this->view->appSystemName = empty(System_Model_Setting::getValue("SiteName", "Site")) ? $config->app->name : System_Model_Setting::getValue("SiteName", "Site");

		// Get Favicon
		$objTemplate = $model->getDefaultTemplate();
		if($objTemplate->FaviconImageHashName){
			$appFaviconPath = $this->view->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get', 'file' => $objTemplate->FaviconImageHashName), null, true);
		}else{
			$faviconID = System_Model_Setting::getValue("Favicon", "Site");
			if($faviconID){
				$fileModel = new System_Model_Fileinfo();
				$fileData = $fileModel->item($faviconID);
				if ($fileData->ID != null) {
					$appFaviconPath = $this->view->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get', 'file' => $fileData->HashName), null, true);
				}
			}else{
				$appFaviconPath = $this->view->baseUrl($config->app->faviconpath);
			}
		}
		$this->view->appFaviconPath = $appFaviconPath;
		
		if($dataInfo->VirtualRealityHashName){
            $folder = str_replace('.zip','',$dataInfo->VirtualRealityHashName);
            $path = '/vrv/'.$folder.'/index.html';
            if(file_exists(APPLICATION_ROOT.'/public'.$path)){
                $this->view->path = $path;
                $this->_helper->layout()->disableLayout();
            }else{
				$this->renderScript('index/_alertnotfound.phtml');
				return;
            }
        }else{
            $this->renderScript('index/_alertnotfound.phtml');
            return;
        }
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
		$advancesearch['VrvItemID'] = $this->getRequest()->getParam('id');
        
        $model = new Vrv_Model_Item();
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
        
        $model = new Vrv_Model_Item();
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
        
        $model = new Vrv_Model_Item();
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
            $result['message'] = Emwork_Translate::text($this->lang, 'vrv', 'validate_error');
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