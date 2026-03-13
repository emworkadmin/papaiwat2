<?php 


class Information_DepartmentController extends Zend_Controller_Action
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
		$CategoryID = $filterInt->filter($this->_request->getParam('CategoryID'));

		$model = new Information_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
		$informationCategoryOption=$model->getCategory($this->lang);
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;
		$this->view->informationCategoryOption = Emwork_Utility::addEmptyOption($informationCategoryOption, Emwork_Translate::text($this->lang, 'general', 'emptyoption'));
		
		$menuID = $this->findMenu("/information/department/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}
	
	public function getlistAction() {
		
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
		
		// Setting
		$informationSettingInformationAmountColumn = System_Model_Setting::getValue("InformationAmountColumn", "Information");
		$informationSettingInformationPerPage = System_Model_Setting::getValue("InformationPerPage", "Information");
		$informationSettingInformationShowAmount = System_Model_Setting::getValue("InformationShowAmount", "Information");
		$informationSettingInformationSortType = System_Model_Setting::getValue("InformationSortType", "Information");
		$informationSettingInformationSortDirection = System_Model_Setting::getValue("InformationSortDirection", "Information");
		$informationSettingInformationShowDetail = System_Model_Setting::getValue("InformationShowDetail", "Information");
		
		$informationSettingDepartmentShowCategory = System_Model_Setting::getValue("DepartmentShowCategory", "Information");
		$informationSettingDepartmentShowJurisdiction = System_Model_Setting::getValue("DepartmentShowJurisdiction", "Information");
		$informationSettingDepartmentShowAddress = System_Model_Setting::getValue("DepartmentShowAddress", "Information");
		$informationSettingDepartmentShowDetail = System_Model_Setting::getValue("DepartmentShowDetail", "Information");
		$informationSettingDepartmentShowTel = System_Model_Setting::getValue("DepartmentShowTel", "Information");
		$informationSettingDepartmentShowFax = System_Model_Setting::getValue("DepartmentShowFax", "Information");
		$informationSettingDepartmentShowEmail = System_Model_Setting::getValue("DepartmentShowEmail", "Information");
		$informationSettingDepartmentShowWebsite = System_Model_Setting::getValue("DepartmentShowWebsite", "Information");
		$informationSettingDepartmentShowDocuments = System_Model_Setting::getValue("DepartmentShowDocuments", "Information");
		$informationSettingDepartmentShowMap = System_Model_Setting::getValue("DepartmentShowMap", "Information");
		$informationSettingDepartmentShowImage = System_Model_Setting::getValue("DepartmentShowImage", "Information");

		//echo "<pre>";print_r($informationSettingInformationAmountColumn);echo "</pre>";exit;
		$sortType = 'Created';
		switch ($informationSettingInformationSortType) {
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
		$sortDirection = in_array($informationSettingInformationSortDirection, array('asc', 'desc')) ? $informationSettingInformationSortDirection : 'desc';
				
		// Init
		$pageSize = empty($informationSettingInformationPerPage) ? 10 : $informationSettingInformationPerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);

		// Search
		$textstr = $this->getRequest()->getParam('search');
		$CategoryID = $this->getRequest()->getParam('Category');
		$Title = $this->getRequest()->getParam('Title');

		$id = $filterInt->filter($this->getRequest()->getParam('id'));

		$advancesearch = array();
		$advancesearch["CategoryID"] = $CategoryID;
		$advancesearch["Title"] = $Title;
		
		$model = new Information_Model_Department();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		// echo "<pre>";print_r($items);echo "</pre>";exit;
		// Set Setting to 
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->informationSettingInformationAmountColumn = $informationSettingInformationAmountColumn;
		$this->view->informationSettingDepartmentShowCategory = $informationSettingDepartmentShowCategory == 'Y';
		$this->view->informationSettingDepartmentShowJurisdiction = $informationSettingDepartmentShowJurisdiction == 'Y';
		$this->view->informationSettingDepartmentShowAddress = $informationSettingDepartmentShowAddress == 'Y';
		$this->view->informationSettingDepartmentShowDetail = $informationSettingDepartmentShowDetail == 'Y';
		$this->view->informationSettingDepartmentShowTel = $informationSettingDepartmentShowTel == 'Y';
		$this->view->informationSettingDepartmentShowFax = $informationSettingDepartmentShowFax == 'Y';
		$this->view->informationSettingDepartmentShowEmail = $informationSettingDepartmentShowEmail == 'Y';
		$this->view->informationSettingDepartmentShowWebsite = $informationSettingDepartmentShowWebsite == 'Y';
		$this->view->informationSettingDepartmentShowDocuments = $informationSettingDepartmentShowDocuments == 'Y';
		$this->view->informationSettingDepartmentShowMap = $informationSettingDepartmentShowMap == 'Y';
		$this->view->informationSettingDepartmentShowImage = $informationSettingDepartmentShowImage == 'Y';
		$this->view->informationSettingInformationShowDetail = $informationSettingInformationShowDetail == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->items = $items;
		if ($informationSettingInformationAmountColumn == 1) {
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
		$id = $this->getRequest ()->getParam ( "id");
		$id = $filterInt->filter ( $id );
		if (empty ( $id )) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		// Save View
		$accessFrom = "Web";
		$memberID = null;
		$userID = null;
		Information_Model_Department::saveView($id, $memberID, $userID, $this->lang, $accessFrom);
		

		// Model
		$model = new Information_Model_Department();
		$dataInfo = $model->getInfo($id);
		$data = $model->getDataByLanguage($id,$this->lang);
		// echo "<pre>";print_r($data);echo"</pre>";
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
				
		$categoryModel = new Information_Model_Category();
		$dataDetailCategory = $categoryModel->getDataByLanguage($id, $this->lang);
		
		// Check Data: Can Access
		if (!$dataInfo || $dataInfo->IsEnable != 'Y' || $dataInfo->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		if (empty ($data->Title)) {
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
		$dataFileDocuments = array();
		if (count($dataFiles) > 0) {
			foreach ($dataFiles as $dataFile) {
				$dataFileDocuments[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName'],'Extension'=>$dataFile['FileExtension']);
			}
		}
		$dataFiles = $model->getFile($data->BackgroundImageID);
		$dataFileBackgroundImage = array();
		if (count($dataFiles) > 0) {
			foreach ($dataFiles as $dataFile) {
				$dataFileBackgroundImage[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName'],'Extension'=>$dataFile['FileExtension']);
			}
		}
		// print_r($dataFileBackgroundImage);
		// Other Setup
		$menuName = $dataDetailCategory->Title;
		$menuUrl = $this->view->url(array("module" => "information", "controller" => "department", "action" => "list"), null, true);
		$this->view->menuName = $menuName;
		$this->view->menuUrl = $menuUrl;
		$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->view->currentUrl = $currentUrl;
		
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
		$this->view->currentUrlQrCodeUrl = $this->view->QrcodeCurrentUrl($currentUrl, null, $siteName);

		// Setting
		$informationSettingInformationAmountColumn = System_Model_Setting::getValue("InformationAmountColumn", "Information");
		$informationSettingInformationPerPage = System_Model_Setting::getValue("InformationPerPage", "Information");
		$informationSettingInformationShowAmount = System_Model_Setting::getValue("InformationShowAmount", "Information");
		$informationSettingInformationShowDetail = System_Model_Setting::getValue("InformationShowDetail", "Information");
		$informationSettingInformationSortType = System_Model_Setting::getValue("InformationSortType", "Information");
		$informationSettingInformationSortDirection = System_Model_Setting::getValue("InformationSortDirection", "Information");

		$informationSettingDepartmentShowCategory = System_Model_Setting::getValue("DepartmentShowCategory", "Information");
		$informationSettingDepartmentShowJurisdiction = System_Model_Setting::getValue("DepartmentShowJurisdiction", "Information");
		$informationSettingDepartmentShowAddress = System_Model_Setting::getValue("DepartmentShowAddress", "Information");
		$informationSettingDepartmentShowDetail = System_Model_Setting::getValue("DepartmentShowDetail", "Information");
		$informationSettingDepartmentShowTel = System_Model_Setting::getValue("DepartmentShowTel", "Information");
		$informationSettingDepartmentShowFax = System_Model_Setting::getValue("DepartmentShowFax", "Information");
		$informationSettingDepartmentShowEmail = System_Model_Setting::getValue("DepartmentShowEmail", "Information");
		$informationSettingDepartmentShowWebsite = System_Model_Setting::getValue("DepartmentShowWebsite", "Information");
		$informationSettingDepartmentShowDocuments = System_Model_Setting::getValue("DepartmentShowDocuments", "Information");
		$informationSettingDepartmentShowMap = System_Model_Setting::getValue("DepartmentShowMap", "Information");
		$informationSettingDepartmentShowImage = System_Model_Setting::getValue("DepartmentShowImage", "Information");


		$this->view->informationSettingInformationAmountColumn = $informationSettingInformationAmountColumn;
		$this->view->informationSettingDepartmentShowCategory = $informationSettingDepartmentShowCategory == 'Y';
		$this->view->informationSettingDepartmentShowJurisdiction = $informationSettingDepartmentShowJurisdiction == 'Y';
		$this->view->informationSettingDepartmentShowAddress = $informationSettingDepartmentShowAddress == 'Y';
		$this->view->informationSettingDepartmentShowDetail = $informationSettingDepartmentShowDetail == 'Y';
		$this->view->informationSettingDepartmentShowTel = $informationSettingDepartmentShowTel == 'Y';
		$this->view->informationSettingDepartmentShowFax = $informationSettingDepartmentShowFax == 'Y';
		$this->view->informationSettingDepartmentShowEmail = $informationSettingDepartmentShowEmail == 'Y';
		$this->view->informationSettingDepartmentShowWebsite = $informationSettingDepartmentShowWebsite == 'Y';
		$this->view->informationSettingDepartmentShowDocuments = $informationSettingDepartmentShowDocuments == 'Y';
		$this->view->informationSettingDepartmentShowMap = $informationSettingDepartmentShowMap == 'Y';
		$this->view->informationSettingDepartmentShowImage = $informationSettingDepartmentShowImage == 'Y';

		// Send To View
		$this->view->data = $data;
		$this->view->dataInfo = $dataInfo;
		$this->view->dataIntroImages = $dataIntroImages;
		$this->view->dataFileDocuments = $dataFileDocuments;
		$this->view->dataFileBackgroundImage=$dataFileBackgroundImage;

		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;
		$this->view->ControllerName = "department";
		
		// Set Global
		$menuID = $this->findMenu("/information/department/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();
		$extraBreadcrumb[] = array('url' => '/information/department/detail/id/' . $id, 'name' => $data->Title);
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