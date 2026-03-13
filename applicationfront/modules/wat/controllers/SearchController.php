<?php 

class Wat_SearchController extends Zend_Controller_Action
{
	protected $lang;
	protected $extensionName;
	protected $extensionCode;

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
		$this->extensionName = Wat_Service_Info::getExtensionName();
		$this->extensionCode = Wat_Service_Info::getExtensionCode();
	}
	
	public function indexAction()
	{
		if(!System_Service_Element::isActive($this->extensionCode)) {
			throw new Zend_Controller_Action_Exception('This page does not exist', 404);
			return;
		}
		
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		$filterStripTags = new Zend_Filter_StripTags();
        
        // Get Param
		$searchParams = array();
		$searchParams['q'] = $filterStripTags->filter($this->_request->getParam('q'));
		$searchParams['region'] = $filterInt->filter($this->_request->getParam('region'));
		$searchParams['province'] = $filterInt->filter($this->_request->getParam('province'));
		$searchParams['type'] = $filterInt->filter($this->_request->getParam('type'));
		
		// Option
		$regionModel = new Wat_Model_Region();
		$regionOption = $regionModel->getOption($this->lang);
		$provinceModel = new Wat_Model_Province();
		$provinceOption = $provinceModel->getOption($this->lang, empty($searchParams['region']) ? null : $searchParams['region']);
		$watTypeModel = new Wat_Model_Type();
		$watTypeOption = $watTypeModel->getOption($this->lang);
		
		// Page Title
		$pageTitle = Emwork_Translate::text($this->lang, "wat", "search_title");
				
		// Send Data to view
		$this->view->pageTitle = $pageTitle;
		$this->view->searchParams = $searchParams;
		$this->view->regionOption = $regionOption;
		$this->view->provinceOption = $provinceOption;
		$this->view->watTypeOption = $watTypeOption;
		
		// Send Data to Template
		$this->view->bodyClass = "wat-body-search";
		
		$menuID = $this->findMenu("/wat/search/index");
		Zend_Registry::set("MenuID", $menuID);
	}
	
	public function getwatsAction() {
		
		$filterInt = new Zend_Filter_Int();
		$filterStripTags = new Zend_Filter_StripTags();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
		
		// Get Param
		$searchParams = array();
		$searchParams['q'] = $filterStripTags->filter($this->_request->getParam('q'));
		$searchParams['region'] = $filterInt->filter($this->_request->getParam('region'));
		$searchParams['province'] = $filterInt->filter($this->_request->getParam('province'));
		$searchParams['type'] = $filterInt->filter($this->_request->getParam('type'));
		
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");		
		
		// Setting
		$watSettingItemAmountColumn = System_Model_Setting::getValue("ItemAmountColumn", "Wat");
		$watSettingItemAmountColumn = empty($watSettingItemAmountColumn) || $watSettingItemAmountColumn <= 1 ? 1 : ($watSettingItemAmountColumn >= 4 ? 4 : $watSettingItemAmountColumn);
		$watSettingItemPerPage = System_Model_Setting::getValue("ItemPerPage", "Wat");
		$watSettingItemPerPage = $watSettingItemPerPage == null ? 10 : $watSettingItemPerPage;
		//$watSettingItemShowAmount = System_Model_Setting::getValue("ItemShowAmount", "Wat");
		$watSettingItemShowImage = System_Model_Setting::getValue("ItemShowImage", "Wat");
		$watSettingItemShowImage = $watSettingItemShowImage == null ? "Y" : $watSettingItemShowImage;
		$watSettingItemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "Wat");
		$watSettingItemShowIntroText = $watSettingItemShowIntroText == null ? "Y" : $watSettingItemShowIntroText;
		$watSettingItemShowAddress = "Y";
		$watSettingItemSortType = System_Model_Setting::getValue("ItemSortType", "Wat");
		$watSettingItemSortType = $watSettingItemSortType == null ? "name" : $watSettingItemSortType;
		$watSettingItemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Wat");
		$watSettingItemSortDirection = $watSettingItemSortDirection == null ? "asc" : $watSettingItemSortDirection;
		
		$fileModel = new System_Model_Fileinfo();
		$watSettingDefaultCoverImageHashName = '';
		$watSettingDefaultCoverImageID = System_Model_Setting::getValue("CoverImage", "Wat");
		if (!empty($watSettingDefaultCoverImageID)) {
			$fileInfo = $fileModel->item($watSettingDefaultCoverImageID);
			if (!empty($fileInfo->ID)) {
				$watSettingDefaultCoverImageHashName = $fileInfo->HashName;
			}
		}
		$watSettingDefaultIntroImageHashName = '';
		$watSettingDefaultIntroImageID = System_Model_Setting::getValue("IntroImage", "Wat");
		if (!empty($watSettingDefaultIntroImageID)) {
			$fileInfo = $fileModel->item($watSettingDefaultIntroImageID);
			if (!empty($fileInfo->ID)) {
				$watSettingDefaultIntroImageHashName = $fileInfo->HashName;
			}
		}

		$sortType = 'Created';
		switch ($watSettingItemSortType) {
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
		$sortDirection = in_array($watSettingItemSortDirection, array('asc', 'desc')) ? $watSettingItemSortDirection : 'desc';
		
		// Init
		$pageSize = empty($watSettingItemPerPage) ? 10 : $watSettingItemPerPage;


		// Search
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		
		$advancesearch = array();
		$advancesearch["searchText"] = $searchParams['q'];
		$advancesearch["RegionID"] = $searchParams['region'];
		$advancesearch["ProvinceCode"] = $searchParams['province'];
		$advancesearch["WatTypeID"] = $searchParams['type'];

		$model = new Wat_Model_Item();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);		

		// Set Setting to view
		$this->view->id = null;

		//$this->view->currentCategoryID = $currentCategoryID;
		$this->view->controllerName = "region";
		$this->view->setting = $setting;
		$this->view->watSettingItemAmountColumn = $watSettingItemAmountColumn;
		$this->view->watSettingItemShowImage = $watSettingItemShowImage == 'Y';
		$this->view->watSettingItemShowIntroText = $watSettingItemShowIntroText == 'Y';
		$this->view->watSettingItemShowAddress = $watSettingItemShowAddress == 'Y';
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		if (!empty($watSettingDefaultIntroImageHashName)) {
			$this->view->defaultImageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $watSettingDefaultIntroImageHashName), null, true);
		}

		// Send Data to view
		$this->view->items = $items;
		if ($watSettingItemAmountColumn == 1) {
			$responce["html"] = $this->view->render('category/_arows.phtml');
		} else {
			$responce["html"] = $this->view->render('category/_acards.phtml');
		}
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;
		
		// Save Search History
		Wat_Model_Search::saveHistory($this->lang, $advancesearch["searchText"], $advancesearch["RegionID"], $advancesearch["ProvinceCode"], $advancesearch["WatTypeID"], $responce['pageIndex'], $responce["total"]);
		
		$this->_helper->json($responce);
	}
	
	public function getprovincesAction(){
		
		$regionID = $this->getRequest()->getParam("id");
		$lang = $this->getRequest()->getParam("lang");
		
		if (empty($lang)) {
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$lang = $defaultLanguage['Code'];
		}
		if ($regionID == -1) {
			$regionID = null;
		}
		
		$result = array();
		
		$model = new Wat_Model_Province();
		$data = $model->getOption($lang, $regionID);
		
		$i = 0;
		foreach ($data as $key => $value) {
			$result[$i]["id"] = $key;
			$result[$i]["name"] = $value;
			$i ++;
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